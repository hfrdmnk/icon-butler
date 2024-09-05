import forms from '@tailwindcss/forms';
import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './vendor/robsontenorio/mary/src/View/Components/**/*.php',
    ],

    theme: {
        container: {
            center: true,
            padding: '1rem',
        },
        extend: {
            fontFamily: {
                sans: [...defaultTheme.fontFamily.sans],
            },
            colors: {
                'base-content-muted': 'oklch(var(--base-content-muted) / <alpha-value>)',
            },
            borderWidth: {
                3: '3px',
                5: '5px',
                6: '6px',
            },
        },
    },

    daisyui: {
        themes: [
            {
                lofi: {
                    ...require('daisyui/src/theming/themes')['lofi'],
                    '--base-content-muted': '71.37% 0.019 261.32',
                },
            },
            {
                black: {
                    ...require('daisyui/src/theming/themes')['black'],
                    '--base-content-muted': '55.1% 0.023 264.36',
                },
            },
        ],
        darkTheme: 'black',
    },

    plugins: [forms, require('daisyui')],
};
