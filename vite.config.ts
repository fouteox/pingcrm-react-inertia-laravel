import { fileURLToPath } from 'node:url';
import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite-plus';

// Les tests unitaires importent directement les sources : ils n'ont besoin ni
// du manifest Laravel, ni des routes Wayfinder générées par artisan, ni des
// autres plugins de build. laravel-vite-plugin refuse en plus de démarrer
// quand CI=true.
const isTestRun = process.env.VITEST !== undefined;

export default defineConfig(({ isSsrBuild }) => ({
    fmt: {
        printWidth: 150,
        tabWidth: 4,
        useTabs: false,
        semi: true,
        singleQuote: true,
        ignorePatterns: [
            '/*',
            '!/resources/',
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
    plugins: isTestRun
        ? []
        : [
              laravel({
                  input: ['resources/css/app.css', 'resources/js/app.tsx'],
                  refresh: true,
              }),
              inertia(),
              react(),
              tailwindcss(),
              wayfinder(),
          ],
    resolve: {
        // Repris de laravel-vite-plugin, absent du run de tests, et miroir du
        // `paths` de tsconfig.json.
        alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
    },
    // Build SSR autonome : l'image de production ne livre pas node_modules, le
    // bundle embarque donc toutes ses dépendances. `vp dev` garde les siennes
    // externes, son module runner n'évaluant pas leur CommonJS.
    ssr: {
        noExternal: isSsrBuild ? true : ['@inertiajs/server'],
    },
    test: {
        include: ['resources/js/**/*.test.{ts,tsx}'],
        environment: 'node',
    },
}));
