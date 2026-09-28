<?php
/**
 * Timeline — one enquiry, hour by hour.
 *
 * Video 3 slide 3, which the source review called the best slide in the
 * deck. It works because it argues nothing: it just shows an enquiry being
 * handled the way the owner already knows it should be, with times attached.
 * The gap between 9:04 and 9:11 is the entire product.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $stops [{ time, title, icon }]
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_stops = (array) ( $args['stops'] ?? array() );

if ( ! $bs_stops ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-timeline' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-timeline__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-timeline__body">%s</p>', esc_html( (string) $args['body'] ) );
}

?>
<ol class="bs-timeline__list">
	<?php foreach ( $bs_stops as $bs_stop ) : ?>
		<li class="bs-timeline__stop">
			<span class="bs-timeline__marker" aria-hidden="true">
				<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_stop['icon'] ?? 'check' ) ) ); ?>
			</span>
			<span class="bs-timeline__time"><?php echo esc_html( (string) ( $bs_stop['time'] ?? '' ) ); ?></span>
			<span class="bs-timeline__title"><?php echo esc_html( (string) ( $bs_stop['title'] ?? '' ) ); ?></span>
		</li>
	<?php endforeach; ?>
</ol>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-timeline__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
