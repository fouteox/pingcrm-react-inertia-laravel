<?php

declare(strict_types=1);

use App\Models\Contact;
use App\Models\Organization;
use App\Models\User;

it('creates contacts and organizations without an optional email', function (string $resource, array $fields) {
    $user = User::factory()->create(['owner' => true]);
    $this->actingAs($user);

    $page = visit('/'.$resource.'/create');

    foreach ($fields as $field => $value) {
        $page->fill('#'.$field, $value);
    }

    $page->submit()
        ->assertPathIs('/'.$resource)
        ->assertNoJavaScriptErrors();

    $this->assertDatabaseHas($resource, [...$fields, 'account_id' => $user->account_id, 'email' => null]);
})->with([
    'contact' => ['contacts', ['first_name' => 'Ada', 'last_name' => 'Lovelace']],
    'organization' => ['organizations', ['name' => 'Analytical Engines']],
]);

it('updates existing records without an email and reports success on their own form', function (string $model, string $resource) {
    $user = User::factory()->create(['owner' => true]);
    $record = $model::factory()->for($user->account)->create(['email' => null]);
    $this->actingAs($user);

    visit('/'.$resource.'/'.$record->id.'/edit')
        ->fill('#city', 'London')
        ->submit()
        ->assertSee('Saved.')
        ->assertNoJavaScriptErrors();

    expect($record->fresh()->city)->toBe('London');
    expect($record->fresh()->email)->toBeNull();
})->with([
    'contact' => [Contact::class, 'contacts'],
    'organization' => [Organization::class, 'organizations'],
]);

it('clears a previous success when a subsequent submission fails and associates its validation error', function () {
    $owner = User::factory()->create(['owner' => true]);
    $user = User::factory()->for($owner->account)->create();
    $this->actingAs($owner);

    $page = visit('/users/'.$user->id.'/edit')
        ->fill('#first_name', 'Updated')
        ->submit()
        ->assertSee('Saved.');

    $page->fill('#email', $owner->email)
        ->submit()
        ->assertSee('The email has already been taken.')
        ->assertDontSee('Saved.')
        ->assertAttribute('#email', 'aria-describedby', 'email-error')
        ->assertSeeIn('#email-error', 'The email has already been taken.')
        ->assertNoJavaScriptErrors();

    expect($user->fresh()->email)->toBe($user->email);
});

it('shows native flash data on a full page load', function () {
    $this->actingAs(User::factory()->create());
    $this->withSession(['inertia.flash_data' => ['success' => 'The record was saved.']]);

    visit('/')
        ->assertSee('The record was saved.')
        ->assertNoJavaScriptErrors();
});

it('shows each new action flash after returning through browser history', function () {
    $owner = User::factory()->create(['owner' => true]);
    $organization = Organization::factory()->for($owner->account)->create();
    $this->actingAs($owner);

    $page = visit('/organizations/'.$organization->id.'/edit')
        ->fill('#city', 'London')
        ->submit()
        ->assertSee('Organization updated.');

    $page->click('[data-slot="sidebar-content"] a[href="/contacts"]')
        ->assertPathIs('/contacts')
        ->assertDontSee('Organization updated.')
        ->back()
        ->assertPathIs('/organizations/'.$organization->id.'/edit')
        ->assertDontSee('Organization updated.')
        ->fill('#city', 'Paris')
        ->submit()
        ->assertSee('Organization updated.')
        ->assertNoJavaScriptErrors();

    expect($organization->fresh()->city)->toBe('Paris');
});

it('deletes and restores a resource using its own confirmation action', function (string $model, string $resource, string $label) {
    $owner = User::factory()->create(['owner' => true]);
    $record = $model::factory()->for($owner->account)->create();
    $this->actingAs($owner);

    $page = visit('/'.$resource.'/'.$record->id.'/edit')
        ->click('Delete '.$label)
        ->assertVisible('[role="alertdialog"]')
        ->click('[role="alertdialog"] button:has-text("Delete '.$label.'")')
        ->assertSee('This '.mb_strtolower($label).' has been deleted.')
        ->assertMissing('[role="alertdialog"]');

    expect($record->fresh()->trashed())->toBeTrue();

    $page->click('Restore')
        ->click('[role="alertdialog"] button:has-text("Restore '.$label.'")')
        ->assertMissing('[role="alertdialog"]')
        ->assertDontSee('This '.mb_strtolower($label).' has been deleted.')
        ->assertNoJavaScriptErrors();

    expect($record->fresh()->trashed())->toBeFalse();
})->with([
    'contact' => [Contact::class, 'contacts', 'Contact'],
    'organization' => [Organization::class, 'organizations', 'Organization'],
    'user' => [User::class, 'users', 'User'],
]);

it('shows an authentication error flash on the login page', function () {
    $this->withSession(['inertia.flash_data' => ['error' => 'Your session expired. Please try again.']]);

    visit('/login')
        ->assertSee('Your session expired. Please try again.')
        ->assertNoJavaScriptErrors();
});
