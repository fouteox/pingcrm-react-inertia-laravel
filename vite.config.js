import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import { defineConfig } from 'vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig(() => {
    const config = {
        plugins: [
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
        resolve: {
            alias: {
                'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
            },
        },
    };

    if (process.env.DDEV_PRIMARY_URL) {
        const port = 5173;
        config.server = {
            host: '0.0.0.0',
            port: port,
            strictPort: true,
            origin: `${process.env.DDEV_PRIMARY_URL}:${port}`,
            cors: {
                origin: process.env.DDEV_PRIMARY_URL,
            },
        };
    }

    return config;
});
