<?php
/**
 * Tick list — what stays yours.
 *
 * Video 5 slide 2. Every line is a boundary, and the list is deliberately
 * long: for an owner weighing a long-term arrangement, the reassurance comes
 * from the count as much as from any single item.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $items [string]
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_items = (array) ( $args['items'] ?? array() );

if ( ! $bs_items ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'class' => 'bs-ticks' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-ticks__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<ul class="bs-ticks__list">
	<?php foreach ( $bs_items as $bs_item ) : ?>
		<li class="bs-ticks__item">
			<span class="bs-ticks__mark">
				<?php get_template_part( 'components/icon', null, array( 'name' => 'check' ) ); ?>
			</span>
			<?php echo esc_html( (string) $bs_item ); ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-ticks__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
