<?php
/**
 * Events — the website half of the n8n contract.
 *
 * Specified in docs/EVENTS-AND-INTEGRATION.md. The PHP side does very little
 * on purpose: it hands the browser a small config object and the browser does
 * the rest, because full-page caching means PHP does not run on most views
 * and anything decided here would be cached and wrong.
 *
 * Two things this file will not do:
 *
 *   - Invent an endpoint. The path /landing-event is known from the supplied
 *     package; the base host is not, and stays empty until supplied
 *     (OPEN-ITEMS item 2). While it is empty the JS collects nothing and
 *     posts nothing.
 *   - Send anything personal. No name, no phone number, no email, and none of
 *     the four gate numbers. Those travel only inside the WhatsApp message the
 *     owner sends from their own device. The event stream records that the
 *     gate was used, never what was in it.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * The ten website events, from Architecture v3 §8.
 *
 * The JS will not send a name that is not on this list. Keeping the list in
 * PHP as well as JS would be two copies of one fact, so it lives here and is
 * handed to the browser with the config.
 *
 * @return array<int,string>
 */
function bs_event_names(): array {
	return array(
		'page_view',
		'video1_start',
		'video1_25',
		'video1_50',
		'video1_75',
		'video1_complete',
		'plan_viewed',
		'plan_selected',
		'start_gate_open',
		'gate_field_filled',
		'gate_whatsapp_click',
		'service_enquiry_click',
		'builtby_view',

		/*
		 * The WhatsApp panel, added in Chat 15. Five names, because the brief
		 * asks for five different things and collapsing them would lose the
		 * distinction that matters most:
		 *
		 *   whatsapp_launcher_click  the bar or floating button was tapped
		 *   whatsapp_panel_open      the panel actually opened
		 *   whatsapp_option_select   which of the options was chosen
		 *   whatsapp_outbound_click  the visitor left for WhatsApp
		 *   call_click               the Call half of the bar
		 *
		 * An outbound click is NOT a received message and NOT a qualified
		 * lead. Layer 4 v4 is explicit about this and it is the easiest number
		 * on the site to misread, so the names are kept far apart: nothing
		 * here is called a lead, a conversation or an enquiry. The received
		 * conversation is counted at the WhatsApp end, against the session
		 * code carried in the message, and it is a different number.
		 */
		'whatsapp_launcher_click',
		'whatsapp_panel_open',
		'whatsapp_option_select',
		'whatsapp_outbound_click',
		'call_click',
	);
}

/**
 * Is the event pipeline configured?
 *
 * @return bool
 */
function bs_events_enabled(): bool {
	return '' !== (string) bs_site( 'integration.n8n_base', '' );
}

/**
 * The config object handed to the browser.
 *
 * @return array
 */
function bs_events_config(): array {
	$base = rtrim( (string) bs_site( 'integration.n8n_base', '' ), '/' );
	$path = (string) bs_site( 'integration.event_path', '/landing-event' );

	return array(
		'endpoint' => '' !== $base ? $base . $path : '',
		'events'   => bs_event_names(),
		'cookie'   => (string) bs_site( 'integration.session_cookie', 'bs_vid' ),
		'days'     => (int) bs_site( 'integration.session_cookie_days', 180 ),
		'source'   => 'website',
		'path'     => bs_current_path(),

		/*
		 * When the one-time "Need help choosing?" invitation may appear. Sent
		 * as configuration rather than hard-coded in the script so the copy
		 * and the timing live together in data/site.json, and so tuning it is
		 * a data edit rather than a code change.
		 */
		'waInvite' => array(
			'scroll'  => (int) bs_site( 'whatsapp_panel.invite.after_scroll_percent', 55 ),
			'seconds' => (int) bs_site( 'whatsapp_panel.invite.after_seconds', 45 ),
		),
	);
}

/**
 * The current request path, without query string.
 *
 * Sent with every event so entry-path analysis works without the endpoint
 * having to parse a full URL, and without a referrer ever being recorded.
 *
 * @return string
 */
function bs_current_path(): string {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$cut = strtok( $uri, '?' );

	return is_string( $cut ) ? $cut : '/';
}

/**
 * Hand the config to brandsure.js.
 *
 * Inline, before the script, so there is no second request and no race. If
 * the script is not enqueued yet (Chat 1 skeleton) this does nothing.
 */
function bs_events_inline_config(): void {
	if ( ! wp_script_is( 'brandsure', 'enqueued' ) ) {
		return;
	}

	wp_add_inline_script(
		'brandsure',
		'window.BrandSureConfig = ' . wp_json_encode( bs_events_config() ) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'bs_events_inline_config', 20 );

/**
 * Build a WhatsApp deep link.
 *
 * Every WhatsApp CTA on the site goes through this one function, which is
 * what keeps Override 3 enforceable: the route parameter is applied here,
 * from data/site.json, rather than assembled by hand in each template.
 *
 * @param string $route   A key in site.json routing, e.g. 'digital_partner'.
 * @param string $message Message body. Empty uses the general template.
 * @return string The wa.me URL, or an empty string when no number is set.
 */
function bs_whatsapp_link( string $route = '', string $message = '' ): string {
	$number = (string) bs_site( 'contact.whatsapp_number', '' );

	// No number yet (OPEN-ITEMS item 1). Callers render the phone/email
	// fallback rather than a link to nowhere.
	if ( '' === $number ) {
		return '';
	}

	if ( '' === $message ) {
		$message = (string) bs_site( 'whatsapp_templates.general', '' );
	}

	if ( '' !== $route ) {
		$config = bs_site( 'routing.' . $route, array() );

		if ( is_array( $config ) && ! empty( $config['param'] ) && ! empty( $config['value'] ) ) {
			// Override 3, enforced in code as well as in the data: Growth
			// Partnership can only ever leave this function as interest=.
			$param = ( 'plan' === $config['param'] && false !== stripos( (string) $config['value'], 'growth partnership' ) )
				? 'interest'
				: $config['param'];

			$message = trim( $message . "\n" . ucfirst( $param ) . ': ' . $config['value'] );
		}
	}

	return 'https://wa.me/' . rawurlencode( $number ) . '?text=' . rawurlencode( $message );
}

/**
 * The gate message with no numbers filled in.
 *
 * This is what a visitor with no JavaScript sends, and what the link carries
 * before anyone types. The AI then asks for the four numbers itself, so the
 * conversation still works — it is just one round longer.
 *
 * @return string
 */
function bs_gate_default_message(): string {
	$template = (string) bs_site( 'whatsapp_templates.gate', '' );

	if ( '' === $template ) {
		return (string) bs_site( 'whatsapp_templates.general', '' );
	}

	// Drop every line that still has an unfilled placeholder, rather than
	// sending "Monthly sales: {sales}" to a real person.
	$lines = array_filter(
		explode( "\n", $template ),
		static fn( string $line ): bool => ! preg_match( '/\{[a-z_]+\}/', $line )
	);

	return trim( implode( "\n", $lines ) );
}

/**
 * The CTA label for a route, from data/site.json.
 *
 * @param string $route Route key.
 * @param string $fallback Used when the route has no label.
 * @return string
 */
function bs_route_cta( string $route, string $fallback = '' ): string {
	return (string) bs_site( 'routing.' . $route . '.cta', $fallback );
}
