<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\Contact;
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

    $account->contacts()->createMany(array_reverse([
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
    ]));
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

it('sorts contacts with the same last name by first name', function () {
    Contact::factory()->for($this->user->account)->create(['first_name' => 'Zoe', 'last_name' => 'Brown']);
    Contact::factory()->for($this->user->account)->create(['first_name' => 'Alice', 'last_name' => 'Brown']);

    $this->actingAs($this->user)->get('/contacts')->assertInertia(fn (Assert $assert) => $assert
        ->where('contacts.data.1.name', 'Alice Brown')
        ->where('contacts.data.2.name', 'Zoe Brown')
    );
});

it('creates a contact belonging to the current account', function () {
    $organization = $this->user->account->organizations()->firstOrFail();

    $this->actingAs($this->user)->post('/contacts', [
        'first_name' => 'Alice',
        'last_name' => 'Brown',
        'organization_id' => $organization->id,
        'email' => 'alice@example.com',
    ])
        ->assertRedirect('/contacts')
        ->assertInertiaFlash('success', translate_with_gender('created', 'Contact'));

    $this->assertDatabaseHas('contacts', [
        'account_id' => $this->user->account_id,
        'organization_id' => $organization->id,
        'first_name' => 'Alice',
        'last_name' => 'Brown',
        'email' => 'alice@example.com',
    ]);
});

it('rejects array values for contact text fields', function (string $field) {
    $this->actingAs($this->user)->postJson('/contacts', [
        'first_name' => 'Alice',
        'last_name' => 'Brown',
        $field => ['invalid'],
    ])
        ->assertUnprocessable()
        ->assertInvalid([$field]);

    $this->assertDatabaseCount('contacts', 2);
})->with(['first_name', 'last_name', 'email', 'phone', 'address', 'city', 'region', 'country', 'postal_code']);

it('rejects an organization from another account on create and update', function (string $method) {
    $foreignOrganization = Organization::factory()->create();
    $contact = $this->user->account->contacts()->firstOrFail();
    $originalOrganization = $contact->organization_id;
    $path = $method === 'postJson' ? '/contacts' : "/contacts/{$contact->id}";

    $this->actingAs($this->user)->{$method}($path, [
        'first_name' => 'Alice',
        'last_name' => 'Brown',
        'organization_id' => $foreignOrganization->id,
    ])
        ->assertUnprocessable()
        ->assertInvalid(['organization_id']);

    $this->assertDatabaseCount('contacts', 2);
    expect($contact->fresh()->organization_id)->toBe($originalOrganization);
})->with(['postJson', 'putJson']);

it('updates, deletes and restores a contact from the account', function () {
    $contact = $this->user->account->contacts()->firstOrFail();

    $this->actingAs($this->user)->put("/contacts/{$contact->id}", [
        'first_name' => 'Alice',
        'last_name' => 'Brown',
        'organization_id' => null,
    ])
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('updated', 'Contact'));

    expect($contact->fresh())->first_name->toBe('Alice')->organization_id->toBeNull();

    $this->delete("/contacts/{$contact->id}")
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('deleted', 'Contact'));
    $this->assertSoftDeleted($contact);

    $this->put("/contacts/{$contact->id}/restore")
        ->assertRedirect()
        ->assertInertiaFlash('success', translate_with_gender('restored', 'Contact'));
    $this->assertNotSoftDeleted($contact);
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
