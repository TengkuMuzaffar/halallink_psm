import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/main.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: "null",
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    // server: {
    //     // For local development with Cloudflare Zero Trust
    //     hmr: {
    //         host: 'halallinkpsm.com',
    //         protocol: 'wss', // WebSocket Secure for Cloudflare
    //     },
    // },
});
