<?php

declare(strict_types=1);

use App\Http\Middleware\HandleAppearanceMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(AppServiceProvider::HOME);
        $middleware->encryptCookies(except: ['appearance']);

        $middleware->web(append: [
            HandleAppearanceMiddleware::class,
            App\Http\Middleware\HandleInertiaRequests::class,
            Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ], prepend: [
            SetLocaleMiddleware::class,
        ]);

        $middleware->trustProxies(at: [
            '10.0.0.0/8',      // Docker Swarm overlay networks
            '172.16.0.0/12',   // Docker bridge networks
            '192.168.0.0/16',  // Private networks
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->respond(function (Response $response, Throwable $exception, Request $request) {
            if (! app()->environment(['local', 'testing']) && in_array($response->getStatusCode(), [500, 503, 404, 403])) {
                return Inertia::render('error', ['status' => $response->getStatusCode()])
                    ->toResponse($request)
                    ->setStatusCode($response->getStatusCode());
            }
            if ($response->getStatusCode() === 419) {
                return back()->with([
                    'message' => __('The page expired, please try again.'),
                ]);
            }
            if ($response->getStatusCode() === 429) {
                return back()->with([
                    'error' => __('Sorry, you are making too many requests to our servers.'),
                ]);
            }

            return $response;
        });
    })->create();
