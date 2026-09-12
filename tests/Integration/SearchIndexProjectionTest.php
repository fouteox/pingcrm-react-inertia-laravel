<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Jobs\SearchIndexProjection;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use App\Services\SearchIndex;
use App\Services\VersionedSearchDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Hash;
use Laravel\Scout\EngineManager;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\RequestUnauthorized;

beforeEach(function () {
    if (! filter_var(env('RUN_DEMO_RESET_INTEGRATION', false), FILTER_VALIDATE_BOOL)) {
        $this->markTestSkipped('PostgreSQL and Typesense integration services are not enabled.');
    }

    expect(DB::getDriverName())->toBe('pgsql');

    config()->set([
        'cache.default' => 'array',
        'queue.default' => 'sync',
        'scout.driver' => 'typesense',
        'scout.prefix' => 'pingcrm_projection_integration_',
        'scout.queue' => false,
        'search.synchronous' => true,
    ]);
    app(EngineManager::class)->forgetEngines();
    Artisan::call('migrate:fresh', ['--force' => true]);

    foreach ([new User, new Organization, new Contact] as $model) {
        try {
            app(Client::class)->getCollections()->{$model->indexableAs()}->delete();
        } catch (ObjectNotFound) {
            // Each test owns these collections; the first run has none yet.
        }
    }

    Artisan::call('search:sync-schema');
});

/** @return array<string, mixed> */
function indexedSearchProjection(Model $model): array
{
    return app(Client::class)->getCollections()->{$model->indexableAs()}
        ->getDocuments()[(string) $model->getKey()]->retrieve();
}

it('drains bounded pages of stale account documents without selecting current or newer revisions', function () {
    $account = Account::factory()->create();
    [$active, $legacy, $trashed, $deleted, $current, $newer] = Contact::withoutSyncingToSearch(
        fn () => Contact::factory()->count(6)->for($account)->create()
    )->all();
    $other = Contact::withoutSyncingToSearch(fn () => Contact::factory()->create());
    Contact::withoutSyncingToSearch(function () use ($trashed, $deleted): void {
        $trashed->delete();
        $deleted->forceDelete();
    });
    $documents = app(VersionedSearchDocuments::class);
    $documents->write($active, $account->id, 1);
    $documents->write($trashed, $account->id, 1);
    $documents->write($deleted, $account->id, 1, deleted: true);
    $documents->write($current, $account->id, 2);
    $documents->write($newer, $account->id, 3);
    $documents->write($other, $other->account_id, 1);
    app(Client::class)->getCollections()->{$legacy->indexableAs()}
        ->getDocuments()->create($legacy->toSearchableArray());

    expect($documents->staleIds(new Contact, $account->id, 2, 250))->toEqualCanonicalizing([
        $active->id, $legacy->id, $trashed->id,
    ]);
    $drainedIds = [];

    for ($page = 0; $page < 3; $page++) {
        $ids = $documents->staleIds(new Contact, $account->id, 2, 1);
        expect($ids)->toHaveCount(1);
        $id = $ids[0];
        $drainedIds[] = $id;
        $documents->write((new Contact)->forceFill(['id' => $id]), $account->id, 2, deleted: true);
    }

    expect($drainedIds)->toEqualCanonicalizing([$active->id, $legacy->id, $trashed->id])
        ->and($documents->staleIds(new Contact, $account->id, 2, 1))->toBeEmpty()
        ->and(indexedSearchProjection($current)['search_deleted'])->toBeFalse()
        ->and(indexedSearchProjection($newer)['search_revision'])->toBe(3)
        ->and(indexedSearchProjection($other)['search_deleted'])->toBeFalse();
    expect(indexedSearchProjection($deleted)['search_deleted'])->toBeTrue();
});

it('only checks search readiness inside a fresh PostgreSQL transaction view', function (string $isolation, bool $ready) {
    $account = Account::factory()->create();

    DB::transaction(function () use ($account, $isolation, $ready): void {
        DB::statement('SET TRANSACTION ISOLATION LEVEL '.$isolation);
        $search = app(SearchIndex::class);

        if ($ready) {
            expect($search->assertReady($account->id))->toBe(0);
        } else {
            expect(fn () => $search->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
        }
    });
})->with([
    'read committed' => ['READ COMMITTED', true],
    'repeatable read' => ['REPEATABLE READ', false],
    'serializable' => ['SERIALIZABLE', false],
]);

it('refuses search and mutation acknowledgement when an indexed collection disappears', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $contact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Analytical']));
    $index->assertReady($account->id);
    $collection = app(Client::class)->getCollections()->{$contact->indexableAs()};
    $collection->delete();

    expect(fn () => Contact::paginateFiltered(['search' => 'Analytical'], $account->id))
        ->toThrow(SearchIndexUnavailable::class);
    expect(fn () => $collection->retrieve())->toThrow(ObjectNotFound::class);

    Exceptions::fake();
    $index->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'Difference']));

    expect($contact->fresh()->first_name)->toBe('Difference');
    expect(fn () => $index->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
    expect(fn () => $collection->retrieve())->toThrow(ObjectNotFound::class);
    Exceptions::assertReported(ObjectNotFound::class);
});

it('does not replace versioned documents when authentication updates private credentials', function (string $operation) {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $user = $index->mutate($account->id, fn () => User::factory()->for($account)->create());
    $document = indexedSearchProjection($user);
    $revision = $index->assertReady($account->id);

    if ($operation === 'remember token') {
        Auth::getProvider()->updateRememberToken($user->fresh(), 'replacement-test-token');
        expect($user->fresh()->remember_token)->toBe('replacement-test-token');
    } else {
        Auth::getProvider()->rehashPasswordIfRequired($user->fresh(), ['password' => 'replacement-test-password'], force: true);
        expect(Hash::check('replacement-test-password', $user->fresh()->password))->toBeTrue();
    }

    expect(indexedSearchProjection($user))->toEqual($document)
        ->and($index->assertReady($account->id))->toBe($revision);
})->with(['remember token', 'password rehash']);

it('projects the complete model lifecycle before declaring search ready', function (string $modelClass, string $nameField) {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $model = $index->mutate($account->id, fn () => $modelClass::factory()->for($account)->create([
        $nameField => 'Analytical',
    ]));
    $createdRevision = $index->assertReady($account->id);

    expect(indexedSearchProjection($model))
        ->toMatchArray([$nameField => 'Analytical', 'search_revision' => $createdRevision, 'search_deleted' => false]);

    $index->mutate($account->id, fn () => tap($model)->update([$nameField => 'Difference']));
    $updatedRevision = $index->assertReady($account->id);

    expect($updatedRevision)->toBeGreaterThan($createdRevision)
        ->and(indexedSearchProjection($model))
        ->toMatchArray([$nameField => 'Difference', 'search_revision' => $updatedRevision, 'search_deleted' => false]);
    $index->mutate($account->id, fn () => tap($model)->delete());
    expect(indexedSearchProjection($model))
        ->toMatchArray(['__soft_deleted' => 1, 'search_revision' => $index->assertReady($account->id)]);

    $index->mutate($account->id, fn () => tap($model)->restore());
    expect(indexedSearchProjection($model))
        ->toMatchArray(['__soft_deleted' => 0, 'search_deleted' => false, 'search_revision' => $index->assertReady($account->id)]);

    $index->mutate($account->id, fn () => tap($model)->forceDelete());
    expect($modelClass::withTrashed()->find($model->getKey()))->toBeNull()
        ->and(indexedSearchProjection($model))
        ->toMatchArray(['search_deleted' => true, 'search_revision' => $index->assertReady($account->id)]);
})->with([
    'contact' => [Contact::class, 'first_name'],
    'organization' => [Organization::class, 'name'],
    'user' => [User::class, 'first_name'],
]);

it('projects organization changes into active and trashed contact documents', function (string $change, string $expectedName) {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $organization = $index->mutate($account->id, fn () => Organization::factory()->for($account)->create(['name' => 'Analytical Engines']));
    $active = $index->mutate($account->id, fn () => Contact::factory()->for($account)->for($organization)->create());
    $trashed = $index->mutate($account->id, fn () => Contact::factory()->for($account)->for($organization)->create());
    $index->mutate($account->id, fn () => tap($trashed)->delete());

    if ($change === 'restore') {
        $index->mutate($account->id, fn () => tap($organization)->delete());
    }

    $index->mutate($account->id, fn () => match ($change) {
        'rename' => tap($organization)->update(['name' => $expectedName]),
        'delete' => tap($organization)->delete(),
        'restore' => tap($organization)->restore(),
    });
    $revision = $index->assertReady($account->id);

    expect(indexedSearchProjection($active))
        ->toMatchArray(['organization_name' => $expectedName, '__soft_deleted' => 0, 'search_revision' => $revision])
        ->and(indexedSearchProjection($trashed))
        ->toMatchArray(['organization_name' => $expectedName, '__soft_deleted' => 1, 'search_revision' => $revision]);
})->with([
    'rename' => ['rename', 'Difference Engines'],
    'delete' => ['delete', ''],
    'restore' => ['restore', 'Analytical Engines'],
]);

it('keeps failed projections durable and closes search until a real queue worker recovers them', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $contact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Analytical']));
    $readyRevision = $index->assertReady($account->id);
    $queuedBeforeFailure = DB::table('jobs')->where('queue', 'search-index')->count();
    $apiKey = config('scout.typesense.client-settings.api_key');
    Exceptions::fake();

    config()->set([
        'scout.typesense.client-settings.api_key' => 'invalid-projection-test-key',
        'scout.typesense.client-settings.num_retries' => 0,
    ]);
    app(EngineManager::class)->forgetEngines();

    try {
        app(SearchIndex::class)->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'Difference']));
    } finally {
        config()->set('scout.typesense.client-settings.api_key', $apiKey);
        app(EngineManager::class)->forgetEngines();
    }

    expect($contact->fresh()->first_name)->toBe('Difference')
        ->and(indexedSearchProjection($contact)['first_name'])->toBe('Analytical')
        ->and($index->readState($account->id)['revision'])->toBeGreaterThan($readyRevision)
        ->and($index->readState($account->id)['indexedRevision'])->toBe($readyRevision)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe($queuedBeforeFailure + 1);
    expect(fn () => $index->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
    Exceptions::assertReported(RequestUnauthorized::class);

    $this->artisan('queue:work', [
        'connection' => 'search-index',
        '--queue' => 'search-index',
        '--stop-when-empty' => true,
        '--sleep' => 0,
        '--tries' => 1,
    ])->assertSuccessful();

    expect(indexedSearchProjection($contact))
        ->toMatchArray(['first_name' => 'Difference', 'search_revision' => $index->assertReady($account->id)])
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe(0)
        ->and(DB::table('failed_jobs')->count())->toBe(0);
});

it('does not acknowledge pending revisions when queued projections arrive out of order or twice', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $contact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Original']));
    config()->set('search.synchronous', false);

    $index->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'Intermediate']));
    $olderRevision = $index->readState($account->id)['revision'];
    $index->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'Current']));
    $newerRevision = $index->readState($account->id)['revision'];
    expect(fn () => $index->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);

    $newer = new SearchIndexProjection($account->id, $newerRevision, Contact::class, $contact->id);
    $older = new SearchIndexProjection($account->id, $olderRevision, Contact::class, $contact->id);
    app()->call([$newer, 'handle']);
    app()->call([$older, 'handle']);
    app()->call([$newer, 'handle']);

    expect(indexedSearchProjection($contact))
        ->toMatchArray(['first_name' => 'Current', 'search_revision' => $newerRevision])
        ->and($index->assertReady($account->id))->toBe($newerRevision);
});

it('keeps unrelated older mutations pending when a newer document is projected first', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $olderContact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Original']));
    $newerContact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Original']));
    config()->set('search.synchronous', false);

    $index->mutate($account->id, fn () => tap($olderContact)->update(['first_name' => 'OlderMutation']));
    $olderRevision = $index->readState($account->id)['revision'];
    $index->mutate($account->id, fn () => tap($newerContact)->update(['first_name' => 'NewerMutation']));
    $newerRevision = $index->readState($account->id)['revision'];

    app()->call([new SearchIndexProjection($account->id, $newerRevision, Contact::class, $newerContact->id), 'handle']);

    if (indexedSearchProjection($olderContact)['first_name'] !== 'OlderMutation') {
        expect(fn () => $index->assertReady($account->id))->toThrow(SearchIndexUnavailable::class);
    }

    app()->call([new SearchIndexProjection($account->id, $olderRevision, Contact::class, $olderContact->id), 'handle']);
    app()->call([new SearchIndexProjection($account->id, $newerRevision, Contact::class, $newerContact->id), 'handle']);

    expect(indexedSearchProjection($olderContact)['first_name'])->toBe('OlderMutation')
        ->and(indexedSearchProjection($newerContact)['first_name'])->toBe('NewerMutation')
        ->and($index->assertReady($account->id))->toBe($newerRevision);
});

it('rolls back the mutation, revision and durable job without publishing an uncommitted document', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    $contact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Original']));
    $beforeState = $index->readState($account->id);
    $beforeJobs = DB::table('jobs')->where('queue', 'search-index')->count();

    expect(fn () => DB::transaction(function () use ($index, $account, $contact): void {
        $index->mutate($account->id, fn () => tap($contact)->update(['first_name' => 'Uncommitted']));
        expect(indexedSearchProjection($contact)['first_name'])->toBe('Original');

        throw new RuntimeException('Roll back the enclosing transaction.');
    }))->toThrow(RuntimeException::class, 'Roll back the enclosing transaction.');

    expect($contact->fresh()->first_name)->toBe('Original')
        ->and(indexedSearchProjection($contact)['first_name'])->toBe('Original')
        ->and($index->readState($account->id))->toBe($beforeState)
        ->and(DB::table('jobs')->where('queue', 'search-index')->count())->toBe($beforeJobs);
});

it('keeps a tombstone when a delayed creation arrives after a hard deletion', function () {
    $account = Account::factory()->create();
    $index = app(SearchIndex::class);
    config()->set('search.synchronous', false);
    $contact = $index->mutate($account->id, fn () => Contact::factory()->for($account)->create(['first_name' => 'Analytical']));
    $createdRevision = $index->readState($account->id)['revision'];
    $oldDocument = clone $contact;
    $oldJob = new SearchIndexProjection($account->id, $createdRevision, Contact::class, $contact->id);
    $index->mutate($account->id, fn () => tap($contact)->forceDelete());
    $deletedRevision = $index->readState($account->id)['revision'];

    app()->call([new SearchIndexProjection($account->id, $deletedRevision, Contact::class, $contact->id), 'handle']);
    app()->call([$oldJob, 'handle']);
    app()->call([new SearchIndexProjection($account->id, $deletedRevision, Contact::class, $contact->id), 'handle']);
    app(VersionedSearchDocuments::class)->write($oldDocument, $account->id, $createdRevision);

    app()->call([$oldJob, 'handle']);
    app()->call([$oldJob, 'handle']);

    expect(Contact::withTrashed()->find($contact->id))->toBeNull()
        ->and(indexedSearchProjection($contact))
        ->toMatchArray(['search_deleted' => true, 'search_revision' => $deletedRevision])
        ->and($index->assertReady($account->id))->toBe($deletedRevision);
});

it('upgrades legacy documents without overwriting an equal or newer revision', function (?int $storedRevision, int $expectedRevision, string $expectedName) {
    $account = Account::factory()->create();
    $contact = Contact::withoutSyncingToSearch(fn () => Contact::factory()->for($account)->create(['first_name' => 'Candidate']));
    $other = Contact::withoutSyncingToSearch(fn () => Contact::factory()->for($account)->create(['first_name' => 'Unrelated']));
    $client = app(Client::class);
    $documents = $client->getCollections()->{$contact->indexableAs()}->getDocuments();
    $stored = [...$contact->toSearchableArray(), 'first_name' => 'Stored'];

    if ($storedRevision !== null) {
        $stored['search_revision'] = $storedRevision;
        $stored['search_deleted'] = false;
    }

    $documents->create($stored);
    $documents->create($other->toSearchableArray());

    app(VersionedSearchDocuments::class)->write($contact, $account->id, 5);

    expect(indexedSearchProjection($contact))
        ->toMatchArray(['first_name' => $expectedName, 'search_revision' => $expectedRevision, 'search_deleted' => false])
        ->and(indexedSearchProjection($other))->toEqual($other->toSearchableArray());
})->with([
    'legacy document' => [null, 5, 'Candidate'],
    'zero revision' => [0, 5, 'Candidate'],
    'older revision' => [4, 5, 'Candidate'],
    'equal revision' => [5, 5, 'Stored'],
    'newer revision' => [6, 6, 'Stored'],
    'maximum revision' => [PHP_INT_MAX, PHP_INT_MAX, 'Stored'],
]);
