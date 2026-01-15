import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/js/admin/app.tsx',
                'resources/js/system/app.tsx',
                'resources/css/app.css',
            ],
            refresh: true,
        }),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@admin': path.resolve(__dirname, './resources/js/admin'),
            '@system': path.resolve(__dirname, './resources/js/system'),
            '@shared': path.resolve(__dirname, './resources/js/shared'),
        },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
