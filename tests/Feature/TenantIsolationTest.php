<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

beforeEach(function () {
    $accountA = Account::create(['name' => 'Acme Corporation']);
    $accountB = Account::create(['name' => 'Globex Corporation']);

    $this->userA = User::factory()->for($accountA)->create(['owner' => true]);
    $this->userB = User::factory()->for($accountB)->create(['owner' => true]);

    $this->organizationB = Organization::factory()->for($accountB)->create(['name' => 'Globex HQ']);

    $this->contactB = Contact::factory()
        ->for($accountB)
        ->for($this->organizationB)
        ->create(['first_name' => 'Jane', 'last_name' => 'Roe']);
});

describe('contacts cross-tenant (resolveRouteBinding scopes by account_id → 404)', function () {
    it('blocks the route', function (string $method, string $pathTemplate) {
        $path = str_replace('{id}', (string) $this->contactB->id, $pathTemplate);

        $this->actingAs($this->userA)
            ->{$method}($path)
            ->assertNotFound();
    })->with([
        'edit' => ['get', '/contacts/{id}/edit'],
        'update' => ['put', '/contacts/{id}'],
        'delete' => ['delete', '/contacts/{id}'],
        'restore' => ['put', '/contacts/{id}/restore'],
    ]);

    it('does not mutate the foreign contact', function () {
        $this->contactB->delete();

        $this->actingAs($this->userA)
            ->put("/contacts/{$this->contactB->id}", [
                'first_name' => 'Hacked',
                'last_name' => 'Roe',
            ])
            ->assertNotFound();

        expect($this->contactB->fresh())
            ->first_name->toBe('Jane')
            ->deleted_at->not->toBeNull();
    });
});

describe('organizations cross-tenant (resolveRouteBinding → 404)', function () {
    it('blocks the route', function (string $method, string $pathTemplate) {
        $path = str_replace('{id}', (string) $this->organizationB->id, $pathTemplate);

        $this->actingAs($this->userA)
            ->{$method}($path)
            ->assertNotFound();
    })->with([
        'edit' => ['get', '/organizations/{id}/edit'],
        'update' => ['put', '/organizations/{id}'],
        'delete' => ['delete', '/organizations/{id}'],
        'restore' => ['put', '/organizations/{id}/restore'],
    ]);

    it('does not mutate the foreign organization', function () {
        $this->actingAs($this->userA)
            ->put("/organizations/{$this->organizationB->id}", ['name' => 'Hacked Corp'])
            ->assertNotFound();

        expect($this->organizationB->fresh()->name)->toBe('Globex HQ');
    });
});

describe('users cross-tenant (UserPolicy blocks → 403)', function () {
    it('blocks the route', function (string $method, string $pathTemplate, array $payload = []) {
        $path = str_replace('{id}', (string) $this->userB->id, $pathTemplate);

        $this->actingAs($this->userA)
            ->{$method}($path, $payload)
            ->assertForbidden();
    })->with([
        'edit' => ['get', '/users/{id}/edit', []],
        'update' => ['put', '/users/{id}', [
            'first_name' => 'Hacked',
            'last_name' => 'Name',
            'email' => 'hacked@example.com',
            'owner' => true,
        ]],
        'delete' => ['delete', '/users/{id}', []],
        'restore' => ['put', '/users/{id}/restore', []],
    ]);

    it('does not mutate the foreign user', function () {
        $originalEmail = $this->userB->email;

        $this->actingAs($this->userA)
            ->put("/users/{$this->userB->id}", [
                'first_name' => 'Hacked',
                'last_name' => 'Name',
                'email' => 'hacked@example.com',
                'owner' => true,
            ])
            ->assertForbidden();

        expect($this->userB->fresh())
            ->email->toBe($originalEmail)
            ->deleted_at->toBeNull();
    });
});

it('scopes index listings to the acting user account', function (string $path, string $component, string $dataKey, int $expectedCount) {
    $this->actingAs($this->userA)
        ->get($path)
        ->assertSuccessful()
        ->assertInertia(fn ($assert) => $assert
            ->component($component)
            ->has("$dataKey.data", $expectedCount)
        );
})->with([
    'contacts' => ['/contacts', 'contacts/index', 'contacts', 0],
    'organizations' => ['/organizations', 'organizations/index', 'organizations', 0],
    'users' => ['/users', 'users/index', 'users', 1],
]);

it('does not restore a deleted record from another account', function (string $modelProperty, string $resource, int $status) {
    $record = $this->{$modelProperty};
    $record->delete();

    $this->actingAs($this->userA)->put("/{$resource}/{$record->id}/restore")
        ->assertStatus($status);

    $this->assertSoftDeleted($record);
})->with([
    'contact' => ['contactB', 'contacts', 404],
    'organization' => ['organizationB', 'organizations', 404],
    'user' => ['userB', 'users', 403],
]);

it('does not expose another accounts matching search results', function (string $path, string $dataKey, string $search) {
    $this->actingAs($this->userA)
        ->get($path.'?'.http_build_query(['search' => $search]))
        ->assertInertia(fn ($assert) => $assert->has("{$dataKey}.data", 0));
})->with([
    'contacts' => ['/contacts', 'contacts', 'Jane'],
    'organizations' => ['/organizations', 'organizations', 'Globex HQ'],
]);

it('does not expose another accounts user through search', function () {
    $this->actingAs($this->userA)
        ->get('/users?'.http_build_query(['search' => $this->userB->email]))
        ->assertInertia(fn ($assert) => $assert->has('users.data', 0));
});
