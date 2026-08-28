<?php

declare(strict_types=1);

test('php runtime updates remain atomic across every managed surface', function () {
    $repositoryRoot = dirname(__DIR__, 2);
    $configuration = json_decode(
        file_get_contents($repositoryRoot.'/renovate.json'),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $runtimeRule = collect($configuration['packageRules'])
        ->firstWhere('groupSlug', 'php-runtime');

    expect($runtimeRule)->not->toBeNull()
        ->and($runtimeRule['matchPackageNames'])->toEqualCanonicalizing([
            'serversideup/php',
            'containerbase/php-prebuild',
        ])
        ->and($runtimeRule['minimumGroupSize'])->toBe(4)
        ->and(mb_substr_count(file_get_contents($repositoryRoot.'/Dockerfile'), 'serversideup/php:'))->toBe(1)
        ->and(mb_substr_count(file_get_contents($repositoryRoot.'/.github/workflows/ci.yml'), 'image: serversideup/php:'))->toBe(1)
        ->and(data_get(json_decode(file_get_contents($repositoryRoot.'/composer.json'), true, 512, JSON_THROW_ON_ERROR), 'require.php'))->not->toBeNull()
        ->and(data_get(json_decode(file_get_contents($repositoryRoot.'/composer.json'), true, 512, JSON_THROW_ON_ERROR), 'config.platform.php'))->not->toBeNull();
});
