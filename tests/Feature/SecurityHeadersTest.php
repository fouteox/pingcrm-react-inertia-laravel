<?php

declare(strict_types=1);

it('sends the hardening headers on every response', function (string $path) {
    $this->get($path)
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
})->with([
    'a page' => ['/login'],
    'a redirect' => ['/'],
    'the health route' => ['/up'],
    'a missing page' => ['/missing-page'],
]);

it('only announces HSTS over HTTPS', function () {
    $this->get('http://localhost/login')->assertHeaderMissing('Strict-Transport-Security');

    $this->get('https://localhost/login')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
});
