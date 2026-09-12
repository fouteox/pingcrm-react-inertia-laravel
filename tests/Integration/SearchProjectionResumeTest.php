<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use App\Services\VersionedSearchDocuments;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Scout\EngineManager;
use Psr\Http\Message\RequestInterface;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL and Typesense integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');
    config()->set([
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'typesense',
        'scout.prefix' => 'pingcrm_resume_review_',
        'scout.queue' => false,
        'scout.typesense.client-settings.num_retries' => 0,
        'search.synchronous' => false,
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

function limitProjectionAttemptDocuments(int $limit): void
{
    $writes = 0;
    $handler = HandlerStack::create();
    $handler->push(function (callable $next) use ($limit, &$writes): Closure {
        return function (RequestInterface $request, array $options) use ($next, $limit, &$writes): PromiseInterface {
            if ($request->getMethod() === 'POST' && str_ends_with(mb_rtrim($request->getUri()->getPath(), '/'), '/documents')) {
                if (++$writes > $limit) {
                    throw new TypesenseClientError('The worker attempt exhausted its document budget.');
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
}

it('finishes a full rebuild across bounded worker attempts without replaying completed documents', function () {
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(9)->for($account)->create([
        'first_name' => 'Checkpoint',
    ]));
    $search = app(SearchIndex::class);
    $search->rebuild($account->id);
    $revision = $search->readState($account->id)['revision'];
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $indexedCounts = [];
    Exceptions::fake();

    try {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            limitProjectionAttemptDocuments(3);
            $this->travel(180)->seconds();

            $this->artisan('queue:work', [
                'connection' => 'search-index',
                '--queue' => 'search-index',
                '--once' => true,
                '--sleep' => 0,
            ])->assertSuccessful();

            $indexedCounts[] = $documents->search(['q' => '*', 'query_by' => 'first_name'])['found'];

            if ($attempt < 2) {
                expect(fn () => app(SearchIndex::class)->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
                expect(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1);
            }
        }

        expect($indexedCounts)->toBe([3, 6, 9])
            ->and(app(SearchIndex::class)->assertReady($account->id))->toBe($revision)
            ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0);

        $page = Contact::paginateFiltered(['search' => 'Checkpoint'], $account->id);
        expect($page->total())->toBe(9)
            ->and($page->getCollection()->modelKeys())->toEqualCanonicalizing($contacts->modelKeys());
    } finally {
        $this->travelBack();
    }
});

it('yields after its real time budget and resumes before the next document', function () {
    config()->set(['search.projection_seconds' => 1, 'search.projection_batch_size' => 50]);
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(3)->for($account)->create());
    $search = app(SearchIndex::class);
    $search->rebuild($account->id);
    $revision = $search->readState($account->id)['revision'];
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $writtenIds = [];
    $handler = HandlerStack::create();
    $handler->push(function (callable $next) use (&$writtenIds): Closure {
        return function (RequestInterface $request, array $options) use ($next, &$writtenIds): PromiseInterface {
            if ($request->getMethod() === 'POST' && str_ends_with(mb_rtrim($request->getUri()->getPath(), '/'), '/documents')) {
                $document = json_decode((string) $request->getBody(), true, flags: JSON_THROW_ON_ERROR);
                $writtenIds[] = (int) $document['id'];

                if (count($writtenIds) === 1) {
                    usleep(1_050_000);
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
    Exceptions::fake();

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--once' => true,
        '--sleep' => 0,
    ])->assertSuccessful();

    expect($writtenIds)->toBe([$contacts->first()->id])
        ->and($documents->search(['q' => '*'])['found'])->toBe(1)
        ->and($account->fresh()->search_projection_id)->toBe($contacts->first()->id)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
    expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--once' => true,
        '--sleep' => 0,
    ])->assertSuccessful();

    expect($writtenIds)->toBe($contacts->modelKeys())
        ->and($documents->search(['q' => '*'])['found'])->toBe(3)
        ->and($search->assertReady($account->id))->toBe($revision)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
    Exceptions::assertNothingReported();
});

it('resumes organization contact projections without replaying the organization or earlier contacts', function () {
    $account = Account::factory()->create();
    $organization = Organization::withoutSyncingToSearch(fn () => Organization::factory()->for($account)->create(['name' => 'Before']));
    $contacts = Contact::withoutSyncingToSearch(function () use ($account, $organization): Collection {
        $contacts = Contact::factory()->count(8)->for($account)->for($organization)->create();
        $contacts->last()->delete();

        return $contacts;
    });
    $versioned = app(VersionedSearchDocuments::class);
    $versioned->write($organization, $account->id, 0);

    foreach ($contacts as $contact) {
        $versioned->write($contact, $account->id, 0);
    }

    $search = app(SearchIndex::class);
    $search->mutate($account->id, fn () => tap($organization)->update(['name' => 'CheckpointOrganization']));
    $revision = $search->readState($account->id)['revision'];
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $updatedCounts = [];
    Exceptions::fake();

    try {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            limitProjectionAttemptDocuments(3);
            $this->travel(180)->seconds();
            $this->artisan('queue:work', [
                'connection' => 'search-index',
                '--queue' => 'search-index',
                '--once' => true,
                '--sleep' => 0,
            ])->assertSuccessful();

            $updatedCounts[] = $documents->search(['q' => 'CheckpointOrganization', 'query_by' => 'organization_name'])['found'];

            if ($attempt < 2) {
                expect(fn () => app(SearchIndex::class)->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
                expect(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1);
            }
        }

        expect($updatedCounts)->toBe([2, 5, 8])
            ->and(app(SearchIndex::class)->assertReady($account->id))->toBe($revision)
            ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0);

        $page = Contact::paginateFiltered(['search' => 'CheckpointOrganization', 'trashed' => 'with'], $account->id);
        expect($page->total())->toBe(8)
            ->and($page->getCollection()->modelKeys())->toEqualCanonicalizing($contacts->modelKeys())
            ->and($documents[(string) $contacts->last()->id]->retrieve()['__soft_deleted'])->toBe(1);
    } finally {
        $this->travelBack();
    }
});

it('resumes cleanup from the remaining obsolete documents after a worker interruption', function () {
    $account = Account::factory()->create();
    $obsolete = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(9)->for($account)->create());
    $other = Contact::withoutSyncingToSearch(fn () => Contact::factory()->create());
    $versioned = app(VersionedSearchDocuments::class);

    foreach ($obsolete as $contact) {
        $versioned->write($contact, $account->id, 0);
    }

    $versioned->write($other, $other->account_id, 0);
    $search = app(SearchIndex::class);
    DB::transaction(function () use ($search, $account): void {
        DB::table('accounts')->where('id', $account->id)->lockForUpdate()->firstOrFail();
        DB::table('contacts')->where('account_id', $account->id)->delete();
        $search->rebuild($account->id);
    });
    $revision = $search->readState($account->id)['revision'];
    $documents = app(Client::class)->getCollections()->{(new Contact)->indexableAs()}->getDocuments();
    $remainingCounts = [];
    Exceptions::fake();

    try {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            limitProjectionAttemptDocuments(3);
            $this->travel(180)->seconds();
            $this->artisan('queue:work', [
                'connection' => 'search-index',
                '--queue' => 'search-index',
                '--once' => true,
                '--sleep' => 0,
            ])->assertSuccessful();

            $remainingCounts[] = $documents->search([
                'q' => '*',
                'filter_by' => 'account_id:='.$account->id.' && search_deleted:!=true',
            ])['found'];

            if ($attempt < 2) {
                expect(fn () => app(SearchIndex::class)->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
                expect(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(1);
            }
        }

        expect($remainingCounts)->toBe([6, 3, 0])
            ->and(app(SearchIndex::class)->assertReady($account->id))->toBe($revision)
            ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
            ->and(DB::table('failed_jobs')->count())->toBe(0)
            ->and($documents[(string) $other->id]->retrieve())
            ->toMatchArray(['account_id' => $other->account_id, 'search_revision' => 0, 'search_deleted' => false]);

        foreach ($obsolete as $contact) {
            expect($documents[(string) $contact->id]->retrieve())
                ->toMatchArray(['search_revision' => $revision, 'search_deleted' => true]);
        }
    } finally {
        $this->travelBack();
    }
});
