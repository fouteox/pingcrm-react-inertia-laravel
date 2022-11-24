const colors = require("tailwindcss/colors");
const defaultTheme = require("tailwindcss/defaultTheme");

/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.jsx",
    ],
    darkMode: false, // or 'media' or 'class'
    theme: {
        colors: {
            transparent: "transparent",
            current: "currentColor",
            black: colors.black,
            white: colors.white,
            red: colors.red,
            orange: colors.orange,
            yellow: colors.yellow,
            green: colors.green,
            gray: colors.slate,
            indigo: {
                100: "#e6e8ff",
                300: "#b2b7ff",
                400: "#7886d7",
                500: "#6574cd",
                600: "#5661b3",
                800: "#2f365f",
                900: "#191e38",
            },
        },
    },
    variants: {
        extend: {
            fill: ["focus", "group-hover"],
        },
    },
    plugins: [require("@tailwindcss/forms")],
};
