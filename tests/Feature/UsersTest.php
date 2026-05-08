<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->account = Account::create(['name' => 'Acme Corporation']);

    $this->owner = User::factory()->for($this->account)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'johndoe@example.com',
        'owner' => true,
    ]);
});

it('lists the account users ordered by name', function () {
    User::factory()->for($this->account)->create(['first_name' => 'Alice', 'last_name' => 'Archer', 'owner' => false]);
    User::factory()->for($this->account)->create(['first_name' => 'Zack', 'last_name' => 'Zimmer', 'owner' => false]);

    $this->actingAs($this->owner)
        ->get('/users')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('users/index')
            ->has('users.data', 3)
            ->where('users.data.0.name', 'Alice Archer')
            ->where('users.data.1.name', 'John Doe')
            ->where('users.data.2.name', 'Zack Zimmer')
        );
});

it('filters users by search term', function () {
    User::factory()->for($this->account)->create(['first_name' => 'Alice', 'last_name' => 'Archer']);

    $this->actingAs($this->owner)
        ->get('/users?search=Alice')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('users/index')
            ->where('filters.search', 'Alice')
            ->has('users.data', 1)
            ->where('users.data.0.name', 'Alice Archer')
        );
});

it('filters users by role', function (string $role, array $expectedNames) {
    User::factory()->for($this->account)->create(['first_name' => 'Alice', 'last_name' => 'Archer', 'owner' => false]);
    User::factory()->for($this->account)->create(['first_name' => 'Owen', 'last_name' => 'Ranger', 'owner' => true]);

    $this->actingAs($this->owner)
        ->get("/users?role=$role")
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('users/index')
            ->where('filters.role', $role)
            ->has('users.data', count($expectedNames))
            ->where('users.data', fn ($data) => collect($data)->pluck('name')->sort()->values()->all() === collect($expectedNames)->sort()->values()->all())
        );
})->with([
    'owner role returns only owners' => ['owner', ['John Doe', 'Owen Ranger']],
    'user role returns only non-owners' => ['user', ['Alice Archer']],
]);

it('renders the create page', function () {
    $this->actingAs($this->owner)
        ->get('/users/create')
        ->assertInertia(fn (Assert $assert) => $assert->component('users/create'));
});

it('stores a new user and redirects to the index', function () {
    $this->actingAs($this->owner)
        ->post('/users', [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'email' => 'sam.smith@example.com',
            'password' => 'SuperSecret123!',
            'owner' => false,
        ])
        ->assertRedirect('/users');

    $this->assertDatabaseHas('users', [
        'email' => 'sam.smith@example.com',
        'account_id' => $this->account->id,
        'owner' => false,
    ]);
});

it('rejects user creation with validation errors', function (array $payload, array $invalidFields) {
    $this->actingAs($this->owner)
        ->from('/users/create')
        ->post('/users', $payload)
        ->assertRedirect('/users/create')
        ->assertInvalid($invalidFields);
})->with([
    'missing required fields' => [
        ['owner' => false],
        ['first_name', 'last_name', 'email'],
    ],
    'invalid email format' => [
        [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'email' => 'not-an-email',
            'password' => 'SuperSecret123!',
            'owner' => false,
        ],
        ['email'],
    ],
]);

it('rejects user creation with a duplicate email', function () {
    $this->actingAs($this->owner)
        ->from('/users/create')
        ->post('/users', [
            'first_name' => 'Dup',
            'last_name' => 'Licate',
            'email' => $this->owner->email,
            'password' => 'SuperSecret123!',
            'owner' => false,
        ])
        ->assertInvalid(['email']);
});

it('renders the edit page for a user of the account', function () {
    $target = User::factory()->for($this->account)->create();

    $this->actingAs($this->owner)
        ->get("/users/{$target->id}/edit")
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('users/edit')
            ->where('user.id', $target->id)
        );
});

it('updates a user of the account', function () {
    $target = User::factory()->for($this->account)->create(['first_name' => 'Old', 'last_name' => 'Name']);

    $this->actingAs($this->owner)
        ->put("/users/{$target->id}", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $target->email,
            'owner' => false,
        ])
        ->assertRedirect();

    expect($target->fresh()->first_name)->toBe('New');
});

it('soft deletes a user', function () {
    $target = User::factory()->for($this->account)->create();

    $this->actingAs($this->owner)
        ->delete("/users/{$target->id}")
        ->assertRedirect();

    expect($target->fresh()->deleted_at)->not->toBeNull();
});

it('restores a trashed user', function () {
    $target = User::factory()->for($this->account)->create();
    $target->delete();

    $this->actingAs($this->owner)
        ->put("/users/{$target->id}/restore")
        ->assertRedirect();

    expect($target->fresh()->deleted_at)->toBeNull();
});

it('shows trashed users when filter is applied', function () {
    $target = User::factory()->for($this->account)->create(['first_name' => 'Gone', 'last_name' => 'User']);
    $target->delete();

    $this->actingAs($this->owner)
        ->get('/users?trashed=with')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('users/index')
            ->has('users.data', 2)
        );
});
