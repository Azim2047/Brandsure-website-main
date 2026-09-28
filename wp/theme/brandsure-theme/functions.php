<?php
/**
 * BrandSure theme setup.
 *
 * This theme exists to undo a specific mistake. The theme it replaces,
 * brandsure-wordpress-theme, hooked template_redirect at priority 0 and did
 * `readfile(); exit;` for nine routes, so on the site's nine most important URLs
 * there was no wp_head(), no the_content() and no wp_footer(). That is why the
 * SEO plugin emitted nothing, why no analytics tag ever fired on a landing page,
 * why the pages were not editable in wp-admin, and why every legal page returned
 * a bare 404 from its page.php fall-through.
 *
 * So the one rule here: render pages the ordinary WordPress way. Nothing in this
 * theme intercepts a request.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

define( 'BRANDSURE_THEME_VERSION', '1.0.0' );

/**
 * Theme supports.
 *
 * @return void
 */
function brandsure_theme_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'custom-logo', array(
		'height'      => 43,
		'width'       => 160,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
	) );

	// The block editor previews page content against the real design system.
	add_editor_style( 'style.css' );

	register_nav_menus( array(
		'footer' => __( 'Footer', 'brandsure-theme' ),
	) );
}
add_action( 'after_setup_theme', 'brandsure_theme_setup' );

/**
 * Stylesheet and webfont.
 *
 * Priority 5, ahead of the plugin's own sheets. The four service pages are
 * served a separate, scoped stylesheet by the plugin which sets --maxw:1080
 * against this one's 1140; loading this first lets that one win, as it did
 * before this theme existed.
 *
 * @return void
 */
function brandsure_theme_assets() {
	wp_enqueue_style(
		'brandsure-inter',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap',
		array(),
		null // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- Versioning a Google Fonts URL breaks its cache.
	);

	$stylesheet = get_stylesheet_directory() . '/style.css';
	wp_enqueue_style(
		'brandsure-theme',
		get_stylesheet_uri(),
		array( 'brandsure-inter' ),
		file_exists( $stylesheet ) ? (string) filemtime( $stylesheet ) : BRANDSURE_THEME_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_theme_assets', 5 );

/**
 * Preconnect to the font host, which the static pages did by hand.
 *
 * @param array  $urls          Resource hints.
 * @param string $relation_type Hint type.
 * @return array
 */
function brandsure_theme_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'brandsure_theme_resource_hints', 10, 2 );

/**
 * Values the page scripts read off <body>.
 *
 * The static pages carried data-page-service and data-page-slug there. The
 * plugin also hands these to its scripts directly; both paths are kept so a
 * page works whichever is present.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function brandsure_theme_body_class( $classes ) {
	$classes[] = 'brandsure';
	return $classes;
}
add_filter( 'body_class', 'brandsure_theme_body_class' );

/**
 * The slug of the singular object being viewed, or ''.
 *
 * @return string
 */
function brandsure_theme_current_slug() {
	if ( ! is_singular() ) {
		return '';
	}
	$post = get_queried_object();
	return $post instanceof WP_Post ? (string) $post->post_name : '';
}

/**
 * Render page content, recording what the body already provides.
 *
 * Page bodies are at different stages of migration: some still carry their own
 * footer and WhatsApp pill from when each page was a self-contained document,
 * others carry neither. Buffering the content lets footer.php supply only what
 * is missing, so no page ends up with two footers and none with none.
 *
 * @return void
 */
function brandsure_theme_render_content() {
	ob_start();
	the_content();
	$content = ob_get_clean();

	$GLOBALS['brandsure_body_has_footer'] = ( false !== strpos( $content, '<footer' ) );
	$GLOBALS['brandsure_body_has_pill']   = ( false !== strpos( $content, 'class="wapill"' ) );

	echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already passed through the_content filters.
}

/**
 * The previous theme registered rewrite rules for its nine static routes.
 * Those must go, or they keep pointing requests at a renderer that is no
 * longer there.
 *
 * @return void
 */
function brandsure_theme_flush_rewrites() {
	flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'brandsure_theme_flush_rewrites' );

/**
 * The BrandSure WhatsApp destination.
 *
 * Defined by the plugin; repeated here only so the theme still works with the
 * plugin deactivated.
 *
 * @return string
 */
function brandsure_theme_wa_url() {
	if ( defined( 'BRANDSURE_WA_URL' ) ) {
		return BRANDSURE_WA_URL;
	}
	return 'https://wa.me/918884446668?text=Hi%20BrandSure.%20I%20am%20a%20furniture%20manufacturer.%20I%20want%20to%20discuss%20my%20business.';
}
