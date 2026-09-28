<?php
/**
 * Chain block — the chain as a page section.
 *
 * components/chain.php stays the bare diagram, because it is also rendered
 * inside a plan card where a section wrapper would be wrong. This puts the
 * same diagram in a section with a headline, a supporting line and an
 * optional note under it.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type int    $active  Leading nodes BrandSure runs.
 *     @type array  $leaks   [{ node, label }]
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-chainblock' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-chainblock__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-chainblock__body">%s</p>', esc_html( (string) $args['body'] ) );
}

get_template_part(
	'components/chain',
	null,
	array(
		'active' => (int) ( $args['active'] ?? 0 ),
		'leaks'  => (array) ( $args['leaks'] ?? array() ),
		'label'  => (string) ( $args['label'] ?? '' ),
	)
);

if ( ! empty( $args['note'] ) ) {
	printf( '<p class="bs-chainblock__note">%s</p>', esc_html( (string) $args['note'] ) );
}

get_template_part( 'components/section', null, array( 'close' => true ) );
