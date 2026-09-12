<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Psr\Http\Message\RequestInterface;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL and Typesense integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');
    config()->set([
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'typesense',
        'scout.prefix' => 'pingcrm_checkpoint_review_',
        'scout.queue' => false,
        'scout.typesense.client-settings.num_retries' => 0,
        'search.synchronous' => false,
        'search.projection_batch_size' => 2,
    ]);
    app(EngineManager::class)->forgetEngines();
    Artisan::call('migrate:fresh', ['--force' => true]);

    foreach ([new User, new Organization, new Contact] as $model) {
        try {
            app(Client::class)->getCollections()->{$model->indexableAs()}->delete();
        } catch (ObjectNotFound) {
            // Each test owns its collections, which are absent on the first run.
        }
    }

    Artisan::call('search:sync-schema');
});

it('resumes a partial full rebuild from the CLI without changing its revision or rebuilding ready accounts', function () {
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(5)->for($account)->create());
    $search = app(SearchIndex::class);
    $projection = $search->rebuild($account->id);
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();

    expect($search->project($projection))->toBeFalse()
        ->and($account->fresh()->search_projection_id)->toBe($contacts[1]->id)
        ->and($documents->search(['q' => '*'])['found'])->toBe(2);

    $this->artisan('search:rebuild', [
        'account' => $account->id,
        '--sync' => true,
        '--resume' => true,
    ])->assertSuccessful();

    expect($search->assertReady($account->id))->toBe($projection->revision)
        ->and($account->fresh()->search_projection_revision)->toBeNull()
        ->and($documents->search(['q' => '*'])['found'])->toBe(5)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1);

    $this->artisan('search:rebuild', [
        'account' => $account->id,
        '--sync' => true,
        '--resume' => true,
    ])->assertSuccessful();

    expect($search->assertReady($account->id))->toBe($projection->revision)
        ->and($account->fresh()->search_revision)->toBe($projection->revision)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1)
        ->and($documents->search(['q' => '*'])['found'])->toBe(5);
});

it('keeps the newer checkpoint when an expired worker resumes the same projection', function () {
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(4)->for($account)->create());
    $projection = app(SearchIndex::class)->rebuild($account->id);
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $intercepted = false;
    $writtenIds = [];
    $handler = HandlerStack::create();
    $handler->push(function (callable $next) use ($account, $contacts, $projection, $documents, &$intercepted, &$writtenIds): Closure {
        return function (RequestInterface $request, array $options) use ($next, $account, $contacts, $projection, $documents, &$intercepted, &$writtenIds): PromiseInterface {
            if ($request->getMethod() === 'POST' && str_ends_with(mb_rtrim($request->getUri()->getPath(), '/'), '/'.$contacts->first()->indexableAs().'/documents')) {
                $document = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $writtenIds[] = (int) $document['id'];

                if (! $intercepted) {
                    $intercepted = true;
                    $this->travel(config('search.lock_seconds') + 1)->seconds();

                    expect(app(SearchIndex::class)->project($projection))->toBeFalse()
                        ->and($account->fresh()->search_projection_id)->toBe($contacts[1]->id)
                        ->and($documents->search(['q' => '*'])['found'])->toBe(2);
                }
            }

            return $next($request, $options);
        };
    });
    app()->instance(Client::class, new Client([
        ...config('scout.typesense.client-settings'),
        'client' => new HttpClient(['handler' => $handler, 'connect_timeout' => 0.5, 'timeout' => 2]),
    ]));
    app(EngineManager::class)->forgetEngines();
    $search = app(SearchIndex::class);

    try {
        expect($search->project($projection))->toBeFalse()
            ->and($intercepted)->toBeTrue()
            ->and($writtenIds)->toBe([$contacts[0]->id, $contacts[0]->id, $contacts[1]->id])
            ->and($account->fresh()->search_projection_revision)->toBe($projection->revision)
            ->and($account->fresh()->search_projection_stage)->toBe(0)
            ->and($account->fresh()->search_projection_id)->toBe($contacts[1]->id)
            ->and($documents->search(['q' => '*'])['found'])->toBe(2);
        expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
        expect(fn () => $documents[(string) $contacts[2]->id]->retrieve())->toThrow(ObjectNotFound::class);

        $this->artisan('queue:work', [
            'connection' => 'search-index',
            '--queue' => 'search-index',
            '--stop-when-empty' => true,
            '--sleep' => 0,
        ])->assertSuccessful();

        expect($search->assertReady($account->id))->toBe($projection->revision)
            ->and($documents->search(['q' => '*'])['found'])->toBe(4)
            ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0);
    } finally {
        $this->travelBack();
    }
});

it('finishes the bounded full snapshot before indexing a contact created by a later revision', function () {
    $account = Account::factory()->create();
    $organization = Organization::withoutSyncingToSearch(fn () => Organization::factory()->for($account)->create());
    $user = User::withoutSyncingToSearch(fn () => User::factory()->for($account)->create());
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(3)->for($account)->for($organization)->create());
    $search = app(SearchIndex::class);
    $projection = $search->rebuild($account->id);
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();

    expect($search->project($projection))->toBeFalse()
        ->and($account->fresh()->search_projection_upper_id)->toBe($contacts->last()->id);

    $laterContact = $search->mutate($account->id, fn () => Contact::factory()->for($account)->for($organization)->create());
    $latestRevision = $search->readState($account->id)['revision'];

    expect($search->project($projection))->toBeFalse()
        ->and($account->fresh()->search_projection_stage)->toBe(1)
        ->and($documents->search(['q' => '*'])['found'])->toBe(3);
    expect(fn () => $documents[(string) $laterContact->id]->retrieve())->toThrow(ObjectNotFound::class);

    expect($search->project($projection))->toBeTrue()
        ->and($search->readState($account->id)['indexedRevision'])->toBe($projection->revision)
        ->and($latestRevision)->toBe($projection->revision + 1);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
    expect(fn () => $documents[(string) $laterContact->id]->retrieve())->toThrow(ObjectNotFound::class);

    foreach ([...$contacts, $organization, $user] as $model) {
        $document = app(Client::class)->getCollections()->{$model->indexableAs()}->getDocuments()[(string) $model->id]->retrieve();
        expect($document['search_revision'])->toBe($projection->revision);
    }

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--stop-when-empty' => true,
        '--sleep' => 0,
    ])->assertSuccessful();

    expect($search->assertReady($account->id))->toBe($latestRevision)
        ->and($documents[(string) $laterContact->id]->retrieve()['search_revision'])->toBe($latestRevision)
        ->and($documents->search(['q' => '*'])['found'])->toBe(4)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});

it('continues a bounded projection with a fresh attempt counter before the PostgreSQL limit', function () {
    config()->set('search.projection_batch_size', 1);
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(3)->for($account)->create());
    $search = app(SearchIndex::class);
    $projection = $search->rebuild($account->id);
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    DB::table('jobs')->where('queue', 'search-index')->update(['attempts' => 32766]);

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--once' => true,
        '--sleep' => 0,
    ])->assertSuccessful();

    expect($account->fresh()->search_projection_id)->toBe($contacts->first()->id)
        ->and($documents->search(['q' => '*'])['found'])->toBe(1)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1)
        ->and(DB::table('jobs')->where('queue', 'search-index')->value('attempts'))->toBe(0);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--stop-when-empty' => true,
        '--sleep' => 0,
    ])->assertSuccessful();

    expect($search->assertReady($account->id))->toBe($projection->revision)
        ->and($documents->search(['q' => '*'])['found'])->toBe(3)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});
