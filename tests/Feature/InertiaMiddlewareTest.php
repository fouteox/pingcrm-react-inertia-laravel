<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

it('shares the sidebar state set by the browser', function (?string $cookie, bool $expected) {
    if ($cookie !== null) {
        $this->withUnencryptedCookie('sidebar_state', $cookie);
    }

    $this->actingAs(User::factory()->create())->get('/')
        ->assertInertia(fn (Assert $assert) => $assert
            ->where('sidebarOpen', $expected)
            ->missing('flash')
        );
})->with([
    'open by default' => [null, true],
    'closed' => ['false', false],
    'open' => ['true', true],
]);

it('flashes a visible error when the page expires', function () {
    Route::middleware('web')->get('/testing/expired', function () {
        throw new TokenMismatchException;
    });

    $this->from('/login')->get('/testing/expired')
        ->assertRedirect('/login')
        ->assertInertiaFlash('error', __('The page expired, please try again.'));

    $this->get('/login')->assertInertia(fn (Assert $assert) => $assert
        ->hasFlash('error', __('The page expired, please try again.'))
        ->missing('flash')
    );

    $this->get('/login')->assertInertia(fn (Assert $assert) => $assert->missingFlash('error'));
});

it('flashes a visible error when requests are throttled', function () {
    Route::middleware('web')->get('/testing/throttled', fn () => abort(429));

    $this->from('/login')->get('/testing/throttled')
        ->assertRedirect('/login')
        ->assertInertiaFlash('error', __('Sorry, you are making too many requests to our servers.'));
});
