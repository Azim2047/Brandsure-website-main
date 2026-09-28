<?php
/**
 * Numbered steps — what happens after you send.
 *
 * The persona is defensive about anything that might turn into a sales
 * process he cannot see the end of. Saying plainly what the next three steps
 * are, before he sends anything, removes the reason to hesitate.
 *
 * @param array $args {
 *     @type string $headline
 *     @type array  $items    [{ title, body }]
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
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'width' => 'prose', 'class' => 'bs-steps' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-steps__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<ol class="bs-steps__list">
	<?php foreach ( $bs_items as $bs_index => $bs_item ) : ?>
		<li class="bs-steps__item">
			<span class="bs-steps__num" aria-hidden="true"><?php echo esc_html( (string) ( $bs_index + 1 ) ); ?></span>
			<div class="bs-steps__text">
				<h3 class="bs-steps__title"><?php echo esc_html( (string) ( $bs_item['title'] ?? '' ) ); ?></h3>
				<?php if ( ! empty( $bs_item['body'] ) ) : ?>
					<p class="bs-steps__body"><?php echo esc_html( (string) $bs_item['body'] ); ?></p>
				<?php endif; ?>
			</div>
		</li>
	<?php endforeach; ?>
</ol>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
