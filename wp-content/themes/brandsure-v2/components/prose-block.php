<?php
/**
 * Prose block — a headline and one or more paragraphs.
 *
 * Exists for the places where the exact wording is fixed and must not be
 * decorated: on the Growth Partnership page it carries the two approved
 * sentences from Override 1 and nothing else. A card, an icon or a pull-quote
 * around them would invite someone to "improve" them later.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $paragraphs [string]
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_paragraphs = (array) ( $args['paragraphs'] ?? array() );

if ( ! $bs_paragraphs ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-prose-block' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-prose-block__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

foreach ( $bs_paragraphs as $bs_paragraph ) {
	printf( '<p class="bs-prose-block__p">%s</p>', esc_html( (string) $bs_paragraph ) );
}

get_template_part( 'components/section', null, array( 'close' => true ) );
