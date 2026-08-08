import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        // Irritacion::TRAMOS guarda clases completas de Tailwind (el color de
        // cada clasificación) para que la leyenda del formulario y la tabla
        // de resultados no las dupliquen cada una por su lado. Sin esta ruta
        // en el scan, el purgado nunca vería esas clases y las eliminaría del
        // CSS final aunque el código PHP las siga usando.
        './app/Matrices/*.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
