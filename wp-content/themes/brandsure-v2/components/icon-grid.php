<?php
/**
 * Icon grid — a set of things, each one icon and a few words.
 *
 * Used for "what we put in" on the Growth Partnership page, where the whole
 * point is to show scope without showing any value. Labels only, never a
 * figure: the swimlane in Architecture v3 §5 is specified as icons with no
 * rupee values, and this component gives no slot for one.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $items [{ icon, label, note }]
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
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'class' => 'bs-grid' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-grid__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-grid__body">%s</p>', esc_html( (string) $args['body'] ) );
}

?>
<ul class="bs-grid__list">
	<?php foreach ( $bs_items as $bs_item ) : ?>
		<li class="bs-grid__item">
			<span class="bs-grid__tile">
				<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_item['icon'] ?? 'check' ) ) ); ?>
			</span>
			<span class="bs-grid__label"><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></span>
			<?php if ( ! empty( $bs_item['note'] ) ) : ?>
				<span class="bs-grid__note"><?php echo esc_html( (string) $bs_item['note'] ); ?></span>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-grid__foot"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
