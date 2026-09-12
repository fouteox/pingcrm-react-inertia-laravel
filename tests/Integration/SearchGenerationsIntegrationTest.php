<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchGenerations;
use App\Services\SearchIndex;
use App\Services\VersionedSearchDocuments;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\EngineManager;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\RequestUnauthorized;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL and Typesense integration services are not enabled.');
    }
    expect(DB::getDriverName())->toBe('pgsql');
    config()->set(['scout.driver' => 'typesense', 'scout.prefix' => 'pingcrm_generation_integration_', 'scout.queue' => false, 'search.synchronous' => false, 'search.projection_batch_size' => 50, 'cache.default' => 'array']);
    app(EngineManager::class)->forgetEngines();
    Artisan::call('migrate:fresh', ['--force' => true]);
    foreach (app(Client::class)->getCollections()->retrieve() as $collection) {
        if (str_starts_with($collection['name'], config('scout.prefix'))) {
            app(Client::class)->getCollections()->{$collection['name']}->delete();
        }
    }
    Artisan::call('search:sync-schema');
});

afterEach(function () {
    if (filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        foreach (app(Client::class)->getCollections()->retrieve() as $collection) {
            if (str_starts_with($collection['name'], 'pingcrm_generation_integration_')) {
                app(Client::class)->getCollections()->{$collection['name']}->delete();
            }
        }
    }
});

/** @return array{Account, Illuminate\Database\Eloquent\Collection<int, Contact>} */
function readyGenerationAccount(): array
{
    $account = Account::factory()->create();
    $organization = Organization::withoutSyncingToSearch(fn () => Organization::factory()->for($account)->create());
    User::withoutSyncingToSearch(fn () => User::factory()->for($account)->create());
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(3)->for($account)->for($organization)->create());
    $search = app(SearchIndex::class);
    $projection = $search->rebuild($account->id);
    for ($attempt = 0; $attempt < 100; $attempt++) {
        if ($search->project($projection)) {
            return [$account->fresh(), $contacts];
        }
    }
    throw new RuntimeException('The fixture did not finish indexing.');
}

function finishSearchGeneration(string $generation): void
{
    for ($attempt = 0; $attempt < 100; $attempt++) {
        if (app(SearchGenerations::class)->project($generation)) {
            return;
        }
    }
    throw new RuntimeException('The candidate did not finish indexing.');
}

function projectGenerationMutation(Contact $contact, Closure $mutation): void
{
    $search = app(SearchIndex::class);
    $search->mutate($contact->account_id, $mutation);
    $revision = $search->readState($contact->account_id)['revision'];
    $projection = new SearchIndexProjection($contact->account_id, $revision, Contact::class, $contact->id);
    for ($attempt = 0; $attempt < 100; $attempt++) {
        if ($search->project($projection)) {
            return;
        }
    }
    throw new RuntimeException('The live delta did not finish indexing.');
}

it('keeps every account searchable through the full background build and physical cutover', function () {
    [$first] = readyGenerationAccount();
    [$second] = readyGenerationAccount();
    $before = [$first->getAttributes(), $second->getAttributes()];
    config()->set('search.projection_batch_size', 1);
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();
    expect($generations->project($generation))->toBeFalse()
        ->and($generations->activeGeneration())->toBeNull();
    foreach ([$first, $second] as $account) {
        expect(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(3);
    }
    finishSearchGeneration($generation);
    expect($generations->activeGeneration())->toBe($generation)
        ->and([$first->fresh()->getAttributes(), $second->fresh()->getAttributes()])->toBe($before);
    foreach ([$first, $second] as $account) {
        expect(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(3)
            ->and(Organization::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(1)
            ->and(User::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(1);
    }
});

it('keeps live mutations available while candidate transport fails and resumes its durable journal', function () {
    [$account, $contacts] = readyGenerationAccount();
    config()->set('search.projection_batch_size', 1);
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();
    expect($generations->project($generation))->toBeFalse();
    $bad = new Client([...config('scout.typesense.client-settings'), 'api_key' => 'invalid-candidate-key', 'num_retries' => 0]);
    $failedCandidate = new SearchGenerations($bad, new VersionedSearchDocuments($bad));
    expect(fn () => $failedCandidate->project($generation))->toThrow(RequestUnauthorized::class);

    $contact = $contacts->first();
    projectGenerationMutation($contact, fn () => tap($contact)->update(['last_name' => 'Livecandidateproof']));
    expect(Contact::paginateFiltered(['search' => 'Livecandidateproof'], $account->id)->total())->toBe(1)
        ->and(DB::table('search_generation_changes')->where('generation', $generation)->count())->toBeGreaterThan(0)
        ->and($generations->activeGeneration())->toBeNull();
    finishSearchGeneration($generation);
    expect(Contact::paginateFiltered(['search' => 'Livecandidateproof'], $account->id)->total())->toBe(1)
        ->and(DB::table('search_generation_changes')->where('generation', $generation)->count())->toBe(0);
});

it('protects a candidate tombstone from a delayed create that its backfill never saw', function () {
    [$account, $contacts] = readyGenerationAccount();
    config()->set('search.projection_batch_size', 1);
    $generation = app(SearchGenerations::class)->start();
    expect(app(SearchGenerations::class)->project($generation))->toBeFalse();
    $deleted = $contacts->last();
    $oldSnapshot = clone $deleted;
    $oldRevision = $account->search_revision;
    projectGenerationMutation($deleted, fn () => tap($deleted)->forceDelete());
    finishSearchGeneration($generation);
    app(VersionedSearchDocuments::class)->write($oldSnapshot, $account->id, $oldRevision, generation: $generation);
    $document = app(Client::class)->getCollections()->{SearchGenerations::collection(new Contact, $generation)}->getDocuments()[(string) $deleted->id]->retrieve();
    expect($document['search_deleted'])->toBeTrue()
        ->and(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(2);
});

it('includes a new account created while the candidate is already building', function () {
    [$first] = readyGenerationAccount();
    config()->set('search.projection_batch_size', 1);
    $generation = app(SearchGenerations::class)->start();
    expect(app(SearchGenerations::class)->project($generation))->toBeFalse();
    [$new] = readyGenerationAccount();
    finishSearchGeneration($generation);
    expect(Contact::paginateFiltered(['search' => '*'], $first->id)->total())->toBe(3)
        ->and(Contact::paginateFiltered(['search' => '*'], $new->id)->total())->toBe(3)
        ->and(User::paginateFiltered(['search' => '*'], $new->id)->total())->toBe(1);
});

it('prunes only a recorded retired generation and keeps the current and legacy collections', function () {
    [$account] = readyGenerationAccount();
    $first = app(SearchGenerations::class)->start();
    finishSearchGeneration($first);
    $second = app(SearchGenerations::class)->start();
    finishSearchGeneration($second);
    $this->artisan('search:rebuild', ['--prune' => $second])->assertFailed();
    $this->artisan('search:rebuild', ['--prune' => 'unknown'])->assertFailed();
    $this->artisan('search:rebuild', ['--prune' => $first])->assertSuccessful();
    $this->artisan('search:rebuild', ['--prune' => $first])->assertSuccessful();
    foreach ([new Contact, new Organization, new User] as $model) {
        expect(fn () => app(Client::class)->getCollections()->{SearchGenerations::collection($model, $first)}->retrieve())->toThrow(ObjectNotFound::class);
        expect(app(Client::class)->getCollections()->{$model->indexableAs()}->retrieve()['num_documents'])->toBeGreaterThan(0);
    }
    expect(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(3);
});

it('invalidates readiness before rolling back an activated physical generation to legacy routing', function () {
    [$account] = readyGenerationAccount();
    $generation = app(SearchGenerations::class)->start();
    finishSearchGeneration($generation);
    $revision = $account->fresh()->search_revision;
    $migration = require database_path('migrations/2026_09_12_153616_create_search_generation_tables.php');
    $migration->down();
    expect($account->fresh()->indexed_revision)->toBeNull()
        ->and($account->fresh()->search_revision)->toBe($revision + 1);
    expect(fn () => app(SearchIndex::class)->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
});

it('does not wait for an unrelated account row lock while taking a snapshot or activating', function (bool $backfillComplete) {
    [$account] = readyGenerationAccount();
    $generations = app(SearchGenerations::class);
    $generation = $generations->start();
    if ($backfillComplete) {
        config()->set('search.projection_batch_size', 5);
        expect($generations->project($generation))->toBeFalse();
    }
    config()->set('search.projection_batch_size', 1);
    $writer = DB::build(config('database.connections.pgsql'));
    $writer->beginTransaction();
    $writer->table('accounts')->where('id', $account->id)->lockForUpdate()->firstOrFail();
    DB::statement("SET lock_timeout = '200ms'");

    try {
        expect($generations->project($generation))->toBe($backfillComplete);
    } finally {
        DB::statement('RESET lock_timeout');
        $writer->rollBack();
        $writer->disconnect();
    }
    finishSearchGeneration($generation);
    expect(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(3);
})->with(['initial snapshot' => false, 'atomic activation' => true]);

it('continues a background rebuild through the native database worker and preserves typo pagination', function () {
    $account = Account::factory()->create();
    $contacts = Contact::withoutSyncingToSearch(fn () => Contact::factory()->count(18)->for($account)
        ->sequence(fn (Sequence $sequence): array => ['first_name' => 'Analytical', 'last_name' => sprintf('Person%02d', $sequence->index)])
        ->create());
    app(SearchIndex::class)->rebuild($account->id);
    config()->set('search.projection_batch_size', 1);
    $generation = app(SearchGenerations::class)->start();

    $this->artisan('queue:work', ['connection' => 'search-index', '--queue' => 'search-index', '--stop-when-empty' => true, '--sleep' => 0])->assertSuccessful();

    expect(app(SearchGenerations::class)->activeGeneration())->toBe($generation)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
    request()->merge(['page' => 2]);
    $page = Contact::paginateFiltered(['search' => 'Analytcal'], $account->id);
    expect($page->total())->toBe(18)
        ->and($page->currentPage())->toBe(2)
        ->and(collect($page->items())->pluck('id')->all())->toBe($contacts->slice(15)->values()->modelKeys());
});

it('refuses candidate projection in a transaction whose snapshot cannot see concurrent commits', function () {
    [$account] = readyGenerationAccount();
    $generation = app(SearchGenerations::class)->start();

    DB::transaction(function () use ($generation): void {
        DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        expect(fn () => app(SearchGenerations::class)->project($generation))->toThrow(SearchIndexUnavailable::class);
    });

    expect(app(SearchGenerations::class)->activeGeneration())->toBeNull()
        ->and(Contact::paginateFiltered(['search' => '*'], $account->id)->total())->toBe(3);
    finishSearchGeneration($generation);
});
