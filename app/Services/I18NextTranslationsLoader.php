<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use SplFileInfo;

final readonly class I18NextTranslationsLoader
{
    public function __construct(
        private Filesystem $fs,
        private FileLoader $loader,
        private string $langPath,
    ) {}

    /** @return array<string, string> */
    public function loadTranslations(string $locale): array
    {
        $fallbackLocale = config()->string('app.fallback_locale', 'en');
        $useLocale = $this->localeExists($locale) ? $locale : $fallbackLocale;

        if (! $this->localeExists($useLocale)) {
            return [];
        }

        $translations = $this->loader->load($useLocale, '*', '*');
        $groups = array_map(
            fn (SplFileInfo $file) => $file->getBasename('.php'),
            $this->fs->files($this->langPath.'/'.$useLocale),
        );

        foreach ($groups as $group) {
            $nonPrefixedGroupTranslations = $this->loader->load($useLocale, $group);
            $groupTranslations = [];
            foreach ($nonPrefixedGroupTranslations as $key => $translation) {
                $groupTranslations[$group.'.'.$key] = $translation;
            }
            $translations = array_merge($translations, $groupTranslations);
        }

        return $this->prepare($translations);
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
