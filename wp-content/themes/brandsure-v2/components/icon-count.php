<?php
/**
 * Icon count — a number drawn as a row of things.
 *
 * "Every number has a shape" (Master Build Architecture §1.2). Five orders is
 * five sofas, not the digit 5. For an owner who thinks in orders rather than
 * in fees, that is the difference between reading a price and understanding
 * one.
 *
 * The count comes from a fact key so it cannot drift from the Numbers Sheet:
 * a fact value like "5 orders / month" yields five icons.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $fact  Fact key whose leading digits are the count.
 *     @type string $icon  Sprite name to repeat.
 *     @type string $body  Sentence under the row.
 *     @type string $note  Qualifier. Rendered smaller.
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_value = bs_fact( (string) ( $args['fact'] ?? '' ) );

if ( '' === $bs_value ) {
	return;
}

preg_match( '/\d+/', $bs_value, $bs_match );

$bs_count = isset( $bs_match[0] ) ? (int) $bs_match[0] : 0;

// A row of icons stops being readable long before it stops being drawable.
// Beyond a dozen the shape says "many", which the number already said.
if ( $bs_count < 1 || $bs_count > 12 ) {
	bs_data_fail( sprintf( 'icon-count cannot draw "%s" as a row of icons', $bs_value ) );
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'width' => 'prose', 'class' => 'bs-count' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-count__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<p class="bs-count__row" role="img" aria-label="<?php echo esc_attr( $bs_value ); ?>">
	<?php for ( $bs_i = 0; $bs_i < $bs_count; $bs_i++ ) : ?>
		<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $args['icon'] ?? 'sofa' ) ) ); ?>
	<?php endfor; ?>
</p>

<?php if ( ! empty( $args['body'] ) ) : ?>
	<p class="bs-count__body"><?php echo esc_html( (string) $args['body'] ); ?></p>
<?php endif; ?>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-count__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
