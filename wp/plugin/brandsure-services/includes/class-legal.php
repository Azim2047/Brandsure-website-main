<?php
/**
 * Company and legal pages.
 *
 * Registers the four policy pages with WordPress's own privacy tooling and
 * makes sure they pick up the BrandSure long-form styling.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * The legal pages, keyed by slug.
 *
 * @return array<string,string> slug => page title.
 */
function brandsure_legal_pages() {
	return array(
		'privacy-policy'       => __( 'Privacy Policy', 'brandsure-services' ),
		'terms-and-conditions' => __( 'Terms & Conditions', 'brandsure-services' ),
		'cookie-policy'        => __( 'Cookie Policy', 'brandsure-services' ),
		'disclaimer'           => __( 'Website Disclaimer', 'brandsure-services' ),
	);
}

/**
 * Whether the current request is one of the legal pages.
 *
 * @return bool
 */
function brandsure_is_legal_page() {
	if ( ! is_page() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	return array_key_exists( $post->post_name, brandsure_legal_pages() );
}

/**
 * Point Settings -> Privacy at the Privacy Policy page, once.
 *
 * WordPress stores this in `wp_page_for_privacy_policy`; setting it makes the
 * page the one WordPress itself links from login and export/erase flows. A
 * one-shot marker means a later manual change is never overwritten.
 *
 * @return void
 */
function brandsure_legal_link_privacy_page() {
	if ( get_option( 'brandsure_privacy_page_linked' ) ) {
		return;
	}

	$page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
	if ( ! $page instanceof WP_Post ) {
		return;
	}

	// Never displace a privacy page an administrator already chose.
	$current = (int) get_option( 'wp_page_for_privacy_policy' );
	if ( $current && get_post( $current ) ) {
		update_option( 'brandsure_privacy_page_linked', 1 );
		return;
	}

	update_option( 'wp_page_for_privacy_policy', (int) $page->ID );
	update_option( 'brandsure_privacy_page_linked', 1 );
}
add_action( 'admin_init', 'brandsure_legal_link_privacy_page' );

/**
 * Register suggested privacy text with WordPress's privacy-policy guide.
 *
 * Appears under Tools -> Privacy so an administrator can review what this
 * site collects. Informational only; it does not publish anything.
 *
 * @return void
 */
function brandsure_legal_privacy_suggested_text() {
	if ( ! function_exists( 'wp_add_privacy_policy_content' ) ) {
		return;
	}

	$text = '<p>' . wp_kses_post(
		__(
			'This website may collect enquiry details submitted by visitors, and may use cookies and similar technologies for website analytics, advertising measurement and remarketing — including Google Analytics, Google Ads, Meta (Facebook and Instagram) advertising tools and similar pixels. Visitors who contact the business through WhatsApp are subject to WhatsApp\'s own privacy terms in addition to this policy.',
			'brandsure-services'
		)
	) . '</p>';

	wp_add_privacy_policy_content(
		__( 'BrandSure website', 'brandsure-services' ),
		$text
	);
}
add_action( 'admin_init', 'brandsure_legal_privacy_suggested_text' );

/**
 * Add a body class so the legal pages can be targeted in CSS.
 *
 * @param string[] $classes Body classes.
 * @return string[]
 */
function brandsure_legal_body_class( $classes ) {
	if ( brandsure_is_legal_page() ) {
		$classes[] = 'brandsure-legal-page';
	}
	return $classes;
}
add_filter( 'body_class', 'brandsure_legal_body_class' );
