<?php
/**
 * Link cards — a short list of places to go next.
 *
 * The Other Services index is a routing surface, not a sales page: four
 * cards, one line each, and a quiet way back to the plans for anyone who
 * wants the whole growth side rather than one piece of it.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $items [{ title, body, url }]
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
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-cards' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-cards__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-cards__body">%s</p>', esc_html( (string) $args['body'] ) );
}

?>
<ul class="bs-cards__grid">
	<?php foreach ( $bs_items as $bs_item ) : ?>
		<li>
			<a class="bs-cards__card" href="<?php echo esc_url( (string) ( $bs_item['url'] ?? '' ) ); ?>">
				<span class="bs-cards__title"><?php echo esc_html( (string) ( $bs_item['title'] ?? '' ) ); ?></span>
				<span class="bs-cards__text"><?php echo esc_html( (string) ( $bs_item['body'] ?? '' ) ); ?></span>
				<span class="bs-cards__go" aria-hidden="true">
					<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right' ) ); ?>
				</span>
			</a>
		</li>
	<?php endforeach; ?>
</ul>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
