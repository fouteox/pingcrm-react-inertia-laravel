import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import fadogen from '@fadogen/vite-plugin'
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    plugins: [
        fadogen(),
        laravel({
            input: 'resources/js/app.tsx',
            ssr: 'resources/js/ssr.tsx',
            refresh: true,
        }),
        react(),
        tailwindcss(),
        wayfinder(),
    ],
    esbuild: {
        jsx: 'automatic',
    }
});
