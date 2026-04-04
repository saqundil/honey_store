import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.vue',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Open Sans', ...defaultTheme.fontFamily.sans],
                condensed: ['Open Sans Condensed', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                honey: {
                    dark:    '#131313',
                    grey:    '#383838',
                    muted:   '#5B5858',
                    nav:     '#3C3C3C',
                    orange:  '#C74817',
                    gold:    '#D3A863',
                    cream:   '#F7F3F0',
                    card:    '#FCFAF9',
                },
            },
            letterSpacing: {
                widest2: '0.15em',
            },
        },
    },
    plugins: [],
};
