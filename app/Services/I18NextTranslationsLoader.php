<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;

final class I18NextTranslationsLoader
{
    /**
     * Les fichiers de langue ne changent pas pendant la vie du processus : le
     * service est un singleton, donc un worker Octane ne les convertit qu'une
     * fois par locale au lieu de les relire à chaque chargement complet.
     *
     * @var array<string, array<string, string>>
     */
    private array $translationsByLocale = [];

    public function __construct(
        private readonly Filesystem $fs,
        private readonly FileLoader $loader,
        private readonly string $langPath,
    ) {}

    /** @return array<string, string> */
    public function loadTranslations(string $locale): array
    {
        $fallbackLocale = config()->string('app.fallback_locale', 'en');
        $useLocale = $this->localeExists($locale) ? $locale : $fallbackLocale;

        if (! $this->localeExists($useLocale)) {
            return [];
        }

        return $this->translationsByLocale[$useLocale] ??= $this->convert($useLocale);
    }

    /**
     * Le client ne lit que le catalogue JSON : aucune clé de groupe PHP
     * (validation, passwords, http-statuses…) n'est appelée depuis
     * resources/js, ces messages étant traduits côté serveur. Les embarquer
     * doublait le poids des traductions de chaque page complète.
     *
     * @return array<string, string>
     */
    private function convert(string $useLocale): array
    {
        return $this->prepare($this->loader->load($useLocale, '*', '*'));
    }

    private function localeExists(string $locale): bool
    {
        return $this->fs->exists($this->langPath.'/'.$locale);
    }

    /**
     * @param  array<string|int, mixed>  $translations
     * @return array<string, string>
     */
    private function prepare(array $translations): array
    {
        $i18nTranslations = [];

        foreach (Arr::dot($translations) as $laravelKey => $laravelValue) {
            if (! is_string($laravelValue)) {
                continue;
            }

            $i18nKey = Str::of((string) $laravelKey)->replaceMatches('/:(\w+)/', '{{$1}}')->toString();
            $value = Str::of($laravelValue)->replaceMatches('/:(\w+)/', '{{$1}}')->toString();

            if (Str::contains($value, '|')) {
                [$one, $other] = explode('|', $value);
                $i18nTranslations[$i18nKey.'_one'] = $one;
                $i18nTranslations[$i18nKey.'_other'] = $other;
            } else {
                $i18nTranslations[$i18nKey] = $value;
            }
        }

        return $i18nTranslations;
    }
}
