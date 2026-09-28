<?php
/**
 * Fit check — who this works for, and who it does not.
 *
 * Video 1 slide 6. Publishing the second list is the point: an owner who has
 * been called by twenty agencies has never once been told he might not be a
 * fit, and saying it plainly buys more trust than another claim would.
 *
 * It also does real work for BrandSure — a visitor who reads the right-hand
 * list and leaves has saved everyone a qualification call.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $fit     { title, items: [string] }
 *     @type array  $not_fit { title, items: [string] }
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_fit     = (array) ( $args['fit'] ?? array() );
$bs_not_fit = (array) ( $args['not_fit'] ?? array() );

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'class' => 'bs-fit' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-fit__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<div class="bs-fit__grid">

	<?php if ( ! empty( $bs_fit['items'] ) ) : ?>
		<div class="bs-fit__col bs-fit__col--yes">
			<h3 class="bs-fit__title"><?php echo esc_html( (string) ( $bs_fit['title'] ?? '' ) ); ?></h3>
			<ul class="bs-fit__list">
				<?php foreach ( (array) $bs_fit['items'] as $bs_item ) : ?>
					<li class="bs-fit__item">
						<span class="bs-fit__mark bs-fit__mark--yes">
							<?php get_template_part( 'components/icon', null, array( 'name' => 'check' ) ); ?>
						</span>
						<?php echo esc_html( (string) $bs_item ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $bs_not_fit['items'] ) ) : ?>
		<div class="bs-fit__col bs-fit__col--no">
			<h3 class="bs-fit__title"><?php echo esc_html( (string) ( $bs_not_fit['title'] ?? '' ) ); ?></h3>
			<ul class="bs-fit__list">
				<?php foreach ( (array) $bs_not_fit['items'] as $bs_item ) : ?>
					<li class="bs-fit__item">
						<span class="bs-fit__mark bs-fit__mark--no">
							<?php get_template_part( 'components/icon', null, array( 'name' => 'close' ) ); ?>
						</span>
						<?php echo esc_html( (string) $bs_item ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>

</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
