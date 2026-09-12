<?php

declare(strict_types=1);

use App\Models\User;

it('marks the current resource navigation active including nested pages and filters', function (string $path, string $navigationPath) {
    $this->actingAs(User::factory()->create(['owner' => true]));

    visit($path)
        ->assertAttribute('[data-slot="sidebar-content"] [data-slot="sidebar-menu-button"][href="'.$navigationPath.'"]', 'aria-current', 'page')
        ->assertNoJavaScriptErrors();
})->with([
    'dashboard' => ['/', '/'],
    'filtered organizations' => ['/organizations?search=Acme', '/organizations'],
    'create contact' => ['/contacts/create', '/contacts'],
    'users' => ['/users', '/users'],
]);

it('offers the role filter before a role has been selected', function () {
    $owner = User::factory()->create(['owner' => true]);
    $member = User::factory()->for($owner->account)->create(['owner' => false]);
    $this->actingAs($owner);

    visit('/users')
        ->click('[aria-label="Filter"]')
        ->assertVisible('#role')
        ->click('#role')
        ->click('[role="option"]:has-text("Owner")')
        ->assertQueryStringHas('role', 'owner')
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->assertDontSeeIn('[data-slot="table-body"]', $member->email)
        ->assertNoJavaScriptErrors();
});

it('names theme controls and exposes the selected appearance', function () {
    $this->actingAs(User::factory()->create());
    $this->withSession(['inertia.flash_data' => ['success' => 'Appearance preview.']]);

    visit('/')
        ->assertSee('Appearance preview.')
        ->click('button[aria-label="Dark"]')
        ->assertAttribute('button[aria-label="Dark"]', 'aria-pressed', 'true')
        ->assertAttribute('button[aria-label="Light"]', 'aria-pressed', 'false')
        ->assertAttribute('html[class]', 'class', 'dark')
        ->assertAttribute('[data-sonner-toaster]', 'data-sonner-theme', 'dark')
        ->click('button[aria-label="Light"]')
        ->assertAttribute('button[aria-label="Light"]', 'aria-pressed', 'true')
        ->assertNoJavaScriptErrors();
});

it('preserves the sidebar preference on a full page reload', function () {
    $this->actingAs(User::factory()->create());

    visit('/')
        ->click('[data-slot="sidebar-trigger"]')
        ->assertAttribute('[data-slot="sidebar"]', 'data-state', 'collapsed')
        ->refresh()
        ->assertAttribute('[data-slot="sidebar"]', 'data-state', 'collapsed')
        ->assertNoJavaScriptErrors();
});

it('keeps literal search text when it matches a filter sentinel or zero', function (string $search) {
    $this->actingAs(User::factory()->create(['owner' => true]));

    visit('/organizations')
        ->fill('[aria-label="Search"]', $search)
        ->assertQueryStringHas('search', $search)
        ->assertNoJavaScriptErrors();
})->with(['any', '0']);

it('renders and keeps the selected language after a full page reload', function () {
    $this->actingAs(User::factory()->create());

    visit('/')
        ->withLocale('fr-FR')
        ->assertSee('Tableau de bord')
        ->assertVisible('button[aria-label="Sombre"]')
        ->click('Français')
        ->click('[role="menuitem"]:has-text("English")')
        ->assertSee('Dashboard')
        ->refresh()
        ->assertSee('Dashboard')
        ->assertVisible('button[aria-label="Dark"]')
        ->assertNoJavaScriptErrors();
});
