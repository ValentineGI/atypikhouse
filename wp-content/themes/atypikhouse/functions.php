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
	}
endif;
add_action( 'after_setup_theme', 'atypikhouse_setup' );

/**
 * Enqueue scripts and styles.
 */
function atypikhouse_scripts() {

	wp_enqueue_style( 'atypikhouse-style', get_stylesheet_uri(), array(), _S_VERSION );
	wp_style_add_data( 'atypikhouse-style', 'rtl', 'replace' );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'atypikhouse_scripts' );