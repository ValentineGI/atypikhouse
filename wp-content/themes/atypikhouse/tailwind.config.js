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
    plugins: [],
};