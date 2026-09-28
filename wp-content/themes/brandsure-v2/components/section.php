<?php
/**
 * Section wrapper.
 *
 * The only place vertical rhythm and background variants are decided. Every
 * page section on the site passes through here, which is why no template
 * needs a spacing rule of its own.
 *
 * Used two ways:
 *   get_template_part( 'components/section', null, array( 'open' => true, ... ) )
 *   ... content ...
 *   get_template_part( 'components/section', null, array( 'close' => true ) )
 *
 * @param array $args {
 *     @type bool   $open    Open the section.
 *     @type bool   $close   Close it.
 *     @type string $tone    'light' (default), 'tint' or 'dark'.
 *     @type string $width   'default', 'prose' or 'full'.
 *     @type string $id      Anchor id.
 *     @type string $class   Extra classes.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

if ( ! empty( $args['close'] ) ) {
	echo '</div></section>';
	return;
}

$bs_tone  = (string) ( $args['tone'] ?? 'light' );
$bs_width = (string) ( $args['width'] ?? 'default' );

if ( ! in_array( $bs_tone, array( 'light', 'tint', 'dark' ), true ) ) {
	$bs_tone = 'light';
}

if ( ! in_array( $bs_width, array( 'default', 'prose', 'full' ), true ) ) {
	$bs_width = 'default';
}

$bs_classes = array( 'bs-section', 'bs-section--' . $bs_tone );

if ( ! empty( $args['class'] ) ) {
	$bs_classes[] = sanitize_html_class( (string) $args['class'] );
}

$bs_inner = 'full' === $bs_width ? 'bs-section__inner' : 'bs-section__inner bs-container';

if ( 'prose' === $bs_width ) {
	$bs_inner .= ' bs-container--prose';
}

printf(
	'<section class="%s"%s><div class="%s">',
	esc_attr( implode( ' ', $bs_classes ) ),
	! empty( $args['id'] ) ? ' id="' . esc_attr( sanitize_title( (string) $args['id'] ) ) . '"' : '',
	esc_attr( $bs_inner )
);
