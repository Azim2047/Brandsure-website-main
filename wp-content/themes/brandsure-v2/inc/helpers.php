<?php
/**
 * Small view helpers shared by components.
 *
 * Anything here must be trivial and presentational. Behaviour belongs in the
 * file that owns it: data loading in data.php, ownership in
 * content-ownership.php, tracking and WhatsApp links in events.php.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a nav URL the page currently being viewed?
 *
 * Compares paths, so a nav entry written as "/plans/" matches whether the
 * site runs at a domain root or in a subdirectory.
 *
 * @param string $url Nav URL, absolute or root-relative.
 * @return bool
 */
function bs_is_current_path( string $url ): bool {
	if ( '' === $url ) {
		return false;
	}

	$target  = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	$current = trim( (string) wp_parse_url( bs_current_path(), PHP_URL_PATH ), '/' );

	if ( '' === $target ) {
		return is_front_page();
	}

	return $target === $current;
}

/**
 * Is the current page one of the four legal pages?
 *
 * They are the one place on the site with no CTA. Someone reading the privacy
 * policy is not in the market, and a conversion prompt there reads badly.
 *
 * @return bool
 */
function bs_is_legal_page(): bool {
	return is_page( array( 'privacy-policy', 'terms-and-conditions', 'cookie-policy', 'disclaimer' ) );
}

/**
 * Which enquiry route each service page uses.
 *
 * Keys into data/site.json routing, so n8n receives the right branch.
 *
 * It lives here rather than in templates/page-service.php because two other
 * things now need to know which pages are service pages — the breadcrumb
 * trail and the Service schema — and a function declared in a template only
 * exists while that template is rendering. That is the same shape of bug the
 * chain component had in Chat 4, found here before it could fatal.
 *
 * @return array<string,string>
 */
function bs_service_routes(): array {
	return array(
		'furniture-growth-marketing-india'        => 'service_marketing',
		'furniture-business-consulting-india'     => 'service_consulting',
		'furniture-product-design-services-india' => 'service_design',
		'furniture-labour-hiring-india'           => 'service_labour',
	);
}

/**
 * Pages that never show the Growth Partnership bridge.
 *
 * `/furniture-labour-hiring-india/` is a genuinely different buyer — a factory
 * owner short two upholsterers is not a partnership prospect — and
 * Architecture v3 §6 is explicit that its only link out is the footer. The old
 * site bridged from it anyway, which is why the rule lives in code rather than
 * in a template's judgement: components/gp-bridge.php checks this list and
 * refuses to render, wherever it is called from.
 *
 * @return array<int,string>
 */
function bs_gp_bridge_excluded(): array {
	return array( 'furniture-labour-hiring-india' );
}

/**
 * The chain's nodes. Order is fixed by the Language Sheet and must not be
 * reordered, shortened or extended by a caller.
 *
 * @return array<int,array{icon:string,label:string}>
 */
function bs_chain_nodes(): array {
	return array(
		array( 'icon' => 'ad',        'label' => __( 'Ad', 'brandsure-v2' ) ),
		array( 'icon' => 'enquiry',   'label' => __( 'Enquiry', 'brandsure-v2' ) ),
		array( 'icon' => 'phone',     'label' => __( 'Call', 'brandsure-v2' ) ),
		array( 'icon' => 'calendar',  'label' => __( 'Appointment', 'brandsure-v2' ) ),
		array( 'icon' => 'showroom',  'label' => __( 'Showroom visit', 'brandsure-v2' ) ),
		array( 'icon' => 'sofa',      'label' => __( 'Order', 'brandsure-v2' ) ),
		array( 'icon' => 'factory',   'label' => __( 'Production', 'brandsure-v2' ) ),
		array( 'icon' => 'truck',     'label' => __( 'Delivery', 'brandsure-v2' ) ),
	);
}

/**
 * Render a button or link.
 *
 * One implementation, so the radius, the type scale and the hover behaviour
 * cannot drift between pages.
 *
 * @param array $args {
 *     @type string $label   Visible text. Required.
 *     @type string $url     Href. Required.
 *     @type string $variant 'primary', 'secondary' or 'link'.
 *     @type string $event   Event name to fire on tap, from bs_event_names().
 *     @type array  $meta    Flat metadata for the event. No personal data.
 *     @type bool   $block   Full width.
 *     @type string $rel     rel attribute.
 * }
 * @return string Markup, or an empty string when the URL is unresolved.
 */
function bs_button( array $args ): string {
	$label   = (string) ( $args['label'] ?? '' );
	$url     = (string) ( $args['url'] ?? '' );
	$variant = (string) ( $args['variant'] ?? 'primary' );

	// An unresolved URL renders nothing rather than a dead control. The most
	// common case is the WhatsApp number still being unsupplied
	// (docs/OPEN-ITEMS.md item 1), and a button that goes nowhere is worse
	// than no button.
	if ( '' === $label || '' === $url ) {
		return '';
	}

	$classes = array( 'bs-btn', 'bs-btn--' . sanitize_html_class( $variant ) );

	if ( ! empty( $args['block'] ) ) {
		$classes[] = 'bs-btn--block';
	}

	$attributes = '';

	if ( ! empty( $args['event'] ) && in_array( $args['event'], bs_event_names(), true ) ) {
		$attributes .= sprintf( ' data-bs-event="%s"', esc_attr( (string) $args['event'] ) );

		if ( ! empty( $args['meta'] ) && is_array( $args['meta'] ) ) {
			$attributes .= sprintf( ' data-bs-meta="%s"', esc_attr( (string) wp_json_encode( $args['meta'] ) ) );
		}
	}

	if ( ! empty( $args['rel'] ) ) {
		$attributes .= sprintf( ' rel="%s"', esc_attr( (string) $args['rel'] ) );
	}

	return sprintf(
		'<a class="%s" href="%s"%s>%s</a>',
		esc_attr( implode( ' ', $classes ) ),
		esc_url( $url ),
		$attributes,
		esc_html( $label )
	);
}

/**
 * Echo a button.
 *
 * @param array $args See bs_button().
 */
function bs_the_button( array $args ): void {
	echo bs_button( $args ); // phpcs:ignore WordPress.Security.EscapingOutput -- escaped in bs_button().
}

/* -------------------------------------------------------------------------
 * The WhatsApp panel
 *
 * Three questions, answered in one place rather than inside the template:
 * may the panel appear on this page, what does it offer here, and where does
 * each option lead. Keeping them here means a page cannot get the panel by
 * accident and cannot lose it by accident either.
 * ---------------------------------------------------------------------- */

/**
 * May the public WhatsApp panel appear on the page being rendered?
 *
 * Three exclusions, each for a different reason:
 *
 *   /start/         The four-number gate is the action, and its handoff
 *                   carries figures the visitor has already typed. A generic
 *                   panel beside it competes with the site's primary KPI and
 *                   offers a worse message than the one already being built.
 *   Legal pages     No CTA at all. Someone reading the privacy policy is not
 *                   in the market; the same rule already keeps the sticky bar
 *                   off them.
 *   Private pages   They carry a stage-specific return-to-WhatsApp action
 *                   tied to a token. This theme renders none of them, so the
 *                   last clause is a boundary recorded in advance rather than
 *                   a live branch — when those templates arrive, this is the
 *                   function they have to satisfy.
 *
 * @return bool
 */
function bs_whatsapp_panel_enabled(): bool {
	if ( '' === (string) bs_site( 'contact.whatsapp_number', '' ) ) {
		return false;
	}

	if ( is_page( 'start' ) || bs_is_legal_page() ) {
		return false;
	}

	return ! bs_is_private_page();
}

/**
 * Is this a token-gated private page?
 *
 * False throughout, and deliberately so: this theme has no private templates,
 * and private content lives behind go.brandsure.in with its own server-side
 * token validation (Layer 4 v4 §9). The function exists so that the rule
 * "the public panel never appears on a private page" is written down at the
 * point it would be enforced, instead of being remembered later.
 *
 * @return bool
 */
function bs_is_private_page(): bool {
	return (bool) apply_filters( 'brandsure_is_private_page', false );
}

/**
 * Which of the four service pages is being viewed, if any.
 *
 * @return string Routing key, or '' off a service page.
 */
function bs_current_service_route(): string {
	$routes = bs_service_routes();
	$slug   = bs_slug();

	return isset( $routes[ $slug ] ) ? $routes[ $slug ] : '';
}

/**
 * What the panel offers on this page.
 *
 * Off a service page: the three generic options.
 *
 * On a service page: that service first, then the generic options — the
 * visitor is reading about product design, so "Ask about furniture product
 * design" should not be three taps away behind "another service".
 *
 * On /furniture-labour-hiring-india/: that service and nothing else. Layer 4
 * v4 §5 gives labour hiring its own route and says not to push that buyer
 * into the main growth qualification. Offering them "Help me choose a plan"
 * is doing precisely that, so the list is trimmed rather than reordered.
 *
 * @return array
 */
function bs_whatsapp_panel_options(): array {
	$panel   = (array) bs_site( 'whatsapp_panel', array() );
	$options = (array) ( $panel['options'] ?? array() );
	$service = bs_current_service_route();

	if ( '' === $service ) {
		return $options;
	}

	$label = (string) bs_site( 'routing.' . $service . '.label', '' );

	$contextual = array(
		'id'    => 'service:' . $service,
		'label' => '' !== $label
			/* translators: %s: the service being read about, e.g. "furniture product design". */
			? sprintf( __( 'Ask about %s', 'brandsure-v2' ), $label )
			: __( 'Ask about this service', 'brandsure-v2' ),
		'hint'  => __( 'The service on this page', 'brandsure-v2' ),
		'icon'  => 'whatsapp',
		'route' => $service,
	);

	if ( 'service_labour' === $service ) {
		return array( $contextual );
	}

	// The contextual option replaces the generic "another service" step,
	// which would otherwise offer the page the reader is already on.
	$rest = array();

	foreach ( $options as $option ) {
		if ( 'services' === ( $option['children'] ?? '' ) ) {
			continue;
		}

		$rest[] = $option;
	}

	return array_merge( array( $contextual ), $rest );
}

/**
 * The route the desktop launcher opens with before any choice is made.
 *
 * On a service page that is the service; everywhere else it is no route at
 * all, which yields the plain "I want to grow my furniture business" message.
 * A launcher must never guess a plan on the visitor's behalf — a guessed
 * plan= arrives in the funnel indistinguishable from an answer.
 *
 * @return string
 */
function bs_whatsapp_panel_default_route(): string {
	return bs_current_service_route();
}

/**
 * The wa.me link for one panel option.
 *
 * An option names either a route (a routing key in data/site.json) or a
 * template (a key in whatsapp_templates). Both end at bs_whatsapp_link(),
 * which is the only place a wa.me URL is built and the only place Override 3
 * is enforced.
 *
 * A service option is built exactly the way components/service-enquiry.php
 * builds one — the service sentence AND the route line — so a panel enquiry
 * and a page enquiry arrive at n8n in the same shape. Two shapes for the same
 * intent would mean the funnel counts them as two different things.
 *
 * @param array $option Option or service entry.
 * @return string
 */
function bs_whatsapp_panel_href( array $option ): string {
	$route    = (string) ( $option['route'] ?? '' );
	$template = (string) ( $option['template'] ?? '' );
	$message  = '' !== $template ? (string) bs_site( 'whatsapp_templates.' . $template, '' ) : '';

	if ( '' !== $route && '' === $message ) {
		$label = (string) bs_site( 'routing.' . $route . '.label', '' );

		// A service route gets the service sentence with its own label; a plan
		// route gets the general opener plus the plan line bs_whatsapp_link()
		// appends.
		if ( '' !== $label ) {
			$template_text = (string) bs_site( 'whatsapp_templates.service_enquiry', '' );
			$message       = '' !== $template_text
				? str_replace( '{service}', $label, $template_text )
				: '';
		}
	}

	return bs_whatsapp_link( $route, $message );
}
