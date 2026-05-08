<?php

declare(strict_types=1);

use App\Models\Account;
use App\Models\User;

it('opens the sidebar user menu without JavaScript errors', function () {
    $user = User::factory()->for(Account::create(['name' => 'Acme']))->create();

    $this->actingAs($user);

    $page = visit('/');

    $page->assertNoJavaScriptErrors()
        ->click($user->name)
        ->assertSee('Logout')
        ->assertSee('My Profile')
        ->assertNoJavaScriptErrors();
});
