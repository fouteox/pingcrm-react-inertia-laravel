<?php

declare(strict_types=1);

use App\Exceptions\SearchIndexUnavailable;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Route::middleware('web')->get('/testing/search-unavailable', function () {
        throw new SearchIndexUnavailable;
    });
});

it('renders an initial unavailable page with its retry status and preserves the query filters', function () {
    $this->actingAs(User::factory()->create(['owner' => true]));

    $this->get('https://search.example.test/testing/search-unavailable?page=2&role=owner&search=0&trashed=with')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '3')
        ->assertInertia(fn (Assert $assert) => $assert
            ->component('search-unavailable')
            ->where('retryUrl', 'https://search.example.test/testing/search-unavailable?page=2&role=owner&search=0&trashed=with')
            ->where('listUrl', 'https://search.example.test/testing/search-unavailable?role=owner&trashed=with')
            ->missing('users')
            ->missing('contacts')
            ->missing('organizations')
        );
});

it('returns a valid Inertia error response during client navigation', function () {
    $this->actingAs(User::factory()->create(['owner' => true]));
    $version = $this->get('/')->inertiaPage()['version'];

    $this->get('https://search.example.test/testing/search-unavailable?page=82&role=owner&search=0', [
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $version,
    ])
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '3')
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'search-unavailable')
        ->assertJsonPath('props.retryUrl', 'https://search.example.test/testing/search-unavailable?page=82&role=owner&search=0')
        ->assertJsonPath('props.listUrl', 'https://search.example.test/testing/search-unavailable?role=owner')
        ->assertJsonMissingPath('props.users');
});

it('keeps ordinary JSON failures as HTTP errors instead of rendering an Inertia page', function () {
    $this->getJson('/testing/search-unavailable?search=Acme')
        ->assertServiceUnavailable()
        ->assertHeader('Retry-After', '3')
        ->assertHeaderMissing('X-Inertia')
        ->assertJsonPath('message', 'Search is temporarily unavailable.');
});
