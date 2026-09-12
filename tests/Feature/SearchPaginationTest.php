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

beforeEach(function () {
    $this->withoutExceptionHandling();
    config()->set('scout.prefix', '');
});

function useTypesenseSearchResponse(string $index, Closure $response): void
{
    $api = Mockery::mock(ApiCall::class);
    $api->shouldReceive('get')->once()->with('/collections/'.$index.'/documents/search', Mockery::type('array'))
        ->andReturnUsing(fn (string $path, array $parameters) => $response($parameters));

    $client = Mockery::mock(Client::class);
    $client->shouldReceive('getCollections')->once()->andReturn(new Collections($api));

    app(EngineManager::class)->extend('typesense', fn () => new TypesenseEngine($client, 1000));
    config()->set('scout.driver', 'typesense');
}

it('paginates Typesense results beyond the first 250 matches', function (string $model, string $resource, string $sort, int $total) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    $page = (int) ceil($total / 15);
    $lastPage = $model::factory(15)->for($account)->create();

    useTypesenseSearchResponse($resource, function (array $parameters) use ($account, $lastPage, $sort, $page, $total): array {
        expect($parameters['q'])->toBe('common')
            ->and($parameters['page'])->toBe($page)
            ->and($parameters['per_page'])->toBe(15)
            ->and($parameters['sort_by'])->toBe($sort)
            ->and($parameters['filter_by'])->toContain('account_id:='.$account->id, '__soft_deleted:=0');

        return [
            'found' => $total,
            'hits' => $lastPage->map(fn ($record) => ['document' => ['id' => (string) $record->id]])->all(),
        ];
    });

    $this->actingAs($actor)
        ->get("/$resource?search=common&page=$page")
        ->assertInertia(fn (Assert $assert) => $assert
            ->where("$resource.meta.total", $total)
            ->where("$resource.meta.current_page", $page)
            ->has("$resource.data", $lastPage->count())
            ->where("$resource.data.0.id", $lastPage->first()->id)
            ->where("$resource.data.".($lastPage->count() - 1).'.id', $lastPage->last()->id));
})->with([
    'contacts beyond 250' => [Contact::class, 'contacts', 'last_name:asc,first_name:asc,sort_id:asc', 270],
    'contacts beyond 1000' => [Contact::class, 'contacts', 'last_name:asc,first_name:asc,sort_id:asc', 1200],
    'organizations beyond 250' => [Organization::class, 'organizations', 'name:asc,sort_id:asc', 270],
    'organizations beyond 1000' => [Organization::class, 'organizations', 'name:asc,sort_id:asc', 1200],
    'users beyond 250' => [User::class, 'users', 'last_name:asc,first_name:asc,sort_id:asc', 270],
    'users beyond 1000' => [User::class, 'users', 'last_name:asc,first_name:asc,sort_id:asc', 1200],
]);

it('sends zero searches and role and trash filters to Typesense before pagination', function (string $trashed, ?int $deleted) {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    $target = User::factory()->for($account)->create(['first_name' => '0', 'owner' => false]);
    $target->delete();

    useTypesenseSearchResponse('users', function (array $parameters) use ($account, $target, $deleted): array {
        expect($parameters['q'])->toBe('0')
            ->and($parameters['per_page'])->toBe(15)
            ->and($parameters['filter_by'])->toContain('account_id:='.$account->id, 'owner:=false');

        if ($deleted === null) {
            expect($parameters['filter_by'])->not->toContain('__soft_deleted');
        } else {
            expect($parameters['filter_by'])->toContain('__soft_deleted:='.$deleted);
        }

        return ['found' => 1, 'hits' => [['document' => ['id' => (string) $target->id]]]];
    });

    $this->actingAs($actor)
        ->get('/users?search=0&role=user&trashed='.$trashed)
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('filters.search', '0')
            ->where('users.meta.total', 1)
            ->where('users.data.0.id', $target->id));
})->with([
    'only deleted' => ['only', 1],
    'including deleted' => ['with', null],
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

it('excludes foreign and deleted records from stale search hits and loads organization data for the page', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create();
    $organization = Organization::factory()->for($account)->create();
    $contact = Contact::factory()->for($account)->for($organization)->create();
    $foreignContact = Contact::factory()->create();
    $deletedContact = Contact::factory()->for($account)->create();
    $deletedContact->delete();

    useTypesenseSearchResponse('contacts', function (array $parameters) use ($account, $contact, $foreignContact, $deletedContact): array {
        expect($parameters['filter_by'])->toContain('account_id:='.$account->id);

        return ['found' => 3, 'hits' => [
            ['document' => ['id' => (string) $foreignContact->id]],
            ['document' => ['id' => (string) $deletedContact->id]],
            ['document' => ['id' => (string) $contact->id]],
        ]];
    });

    $this->actingAs($actor)
        ->get('/contacts?search=common')
        ->assertInertia(fn (Assert $assert) => $assert
            ->has('contacts.data', 1)
            ->where('contacts.data.0.id', $contact->id)
            ->where('contacts.data.0.organization.name', $organization->name));
});

it('excludes users whose role no longer matches their search index entry', function () {
    $account = Account::factory()->create();
    $actor = User::factory()->for($account)->create(['owner' => true]);
    $target = User::factory()->for($account)->create(['owner' => false]);

    useTypesenseSearchResponse('users', fn (array $parameters): array => ['found' => 2, 'hits' => [
        ['document' => ['id' => (string) $actor->id]],
        ['document' => ['id' => (string) $target->id]],
    ]]);

    $this->actingAs($actor)
        ->get('/users?search=common&role=user')
        ->assertInertia(fn (Assert $assert) => $assert
            ->has('users.data', 1)
            ->where('users.data.0.id', $target->id));
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
