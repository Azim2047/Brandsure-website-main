<?php
/**
 * Lanes — who does what.
 *
 * Two columns of icons with short labels, never bullets of prose. The
 * boundary is drawn confidently because it is a trust signal, not a
 * disclaimer: the owner's biggest unspoken fear is losing control, and the
 * fastest way to answer it is to show exactly where our work stops and his
 * begins.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $ours  { title, items: [{ icon, label }] }
 *     @type array  $yours { title, items: [{ icon, label }] }
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_lanes = array(
	'ours'  => array( 'data' => (array) ( $args['ours'] ?? array() ), 'key' => 'ours' ),
	'yours' => array( 'data' => (array) ( $args['yours'] ?? array() ), 'key' => 'yours' ),
);

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'class' => 'bs-lanes' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-lanes__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<div class="bs-lanes__grid">
	<?php foreach ( $bs_lanes as $bs_lane ) : ?>
		<?php
		$bs_data  = $bs_lane['data'];
		$bs_items = (array) ( $bs_data['items'] ?? array() );

		if ( ! $bs_items ) {
			continue;
		}
		?>
		<div class="bs-lanes__lane bs-lanes__lane--<?php echo esc_attr( $bs_lane['key'] ); ?>">
			<h3 class="bs-lanes__title"><?php echo esc_html( (string) ( $bs_data['title'] ?? '' ) ); ?></h3>

			<ul class="bs-lanes__items">
				<?php foreach ( $bs_items as $bs_item ) : ?>
					<li class="bs-lanes__item">
						<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_item['icon'] ?? 'check' ) ) ); ?>
						<span><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endforeach; ?>
</div>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-lanes__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
