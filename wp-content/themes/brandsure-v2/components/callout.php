<?php
/**
 * Callout — one bordered card with a single action.
 *
 * Used on /plans/ for the "not sure which fits" card that stands where
 * Video 2 would go if Video 2 were public. It is not: Video 2 is gated, so
 * the page routes to the gate instead of embedding it.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $action { label, url, event }
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_action = (array) ( $args['action'] ?? array() );

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-callout' )
);

?>
<div class="bs-callout__box">
	<?php if ( ! empty( $args['headline'] ) ) : ?>
		<h2 class="bs-callout__headline"><?php echo esc_html( (string) $args['headline'] ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $args['body'] ) ) : ?>
		<p class="bs-callout__body"><?php echo esc_html( (string) $args['body'] ); ?></p>
	<?php endif; ?>

	<?php
	bs_the_button(
		array(
			'label'   => (string) ( $bs_action['label'] ?? '' ),
			'url'     => (string) ( $bs_action['url'] ?? '' ),
			'variant' => 'primary',
			'block'   => true,
			'event'   => (string) ( $bs_action['event'] ?? '' ),
		)
	);
	?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
