import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],
    theme: {
        extend: {
            colors: {
                'ssbc-green': '#153e35',
                'ssbc-gold':  '#daa900',
                'ssbc-sage':  '#90aba0',
                'ssbc-beige': '#f0e6dc',
                'ssbc-light': '#f4f5f7',
                'ssbc-dark':  '#1a1a2e',
            },
            fontFamily: {
                display: ['"El Messiri"', 'serif'],
                body: ['"Noto Kufi Arabic"', 'sans-serif'],
            },
            maxWidth: {
                '6xl': '72rem',
            },
        },
    },
    plugins: [forms, typography],
};
