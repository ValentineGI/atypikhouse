const mix = require('laravel-mix');
const tailwindcss = require('tailwindcss');

// mix.autoload({ 'jquery': ['$', 'window.jQuery', 'jQuery'] })

mix
//.js('./src/js/main.js', './dist/js')
// .extract(['jquery'])
//.sass('./sass/style-editor.scss', './css')
.sass('./sass/style.scss', '.')
.options({
	processCssUrls: false,
	postCss: [
		tailwindcss('./tailwind.config.js'),
	],
})
.browserSync({
	proxy: 'local.atypikhouse.fr',
	files: [
		// "./dist/css/*.css",
		"./**/*.js",
		"./*.css",
		"./sass/**/*.scss",
		"./**/*.php",
	]
})