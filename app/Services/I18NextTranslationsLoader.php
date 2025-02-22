<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Illuminate\Translation\FileLoader;
use RecursiveArrayIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final readonly class I18NextTranslationsLoader
{
    public function __construct(
        private Filesystem $fs,
        private FileLoader $loader,
        private string $langPath,
    ) {}

    public function loadTranslations(string $locale): array
    {
        $fallbackLocale = config('app.fallback_locale', 'en');
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

    private function prepare(array $translations): array
    {
        $i18nTranslations = [];
        $keys = array_keys($translations);

        for ($i = 0; $i < count($keys); $i++) {
            $laravelKey = $keys[$i];
            $i18nKey = preg_replace("/:(\w+)/", '{{$1}}', is_int($laravelKey) ? (string) $laravelKey : $laravelKey);
            $laravelValue = $translations[$laravelKey];

            if (is_array($laravelValue)) {
                $i18nTranslations[$i18nKey] = $this->prepare($laravelValue);
            } else {
                $translationWithReplacedVariableSyntax = preg_replace("/:(\w+)/", '{{$1}}', $laravelValue);
                // handle pluralisation
                if (Str::contains($translationWithReplacedVariableSyntax, '|')) {
                    [$one, $other] = explode('|', $translationWithReplacedVariableSyntax);
                    $i18nTranslations[$i18nKey.'_one'] = $one;
                    $i18nTranslations[$i18nKey.'_other'] = $other;
                } else {
                    $i18nTranslations[$i18nKey] = $translationWithReplacedVariableSyntax;
                }
            }
        }

        return $this->flatten($i18nTranslations);
    }

    private function flatten($translations): array
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($translations));
        $flattened = [];

        foreach ($iterator as $leafValue) {
            $keys = [];

            foreach (range(0, $iterator->getDepth()) as $depth) {
                $keys[] = $iterator->getSubIterator($depth)->key();
            }

            $flattened[implode('.', $keys)] = $leafValue;
        }

        return $flattened;
    }
}
