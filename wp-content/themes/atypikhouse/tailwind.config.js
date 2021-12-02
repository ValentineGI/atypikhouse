const fs = require("fs");

const themePhpFiles = [];
fs.readdirSync(".").forEach((file) => {
    if (file.includes(".php")) {
        themePhpFiles.push(file);
    }
});

const purgeArray = [
    "./template-parts/**/*.php",
    ...themePhpFiles,
];

module.exports = {
    mode: "jit",
    purge: purgeArray,
    darkMode: false, 
    theme: {
        screens: {
            mobile: {
              max: '639px'
            },
            "md-max": {
              max: '767px'
            },
            "lg-max": {
                max: "1023px",
            },
            xs: '460px',
            sm: '640px',
            md: '768px',
            lg: '1132px',
        },
        fontWeight: {
            normal: 400,
            medium: 500,
            bold: 700
        },
        extend: {
            container: {
                center: true,
                padding: "1rem",
            },
        },
        backgroundColor: theme => ({
            ...theme('colors'),
            'primary': '#AD5949',
            'secondary': '#BA7B43',
        }),
        colors: {
            primary: '#AD5949',
            secondary: '#BA7B43',
            primgrey : '#8C8C8C',
            white: '#ffffff',
            black: '#000000',
            // ...
        },
    },
    variants: {
        extend: {
        },
    },
    plugins: [
        require('@tailwindcss/aspect-ratio'),
        require('@tailwindcss/forms')
    ],
};