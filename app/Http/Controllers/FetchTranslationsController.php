<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\I18NextTranslationsLoader;

final class FetchTranslationsController extends Controller
{
    public function __construct(private readonly I18NextTranslationsLoader $translationsLoader) {}

    /**
     * @return array<string, string>
     */
    public function __invoke(string $locale): array
    {
        return $this->translationsLoader->loadTranslations($locale);
    }
}
