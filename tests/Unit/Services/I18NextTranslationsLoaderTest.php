<?php

declare(strict_types=1);

use App\Services\I18NextTranslationsLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Translation\FileLoader;

uses(Tests\TestCase::class);

it('converts nested Laravel translations to flat i18next keys without empty groups', function () {
    $files = Mockery::mock(Filesystem::class);
    $files->shouldReceive('exists')->with('/translations/en')->andReturnTrue();
    $files->shouldReceive('files')->with('/translations/en')->andReturn([new SplFileInfo('/translations/en/validation.php')]);

    $loader = Mockery::mock(FileLoader::class);
    $loader->shouldReceive('load')->with('en', '*', '*')->andReturn([
        'Welcome :name' => 'Hello :name',
        'Contact' => 'Contact|Contacts',
    ]);
    $loader->shouldReceive('load')->with('en', 'validation')->andReturn([
        'nested' => ['required' => 'The :attribute field is required.'],
        'empty' => [],
    ]);

    expect((new I18NextTranslationsLoader($files, $loader, '/translations'))->loadTranslations('en'))->toBe([
        'Welcome {{name}}' => 'Hello {{name}}',
        'Contact_one' => 'Contact',
        'Contact_other' => 'Contacts',
        'validation.nested.required' => 'The {{attribute}} field is required.',
    ]);
});
