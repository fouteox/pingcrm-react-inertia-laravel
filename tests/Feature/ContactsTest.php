<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Organization;
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

    $organization = Organization::factory()->for($account)->create([
        'name' => 'Example Organization Inc.',
    ]);

    $account->contacts()->createMany([
        [
            'organization_id' => $organization->id,
            'first_name' => 'Martin',
            'last_name' => 'Abbott',
            'email' => 'martin.abbott@example.com',
            'phone' => '555-111-2222',
            'address' => '330 Glenda Shore',
            'city' => 'Murphyland',
            'region' => 'Tennessee',
            'country' => 'US',
            'postal_code' => '57851',
        ],
        [
            'organization_id' => $organization->id,
            'first_name' => 'Lynn',
            'last_name' => 'Kub',
            'email' => 'lynn.kub@example.com',
            'phone' => '555-333-4444',
            'address' => '199 Connelly Turnpike',
            'city' => 'Woodstock',
            'region' => 'Colorado',
            'country' => 'US',
            'postal_code' => '11623',
        ],
    ]);
});

it('lists the account contacts ordered by name', function () {
    $this->actingAs($this->user)
        ->get('/contacts')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('contacts/index')
            ->has('contacts.data', 2)
            ->has('contacts.data.0', fn (Assert $assert) => $assert
                ->has('id')
                ->where('name', 'Martin Abbott')
                ->where('phone', '555-111-2222')
                ->where('city', 'Murphyland')
                ->where('deleted_at', null)
                ->has('organization', fn (Assert $assert) => $assert
                    ->where('name', 'Example Organization Inc.')
                    ->etc()
                )
            )
            ->has('contacts.data.1', fn (Assert $assert) => $assert
                ->where('name', 'Lynn Kub')
                ->where('phone', '555-333-4444')
                ->where('city', 'Woodstock')
                ->where('deleted_at', null)
                ->etc()
            )
        );
});

it('filters contacts by search term', function () {
    $this->actingAs($this->user)
        ->get('/contacts?search=Martin')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('contacts/index')
            ->where('filters.search', 'Martin')
            ->has('contacts.data', 1)
            ->where('contacts.data.0.name', 'Martin Abbott')
        );
});

describe('soft-deleted contacts', function () {
    beforeEach(function () {
        $this->user->account->contacts()->firstWhere('first_name', 'Martin')->delete();
    });

    it('hides them by default', function () {
        $this->actingAs($this->user)
            ->get('/contacts')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('contacts/index')
                ->has('contacts.data', 1)
                ->where('contacts.data.0.name', 'Lynn Kub')
            );
    });

    it('shows them when trashed filter is "with"', function () {
        $this->actingAs($this->user)
            ->get('/contacts?trashed=with')
            ->assertInertia(fn (Assert $assert) => $assert
                ->component('contacts/index')
                ->has('contacts.data', 2)
                ->where('contacts.data.0.name', 'Martin Abbott')
                ->where('contacts.data.1.name', 'Lynn Kub')
            );
    });
});
