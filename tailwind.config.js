import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                theme: {
                    'bg-primary': 'var(--bg-primary)',
                    'bg-secondary': 'var(--bg-secondary)',
                    'bg-tertiary': 'var(--bg-tertiary)',
                    'bg-card': 'var(--bg-card)',
                    'bg-hover': 'var(--bg-hover)',
                    'bg-input': 'var(--bg-input)',
                    'text-primary': 'var(--text-primary)',
                    'text-secondary': 'var(--text-secondary)',
                    'text-muted': 'var(--text-muted)',
                    'text-inverse': 'var(--text-inverse)',
                    border: 'var(--border)',
                    'border-light': 'var(--border-light)',
                    accent: 'var(--accent)',
                    'accent-hover': 'var(--accent-hover)',
                    'accent-light': 'var(--accent-light)',
                    success: 'var(--success)',
                    warning: 'var(--warning)',
                    error: 'var(--error)',
                },
            },
        },
    },

    plugins: [forms],
};
