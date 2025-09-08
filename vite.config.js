import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

// vite.config.js
export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/app.js'], // <-- CSS entry removed
            refresh: true,
        }),
    ],
});
