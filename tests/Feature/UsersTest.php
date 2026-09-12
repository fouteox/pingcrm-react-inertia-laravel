<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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
        ->assertRedirect('/users')
        ->assertInertiaFlash('success', translate_with_gender('created', 'User'));

    $this->assertDatabaseHas('users', [
        'email' => 'sam.smith@example.com',
        'account_id' => $this->account->id,
        'owner' => false,
    ]);

    expect(Hash::check('SuperSecret123!', User::where('email', 'sam.smith@example.com')->firstOrFail()->password))->toBeTrue();
});

it('requires a password when creating a user', function () {
    $this->actingAs($this->owner)
        ->postJson('/users', [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'email' => 'sam.smith@example.com',
            'owner' => false,
        ])
        ->assertUnprocessable()
        ->assertInvalid(['password']);

    $this->assertDatabaseMissing('users', ['email' => 'sam.smith@example.com']);
});

it('rejects array values for user names', function (string $field) {
    $this->actingAs($this->owner)
        ->postJson('/users', [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'email' => 'sam.smith@example.com',
            'password' => 'SuperSecret123!',
            'owner' => false,
            $field => ['invalid'],
        ])
        ->assertUnprocessable()
        ->assertInvalid([$field]);

    $this->assertDatabaseMissing('users', ['email' => 'sam.smith@example.com']);
})->with(['first_name', 'last_name']);

it('ignores an unsupported photo input when creating a user', function () {
    $this->actingAs($this->owner)
        ->post('/users', [
            'first_name' => 'Sam',
            'last_name' => 'Smith',
            'email' => 'sam.smith@example.com',
            'password' => 'SuperSecret123!',
            'owner' => false,
            'photo' => 'untrusted/path.jpg',
        ])
        ->assertRedirect('/users')
        ->assertValid();

    expect(User::where('email', 'sam.smith@example.com')->firstOrFail()->photo)->toBeNull();
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
        ['first_name', 'last_name', 'email', 'password'],
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
    $password = $target->password;

    $this->actingAs($this->owner)
        ->put("/users/{$target->id}", [
            'first_name' => 'New',
            'last_name' => 'Name',
            'email' => $target->email,
            'owner' => false,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('updated', 'User'));

    expect($target->fresh())
        ->first_name->toBe('New')
        ->password->toBe($password);
});

it('replaces a users password only when a valid password is supplied', function () {
    $target = User::factory()->for($this->account)->create();

    $this->actingAs($this->owner)
        ->put("/users/{$target->id}", [
            'first_name' => $target->first_name,
            'last_name' => $target->last_name,
            'email' => $target->email,
            'owner' => false,
            'password' => 'ChangedSecret123!',
        ])
        ->assertRedirect()
        ->assertValid();

    expect(Hash::check('ChangedSecret123!', $target->fresh()->password))->toBeTrue();
});

it('soft deletes a user', function () {
    $target = User::factory()->for($this->account)->create();

    $this->actingAs($this->owner)
        ->delete("/users/{$target->id}")
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('deleted', 'User'));

    expect($target->fresh()->deleted_at)->not->toBeNull();
});

it('restores a trashed user', function () {
    $target = User::factory()->for($this->account)->create();
    $target->delete();

    $this->actingAs($this->owner)
        ->put("/users/{$target->id}/restore")
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('restored', 'User'));

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

it('exposes the demo protection consistently in user resources', function (string $environment, string $email, bool $canDelete) {
    app()->detectEnvironment(fn () => $environment);
    $this->owner->update(['email' => $email]);

    $this->actingAs($this->owner)->get("/users/{$this->owner->id}/edit")
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('user.can_delete', $canDelete)
            ->where('auth.user.can_delete', $canDelete)
        );
})->with([
    'demo in production' => ['production', 'johndoe@example.com', false],
    'ordinary user in production' => ['production', 'alice@example.com', true],
    'demo outside production' => ['testing', 'johndoe@example.com', true],
]);

it('preserves the protected demo user and flashes the refusal to delete it', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->actingAs($this->owner)
        ->withSession(['_token' => 'demo-test-token'])
        ->delete("/users/{$this->owner->id}", ['_token' => 'demo-test-token'])
        ->assertRedirect()
        ->assertInertiaFlash('error', __('Deleting the demo user is not allowed.'));

    $this->assertNotSoftDeleted($this->owner);
});
