<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $account = Account::create(['name' => 'Acme Corporation']);

    $this->user = User::factory()->for($account)->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'johndoe@example.com',
        'owner' => true,
    ]);

    $account->organizations()->createMany([
        [
            'name' => 'Apple',
            'email' => 'info@apple.com',
            'phone' => '647-943-4400',
            'address' => '1600-120 Bremner Blvd',
            'city' => 'Toronto',
            'region' => 'ON',
            'country' => 'CA',
            'postal_code' => 'M5J 0A8',
        ],
        [
            'name' => 'Microsoft',
            'email' => 'info@microsoft.com',
            'phone' => '877-568-2495',
            'address' => 'One Microsoft Way',
            'city' => 'Redmond',
            'region' => 'WA',
            'country' => 'US',
            'postal_code' => '98052',
        ],
    ]);
});

it('lists the account organizations ordered by name', function () {
    $this->actingAs($this->user)
        ->get('/organizations')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('organizations/index')
            ->has('organizations.data', 2)
            ->has('organizations.data.0', fn (Assert $assert) => $assert
                ->has('id')
                ->where('name', 'Apple')
                ->where('phone', '647-943-4400')
                ->where('city', 'Toronto')
                ->where('deleted_at', null)
            )
            ->has('organizations.data.1', fn (Assert $assert) => $assert
                ->where('name', 'Microsoft')
                ->where('phone', '877-568-2495')
                ->where('city', 'Redmond')
                ->where('deleted_at', null)
                ->etc()
            )
        );
});

it('filters organizations by search term', function () {
    $this->actingAs($this->user)
        ->get('/organizations?search=Apple')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('organizations/index')
            ->where('filters.search', 'Apple')
            ->has('organizations.data', 1)
            ->where('organizations.data.0.name', 'Apple')
        );
});

describe('soft-deleted organizations', function () {
    beforeEach(function () {
        $this->user->account->organizations()->firstWhere('name', 'Microsoft')->delete();
    });

    it('hides them by default', function () {
        $this->actingAs($this->user)
            ->get('/organizations')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('organizations/index')
                ->has('organizations.data', 1)
                ->where('organizations.data.0.name', 'Apple')
            );
    });

    it('shows them when trashed filter is "with"', function () {
        $this->actingAs($this->user)
            ->get('/organizations?trashed=with')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('organizations/index')
                ->has('organizations.data', 2)
                ->where('organizations.data.0.name', 'Apple')
                ->where('organizations.data.1.name', 'Microsoft')
            );
    });
});
