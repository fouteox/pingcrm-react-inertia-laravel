<?php

declare(strict_types=1);

return [
    'synchronous' => env('SEARCH_SYNCHRONOUS', true),
    'lock_seconds' => 150,
    'retry_delay' => 5,
    'projection_batch_size' => 50,
    'projection_seconds' => 15,
];
