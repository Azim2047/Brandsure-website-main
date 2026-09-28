<?php
/**
 * Sticky WhatsApp + Call bar.
 *
 * On every public page, on phones. Architecture v3 §2: no page is a dead end.
 * The reader is an owner holding a phone, and the two things he will actually
 * do are message and call — so both stay within thumb reach the whole way
 * down the page.
 *
 * Hidden on /start/, where the page's own gate CTA is the action and a second
 * competing WhatsApp button would split the primary KPI.
 *
 * On phones this bar is also the launcher for the WhatsApp panel: its first
 * action opens the bottom sheet rather than jumping straight to WhatsApp.
 * There is no second floating button on mobile — one control, one action.
 * The href stays a real wa.me link so that with no JavaScript the tap still
 * reaches WhatsApp with a sensible message; the panel is the enhancement, not
 * the mechanism.
 *
 * Renders nothing at all while the WhatsApp number and phone number are
 * unresolved (docs/OPEN-ITEMS.md items 1 and 5) — a bar of dead buttons is
 * worse than no bar.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

if ( is_page( 'start' ) ) {
	return;
}

$bs_whatsapp = bs_whatsapp_link( bs_whatsapp_panel_default_route() );
$bs_tel      = (string) bs_site( 'contact.phone_tel', '' );

/*
 * The bar is on every page, but service_enquiry_click means one thing:
 * somebody asked about a standalone service. Firing it from the home page
 * would put home-page taps into the number that is supposed to show what the
 * four service pages are worth, and nobody reading that number later would
 * know. So it is fired only where it is true.
 *
 * Taps from the bar on other pages are therefore not counted. That is the
 * honest position while Architecture v3 §8's list has no site-wide WhatsApp
 * event: an uncounted tap costs a number we never had, and the alternative —
 * borrowing gate_whatsapp_click — would corrupt the site's primary KPI.
 */
$bs_event = isset( bs_service_routes()[ bs_slug() ] ) ? 'service_enquiry_click' : '';

if ( '' === $bs_whatsapp && '' === $bs_tel ) {
	return;
}

?>
<div class="bs-sticky" data-bs-sticky>
	<?php if ( '' !== $bs_whatsapp ) : ?>
		<a
			class="bs-sticky__action bs-sticky__action--whatsapp"
			href="<?php echo esc_url( $bs_whatsapp ); ?>"
			rel="noopener"
			<?php if ( bs_whatsapp_panel_enabled() ) : ?>
				data-bs-wa-open="sticky"
				aria-haspopup="dialog"
				aria-controls="bs-wa-panel"
			<?php endif; ?>
			<?php if ( '' !== $bs_event ) : ?>
				data-bs-event="<?php echo esc_attr( $bs_event ); ?>"
				data-bs-meta='{"placement":"sticky_bar"}'
			<?php endif; ?>>
			<?php get_template_part( 'components/icon', null, array( 'name' => 'whatsapp' ) ); ?>
			<span><?php echo esc_html( bs_site( 'sticky_bar.whatsapp_label', __( 'WhatsApp', 'brandsure-v2' ) ) ); ?></span>
		</a>
	<?php endif; ?>

	<?php if ( '' !== $bs_tel ) : ?>
		<a
			class="bs-sticky__action bs-sticky__action--call"
			href="tel:<?php echo esc_attr( $bs_tel ); ?>"
			data-bs-event="call_click"
			data-bs-meta='{"placement":"sticky_bar"}'>
			<?php get_template_part( 'components/icon', null, array( 'name' => 'phone' ) ); ?>
			<span><?php echo esc_html( bs_site( 'sticky_bar.call_label', __( 'Call', 'brandsure-v2' ) ) ); ?></span>
		</a>
	<?php endif; ?>
</div>
