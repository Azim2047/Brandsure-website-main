<?php
/**
 * The callback request form on the homepage.
 *
 * The static page's form had no endpoint: it validated the fields, pushed a
 * dataLayer event, logged the values to the browser console and showed
 * "Got it. We'll call you." A furniture owner who left their number would
 * never have been called, because nothing ever received it.
 *
 * So the form posts here instead. Each request is stored as a private
 * brandsure_enquiry post, visible in wp-admin under Enquiries, and emailed to
 * the site's admin address. Nothing about the form's markup, copy or success
 * state changes.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/** Post type holding the callback requests. */
const BRANDSURE_ENQUIRY_TYPE = 'brandsure_enquiry';

/** REST namespace and route the form posts to. */
const BRANDSURE_ENQUIRY_ROUTE = 'brandsure/v1';

/**
 * Register the storage for callback requests.
 *
 * Private and non-public: an enquiry is not content, it is an inbox.
 *
 * @return void
 */
function brandsure_enquiry_register_type() {
	register_post_type(
		BRANDSURE_ENQUIRY_TYPE,
		array(
			'labels'          => array(
				'name'          => __( 'Enquiries', 'brandsure-services' ),
				'singular_name' => __( 'Enquiry', 'brandsure-services' ),
				'menu_name'     => __( 'Enquiries', 'brandsure-services' ),
				'all_items'     => __( 'All enquiries', 'brandsure-services' ),
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_icon'       => 'dashicons-phone',
			'menu_position'   => 26,
			'supports'        => array( 'title', 'editor' ),
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'capabilities'    => array(
				'create_posts' => 'do_not_allow', // Only the form creates these.
			),
			'has_archive'     => false,
			'rewrite'         => false,
			'exclude_from_search' => true,
		)
	);
}
add_action( 'init', 'brandsure_enquiry_register_type' );

/**
 * Register the form's endpoint.
 *
 * @return void
 */
function brandsure_enquiry_register_route() {
	register_rest_route(
		BRANDSURE_ENQUIRY_ROUTE,
		'/callback',
		array(
			'methods'             => 'POST',
			'callback'            => 'brandsure_enquiry_handle',
			'permission_callback' => '__return_true', // A public contact form.
			'args'                => array(
				'name'  => array(
					'required' => true,
					'type'     => 'string',
				),
				'phone' => array(
					'required' => true,
					'type'     => 'string',
				),
				'city'  => array(
					'required' => true,
					'type'     => 'string',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'brandsure_enquiry_register_route' );

/**
 * Store and forward one callback request.
 *
 * @param WP_REST_Request $request Incoming request.
 * @return WP_REST_Response|WP_Error
 */
function brandsure_enquiry_handle( $request ) {
	$name  = sanitize_text_field( (string) $request->get_param( 'name' ) );
	$phone = sanitize_text_field( (string) $request->get_param( 'phone' ) );
	$city  = sanitize_text_field( (string) $request->get_param( 'city' ) );

	$digits = preg_replace( '/\D/', '', $phone );
	if ( '' === $name || '' === $city || strlen( $digits ) < 10 || strlen( $digits ) > 12 ) {
		return new WP_Error(
			'brandsure_enquiry_invalid',
			__( 'Please check the name, mobile number and city.', 'brandsure-services' ),
			array( 'status' => 400 )
		);
	}

	// Two identical submissions inside a minute are a double-tap, not two leads.
	$fingerprint = 'brandsure_enq_' . md5( $digits . '|' . $name );
	if ( get_transient( $fingerprint ) ) {
		return new WP_REST_Response( array( 'stored' => true, 'duplicate' => true ), 200 );
	}
	set_transient( $fingerprint, 1, MINUTE_IN_SECONDS );

	$source   = esc_url_raw( (string) $request->get_param( 'source' ) );
	$campaign = sanitize_text_field( (string) $request->get_param( 'campaign' ) );
	$referrer = sanitize_text_field( (string) $request->get_param( 'traffic_source' ) );

	$lines = array(
		sprintf( 'Name: %s', $name ),
		sprintf( 'Mobile: %s', $phone ),
		sprintf( 'City: %s', $city ),
	);
	if ( '' !== $source ) {
		$lines[] = sprintf( 'Page: %s', $source );
	}
	if ( '' !== $referrer ) {
		$lines[] = sprintf( 'Traffic source: %s', $referrer );
	}
	if ( '' !== $campaign ) {
		$lines[] = sprintf( 'Campaign: %s', $campaign );
	}
	$body = implode( "\n", $lines );

	$post_id = wp_insert_post(
		array(
			'post_type'    => BRANDSURE_ENQUIRY_TYPE,
			'post_status'  => 'private',
			'post_title'   => sprintf( '%s — %s (%s)', $name, $phone, $city ),
			'post_content' => $body,
		),
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	$meta = array(
		'name'           => $name,
		'phone'          => $phone,
		'city'           => $city,
		'source'         => $source,
		'campaign'       => $campaign,
		'traffic_source' => $referrer,
	);
	foreach ( $meta as $key => $value ) {
		if ( '' !== $value ) {
			update_post_meta( $post_id, '_brandsure_' . $key, $value );
		}
	}

	brandsure_enquiry_notify( $name, $phone, $city, $body );

	/**
	 * Fires after a callback request has been stored.
	 *
	 * Hook here to forward the enquiry to a CRM or an automation webhook.
	 *
	 * @param int   $post_id Stored enquiry ID.
	 * @param array $fields  name, phone, city.
	 */
	do_action(
		'brandsure_enquiry_received',
		$post_id,
		array(
			'name'  => $name,
			'phone' => $phone,
			'city'  => $city,
		)
	);

	return new WP_REST_Response( array( 'stored' => true ), 201 );
}

/**
 * Email the enquiry to whoever answers the phone.
 *
 * @param string $name  Enquirer's name.
 * @param string $phone Mobile number.
 * @param string $city  City.
 * @param string $body  Formatted detail block.
 * @return void
 */
function brandsure_enquiry_notify( $name, $phone, $city, $body ) {
	/**
	 * Filter the addresses a callback request is sent to.
	 *
	 * @param string[] $to Recipient addresses.
	 */
	$to = apply_filters( 'brandsure_enquiry_recipients', array( get_option( 'admin_email' ) ) );
	$to = array_values( array_filter( array_map( 'sanitize_email', (array) $to ) ) );

	if ( empty( $to ) ) {
		return;
	}

	wp_mail(
		$to,
		sprintf(
			/* translators: 1: name, 2: city. */
			__( 'Callback request: %1$s (%2$s)', 'brandsure-services' ),
			$name,
			$city
		),
		sprintf(
			"%s\n\n%s\n",
			__( 'A callback request came in from the BrandSure website.', 'brandsure-services' ),
			$body
		)
	);
}

/**
 * Columns that make the enquiry list usable at a glance.
 *
 * @param array<string,string> $columns Existing columns.
 * @return array<string,string>
 */
function brandsure_enquiry_columns( $columns ) {
	return array(
		'cb'                => isset( $columns['cb'] ) ? $columns['cb'] : '',
		'title'             => __( 'Enquiry', 'brandsure-services' ),
		'brandsure_phone'   => __( 'Mobile', 'brandsure-services' ),
		'brandsure_city'    => __( 'City', 'brandsure-services' ),
		'date'              => __( 'Received', 'brandsure-services' ),
	);
}
add_filter( 'manage_' . BRANDSURE_ENQUIRY_TYPE . '_posts_columns', 'brandsure_enquiry_columns' );

/**
 * Fill the custom columns.
 *
 * @param string $column  Column key.
 * @param int    $post_id Enquiry ID.
 * @return void
 */
function brandsure_enquiry_column( $column, $post_id ) {
	if ( 'brandsure_phone' === $column ) {
		$phone = (string) get_post_meta( $post_id, '_brandsure_phone', true );
		if ( '' !== $phone ) {
			printf(
				'<a href="tel:%s">%s</a>',
				esc_attr( preg_replace( '/[^\d+]/', '', $phone ) ),
				esc_html( $phone )
			);
		}
	}
	if ( 'brandsure_city' === $column ) {
		echo esc_html( (string) get_post_meta( $post_id, '_brandsure_city', true ) );
	}
}
add_action( 'manage_' . BRANDSURE_ENQUIRY_TYPE . '_posts_custom_column', 'brandsure_enquiry_column', 10, 2 );
