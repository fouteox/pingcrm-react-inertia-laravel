<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

final class SetLocaleMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->getPreferredLanguage(['fr', 'en']);

        if (! $locale) {
            $locale = config('app.locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
