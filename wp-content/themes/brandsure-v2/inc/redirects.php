<?php
/**
 * The redirect table.
 *
 * docs/REDIRECTS.md §2, and nothing beyond it. There is exactly one content
 * redirect in this build — /pricing/ to /plans/ — and the shortness of that
 * list is deliberate: the old site's four service URLs and /services/ are
 * ranking assets that keep their slugs and are never redirected. A 301 is
 * permanent and cached hard by browsers, so a wrong one is expensive to take
 * back.
 *
 * These live in the theme rather than only at the server, so they deploy and
 * roll back with the theme and are visible to anyone reading the code. The
 * host-level canonical rules — HTTPS, apex, trailing slash, lowercase — stay
 * at the server, where they belong; they must resolve before any of this runs
 * so a request never chains through two hops.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Paths that moved, as path => destination.
 *
 * Matched on the normalised path only. Query strings are preserved by
 * wp_safe_redirect's caller below, so /pricing/?utm_source=x survives the
 * move with its parameters intact — losing them would break the reporting on
 * whatever campaign is still pointing at the old URL.
 *
 * @return array<string,string>
 */
function bs_redirect_table(): array {
	return array(
		// The page moved and was renamed (Architecture v3 §4). The three
		// alternate forms are the ones the old static build actually served.
		'pricing'            => '/plans/',
		'pricing/index.html' => '/plans/',
		'pricing.html'       => '/plans/',

		// Not a content redirect: WordPress's sitemap index lives at
		// /wp-sitemap.xml, and /sitemap.xml is where people and tools look.
		'sitemap.xml'        => '/wp-sitemap.xml',
	);
}

/**
 * Apply the table.
 */
function bs_redirects(): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	$path = trim( (string) wp_parse_url( bs_current_path(), PHP_URL_PATH ), '/' );

	if ( '' === $path ) {
		return;
	}

	$target = bs_redirect_table()[ strtolower( $path ) ] ?? '';

	if ( '' === $target ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reading the query string of a public GET to carry it across.
	$query = isset( $_SERVER['QUERY_STRING'] ) ? (string) wp_unslash( $_SERVER['QUERY_STRING'] ) : '';
	$url   = home_url( $target );

	if ( '' !== $query ) {
		$url .= ( str_contains( $url, '?' ) ? '&' : '?' ) . $query;
	}

	wp_safe_redirect( $url, 301, 'BrandSure' );
	exit;
}
add_action( 'template_redirect', 'bs_redirects', 1 );
