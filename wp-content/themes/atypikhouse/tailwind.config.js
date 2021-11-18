const fs = require("fs");

const themePhpFiles = [];
fs.readdirSync(".").forEach((file) => {
    if (file.includes(".php")) {
        themePhpFiles.push(file);
    }
});

const purgeArray = [
    ...themePhpFiles,
];

module.exports = {
    mode: "jit",
    purge: purgeArray,
    darkMode: false, // or 'media' or 'class'
    theme: {
        screens: {
            mobile: {
              max: '639px'
            },
            "md-max": {
              max: '767px'
            },
            xs: '460px',
            sm: '640px',
            md: '768px',
            lg: '1132px',
            // lg: '1024px',
            // container: '1100px',
            // xl: '1280px',
            // '2xl': '1536px',
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