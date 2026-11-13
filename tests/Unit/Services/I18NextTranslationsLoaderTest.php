<?php

declare(strict_types=1);

use App\Services\I18NextTranslationsLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

uses(Tests\TestCase::class);

it('converts the JSON catalogue to flat i18next keys and leaves the PHP groups on the server', function () {
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('exists')->with('/translations/en')->andReturnTrue();

    $loader = Mockery::mock(FileLoader::class);
    $loader->shouldReceive('load')->with('en', '*', '*')->andReturn([
        'Welcome :name' => 'Hello :name',
        'Contact' => 'Contact|Contacts',
    ]);
    $loader->shouldNotReceive('load')->with('en', 'validation');

    expect((new I18NextTranslationsLoader($files, $loader, '/translations'))->loadTranslations('en'))->toBe([
        'Welcome {{name}}' => 'Hello {{name}}',
        'Contact_one' => 'Contact',
        'Contact_other' => 'Contacts',
    ]);
});

it('converts each locale only once for the lifetime of the service', function () {
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('exists')->with('/translations/en')->andReturnTrue();
    $files->shouldReceive('exists')->with('/translations/fr')->andReturnTrue();

    $loader = Mockery::mock(FileLoader::class);
    $loader->shouldReceive('load')->with('en', '*', '*')->once()->andReturn(['Contact' => 'Contact']);
    $loader->shouldReceive('load')->with('fr', '*', '*')->once()->andReturn(['Contact' => 'Contact FR']);

    $translations = new I18NextTranslationsLoader($files, $loader, '/translations');

    expect($translations->loadTranslations('en'))->toBe(['Contact' => 'Contact'])
        ->and($translations->loadTranslations('en'))->toBe(['Contact' => 'Contact'])
        ->and($translations->loadTranslations('fr'))->toBe(['Contact' => 'Contact FR'])
        ->and($translations->loadTranslations('fr'))->toBe(['Contact' => 'Contact FR']);
});

it('memoises the fallback locale separately from a missing one', function () {
    config()->set('app.fallback_locale', 'en');

    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('exists')->with('/translations/zz')->andReturnFalse();
    $files->shouldReceive('exists')->with('/translations/en')->andReturnTrue();

    $loader = Mockery::mock(FileLoader::class);
    $loader->shouldReceive('load')->with('en', '*', '*')->once()->andReturn(['Contact' => 'Contact']);

    $translations = new I18NextTranslationsLoader($files, $loader, '/translations');

    expect($translations->loadTranslations('zz'))->toBe(['Contact' => 'Contact'])
        ->and($translations->loadTranslations('en'))->toBe(['Contact' => 'Contact']);
});
