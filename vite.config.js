import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
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
    build: {
        rollupOptions: {
            output: {
                // Split heavy vendors into their own long-cached chunks so they
                // download in parallel and don't invalidate on app changes.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('vuetify')) return 'vuetify';
                    if (id.includes('element-plus') || id.includes('@element-plus')) return 'element-plus';
                    if (id.includes('apexcharts')) return 'apexcharts';
                    if (id.includes('@mdi/font')) return 'mdi';
                },
            },
        },
    },
});
