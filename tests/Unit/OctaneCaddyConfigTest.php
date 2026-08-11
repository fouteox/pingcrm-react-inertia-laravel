<?php

declare(strict_types=1);

use Illuminate\Support\Env;
use Tests\TestCase;

uses(TestCase::class);

it('passes explicit Caddy drain snippets to the Octane FrankenPHP process', function () {
    $repository = Env::getRepository();
    $variables = [
        'OCTANE_CADDY_GLOBAL_OPTIONS' => "metrics\ngrace_period 100s",
        'OCTANE_CADDY_SERVER_EXTRA_DIRECTIVES' => <<<'CADDY'
            @drainMetrics {
                path /.well-known/frankenphp-drain/metrics
            }
            metrics @drainMetrics
            CADDY,
    ];
    $originalValues = collect(array_keys($variables))
        ->mapWithKeys(fn (string $key): array => [$key => $repository->get($key)]);

    try {
        foreach ($variables as $key => $value) {
            $repository->set($key, $value);
        }

        $octane = require config_path('octane.php');

        expect($octane)->toHaveKey('caddy.env')
            ->and($octane['caddy']['env'])->toBe([
                'CADDY_GLOBAL_OPTIONS' => $variables['OCTANE_CADDY_GLOBAL_OPTIONS'],
                'CADDY_SERVER_EXTRA_DIRECTIVES' => $variables['OCTANE_CADDY_SERVER_EXTRA_DIRECTIVES'],
            ]);
    } finally {
        foreach ($originalValues as $key => $value) {
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
        }
    }
});

it('leaves the native Octane Caddy defaults untouched without explicit overrides', function () {
    $repository = Env::getRepository();
    $keys = ['OCTANE_CADDY_GLOBAL_OPTIONS', 'OCTANE_CADDY_SERVER_EXTRA_DIRECTIVES'];
    $originalValues = collect($keys)
        ->mapWithKeys(fn (string $key): array => [$key => $repository->get($key)]);

    try {
        foreach ($keys as $key) {
            $repository->clear($key);
        }

        $octane = require config_path('octane.php');

        expect($octane)->toHaveKey('caddy.env')
            ->and($octane['caddy']['env'])->toBe([]);
    } finally {
        foreach ($originalValues as $key => $value) {
            $value === null ? $repository->clear($key) : $repository->set($key, $value);
        }
    }
});
