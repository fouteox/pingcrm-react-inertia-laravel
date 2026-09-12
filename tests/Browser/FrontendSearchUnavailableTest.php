<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Http\Controllers\UsersController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;
use Pest\Browser\Api\AwaitableWebpage;

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
        ->assertSee('Nous réessayons automatiquement.')
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

it('resumes automatic retries after a manual retry fails with a network error', function () {
    $owner = User::factory()->create(['owner' => true, 'first_name' => 'Recovery']);
    $this->actingAs($owner);
    $queries = [];

    Route::middleware('web')->get('/users', function (Request $request) use (&$queries) {
        $queries[] = $request->query();

        if (count($queries) === 1) {
            throw new SearchIndexUnavailable;
        }

        if (count($queries) === 2) {
            // Browsers block port 1, producing an XHR network error without a remote service.
            return redirect('http://127.0.0.1:1/search-outage');
        }

        return app()->call([app(UsersController::class), 'index']);
    });

    $page = visit('/users?search=Recovery&role=owner&trashed=with')
        ->assertSee('Search is temporarily unavailable.');
    $page->script("window.manualRetryFinished = false; document.addEventListener('inertia:finish', () => { window.manualRetryFinished = true; }, { once: true });");

    $page->click('Try again');
    $page->page()->waitForFunction('window.manualRetryFinished');

    expect($queries)->toHaveCount(2);

    $page->assertSee('Search is temporarily unavailable.')
        ->assertMissing('[data-slot="table"]')
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->assertPathIs('/users')
        ->assertQueryStringHas('search', 'Recovery')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->assertDontSee('Search is temporarily unavailable.')
        ->assertNoJavaScriptErrors();

    expect($queries)->toHaveCount(3)
        ->and($queries[1])->toEqual($queries[0])
        ->and($queries[2])->toEqual($queries[0]);
});

it('automatically restores the requested search page and filters when the index recovers', function () {
    $owner = User::factory()->create(['owner' => true, 'first_name' => 'Recovery', 'last_name' => 'Alpha']);
    User::factory()->for($owner->account)->count(15)->create(['owner' => true, 'first_name' => 'Recovery', 'last_name' => 'Alpha']);
    $last = User::factory()->for($owner->account)->create(['owner' => true, 'first_name' => 'Recovery', 'last_name' => 'Zeta']);
    $this->actingAs($owner);
    $unavailable = true;
    $queries = [];

    Route::middleware('web')->get('/users', function (Request $request) use (&$unavailable, &$queries) {
        $queries[] = $request->query();

        if ($unavailable) {
            throw new SearchIndexUnavailable;
        }

        return app()->call([app(UsersController::class), 'index']);
    });

    $page = visit('/users?search=Recovery&role=owner&trashed=with&page=2')
        ->assertSee('Search is temporarily unavailable.');
    $historyLength = $page->script('window.history.length');
    $unavailable = false;

    $page->assertSeeIn('[data-slot="table-body"]', $last->email)
        ->assertQueryStringHas('search', 'Recovery')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->assertQueryStringHas('page', '2')
        ->assertDontSee('Search is temporarily unavailable.')
        ->wait(3.5)
        ->assertNoJavaScriptErrors();

    expect($queries)->toHaveCount(2)
        ->and($queries[1])->toEqual($queries[0])
        ->and($page->script('window.history.length'))->toBe($historyLength);
});

it('does not overlap slow automatic retries or a manual retry', function () {
    $this->actingAs(User::factory()->create(['owner' => true]));

    Route::middleware('web')->get('/users', function (Request $request) {
        if ($request->inertia()) {
            Sleep::for(4)->seconds();
        }

        throw new SearchIndexUnavailable;
    });

    $page = visit('/users?search=Recovery&role=owner&trashed=with')
        ->assertSee('Search is temporarily unavailable.');

    observeSearchRecoveryRequests($page, 'Try again');
    $page->wait(8);

    expect($page->script('window.searchRecovery.starts'))->toBe(1)
        ->and($page->script('window.searchRecovery.maxActive'))->toBe(1)
        ->and($page->script('window.searchRecovery.actionDisabled'))->toBeTrue();

    $page->click('Try again');

    expect($page->script('window.searchRecovery.starts'))->toBe(2)
        ->and($page->script('window.searchRecovery.maxActive'))->toBe(1);

    $page->wait(3.5);

    expect($page->script('window.searchRecovery.starts'))->toBe(3)
        ->and($page->script('window.searchRecovery.maxActive'))->toBe(1);

    $page->assertSee('Search is temporarily unavailable.')
        ->assertQueryStringHas('search', 'Recovery')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->assertNoJavaScriptErrors();
});

it('cancels the pending retry and stops polling after leaving the unavailable search', function () {
    $owner = User::factory()->create(['owner' => true]);
    $this->actingAs($owner);

    Route::middleware('web')->get('/users', function (Request $request) {
        if ($request->filled('search')) {
            if ($request->inertia()) {
                Sleep::for(4)->seconds();
            }

            throw new SearchIndexUnavailable;
        }

        return app()->call([app(UsersController::class), 'index']);
    });

    $page = visit('/users?search=Recovery&role=owner&trashed=with')
        ->assertSee('Search is temporarily unavailable.');

    observeSearchRecoveryRequests($page, 'View the list without searching');
    $page->wait(8)
        ->assertSeeIn('[data-slot="table-body"]', $owner->email)
        ->assertQueryStringMissing('search')
        ->assertQueryStringHas('role', 'owner')
        ->assertQueryStringHas('trashed', 'with')
        ->wait(3.5)
        ->assertNoJavaScriptErrors();

    expect($page->script('window.searchRecovery.starts'))->toBe(2)
        ->and($page->script('window.searchRecovery.cancelledSearches'))->toBe(1)
        ->and($page->script('window.searchRecovery.active.size'))->toBe(0);
});

function observeSearchRecoveryRequests(AwaitableWebpage $page, string $action): void
{
    $action = json_encode($action, JSON_THROW_ON_ERROR);

    $page->script(<<<JS
        window.searchRecovery = { starts: 0, active: new Set(), maxActive: 0, cancelledSearches: 0, actionDisabled: false };
        document.addEventListener('inertia:start', event => {
            const visit = event.detail.visit;
            if (visit.url.pathname !== '/users') return;
            const observed = window.searchRecovery;
            observed.starts++;
            observed.active.add(visit.id);
            observed.maxActive = Math.max(observed.maxActive, observed.active.size);
            if (observed.starts === 1 && visit.url.searchParams.has('search')) {
                setTimeout(() => {
                    const control = [...document.querySelectorAll('button, a')].find(control => control.textContent.trim() === {$action});
                    observed.actionDisabled = control.matches('[aria-disabled="true"], [disabled], [data-disabled]');
                    control.click();
                }, 100);
            }
        });
        document.addEventListener('inertia:finish', event => {
            const visit = event.detail.visit;
            if (visit.url.pathname !== '/users') return;
            const observed = window.searchRecovery;
            observed.active.delete(visit.id);
            if (visit.cancelled && visit.url.searchParams.has('search')) observed.cancelledSearches++;
        });
        JS);
}
