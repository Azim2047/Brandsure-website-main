<?php
/**
 * Two halves — what you built, and what is still to build.
 *
 * Video 1 slide 5, as one picture. The left column is solid because that half
 * exists; the right is dashed because it does not. No numbers, no offer, no
 * blame — the owner is meant to recognise his own business, not be sold to.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $built    { title, items: [{ icon, label }] }
 *     @type array  $unbuilt  { title, items: [{ icon, label }] }
 *     @type string $note     One line under the picture.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_halves = array(
	'built'   => array( 'data' => (array) ( $args['built'] ?? array() ), 'state' => 'built' ),
	'unbuilt' => array( 'data' => (array) ( $args['unbuilt'] ?? array() ), 'state' => 'unbuilt' ),
);

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-halves' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-halves__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<div class="bs-halves__grid">
	<?php foreach ( $bs_halves as $bs_half ) : ?>
		<?php
		$bs_data  = $bs_half['data'];
		$bs_items = (array) ( $bs_data['items'] ?? array() );

		if ( ! $bs_items ) {
			continue;
		}
		?>
		<div class="bs-halves__col bs-halves__col--<?php echo esc_attr( $bs_half['state'] ); ?>">
			<h3 class="bs-halves__title"><?php echo esc_html( (string) ( $bs_data['title'] ?? '' ) ); ?></h3>

			<ul class="bs-halves__items">
				<?php foreach ( $bs_items as $bs_item ) : ?>
					<li class="bs-halves__item">
						<?php
						get_template_part(
							'components/icon',
							null,
							array( 'name' => (string) ( $bs_item['icon'] ?? 'check' ) )
						);
						?>
						<span><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-halves__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
