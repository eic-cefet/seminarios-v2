import { defineConfig } from 'vitest/config';
import path from 'path';

export default defineConfig({
    resolve: {
        alias: {
            '@': path.resolve(__dirname, './resources/js'),
            '@admin': path.resolve(__dirname, './resources/js/admin'),
            '@system': path.resolve(__dirname, './resources/js/system'),
            '@shared': path.resolve(__dirname, './resources/js/shared'),
        },
    },
    test: {
        environment: 'jsdom',
        globals: true,
        setupFiles: ['./resources/js/test/setup.ts'],
        include: ['resources/js/**/*.test.{ts,tsx}'],
        css: false,
        coverage: {
            provider: 'v8',
            reporter: ['text', 'json'],
            include: ['resources/js/**/*.{ts,tsx}'],
            exclude: [
                'resources/js/**/*.test.{ts,tsx}',
                'resources/js/**/*.d.ts',
                'resources/js/test/**',
                'resources/js/admin/app.tsx',
                'resources/js/system/app.tsx',
                'resources/js/shared/types/index.ts',
                'resources/js/admin/pages/seminars/index.tsx',
                'resources/js/admin/pages/workshops/index.tsx',
            ],
            thresholds: {
                statements: 90,
                branches: 90,
                functions: 90,
                lines: 90,
            },
        },
    },
});
