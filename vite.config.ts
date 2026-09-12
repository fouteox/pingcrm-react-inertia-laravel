import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite-plus';

export default defineConfig({
    fmt: {
        printWidth: 150,
        tabWidth: 4,
        useTabs: false,
        semi: true,
        singleQuote: true,
        ignorePatterns: [
            '**/*',
            '!resources/**',
            'resources/js/components/ui/**',
            'resources/js/wayfinder/**',
        ],
        overrides: [
            { files: ['**/*.yml'], options: { tabWidth: 2 } },
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn'],
            stylesheet: 'resources/css/app.css',
        },
        sortImports: {
            groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
            newlinesBetween: false,
        },
    },
    lint: {
        ignorePatterns: ['**/*', '!resources/**', 'resources/js/wayfinder/**'],
        plugins: ['unicorn', 'typescript', 'oxc', 'react', 'jsx-a11y'],
        rules: {
            'react/rules-of-hooks': 'error',
            'react/exhaustive-deps': 'error',
            // Composite controls and SVGs need explicit roles without changing their element.
            'jsx-a11y/prefer-tag-over-role': 'off',
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
        }),
        inertia(),
        react(),
        tailwindcss(),
        wayfinder(),
    ],
    ssr: {
        noExternal: ['@inertiajs/server'],
    },
});
