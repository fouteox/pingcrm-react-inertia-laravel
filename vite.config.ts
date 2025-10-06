import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { defineConfig, type UserConfig } from 'vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';

export default defineConfig(() => {
    const config: UserConfig = {
        plugins: [
            laravel({
                input: 'resources/js/app.tsx',
                ssr: 'resources/js/ssr.tsx',
                refresh: true
            }),
            react(),
            tailwindcss(),
            wayfinder(),
        ],
        esbuild: {
            jsx: 'automatic',
        }
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
