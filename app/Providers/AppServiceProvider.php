<?php

declare(strict_types=1);

namespace App\Providers;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Typesense\Client;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * This is used by Laravel authentication to redirect users after login.
     *
     * @var string
     */
    public const string HOME = '/';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        JsonResource::withoutWrapping();

        $this->app->bind(Client::class, fn () => new Client([
            ...Config::array('scout.typesense.client-settings'),
            'client' => new HttpClient([
                'connect_timeout' => Config::get('scout.typesense.client-settings.connection_timeout_seconds'),
                'timeout' => Config::get('scout.typesense.client-settings.request_timeout_seconds'),
            ]),
        ]));
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);
    }
}
