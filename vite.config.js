import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import basicSsl from '@vitejs/plugin-basic-ssl'

export default defineConfig({
    server: {
        https: true,
        host: '0.0.0.0',
        hmr: {
            host: 'localhost',
        }
    },
    plugins: [
        basicSsl({
            /** name of certification */
            name: 'footeox',
            /** custom trust domains */
            domains: ['localhost'],
            /** custom certification directory */
            certDir: './cert'
        }),
        laravel({
            input: 'resources/js/app.jsx',
            refresh: true,
        }),
        react(),
    ],
});
