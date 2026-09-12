<?php

declare(strict_types=1);

use App\Jobs\SearchIndexProjection;
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
        'scout.prefix' => 'pingcrm_late_create_review_',
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

it('excludes a delayed creation after a complete rebuild has removed its SQL row', function (int $remainingContactCount, bool $fullProjection) {
    $account = Account::factory()->create();
    $search = app(SearchIndex::class);
    $contact = $search->mutate($account->id, fn () => Contact::factory()->for($account)->create([
        'first_name' => 'GhostCreation',
        'last_name' => 'Zebra',
    ]));
    if ($fullProjection) {
        $search->rebuild($account->id);
    }

    $creationRevision = $search->readState($account->id)['revision'];
    $creation = $fullProjection
        ? new SearchIndexProjection($account->id, $creationRevision)
        : new SearchIndexProjection($account->id, $creationRevision, Contact::class, $contact->id);
    $documents = app(Client::class)->getCollections()->{$contact->indexableAs()}->getDocuments();
    $intercepted = false;
    $rebuiltRevision = null;
    $handler = HandlerStack::create();
    $handler->push(function (callable $next) use ($account, $contact, $documents, $remainingContactCount, &$intercepted, &$rebuiltRevision): Closure {
        return function (RequestInterface $request, array $options) use ($next, $account, $contact, $documents, $remainingContactCount, &$intercepted, &$rebuiltRevision): PromiseInterface {
            if (! $intercepted && $request->getMethod() === 'POST' && str_ends_with(mb_rtrim($request->getUri()->getPath(), '/'), '/'.$contact->indexableAs().'/documents')) {
                $intercepted = true;
                expect(fn () => $documents[(string) $contact->id]->retrieve())->toThrow(ObjectNotFound::class);

                $this->travel(config('search.lock_seconds') + 1)->seconds();
                $current = app(SearchIndex::class);
                DB::transaction(function () use ($current, $account, $contact, $remainingContactCount): void {
                    DB::table('accounts')->where('id', $account->id)->lockForUpdate()->firstOrFail();
                    DB::table('contacts')->where('id', $contact->id)->delete();
                    Contact::withoutSyncingToSearch(fn () => Contact::factory()->count($remainingContactCount)->for($account)->create([
                        'first_name' => 'GhostCreation',
                        'last_name' => 'Aardvark',
                    ]));
                    $current->rebuild($account->id);
                });
                $rebuiltRevision = $current->readState($account->id)['revision'];
                $current->project(new SearchIndexProjection($account->id, $rebuiltRevision));

                expect($current->assertReady($account->id))->toBe($rebuiltRevision);
                expect($documents->search(['q' => '*', 'query_by' => 'first_name'])['found'])->toBe($remainingContactCount);
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
        $search->project($creation);
        $search->project($creation);

        expect($intercepted)->toBeTrue()
            ->and(Contact::withTrashed()->find($contact->id))->toBeNull()
            ->and($search->assertReady($account->id))->toBe($rebuiltRevision);

        $page = Contact::paginateFiltered(['search' => 'GhostCreation'], $account->id);

        expect($page->total())->toBe($remainingContactCount)
            ->and($page->items())->toHaveCount($remainingContactCount);
    } finally {
        $this->travelBack();
    }
})->with([
    'empty search after an incremental projection' => [0, false],
    'full first page after an incremental projection' => [15, false],
    'empty search after a complete projection' => [0, true],
    'full first page after a complete projection' => [15, true],
]);
