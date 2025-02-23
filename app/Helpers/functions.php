<?php

declare(strict_types=1);

use App\Helpers\TranslationHelper;

if (! function_exists('translate_with_gender')) {
    function translate_with_gender(string $action, string $item): string
    {
        return TranslationHelper::translate($action, $item);
    }
}
