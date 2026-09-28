<?php
/**
 * Discreet footer links to the blog and the policy pages.
 *
 * Three ways in, so this works whatever the active theme does:
 *
 *   1. appended to any footer navigation menu, when the theme has one;
 *   2. the [brandsure_legal_links] shortcode, for a footer widget or block;
 *   3. a small strip printed in wp_footer, used only when neither of the
 *      above already rendered the links on this request.
 *
 * The primary navigation is never touched.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether the links have already been rendered on this request.
 *
 * @var bool
 */
$GLOBALS['brandsure_footer_links_done'] = false;

/**
 * The footer links, in order: blog first, then the policies.
 *
 * @return array<int,array{label:string,url:string}>
 */
function brandsure_footer_link_items() {
	$items = array();

	$blog_url = brandsure_blog_url();
	if ( $blog_url && home_url( '/' ) !== $blog_url ) {
		$items[] = array(
			'label' => __( 'Blog', 'brandsure-services' ),
			'url'   => $blog_url,
		);
	}

	foreach ( brandsure_legal_pages() as $slug => $title ) {
		$page = get_page_by_path( $slug, OBJECT, 'page' );
		if ( ! $page instanceof WP_Post || 'publish' !== $page->post_status ) {
			// A draft policy would 404 for visitors, so it stays out of the footer.
			continue;
		}
		$items[] = array(
			'label' => get_the_title( $page ),
			'url'   => (string) get_permalink( $page ),
		);
	}

	/**
	 * Filter the discreet footer links.
	 *
	 * @param array $items List of label/url pairs.
	 */
	return (array) apply_filters( 'brandsure_footer_link_items', $items );
}

/**
 * Mark the links as rendered for this request.
 *
 * @return void
 */
function brandsure_footer_mark_rendered() {
	$GLOBALS['brandsure_footer_links_done'] = true;
}

/**
 * Append the links to a footer navigation menu.
 *
 * Only menus whose theme location name contains "footer" are touched, and a
 * link already present in the menu is never added twice.
 *
 * @param string   $items HTML list items.
 * @param stdClass $args  wp_nav_menu arguments.
 * @return string
 */
function brandsure_footer_nav_items( $items, $args ) {
	$location = isset( $args->theme_location ) ? (string) $args->theme_location : '';
	if ( '' === $location || false === stripos( $location, 'footer' ) ) {
		return $items;
	}

	$added = '';
	foreach ( brandsure_footer_link_items() as $item ) {
		$path = wp_parse_url( $item['url'], PHP_URL_PATH );
		if ( $path && false !== strpos( $items, 'href="' . esc_url( $item['url'] ) . '"' ) ) {
			continue;
		}
		if ( $path && false !== strpos( $items, rtrim( (string) $path, '/' ) . '/"' ) ) {
			continue;
		}
		$added .= sprintf(
			'<li class="menu-item brandsure-footer-link"><a href="%s">%s</a></li>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	if ( '' !== $added ) {
		brandsure_footer_mark_rendered();
	}

	return $items . $added;
}
add_filter( 'wp_nav_menu_items', 'brandsure_footer_nav_items', 20, 2 );

/**
 * Render the links as a small inline strip.
 *
 * @return string
 */
function brandsure_footer_links_markup() {
	$items = brandsure_footer_link_items();
	if ( empty( $items ) ) {
		return '';
	}

	$links = '';
	foreach ( $items as $item ) {
		$links .= sprintf(
			'<a href="%s">%s</a>',
			esc_url( $item['url'] ),
			esc_html( $item['label'] )
		);
	}

	return sprintf(
		'<nav class="bslinks" aria-label="%s">%s</nav>',
		esc_attr__( 'Policies', 'brandsure-services' ),
		$links
	);
}

/**
 * [brandsure_legal_links] — drop the strip into a footer widget or block.
 *
 * @return string
 */
function brandsure_footer_links_shortcode() {
	brandsure_footer_mark_rendered();
	return brandsure_footer_links_markup();
}
add_shortcode( 'brandsure_legal_links', 'brandsure_footer_links_shortcode' );

/**
 * Template tag, for a theme that wants to place the strip itself.
 *
 * @return void
 */
function brandsure_footer_links() {
	brandsure_footer_mark_rendered();
	echo wp_kses_post( brandsure_footer_links_markup() );
}

/**
 * Fallback: print the strip at the end of the page when nothing else did.
 *
 * @return void
 */
function brandsure_footer_links_fallback() {
	if ( ! empty( $GLOBALS['brandsure_footer_links_done'] ) ) {
		return;
	}

	/**
	 * Filter whether to print the fallback footer strip.
	 *
	 * Return false if the links are placed in the theme instead.
	 *
	 * @param bool $enabled Default true.
	 */
	if ( ! apply_filters( 'brandsure_footer_render_strip', true ) ) {
		return;
	}

	$markup = brandsure_footer_links_markup();
	if ( '' === $markup ) {
		return;
	}

	// The strip needs the shared tokens; enqueue late only if not already on.
	if ( ! wp_style_is( 'brandsure-blog', 'enqueued' ) ) {
		$shared = 'assets/brandsure-services.css';
		$blog   = 'assets/brandsure-blog.css';
		wp_enqueue_style(
			'brandsure-services',
			BRANDSURE_SERVICES_URL . $shared,
			array(),
			brandsure_services_asset_version( $shared )
		);
		wp_enqueue_style(
			'brandsure-blog',
			BRANDSURE_SERVICES_URL . $blog,
			array( 'brandsure-services' ),
			brandsure_services_asset_version( $blog )
		);
		wp_print_styles( array( 'brandsure-services', 'brandsure-blog' ) );
	}

	echo wp_kses_post( $markup );
	brandsure_footer_mark_rendered();
}
add_action( 'wp_footer', 'brandsure_footer_links_fallback', 99 );
