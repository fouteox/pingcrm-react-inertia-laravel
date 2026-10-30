import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import { svelte } from '@sveltejs/vite-plugin-svelte';
import tailwindcss from '@tailwindcss/vite';
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
        plugins: ['unicorn', 'typescript', 'oxc'],
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        svelte(),
        tailwindcss(),
        wayfinder(),
    ],
    ssr: {
        noExternal: ['@inertiajs/server'],
    },
});
