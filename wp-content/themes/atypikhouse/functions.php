<?php
/**
 * atypikhouse functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package atypikhouse
 */

if ( ! defined( '_S_VERSION' ) ) {
	// Replace the version number of the theme on each release.
	define( '_S_VERSION', '1.0.6' );
}

if ( ! function_exists( 'atypikhouse_setup' ) ) :
	/**
	 * Sets up theme defaults and registers support for various WordPress features.
	 *
	 * Note that this function is hooked into the after_setup_theme hook, which
	 * runs before the init hook. The init hook is too late for some features, such
	 * as indicating support for post thumbnails.
	 */
	function atypikhouse_setup() {

		/*
		 * Make theme available for translation.
		 * Translations can be filed in the /languages/ directory.
		 * If you're building a theme based on Fortesens, use a find and replace
		 * to change 'fortesens' to the name of your theme in all the template files.
		 */
		load_theme_textdomain( 'fortesens', get_template_directory() . '/languages' );

		// This theme uses wp_nav_menu() in one location.
		register_nav_menus(
			array(
				'menu-header' => esc_html__( 'Header', 'atypikhouse' ),
			)
		);
		register_nav_menus(
			array(
				'menu-footer' => esc_html__( 'Footer', 'atypikhouse' ),
			)
		);

		// Add default posts and comments RSS feed links to head.
		add_theme_support( 'automatic-feed-links' );

		/*
			* Let WordPress manage the document title.
			* By adding theme support, we declare that this theme does not use a
			* hard-coded <title> tag in the document head, and expect WordPress to
			* provide it for us.
			*/
		add_theme_support( 'title-tag' );

		/**
		 * Add support for core custom logo.
		 *
		 * @link https://codex.wordpress.org/Theme_Logo
		 */
		add_theme_support(
			'custom-logo',
		);

		/*
		 * Enable support for Post Thumbnails on posts and pages.
		 *
		 * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
		 */
		add_theme_support( 'post-thumbnails' );
	}
endif;
add_action( 'after_setup_theme', 'atypikhouse_setup' );

/**
 * Enqueue scripts and styles.
 */
function atypikhouse_scripts() {

	wp_enqueue_style( 'gfont-lato', 'https://fonts.googleapis.com/css2?family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,400;1,700;1,900&display=swap', array() );

	wp_enqueue_style( 'swiper-css', 'https://unpkg.com/swiper/swiper-bundle.min.css', array(), _S_VERSION );

	wp_enqueue_style( 'atypikhouse-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'atypikhouse-style', 'rtl', 'replace' );

	wp_enqueue_script( 'swiper-js', 'https://unpkg.com/swiper@7/swiper-bundle.min.js', array(), _S_VERSION );
    
    // Déclarer le JS
	wp_register_script('atypikhouse-script', get_template_directory_uri() . '/js/script.js', array(), '1.0', true);
	wp_enqueue_script('atypikhouse-script');

}
add_action( 'wp_enqueue_scripts', 'atypikhouse_scripts' );

/**
 * Load acf functions
 */
require get_template_directory() . '/inc/acf.php';