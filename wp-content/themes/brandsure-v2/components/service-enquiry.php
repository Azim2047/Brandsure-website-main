<?php
/**
 * Service enquiry — a service page's own way in.
 *
 * Architecture v3 §6: each of the four services has its own enquiry path, so
 * n8n routes it into that branch rather than into the main growth funnel.
 * Somebody looking for two upholsterers is not a Growth Partnership lead and
 * should not be treated as one.
 *
 * The route is looked up from data/site.json by key, so the prefill and the
 * event fire correctly without a template hard-coding either.
 *
 * @param array $args {
 *     @type string $route    Routing key, e.g. 'service_labour'.
 *     @type string $headline
 *     @type string $body
 *     @type string $label    Button label.
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_route = (string) ( $args['route'] ?? '' );
$bs_tel   = (string) bs_site( 'contact.phone_tel', '' );

/*
 * The opener has to match what the reader came for. Someone on the labour
 * page asking "I want to grow my furniture business" reads as a form letter,
 * and it is the wrong request — they want two upholsterers. The label comes
 * from the route in data/site.json, so each service says its own name.
 */
$bs_label   = (string) bs_site( 'routing.' . $bs_route . '.label', '' );
$bs_message = (string) bs_site( 'whatsapp_templates.service_enquiry', '' );

$bs_message = ( '' !== $bs_message && '' !== $bs_label )
	? str_replace( '{service}', $bs_label, $bs_message )
	: '';

$bs_link = bs_whatsapp_link( $bs_route, $bs_message );

// No WhatsApp number configured yet (docs/OPEN-ITEMS.md item 1). Fall back to
// the phone rather than rendering a dead control; the guard blocks launch
// while both are empty.
if ( '' === $bs_link && '' === $bs_tel ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'width' => 'prose', 'class' => 'bs-enquiry' )
);

?>
<div class="bs-enquiry__box">
	<h2 class="bs-enquiry__headline">
		<?php echo esc_html( (string) ( $args['headline'] ?? __( 'Tell us what you need.', 'brandsure-v2' ) ) ); ?>
	</h2>

	<?php if ( ! empty( $args['body'] ) ) : ?>
		<p class="bs-enquiry__body"><?php echo esc_html( (string) $args['body'] ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $bs_link ) : ?>
		<a class="bs-btn bs-btn--primary bs-btn--block"
			href="<?php echo esc_url( $bs_link ); ?>"
			rel="noopener"
			data-bs-event="service_enquiry_click"
			data-bs-meta='<?php echo esc_attr( (string) wp_json_encode( array( 'service' => $bs_route ) ) ); ?>'>
			<?php get_template_part( 'components/icon', null, array( 'name' => 'whatsapp' ) ); ?>
			<?php echo esc_html( (string) ( $args['label'] ?? __( 'Ask on WhatsApp', 'brandsure-v2' ) ) ); ?>
		</a>
	<?php else : ?>
		<a class="bs-btn bs-btn--primary bs-btn--block" href="tel:<?php echo esc_attr( $bs_tel ); ?>">
			<?php get_template_part( 'components/icon', null, array( 'name' => 'phone' ) ); ?>
			<?php echo esc_html__( 'Call us', 'brandsure-v2' ); ?>
		</a>
	<?php endif; ?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
