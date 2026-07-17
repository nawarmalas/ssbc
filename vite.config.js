import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    // Pre-bundling would break the emscripten import.meta.url wasm lookup
    // in the dev server; the production build handles it natively.
    optimizeDeps: {
        exclude: ['@jsquash/webp'],
    },
});
