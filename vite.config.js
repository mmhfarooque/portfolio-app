import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig(({ isSsrBuild }) => ({
    plugins: [
        laravel({
            input: ['resources/js/app.js', 'resources/css/app.css'],
            ssr: 'resources/js/ssr.js',
            refresh: true,
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
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            // manualChunks conflicts with the SSR bundle's inlined dynamic
            // imports, so only split vendor chunks for the client build.
            output: isSsrBuild
                ? {}
                : {
                      manualChunks: {
                          'vendor-vue': ['vue', '@inertiajs/vue3'],
                          'vendor-ziggy': ['ziggy-js'],
                      },
                  },
        },
    },
}));
