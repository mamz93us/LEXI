import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                arabic: ['Cairo', 'Tajawal', 'sans-serif'],
            },
            colors: {
                lexa: {
                    50:  '#f4f7fb',
                    100: '#e7eef5',
                    200: '#cbdbea',
                    300: '#9dbed7',
                    400: '#699bc1',
                    500: '#4880aa',
                    600: '#36668e',
                    700: '#2d5274',
                    800: '#284661',
                    900: '#253c52',
                },
            },
        },
    },

    plugins: [forms],
};
