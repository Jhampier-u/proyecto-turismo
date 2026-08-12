import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            // Los 1056 usos de gray-* de las vistas siguen escritos igual y
            // pasan todos a la vez al tinte azulado de slate. Se hace aquí y
            // no reescribiéndolos porque el único riesgo real era que
            // convivieran los dos grises -gray es neutro, slate tira a azul, y
            // mezclados se nota-; migrados juntos, no hay mezcla posible.
            //
            // La regla que se sigue de esto: TODO el código escribe gray-*,
            // también el nuevo. Este fichero es el único sitio que decide qué
            // significa gris.
            colors: {
                gray: colors.slate,
            },
        },
    },

    plugins: [forms],
};
