import { defineConfig } from 'vite';
import laravel, { refreshPaths } from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/vue-app.js', // Vue entry point
            ],
            refresh: [
                ...refreshPaths,
                'app/Http/Livewire/**',
                'resources/js/vue/**', // Watch Vue files for HMR
            ],
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': path.resolve(__dirname, 'resources/js'),
        },
        dedupe: ['vue'], // Prevent duplicate Vue instances
    },
    server: {
        host: '127.0.0.1', // Force IPv4 instead of IPv6
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1', // Force IPv4 for HMR
        },
    },
});
