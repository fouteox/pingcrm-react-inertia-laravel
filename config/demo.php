<?php

declare(strict_types=1);

return [
    'reset' => [
        'lock_seconds' => (int) env('DEMO_RESET_LOCK_SECONDS', 600),
        'database_lock_timeout_seconds' => (int) env('DEMO_RESET_DB_LOCK_TIMEOUT_SECONDS', 10),
        'database_transaction_timeout_seconds' => (int) env('DEMO_RESET_DB_TRANSACTION_TIMEOUT_SECONDS', 120),
        'transaction_attempts' => (int) env('DEMO_RESET_TRANSACTION_ATTEMPTS', 3),
    ],
];
