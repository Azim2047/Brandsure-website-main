<?php
/**
 * Built by BrandSure — the referral entry point.
 *
 * Reached from the credit on a client's website, so the visitor arrives
 * warmer than from anywhere else in the system: they have just used
 * something we made, and it worked well enough that they followed the link.
 *
 * Two things this template does that no other page does.
 *
 * noindex. This is a referral surface, not a search surface. Letting Google
 * index it — and index a separate copy per ?ref= value — would split rankings
 * across near-identical pages for no gain.
 *
 * Personalisation from ?ref=. Resolved server-side against the clients array
 * in data/proof.json. A query string already defeats most full-page caches,
 * so there is nothing to gain from doing it in JavaScript, and doing it here
 * means it works with JavaScript off. An unknown, absent or malformed ref
 * falls through to the generic version rather than showing a broken
 * sentence — which is what every visitor sees today, because no client has
 * been cleared for naming yet (docs/OPEN-ITEMS.md item 8).
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'wp_robots', 'wp_robots_no_robots' );

$bs_page    = bs_data( 'pages/built-by' );
$bs_clients = (array) ( bs_data( 'proof' )['clients'] ?? array() );

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- a public read-only referral parameter.
$bs_ref = isset( $_GET['ref'] ) ? sanitize_key( wp_unslash( $_GET['ref'] ) ) : '';

$bs_client  = '';
$bs_eyebrow = (string) ( $bs_page['generic_eyebrow'] ?? '' );

foreach ( $bs_clients as $bs_candidate ) {
	if ( '' !== $bs_ref && $bs_ref === ( $bs_candidate['slug'] ?? '' ) && ! empty( $bs_candidate['name'] ) ) {
		$bs_client  = (string) $bs_candidate['name'];
		$bs_eyebrow = sprintf( (string) ( $bs_page['named_eyebrow'] ?? '%s' ), $bs_client );
		break;
	}
}

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php
	get_template_part(
		'components/hero',
		null,
		array(
			'tone'      => 'dark',
			'eyebrow'   => $bs_eyebrow,
			'headline'  => (string) ( $bs_page['headline'] ?? '' ),
			'body'      => (string) ( $bs_page['body'] ?? '' ),
			'primary'   => (array) ( $bs_page['cta'] ?? array() ),
			'secondary' => (array) ( $bs_page['secondary'] ?? array() ),
		)
	);

	get_template_part(
		'components/figures',
		null,
		array(
			'tone'     => 'light',
			'headline' => __( 'Who you are dealing with.', 'brandsure-v2' ),
			'items'    => (array) ( bs_data( 'proof' )['credentials'] ?? array() ),
		)
	);

	get_template_part( 'components/cta' );
	?>
</main>
<?php

get_footer();
