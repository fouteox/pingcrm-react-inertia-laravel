<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\CollectionEngine;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;

beforeEach(function () {
    $this->withoutExceptionHandling();
    config()->set('scout.prefix', '');
});

function useTypesenseSearchResponse(string $index, ?Closure $response): void
{
    $api = Mockery::mock(ApiCall::class);
    $client = Mockery::mock(Client::class);

    if ($response === null) {
        $client->shouldNotReceive('getCollections');
    } else {
        $api->shouldReceive('get')->once()->with("/collections/$index/documents/search", Mockery::type('array'))
            ->andReturnUsing(fn (string $path, array $parameters): array => $response($parameters));
        $client->shouldReceive('getCollections')->once()->andReturn(new Collections($api));
    }

    $client->shouldNotReceive('getMultiSearch');

    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');
}

function assertNativeTypesenseFilters(array $parameters, int $accountId, ?string $role = null, ?string $trashed = null): void
{
    $filters = ['account_id:='.$accountId, 'search_deleted:=false', 'search_revision:>=0'];

    if ($trashed !== 'with') {
        $filters[] = '__soft_deleted:='.($trashed === 'only' ? '1' : '0');
    }

    if ($role !== null) {
        $filters[] = 'owner:='.($role === 'owner' ? 'true' : 'false');
    }

    expect($parameters['filter_curated_hits'])->toBeTrue()
        ->and(explode(' && ', $parameters['filter_by']))->toEqualCanonicalizing($filters);
}

it('paginates typo matches beyond 1200 records through native Typesense pagination', function (string $model, string $resource, string $sort) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['first_name' => 'Viewer', 'last_name' => 'Observer']);
    $attributes = $model === Organization::class
        ? ['name' => 'Common']
        : ['first_name' => 'Common', 'last_name' => 'Person'];
    $model::factory()->create($attributes);
    $model::factory()->for($account)->create([...$attributes, 'deleted_at' => now()]);
    $matches = $model::factory(1217)->for($account)->create($attributes);
    $lastPage = $matches->slice(1215)->values();

    useTypesenseSearchResponse($resource, function (array $parameters) use ($account, $matches, $sort): array {
        expect($parameters['q'])->toBe('commmon')
            ->and($parameters['page'])->toBe(82)
            ->and($parameters['per_page'])->toBe(15)
            ->and($parameters['sort_by'])->toBe($sort);
        assertNativeTypesenseFilters($parameters, $account->id);

        return [
            'found' => $matches->count(),
            'hits' => $matches->forPage($parameters['page'], $parameters['per_page'])
                ->map(fn ($record) => ['document' => ['id' => (string) $record->id]])->values()->all(),
        ];
    });

    $this->actingAs($actor)
        ->get("/$resource?search=commmon&page=82")
        ->assertInertia(fn (Assert $assert) => $assert
            ->where("$resource.meta.total", 1217)
            ->where("$resource.meta.last_page", 82)
            ->where("$resource.meta.current_page", 82)
            ->has("$resource.data", 2)
            ->where("$resource.data.0.id", $lastPage->first()->id)
            ->where("$resource.data.1.id", $lastPage->last()->id));
})->with([
    'contacts' => [Contact::class, 'contacts', 'last_name:asc,first_name:asc'],
    'organizations' => [Organization::class, 'organizations', 'name:asc'],
    'users' => [User::class, 'users', 'last_name:asc,first_name:asc'],
]);

it('paginates zero searches using native tenant role and trash filters', function (string $role, ?string $trashed) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => true]);
    $attributes = ['first_name' => '0', 'last_name' => 'Person'];
    User::factory(3)->create($attributes);
    $users = User::factory(16)->for($account)->create([...$attributes, 'owner' => false]);
    $deletedUsers = User::factory(17)->for($account)->create([...$attributes, 'owner' => false, 'deleted_at' => now()]);
    $owners = User::factory(18)->for($account)->create([...$attributes, 'owner' => true]);
    $deletedOwners = User::factory(19)->for($account)->create([...$attributes, 'owner' => true, 'deleted_at' => now()]);
    $active = $role === 'owner' ? $owners : $users;
    $deleted = $role === 'owner' ? $deletedOwners : $deletedUsers;
    $expected = match ($trashed) {
        'with' => $active->concat($deleted),
        'only' => $deleted,
        default => $active,
    };
    useTypesenseSearchResponse('users', function (array $parameters) use ($account, $role, $trashed, $expected): array {
        expect($parameters['q'])->toBe('0');
        assertNativeTypesenseFilters($parameters, $account->id, $role, $trashed);

        return [
            'found' => $expected->count(),
            'hits' => $expected->forPage($parameters['page'], $parameters['per_page'])
                ->map(fn (User $user) => ['document' => ['id' => (string) $user->id]])->values()->all(),
        ];
    });
    $expectedPage = $expected->slice(15, 15)->pluck('id')->all();

    $this->actingAs($actor)
        ->get('/users?'.http_build_query(['search' => '0', 'role' => $role, 'trashed' => $trashed, 'page' => 2]))
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('filters.search', '0')
            ->where('users.meta.total', $expected->count())
            ->where('users.meta.last_page', (int) ceil($expected->count() / 15))
            ->where('users.meta.current_page', 2)
            ->where('users.data', fn ($data): bool => collect($data)->pluck('id')->all() === $expectedPage));
})->with([
    'active users' => ['user', null],
    'all users' => ['user', 'with'],
    'deleted users' => ['user', 'only'],
    'active owners' => ['owner', null],
    'all owners' => ['owner', 'with'],
    'deleted owners' => ['owner', 'only'],
]);

it('returns a native empty result when no records match the indexed filters', function (string $model, string $resource) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => false]);
    $attributes = $model === User::class ? ['owner' => true] : [];
    $model::factory()->create($attributes);
    $model::factory()->for($account)->create([...$attributes, 'deleted_at' => now()]);

    useTypesenseSearchResponse($resource, function (array $parameters) use ($account, $model): array {
        assertNativeTypesenseFilters($parameters, $account->id, $model === User::class ? 'owner' : null);

        return ['found' => 0, 'hits' => []];
    });

    $this->actingAs($actor)
        ->get("/$resource?".http_build_query(['search' => 'anything', 'role' => $model === User::class ? 'owner' : null]))
        ->assertInertia(fn (Assert $assert) => $assert
            ->where("$resource.meta.total", 0)
            ->where("$resource.meta.last_page", 1)
            ->has("$resource.data", 0));
})->with([
    'contacts' => [Contact::class, 'contacts'],
    'organizations' => [Organization::class, 'organizations'],
    'users' => [User::class, 'users'],
]);

it('keeps name ordering stable across search pages when names are identical', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    $contacts = Contact::factory(16)->for($account)->create(['first_name' => 'Same', 'last_name' => 'Person']);

    $this->actingAs($actor)
        ->get('/contacts?search=Same&page=2')
        ->assertInertia(fn (Assert $assert) => $assert
            ->has('contacts.data', 1)
            ->where('contacts.data.0.id', $contacts->last()->id));
});

it('rejects a full native page containing a hit that no longer matches SQL filters', function (string $model, string $resource, string $change, ?string $role, ?string $trashed) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => true]);
    $attributes = $model === User::class ? ['owner' => false] : [];

    if ($trashed === 'only') {
        $attributes['deleted_at'] = now();
    }

    $records = $model::factory(15)->for($account)->create($attributes);
    $stale = $records->last();
    $row = DB::table($stale->getTable())->where('id', $stale->id);

    match ($change) {
        'tenant' => $row->update(['account_id' => Account::factory()->create()->id]),
        'role' => $row->update(['owner' => true]),
        'deleted' => $row->update(['deleted_at' => now()]),
        'restored' => $row->update(['deleted_at' => null]),
        'removed' => $row->delete(),
    };

    useTypesenseSearchResponse($resource, function (array $parameters) use ($account, $role, $trashed, $records): array {
        assertNativeTypesenseFilters($parameters, $account->id, $role, $trashed);

        return ['found' => 15, 'hits' => $records->map(fn ($record): array => ['document' => ['id' => (string) $record->id]])->all()];
    });

    $this->expectException(SearchIndexUnavailable::class);

    $this->actingAs($actor)->get("/$resource?".http_build_query(['search' => 'common', 'role' => $role, 'trashed' => $trashed]));
})->with([
    'contact moved to another tenant' => [Contact::class, 'contacts', 'tenant', null, null],
    'organization moved to another tenant' => [Organization::class, 'organizations', 'tenant', null, null],
    'user moved to another tenant' => [User::class, 'users', 'tenant', 'user', null],
    'contact deleted' => [Contact::class, 'contacts', 'deleted', null, null],
    'organization deleted' => [Organization::class, 'organizations', 'deleted', null, null],
    'user deleted' => [User::class, 'users', 'deleted', 'user', null],
    'contact restored while viewing trash' => [Contact::class, 'contacts', 'restored', null, 'only'],
    'user role changed' => [User::class, 'users', 'role', 'user', null],
    'contact permanently deleted' => [Contact::class, 'contacts', 'removed', null, null],
]);

it('reports a failed native Typesense request instead of returning an empty page', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    Contact::factory()->for($account)->create();
    $failure = new TypesenseClientError('Search temporarily unavailable', 503);
    useTypesenseSearchResponse('contacts', fn (): array => throw $failure);
    $this->actingAs($actor);

    expect(fn () => $this->get('/contacts?search=common'))
        ->toThrow(fn (SearchIndexUnavailable $exception) => expect($exception->getPrevious())->toBe($failure));
});

it('does not search an index with an unacknowledged revision', function (?int $indexedRevision) {
    $actor = User::factory()->create();
    Contact::factory()->for($actor->account)->create();
    $actor->account->forceFill(['search_revision' => 1, 'indexed_revision' => $indexedRevision])->save();
    useTypesenseSearchResponse('contacts', null);

    $this->expectException(SearchIndexUnavailable::class);

    $this->actingAs($actor)->get('/contacts?search=common');
})->with(['not initialized' => [null], 'projection pending' => [0]]);

it('rejects a page when the account revision changes during the search request', function (int $indexedRevision) {
    $actor = User::factory()->create();
    $contact = Contact::factory()->for($actor->account)->create();

    useTypesenseSearchResponse('contacts', function () use ($actor, $contact, $indexedRevision): array {
        DB::table('accounts')->where('id', $actor->account_id)->update(['search_revision' => 1, 'indexed_revision' => $indexedRevision]);

        return ['found' => 1, 'hits' => [['document' => ['id' => (string) $contact->id]]]];
    });

    $this->expectException(SearchIndexUnavailable::class);

    $this->actingAs($actor)->get('/contacts?search=common');
})->with(['projection pending' => [0], 'new revision already indexed' => [1]]);

it('rejects a native page with fewer hits than its reported total requires', function (int $page, int $returnedHits) {
    $actor = User::factory()->create();
    $contacts = Contact::factory(16)->for($actor->account)->create();

    useTypesenseSearchResponse('contacts', fn (): array => [
        'found' => 16,
        'hits' => $contacts->take($returnedHits)->map(fn (Contact $contact): array => ['document' => ['id' => (string) $contact->id]])->all(),
    ]);

    $this->expectException(SearchIndexUnavailable::class);

    $this->actingAs($actor)->get('/contacts?search=common&page='.$page);
})->with(['incomplete first page' => [1, 14], 'missing last hit' => [2, 0]]);

it('rejects a cutoff native search even when its current page is full', function () {
    $actor = User::factory()->create();
    $contacts = Contact::factory(15)->for($actor->account)->create();
    useTypesenseSearchResponse('contacts', fn (): array => [
        'found' => 15,
        'search_cutoff' => true,
        'hits' => $contacts->map(fn (Contact $contact): array => ['document' => ['id' => (string) $contact->id]])->all(),
    ]);

    $this->expectException(SearchIndexUnavailable::class);

    $this->actingAs($actor)->get('/contacts?search=common');
});

it('keeps the native total for an empty page beyond the last page', function () {
    $actor = User::factory()->create();
    Contact::factory(16)->for($actor->account)->create();
    useTypesenseSearchResponse('contacts', fn (): array => ['found' => 16, 'hits' => []]);

    $this->actingAs($actor)->get('/contacts?search=common&page=3')
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('contacts.meta.total', 16)
            ->where('contacts.meta.last_page', 2)
            ->where('contacts.meta.current_page', 3)
            ->has('contacts.data', 0));
});

it('only queries SQL contact identifiers returned on the current native page', function () {
    $actor = User::factory()->create();
    $organization = Organization::factory()->for($actor->account)->create();
    $contacts = Contact::factory(1217)->for($actor->account)->for($organization)->create();
    $pageIds = $contacts->slice(1215)->values()->modelKeys();

    useTypesenseSearchResponse('contacts', function (array $parameters) use ($actor, $pageIds): array {
        assertNativeTypesenseFilters($parameters, $actor->account_id);
        expect($parameters['page'])->toBe(82)->and($parameters['per_page'])->toBe(15);

        return ['found' => 1217, 'hits' => array_map(fn (int $id): array => ['document' => ['id' => (string) $id]], $pageIds)];
    });

    DB::enableQueryLog();

    try {
        $this->actingAs($actor)->get('/contacts?search=common&page=82')
            ->assertInertia(fn (Assert $assert) => $assert
                ->where('contacts.meta.total', 1217)
                ->has('contacts.data', 2)
                ->where('contacts.data.0.organization.name', $organization->name));

        $queries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains($query['query'], 'from "contacts"'));
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }

    expect($queries)->not->toBeEmpty();

    foreach ($queries as $query) {
        expect(preg_match('/"(?:contacts"\.")?id" in \(([0-9, ]+)\)/', $query['query'], $matches))->toBe(1);
        expect(array_map(intval(...), explode(',', $matches[1])))->toEqualCanonicalizing($pageIds);
    }
});

it('reindexes active and trashed contacts when their organization changes', function (string $change, string $expectedName) {
    $account = Account::factory()->create();
    $organization = Organization::factory()->for($account)->create(['name' => 'Old name']);
    $contacts = Contact::factory(2)->for($account)->for($organization)->create();
    $contacts->last()->delete();

    if ($change === 'restored') {
        $organization->delete();
    }

    $indexed = collect();
    $engine = Mockery::mock(CollectionEngine::class)->makePartial();
    $engine->shouldReceive('update')->andReturnUsing(function (Collection $models) use ($indexed): void {
        foreach ($models as $model) {
            if ($model instanceof Contact) {
                $indexed->put($model->id, $model->toSearchableArray()['organization_name']);
            }
        }
    });
    app(EngineManager::class)->extend('recording', fn () => $engine);
    config()->set(['scout.driver' => 'recording', 'scout.queue' => false]);

    match ($change) {
        'renamed' => $organization->update(['name' => 'New name']),
        'deleted' => $organization->delete(),
        'restored' => $organization->restore(),
    };

    expect($indexed->all())->toBe($contacts->mapWithKeys(fn (Contact $contact) => [$contact->id => $expectedName])->all());
})->with([
    'renamed' => ['renamed', 'New name'],
    'deleted' => ['deleted', ''],
    'restored' => ['restored', 'Old name'],
]);

it('serves the native user page when an unrelated contact changes before or during the search', function (bool $duringSearch) {
    $actor = User::factory()->create(['first_name' => 'Ada', 'owner' => true]);
    $contact = Contact::factory()->for($actor->account)->create();
    config()->set('search.synchronous', false);
    $change = fn () => app(App\Services\SearchIndex::class)->mutate(
        $actor->account_id,
        fn () => tap($contact)->update(['last_name' => 'Changed']),
    );
    useTypesenseSearchResponse('users', function (array $parameters) use ($actor, $duringSearch, $change): array {
        assertNativeTypesenseFilters($parameters, $actor->account_id, 'owner');

        if ($duringSearch) {
            $change();
        }

        return ['found' => 1, 'hits' => [['document' => ['id' => (string) $actor->id]]]];
    });

    if (! $duringSearch) {
        $change();
    }

    $this->actingAs($actor)->get('/users?search=Ada&role=owner')->assertOk()
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('users.meta.total', 1)
            ->where('users.data.0.id', $actor->id));
})->with(['pending before search' => false, 'committed during search' => true]);

it('retries the complete search once when its retired collection is removed during a generation switch', function () {
    $actor = User::factory()->create(['first_name' => 'Ada', 'owner' => true]);
    $old = '11111111-1111-4111-8111-111111111111';
    $new = '22222222-2222-4222-8222-222222222222';
    DB::table('search_index_manifest')->update(['active_generation' => $old]);
    $requests = [];
    $handler = GuzzleHttp\HandlerStack::create(new GuzzleHttp\Handler\MockHandler([
        function () use ($new) {
            DB::table('search_index_manifest')->update(['active_generation' => $new]);

            return new GuzzleHttp\Psr7\Response(404, body: '{"message":"Collection not found"}');
        },
        new GuzzleHttp\Psr7\Response(200, body: json_encode([
            'found' => 1,
            'hits' => [['document' => ['id' => (string) $actor->id]]],
        ], JSON_THROW_ON_ERROR)),
    ]));
    $handler->push(GuzzleHttp\Middleware::history($requests));
    $client = new Client([
        'api_key' => 'test-key',
        'nodes' => [['host' => 'localhost', 'port' => '8108', 'protocol' => 'http']],
        'num_retries' => 0,
        'client' => new GuzzleHttp\Client(['handler' => $handler]),
    ]);
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');

    $this->actingAs($actor)->get('/users?search=Ada&role=owner&trashed=with&page=1')
        ->assertOk()->assertInertia(fn (Assert $assert) => $assert
        ->where('users.meta.total', 1)
        ->where('users.data.0.id', $actor->id));

    expect($requests)->toHaveCount(2)
        ->and($requests[0]['request']->getUri()->getPath())->toContain('users__'.$old)
        ->and($requests[1]['request']->getUri()->getPath())->toContain('users__'.$new)
        ->and($requests[1]['request']->getUri()->getQuery())->toBe($requests[0]['request']->getUri()->getQuery());
});

it('bounds retries when retired collections disappear during repeated generation switches', function () {
    $actor = User::factory()->create();
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->twice()->with(Mockery::type('string'), Mockery::type('array'))
        ->andReturnUsing(function () {
            DB::table('search_index_manifest')->update(['active_generation' => (string) Illuminate\Support\Str::uuid()]);

            throw new Typesense\Exceptions\ObjectNotFound('Collection retired');
        });
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->twice()->andReturn(new Collections($api));
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');

    $this->expectException(SearchIndexUnavailable::class);
    $this->actingAs($actor)->get('/users?search=Ada');
});
