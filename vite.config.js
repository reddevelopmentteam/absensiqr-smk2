import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import collectModuleAssetsPaths from './vite-module-loader.js';

const moduleAssets = await collectModuleAssetsPaths([], 'Modules');

export default defineConfig({
    build: {
        rollupOptions: {
            // Blade dynamically imports scanner.js outside Vite's module graph.
            preserveEntrySignatures: 'strict',
        },
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/generateCard.js',
                'resources/js/generateQR.js',
                'resources/js/thermal/escpos.js',
                'resources/js/thermal/transport-usb.js',
                'resources/js/thermal/transport-bt.js',
                'resources/js/thermal/surat-payload.js',
                'resources/js/thermal/transport-native.js',
                ...moduleAssets,
            ],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        hmr: {
            host: 'localhost',
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
