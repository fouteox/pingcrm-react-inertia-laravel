<?php

declare(strict_types=1);

it('returns a flat translations map for a known locale', function () {
    $response = $this->get('/locales/en/translation.json')->assertSuccessful();

    $payload = $response->json();

    expect($payload)->toBeArray()->not->toBeEmpty()
        ->and($payload)->toHaveKey('Dashboard');
});

it('falls back gracefully when the locale does not exist', function () {
    config()->set('app.fallback_locale', 'en');

    $this->get('/locales/zz/translation.json')
        ->assertSuccessful()
        ->assertJsonPath('Dashboard', __('Dashboard', [], 'en'));
});
