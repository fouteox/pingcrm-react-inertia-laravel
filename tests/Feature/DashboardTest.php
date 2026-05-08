<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('redirects guests to the login page', function () {
    $this->get('/')->assertRedirect(route('login', absolute: false));
});

it('renders the dashboard for authenticated users', function () {
    $user = User::factory()->for(Account::create(['name' => 'Acme']))->create();

    $this->actingAs($user)
        ->get('/')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $assert) => $assert->component('dashboard'));
});

it('renders the fadogen page for authenticated users', function () {
    $user = User::factory()->for(Account::create(['name' => 'Acme']))->create();

    $this->actingAs($user)
        ->get('/fadogen')
        ->assertSuccessful()
        ->assertInertia(fn (Assert $assert) => $assert->component('fadogen'));
});
