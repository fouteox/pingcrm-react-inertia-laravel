<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\I18NextTranslationsLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use Illuminate\Translation\FileLoader;

final class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(I18NextTranslationsLoader::class, function ($app) {
            return new I18NextTranslationsLoader(
                new Filesystem,
                new FileLoader($app['files'], $app->langPath()),
                $app->langPath()
            );
        });
    }

    public function boot(): void {}
}
