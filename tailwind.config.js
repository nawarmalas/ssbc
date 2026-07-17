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
                // Darker variants: gold/sage only meet WCAG AA contrast on
                // the dark green background — use these on white/beige.
                'ssbc-gold-deep': '#7d6400',
                'ssbc-sage-deep': '#4e6a5e',
                'ssbc-sage':  '#90aba0',
                'ssbc-beige': '#f0e6dc',
                'ssbc-light': '#f4f5f7',
                'ssbc-dark':  '#1a1a2e',
            },
            fontFamily: {
                display: ['"El Messiri"', '"El Messiri Fallback"', 'serif'],
                body: ['"Noto Kufi Arabic"', '"Noto Kufi Arabic Fallback"', 'sans-serif'],
            },
            maxWidth: {
                '6xl': '72rem',
            },
        },
    },
    plugins: [forms, typography],
};
