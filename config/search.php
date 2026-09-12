<?php

declare(strict_types=1);

return [
    'synchronous' => env('SEARCH_SYNCHRONOUS', true),
    'lock_seconds' => 150,
    'retry_delay' => 5,
];
