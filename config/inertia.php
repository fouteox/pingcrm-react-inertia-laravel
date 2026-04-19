<?php

declare(strict_types=1);

return [

    'ssr' => [

        'enabled' => (bool) env('INERTIA_SSR_ENABLED', true),

        'url' => env('INERTIA_SSR_URL', 'http://127.0.0.1:13714'),

        'ensure_bundle_exists' => (bool) env('INERTIA_SSR_ENSURE_BUNDLE_EXISTS', true),

    ],

    'pages' => [

        'paths' => [

            resource_path('js/pages'),

        ],

        'extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

    'testing' => [

        'ensure_pages_exist' => true,

    ],

    'history' => [

        'encrypt' => (bool) env('INERTIA_ENCRYPT_HISTORY', false),

    ],

];
