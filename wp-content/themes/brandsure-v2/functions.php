<?php
/**
 * BrandSure v2 — theme bootstrap.
 *
 * Setup, asset enqueues and includes only. No markup is written in this file,
 * and no copy. Markup lives in components/, copy lives in data/ (controlled
 * pages) or in WordPress (editable pages) — never in both. See
 * docs/CONTENT-ARCHITECTURE.md.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

define( 'BRANDSURE_VERSION', '2.0.0-alpha' );
define( 'BRANDSURE_DIR', get_template_directory() );
define( 'BRANDSURE_URI', get_template_directory_uri() );

/**
 * Theme supports.
 *
 * Deliberately narrow. No custom colours, no font-size presets and no wide
 * alignment: the palette and the type scale come from tokens.css, and letting
 * the editor offer alternatives is how a design system drifts.
 */
function brandsure_setup(): void {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );

	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-font-sizes' );
	add_theme_support( 'disable-custom-gradients' );
	add_theme_support( 'editor-color-palette', array() );
	add_theme_support( 'editor-font-sizes', array() );
	add_theme_support( 'editor-gradient-presets', array() );
	remove_theme_support( 'core-block-patterns' );

	// Navigation is rendered from data/site.json, not from WP menus, so that
	// the header cannot be changed from the admin. Registered anyway so the
	// admin does not offer a broken menu screen.
	register_nav_menus(
		array(
			'primary' => __( 'Primary (rendered from data/site.json — not editable here)', 'brandsure-v2' ),
		)
	);
}
add_action( 'after_setup_theme', 'brandsure_setup' );

/**
 * Front-end assets.
 *
 * Two stylesheets, one script, in this order. tokens.css must load first:
 * everything else depends on its custom properties.
 */
function brandsure_assets(): void {
	// Inter, self-hosted. No third-party font request: it costs a DNS lookup
	// and a connection on a 4G phone, and it hands a visitor list to someone
	// else. Loaded only once the font files are in place — until then the
	// stack in tokens.css falls back to the device's own UI font, which is
	// close enough that nothing looks broken. See assets/fonts/README.md.
	if ( file_exists( BRANDSURE_DIR . '/assets/css/fonts.css' ) ) {
		wp_enqueue_style(
			'brandsure-fonts',
			BRANDSURE_URI . '/assets/css/fonts.css',
			array(),
			BRANDSURE_VERSION
		);
	}

	wp_enqueue_style(
		'brandsure-tokens',
		BRANDSURE_URI . '/assets/css/tokens.css',
		array(),
		BRANDSURE_VERSION
	);

	// Written in Chat 1. Enqueued only once it exists, so the skeleton does
	// not 404 while the component styles are still to be built.
	if ( file_exists( BRANDSURE_DIR . '/assets/css/brandsure.css' ) ) {
		wp_enqueue_style(
			'brandsure',
			BRANDSURE_URI . '/assets/css/brandsure.css',
			array( 'brandsure-tokens' ),
			BRANDSURE_VERSION
		);
	}

	if ( file_exists( BRANDSURE_DIR . '/assets/js/brandsure.js' ) ) {
		wp_enqueue_script(
			'brandsure',
			BRANDSURE_URI . '/assets/js/brandsure.js',
			array(),
			BRANDSURE_VERSION,
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'brandsure_assets' );

/**
 * Strip the noise WordPress adds to <head>.
 *
 * Emoji scripts, generator tags and the block library's default stylesheet all
 * cost bytes on a 4G phone and give nothing back. The performance budget is
 * LCP under 1.5s and under 600KB excluding video.
 */
function brandsure_trim_head(): void {
	remove_action( 'wp_head', 'wp_generator' );
	remove_action( 'wp_head', 'wlwmanifest_link' );
	remove_action( 'wp_head', 'rsd_link' );
	remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'brandsure_trim_head' );

/**
 * Includes.
 *
 * Each file is added in the chat that builds it (see docs/BUILD-MANIFEST.md
 * §G). Loading conditionally keeps the skeleton installable today and lets the
 * list grow without touching this loop.
 */
$brandsure_includes = array(
	'inc/data.php',              // Chat 1 — load, validate and cache data/*.json
	'inc/content-ownership.php', // Chat 1 — the controlled/editable registry and its locks
	'inc/guard.php',             // Chat 1 — disclosure guard for developer content, run in CI
	'inc/content-guard.php',     // Chat 8 — the same rules applied to WordPress editor content
	'inc/events.php',            // Chat 1 — events, WhatsApp links, wired to n8n in Chat 11
	'inc/helpers.php',           // Chat 1 — small view helpers for components
	'inc/schema.php',            // Chat 11 — Organization / Service / FAQPage / Article
	'inc/seo.php',               // Chat 11 — titles, meta, canonical, OG
	'inc/redirects.php',         // Chat 11 — the /pricing/ 301
);

foreach ( $brandsure_includes as $brandsure_include ) {
	$brandsure_path = BRANDSURE_DIR . '/' . $brandsure_include;
	if ( file_exists( $brandsure_path ) ) {
		require_once $brandsure_path;
	}
}
unset( $brandsure_include, $brandsure_path );
