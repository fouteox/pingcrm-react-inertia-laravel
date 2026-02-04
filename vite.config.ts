import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import fadogen from '@fadogen/vite-plugin'
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig({
    server: {
        host: '0.0.0.0',
        hmr: {
            host: process.env.VITE_DEV_HOST || 'localhost',
            clientPort: 443,
            protocol: 'wss',
        },
        origin: process.env.VITE_DEV_HOST ? `https://${process.env.VITE_DEV_HOST}` : undefined,
        cors: {
            origin: process.env.APP_HOST ? `https://${process.env.APP_HOST}` : true,
        },
        allowedHosts: true,
        watch: {
            ignored: [
                '**/resources/js/actions/**',
                '**/resources/js/routes/**',
                '**/resources/js/wayfinder/**',
            ],
        },
    },
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
    },
    ssr: {
        noExternal: true,
    },
});
