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

it('keeps the server-only translation groups out of the client catalogue', function (string $locale) {
    $groups = array_map(
        fn (string $file): string => basename($file, '.php'),
        glob(lang_path($locale.'/*.php')) ?: [],
    );

    expect($groups)->toContain('validation');

    $keys = array_keys($this->get("/locales/{$locale}/translation.json")->assertSuccessful()->json());

    foreach ($groups as $group) {
        expect(array_filter($keys, fn (string $key): bool => str_starts_with($key, $group.'.')))->toBeEmpty();
    }
})->with(['en', 'fr']);

it('never asks the client catalogue for a server-only translation group', function () {
    $groups = array_map(
        fn (string $file): string => preg_quote(basename($file, '.php'), '/'),
        glob(lang_path('en/*.php')) ?: [],
    );
    $pattern = '/[\'"`]('.implode('|', $groups).')\.[a-z0-9_.-]+[\'"`]/i';
    $offenders = [];

    foreach (Illuminate\Support\Facades\File::allFiles(resource_path('js')) as $file) {
        if (str_contains($file->getPathname(), DIRECTORY_SEPARATOR.'wayfinder'.DIRECTORY_SEPARATOR)) {
            continue;
        }

        if (preg_match($pattern, $file->getContents(), $match) === 1) {
            $offenders[] = $file->getRelativePathname().': '.$match[0];
        }
    }

    expect($offenders)->toBeEmpty();
});
