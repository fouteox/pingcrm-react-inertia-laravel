<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Resources\UserResource;
use App\Services\I18NextTranslationsLoader;
use Illuminate\Http\Request;
use Inertia\Middleware;

final class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    public function __construct(private readonly I18NextTranslationsLoader $translationsLoader) {}

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            ...parent::share($request),
            'auth' => fn () => [
                'user' => $request->user()
                    ? new UserResource($request->user()->loadMissing('account'))
                    : null,
            ],
            'locale' => fn () => $locale,
            'translations' => ! $request->inertia() ? [
                $locale => [
                    'translation' => $this->translationsLoader->loadTranslations($locale),
                ],
            ] : null,
            'flash' => fn () => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }
}
