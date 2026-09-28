<?php
/**
 * Nested boxes — a growth layer around a business that does not move.
 *
 * Video 5 slide 1, and Architecture v3 calls it the strongest trust visual in
 * the system. It answers the owner's largest unspoken fear — losing control —
 * before any words do, and it does it while revealing nothing commercial:
 * the picture is about where the boundary sits, not about money.
 *
 * The inner box is drawn solid because that business already exists and is
 * not being changed. The outer one is the layer BrandSure builds around it.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $outer { title, items: [{ icon, label }] }
 *     @type array  $inner { title, items: [{ icon, label }] }
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_outer = (array) ( $args['outer'] ?? array() );
$bs_inner = (array) ( $args['inner'] ?? array() );

if ( empty( $bs_inner['items'] ) ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-nested' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-nested__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<div class="bs-nested__outer">
	<p class="bs-nested__title bs-nested__title--outer">
		<?php echo esc_html( (string) ( $bs_outer['title'] ?? '' ) ); ?>
	</p>

	<?php if ( ! empty( $bs_outer['items'] ) ) : ?>
		<ul class="bs-nested__items bs-nested__items--outer">
			<?php foreach ( (array) $bs_outer['items'] as $bs_item ) : ?>
				<li class="bs-nested__item">
					<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_item['icon'] ?? 'check' ) ) ); ?>
					<span><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<div class="bs-nested__inner">
		<p class="bs-nested__title bs-nested__title--inner">
			<?php echo esc_html( (string) ( $bs_inner['title'] ?? '' ) ); ?>
		</p>

		<ul class="bs-nested__items bs-nested__items--inner">
			<?php foreach ( (array) $bs_inner['items'] as $bs_item ) : ?>
				<li class="bs-nested__item">
					<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_item['icon'] ?? 'check' ) ) ); ?>
					<span><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</div>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-nested__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
