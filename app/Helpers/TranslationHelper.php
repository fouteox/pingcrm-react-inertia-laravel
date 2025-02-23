<?php

declare(strict_types=1);

namespace App\Helpers;

use Illuminate\Support\Str;

final class TranslationHelper
{
    private const array GENDERS = [
        'fr' => [
            'contact' => 'm',
            'organization' => 'f',
            'document' => 'm',
            'facture' => 'f',
            'message' => 'm',
            'commande' => 'f',
        ],
        'en' => [
            'contact' => 'n',
            'organization' => 'n',
            'document' => 'n',
            'facture' => 'n',
            'message' => 'n',
            'commande' => 'n',
        ],
    ];

    public static function translate(string $action, string $item): string
    {
        $locale = app()->getLocale();
        $itemLower = mb_strtolower($item);

        $translation = __($item);
        $displayItem = explode('|', $translation)[0];

        if ($locale === 'en') {
            return trans($action, ['item' => $displayItem]);
        }

        $gender = self::getGenderForItem($itemLower, $locale);
        return trans("$action.$gender", ['item' => $displayItem]);
    }

    private static function getGenderForItem(string $item, ?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        $baseItem = Str::snake($item);
        $baseItem = str_replace('_', '', $baseItem);

        return self::GENDERS[$locale][$baseItem] ?? 'm';
    }
}
