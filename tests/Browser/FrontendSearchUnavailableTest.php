<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Http\Controllers\UsersController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

it('replaces an Inertia search with an unavailable page and retries the same filters', function () {
    $owner = User::factory()->create(['owner' => true]);
    $this->actingAs($owner);

    Route::middleware('web')->get('/users', function (Request $request) {
        if ($request->filled('search')) {
            throw new SearchIndexUnavailable;
        }

        return app()->call([app(UsersController::class), 'index']);
    });

    $page = visit('/users?role=owner&trashed=with')
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->fill('[aria-label="Search"]', '0')
        ->assertSee('Search is temporarily unavailable.')
        ->assertQueryStringHas('search', '0')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->assertMissing('[data-slot="table"]')
        ->assertMissing('dialog')
        ->click('Try again')
        ->assertSee('Search is temporarily unavailable.')
        ->assertQueryStringHas('search', '0')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with');

    $page->click('View the list without searching')
        ->assertQueryStringMissing('search')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->assertNoJavaScriptErrors();
});

it('renders the unavailable page in French on an initial page load', function () {
    $this->actingAs(User::factory()->create(['owner' => true]));
    Route::middleware('web')->get('/testing/search-unavailable', function () {
        throw new SearchIndexUnavailable;
    });

    visit('/testing/search-unavailable?search=Acme')
        ->withLocale('fr-FR')
        ->assertSee('La recherche est temporairement indisponible.')
        ->assertSee('Réessayer')
        ->assertSee('Consulter la liste sans recherche')
        ->assertQueryStringHas('search', 'Acme')
        ->assertNoJavaScriptErrors();
});

it('shows search results when a manual retry succeeds', function () {
    $owner = User::factory()->create(['owner' => true, 'first_name' => 'Recovery']);
    $this->actingAs($owner);
    $unavailable = true;

    Route::middleware('web')->get('/users', function () use (&$unavailable) {
        if ($unavailable) {
            throw new SearchIndexUnavailable;
        }

        return app()->call([app(UsersController::class), 'index']);
    });

    $page = visit('/users?role=owner&search=Recovery')
        ->assertSee('Search is temporarily unavailable.');

    $unavailable = false;

    $page->click('Try again')
        ->assertQueryStringHas('search', 'Recovery')
        ->assertQueryStringHas('role', 'owner')
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->assertDontSee('Search is temporarily unavailable.')
        ->assertNoJavaScriptErrors();
});
