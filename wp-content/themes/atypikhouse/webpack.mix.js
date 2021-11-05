const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');
// const tailwindcss = require('@tailwindcss/jit');
const autoprefixer = require('autoprefixer');

// mix.autoload({ 'jquery': ['$', 'window.jQuery', 'jQuery'] })

mix
//.js('./src/js/main.js', './dist/js')
// .extract(['jquery'])
//.sass('./sass/style-editor.scss', './css')
.sass('./scss/style.scss', '.')
.options({
	processCssUrls: false,
	postCss: [
		tailwindcss('./tailwind.config.js'),
		autoprefixer({
			grid: true
		})
	],
})
.browserSync({
	proxy: 'local.atypikhouse.fr',
	files: [
		// "./dist/css/*.css",
		//"./dist/js/*.js",
		"./*.css",
		"./**/*.php",
	]
})