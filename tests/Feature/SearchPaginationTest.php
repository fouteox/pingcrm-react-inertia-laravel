<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\CollectionEngine;
use Laravel\Scout\Engines\TypesenseEngine;
use Typesense\ApiCall;
use Typesense\Client;
use Typesense\Collections;
use Typesense\Exceptions\TypesenseClientError;
use Typesense\MultiSearch;

beforeEach(function () {
    $this->withoutExceptionHandling();
    config()->set('scout.prefix', '');
});

function useTypesenseSearchResponse(string $index, Closure $response): void
{
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('post')->once()->with('/multi_search', Mockery::type('array'), true, [])
        ->andReturnUsing(function (string $path, array $body) use ($index, $response): array {
            expect($body['searches'])->toHaveCount(1)
                ->and($body['searches'][0]['collection'])->toBe($index);

            return ['results' => [$response($body['searches'][0])]];
        });

    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->once()->andReturn(new Collections($api));
    $client->shouldReceive('getMultiSearch')->once()->andReturn(new MultiSearch($api));

    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');
}

function assertTypesenseEligibleIds(array $parameters, array $expectedIds): array
{
    expect($parameters['filter_curated_hits'])->toBeTrue()
        ->and($parameters['filter_by'])->toMatch('/^id:=\[[0-9, ]+\]$/');

    $ids = array_map(trim(...), explode(',', mb_substr($parameters['filter_by'], 5, -1)));

    expect($ids)->toEqualCanonicalizing(array_map(strval(...), $expectedIds));

    return $ids;
}

it('paginates typo matches beyond 1200 eligible records through a native POST search', function (string $model, string $resource, string $sort) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['first_name' => 'Viewer', 'last_name' => 'Observer']);
    $attributes = $model === Organization::class
        ? ['name' => 'Common']
        : ['first_name' => 'Common', 'last_name' => 'Person'];
    $model::factory()->create($attributes);
    $model::factory()->for($account)->create([...$attributes, 'deleted_at' => now()]);
    $matches = $model::factory(1217)->for($account)->create($attributes);
    $eligibleIds = $matches->modelKeys();

    if ($model === User::class) {
        $eligibleIds[] = $actor->id;
    }

    $lastPage = $matches->slice(1215)->values();

    useTypesenseSearchResponse($resource, function (array $parameters) use ($eligibleIds, $matches, $sort): array {
        expect($parameters['q'])->toBe('commmon')
            ->and($parameters['page'])->toBe(82)
            ->and($parameters['per_page'])->toBe(15)
            ->and($parameters['sort_by'])->toBe($sort);
        $ids = assertTypesenseEligibleIds($parameters, $eligibleIds);
        $indexedMatches = $matches->whereIn('id', $ids);

        return [
            'found' => $indexedMatches->count(),
            'hits' => $indexedMatches->forPage($parameters['page'], $parameters['per_page'])
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

it('paginates zero searches using current SQL roles and trash states', function (string $role, ?string $trashed) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => true]);
    $attributes = ['first_name' => '0', 'last_name' => 'Person'];
    $foreignUsers = User::factory(3)->create($attributes);
    $users = User::factory(16)->for($account)->create([...$attributes, 'owner' => true]);
    $deletedUsers = User::factory(17)->for($account)->create([...$attributes, 'owner' => true, 'deleted_at' => now()]);
    $owners = User::factory(18)->for($account)->create([...$attributes, 'owner' => true]);
    $deletedOwners = User::factory(19)->for($account)->create([...$attributes, 'owner' => true, 'deleted_at' => now()]);
    User::withTrashed()->whereKey($users->concat($deletedUsers)->modelKeys())->update(['owner' => false]);
    $active = $role === 'owner' ? $owners : $users;
    $deleted = $role === 'owner' ? $deletedOwners : $deletedUsers;
    $expected = match ($trashed) {
        'with' => $active->concat($deleted),
        'only' => $deleted,
        default => $active,
    };
    $eligibleIds = $expected->modelKeys();

    if ($role === 'owner' && $trashed !== 'only') {
        $eligibleIds[] = $actor->id;
    }

    $indexed = $foreignUsers->concat($users)->concat($deletedUsers)->concat($owners)->concat($deletedOwners);
    useTypesenseSearchResponse('users', function (array $parameters) use ($eligibleIds, $indexed): array {
        expect($parameters['q'])->toBe('0');
        $ids = assertTypesenseEligibleIds($parameters, $eligibleIds);
        $matches = $indexed->whereIn('id', $ids)->values();

        return [
            'found' => $matches->count(),
            'hits' => $matches->forPage($parameters['page'], $parameters['per_page'])
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

it('returns an empty page without calling Typesense when SQL has no eligible records', function (string $model, string $resource) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => false]);
    $attributes = $model === User::class ? ['owner' => true] : [];
    $model::factory()->create($attributes);
    $model::factory()->for($account)->create([...$attributes, 'deleted_at' => now()]);

    $client = Mockery::mock(Client::class);
    $client->shouldNotReceive('getCollections');
    $client->shouldNotReceive('getMultiSearch');
    app()->instance(Client::class, $client);
    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');

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

it('excludes stale ineligible hits before computing the total and slicing a page', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    $organization = Organization::factory()->for($account)->create();
    $foreignContact = Contact::factory()->create();
    $deletedContact = Contact::factory()->for($account)->create(['deleted_at' => now()]);
    $removedContact = Contact::factory()->for($account)->create();
    $removedContact->forceDelete();
    $contacts = Contact::factory(16)->for($account)->for($organization)->create(['first_name' => 'Common', 'last_name' => 'Person']);
    $indexed = collect([$foreignContact, $deletedContact, $removedContact])->concat($contacts);

    useTypesenseSearchResponse('contacts', function (array $parameters) use ($contacts, $indexed): array {
        $ids = assertTypesenseEligibleIds($parameters, $contacts->modelKeys());
        $matches = $indexed->whereIn('id', $ids)->values();

        return [
            'found' => $matches->count(),
            'hits' => $matches->forPage($parameters['page'], $parameters['per_page'])
                ->map(fn (Contact $contact) => ['document' => ['id' => (string) $contact->id]])->values()->all(),
        ];
    });

    $this->actingAs($actor)
        ->get('/contacts?search=common&page=2')
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('contacts.meta.total', 16)
            ->where('contacts.meta.last_page', 2)
            ->has('contacts.data', 1)
            ->where('contacts.data.0.id', $contacts->last()->id)
            ->where('contacts.data.0.organization.name', $organization->name));
});

it('propagates individual Typesense search errors instead of returning an empty page', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    Contact::factory()->for($account)->create();
    useTypesenseSearchResponse('contacts', fn (): array => ['code' => 503, 'error' => 'Search temporarily unavailable']);

    $this->expectException(TypesenseClientError::class);
    $this->expectExceptionMessage('Search temporarily unavailable');
    $this->expectExceptionCode(503);

    $this->actingAs($actor)->get('/contacts?search=common');
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
