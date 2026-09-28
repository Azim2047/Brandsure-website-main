<?php
/**
 * Data loader — reads, validates and caches data/*.json.
 *
 * This is the only code that opens a file in data/. Templates and components
 * call bs_data(), bs_site() or bs_fact(); they never touch the filesystem.
 *
 * Two guarantees this file makes, and the reasons for them:
 *
 * 1. Keys beginning with an underscore are stripped at load. Those are the
 *    editorial notes inside the JSON ("_comment", "_note", "_id_note"). They
 *    carry build context, sometimes including why a value is deliberately
 *    empty, and must never reach markup. Stripping them here means no
 *    component has to remember to skip them.
 *
 * 2. A missing or malformed file is loud under WP_DEBUG and silent in
 *    production. A content mistake should stop a developer immediately and
 *    should never take the public site down.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Load a data file.
 *
 * @param string $key Path under data/, without the extension. 'site',
 *                    'commercial', 'pages/home'.
 * @return array Parsed data, or an empty array if the file is missing or bad.
 */
function bs_data( string $key ): array {
	static $memo = array();

	if ( isset( $memo[ $key ] ) ) {
		return $memo[ $key ];
	}

	// Only word characters, dashes and a single directory level. This is
	// belt-and-braces: every caller passes a literal, but the helper must not
	// become a file-read primitive if one day a caller passes a variable.
	if ( ! preg_match( '#^[a-z0-9\-]+(/[a-z0-9\-]+)?$#', $key ) ) {
		bs_data_fail( sprintf( 'Refused an unsafe data key: %s', $key ) );
		return array();
	}

	$transient = 'bs_data_' . md5( $key . '|' . BRANDSURE_VERSION );

	if ( ! bs_data_is_dev() ) {
		$cached = get_transient( $transient );
		if ( is_array( $cached ) ) {
			$memo[ $key ] = $cached;
			return $cached;
		}
	}

	$path = BRANDSURE_DIR . '/data/' . $key . '.json';

	if ( ! is_readable( $path ) ) {
		bs_data_fail( sprintf( 'Missing data file: data/%s.json', $key ) );
		$memo[ $key ] = array();
		return array();
	}

	$raw    = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$parsed = json_decode( (string) $raw, true );

	if ( ! is_array( $parsed ) ) {
		bs_data_fail(
			sprintf( 'Malformed JSON in data/%s.json — %s', $key, json_last_error_msg() )
		);
		$memo[ $key ] = array();
		return array();
	}

	$parsed = bs_data_strip_notes( $parsed );

	if ( str_starts_with( $key, 'pages/' ) ) {
		$parsed = bs_data_validate_page( $key, $parsed );
	}

	if ( ! bs_data_is_dev() ) {
		set_transient( $transient, $parsed, DAY_IN_SECONDS );
	}

	$memo[ $key ] = $parsed;
	return $parsed;
}

/**
 * Read a value from site.json by dot path.
 *
 * @param string $path    Dot path, e.g. 'contact.whatsapp_number'.
 * @param mixed  $default Returned when the path is absent or empty.
 * @return mixed
 */
function bs_site( string $path, $default = '' ) {
	$node = bs_data( 'site' );

	foreach ( explode( '.', $path ) as $segment ) {
		if ( ! is_array( $node ) || ! array_key_exists( $segment, $node ) ) {
			return $default;
		}
		$node = $node[ $segment ];
	}

	// An empty string in site.json is an unresolved input (docs/OPEN-ITEMS.md),
	// not a value. Callers get the default and decide how to degrade.
	if ( '' === $node || array() === $node ) {
		return $default;
	}

	return $node;
}

/**
 * Read a public commercial fact.
 *
 * Only facts marked public:true are ever returned. data/commercial.json holds
 * nothing else, so this is a second lock on an already-locked door — but the
 * cost is one comparison and the failure it prevents is the one failure this
 * project cannot have.
 *
 * @param string $key   Fact key, e.g. 'digital_partner_monthly_fee'.
 * @param string $field 'value' or 'note'.
 * @return mixed Empty string when absent or not public.
 */
function bs_fact( string $key, string $field = 'value' ) {
	$facts = bs_data( 'commercial' );

	if ( ! isset( $facts[ $key ] ) || ! is_array( $facts[ $key ] ) ) {
		bs_data_fail( sprintf( 'Unknown commercial fact: %s', $key ) );
		return '';
	}

	if ( empty( $facts[ $key ]['public'] ) ) {
		bs_data_fail( sprintf( 'Refused a non-public commercial fact: %s', $key ) );
		return '';
	}

	return $facts[ $key ][ $field ] ?? '';
}

/**
 * Recursively remove underscore-prefixed keys.
 *
 * @param array $node Parsed JSON node.
 * @return array
 */
function bs_data_strip_notes( array $node ): array {
	$clean = array();

	foreach ( $node as $key => $value ) {
		if ( is_string( $key ) && str_starts_with( $key, '_' ) ) {
			continue;
		}
		$clean[ $key ] = is_array( $value ) ? bs_data_strip_notes( $value ) : $value;
	}

	return $clean;
}

/**
 * Validate a page file's section list.
 *
 * Every section needs a type, and every type needs a component that can
 * render it. Catching that here turns "the section silently vanished" into a
 * named error at the moment the data file is edited.
 *
 * @param string $key    Data key, for the error message.
 * @param array  $parsed Parsed page data.
 * @return array The page data, with unrenderable sections dropped.
 */
function bs_data_validate_page( string $key, array $parsed ): array {
	if ( ! isset( $parsed['sections'] ) || ! is_array( $parsed['sections'] ) ) {
		// A page file with no sections is legitimate only while the page is
		// still to be built, so this is a notice rather than a failure.
		$parsed['sections'] = array();
		return $parsed;
	}

	$kept = array();

	foreach ( $parsed['sections'] as $index => $section ) {
		if ( ! is_array( $section ) || empty( $section['type'] ) ) {
			bs_data_fail( sprintf( 'data/%s.json section %d has no type', $key, $index ) );
			continue;
		}

		$type = (string) $section['type'];

		if ( ! preg_match( '/^[a-z0-9\-]+$/', $type ) ) {
			bs_data_fail( sprintf( 'data/%s.json section %d has an invalid type: %s', $key, $index, $type ) );
			continue;
		}

		if ( ! is_readable( BRANDSURE_DIR . '/components/' . $type . '.php' ) ) {
			bs_data_fail(
				sprintf( 'data/%s.json section %d needs components/%s.php, which does not exist', $key, $index, $type )
			);
			continue;
		}

		$kept[] = $section;
	}

	$parsed['sections'] = $kept;
	return $parsed;
}

/**
 * Render a page's sections by handing each to its component.
 *
 * This is the entire body of every controlled page template. A template that
 * does more than call this is doing a component's job.
 *
 * @param string $key Page data key, e.g. 'pages/home'.
 */
function bs_render_sections( string $key ): void {
	$page = bs_data( $key );

	foreach ( $page['sections'] as $section ) {
		get_template_part( 'components/' . $section['type'], null, $section );
	}
}

/**
 * Are we in a context where a data error should be shouted about?
 *
 * @return bool
 */
function bs_data_is_dev(): bool {
	return ( defined( 'WP_DEBUG' ) && WP_DEBUG );
}

/**
 * Report a data problem.
 *
 * Loud in development, logged and swallowed in production.
 *
 * @param string $message Human-readable description.
 */
function bs_data_fail( string $message ): void {
	if ( bs_data_is_dev() ) {
		trigger_error( 'BrandSure data: ' . esc_html( $message ), E_USER_WARNING ); // phpcs:ignore
		return;
	}

	error_log( 'BrandSure data: ' . $message ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions
}

/**
 * Drop the data transients. Runs on theme switch and after an update, so a
 * deploy never serves the previous release's copy.
 */
function bs_data_flush(): void {
	global $wpdb;

	$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_bs_data_%'
		 OR option_name LIKE '_transient_timeout_bs_data_%'"
	);
}
add_action( 'after_switch_theme', 'bs_data_flush' );
add_action( 'upgrader_process_complete', 'bs_data_flush' );
