<?php
/**
 * Plugin Name:       BrandSure Site Infrastructure
 * Description:       Shared styles, behaviour, SEO tags and schema for the BrandSure service pages, the services hub / how-it-works / pricing pages, the blog templates (native WordPress Posts), the company/legal pages, the About page's Instagram gallery and team photograph, and the single global header used by every page. Also repairs the .html links and escaped scripts left behind in pages that were pasted in as whole static documents. Keeps one copy of the CSS/JS instead of one per page, and leaves all copy editable in the block editor.
 * Version:           1.7.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            BrandSure Consultancy Pvt. Ltd.
 * License:           GPL-2.0-or-later
 * Text Domain:       brandsure-services
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

define( 'BRANDSURE_SERVICES_VERSION', '1.7.0' );
define( 'BRANDSURE_SERVICES_FILE', __FILE__ );
define( 'BRANDSURE_SERVICES_DIR', plugin_dir_path( __FILE__ ) );
define( 'BRANDSURE_SERVICES_URL', plugin_dir_url( __FILE__ ) );

/**
 * SEO and schema defaults extracted from the original static pages.
 *
 * @return array<string,array<string,mixed>>
 */
function brandsure_services_defaults() {
	static $defaults = null;
	if ( null === $defaults ) {
		$defaults = require BRANDSURE_SERVICES_DIR . 'seo-defaults.php';
	}
	return $defaults;
}

/**
 * Slugs of the four service pages, in menu order.
 *
 * @return string[]
 */
function brandsure_services_slugs() {
	return array_keys( brandsure_services_defaults() );
}

/**
 * The service slug for the page being viewed, or '' when this is not one.
 *
 * @return string
 */
function brandsure_services_current_slug() {
	if ( ! is_page() ) {
		return '';
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return '';
	}
	return in_array( $post->post_name, brandsure_services_slugs(), true ) ? $post->post_name : '';
}

/* -------------------------------------------------------------------------
 * Assets — enqueued once, only on the four service pages.
 * ---------------------------------------------------------------------- */

/**
 * Enqueue the shared service-page stylesheet and script.
 *
 * @return void
 */
function brandsure_services_enqueue() {
	$slug = brandsure_services_current_slug();
	if ( '' === $slug ) {
		return;
	}

	$css = 'assets/brandsure-services.css';
	$js  = 'assets/brandsure-services.js';

	wp_enqueue_style(
		'brandsure-services',
		BRANDSURE_SERVICES_URL . $css,
		array(),
		brandsure_services_asset_version( $css )
	);

	wp_enqueue_script(
		'brandsure-services',
		BRANDSURE_SERVICES_URL . $js,
		array(),
		brandsure_services_asset_version( $js ),
		true
	);

	$defaults = brandsure_services_defaults();

	// The static pages carried these on <body data-page-service data-page-slug>.
	// The body tag belongs to the theme here, so hand the values to the script.
	wp_add_inline_script(
		'brandsure-services',
		'window.BrandSureService=' . wp_json_encode(
			array(
				'service' => $defaults[ $slug ]['service'],
				'slug'    => $slug,
			)
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_services_enqueue' );

/**
 * File-modification-time asset version so caches break on deploy.
 *
 * @param string $relative Path relative to the plugin directory.
 * @return string
 */
function brandsure_services_asset_version( $relative ) {
	$path = BRANDSURE_SERVICES_DIR . $relative;
	$time = file_exists( $path ) ? filemtime( $path ) : false;
	return $time ? (string) $time : BRANDSURE_SERVICES_VERSION;
}

/* -------------------------------------------------------------------------
 * SEO — editable per page, with the original values as fallback.
 * ---------------------------------------------------------------------- */

/**
 * Whether a dedicated SEO plugin is managing the document head.
 *
 * When one is active it owns the title, description and canonical, so this
 * plugin stays out of the way rather than emitting a competing second tag.
 *
 * @return bool
 */
function brandsure_services_seo_plugin_active() {
	$owns_head = defined( 'WPSEO_VERSION' )                 // Yoast SEO.
		|| class_exists( 'RankMath' )                        // Rank Math.
		|| defined( 'AIOSEO_VERSION' )                       // All in One SEO.
		|| defined( 'SEOPRESS_VERSION' )                     // SEOPress.
		|| class_exists( 'The_SEO_Framework\\Load' );        // The SEO Framework.

	/**
	 * Filter whether an SEO plugin owns the document head.
	 *
	 * @param bool $owns_head Detected state.
	 */
	return (bool) apply_filters( 'brandsure_services_seo_plugin_active', $owns_head );
}

/**
 * Resolve one SEO field for a service page: saved value first, then default.
 *
 * @param int    $post_id Page ID.
 * @param string $slug    Service slug.
 * @param string $field   Field key.
 * @return string
 */
function brandsure_services_seo_field( $post_id, $slug, $field ) {
	$saved = get_post_meta( $post_id, '_brandsure_' . $field, true );
	if ( is_string( $saved ) && '' !== trim( $saved ) ) {
		return trim( $saved );
	}
	$defaults = brandsure_services_defaults();
	return isset( $defaults[ $slug ][ $field ] ) ? (string) $defaults[ $slug ][ $field ] : '';
}

/**
 * Use the SEO title as the document <title> when no SEO plugin is active.
 *
 * @param string $title Incoming title.
 * @return string
 */
function brandsure_services_document_title( $title ) {
	$slug = brandsure_services_current_slug();
	if ( '' === $slug || brandsure_services_seo_plugin_active() ) {
		return $title;
	}
	$custom = brandsure_services_seo_field( get_queried_object_id(), $slug, 'title' );
	return '' !== $custom ? $custom : $title;
}
add_filter( 'pre_get_document_title', 'brandsure_services_document_title' );

/**
 * Emit description, robots, canonical, Open Graph and Twitter tags.
 *
 * @return void
 */
function brandsure_services_head_meta() {
	$slug = brandsure_services_current_slug();
	if ( '' === $slug || brandsure_services_seo_plugin_active() ) {
		return;
	}

	$id  = get_queried_object_id();
	$get = static function ( $field ) use ( $id, $slug ) {
		return brandsure_services_seo_field( $id, $slug, $field );
	};

	$canonical = $get( 'canonical' );
	$canonical = '' !== $canonical ? $canonical : get_permalink( $id );

	$tags = array(
		array( 'name', 'description', $get( 'description' ) ),
		array( 'name', 'robots', $get( 'robots' ) ),
		array( 'property', 'og:type', 'website' ),
		array( 'property', 'og:site_name', get_bloginfo( 'name' ) ),
		array( 'property', 'og:title', $get( 'og_title' ) ),
		array( 'property', 'og:description', $get( 'og_description' ) ),
		array( 'property', 'og:url', $canonical ),
		array( 'property', 'og:image', $get( 'og_image' ) ),
		array( 'property', 'og:locale', 'en_IN' ),
		array( 'name', 'twitter:card', 'summary_large_image' ),
		array( 'name', 'twitter:title', $get( 'twitter_title' ) ),
		array( 'name', 'twitter:description', $get( 'twitter_description' ) ),
	);

	echo "\n<!-- BrandSure service page SEO -->\n";
	printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $canonical ) );
	foreach ( $tags as $tag ) {
		list( $attr, $key, $value ) = $tag;
		if ( '' === $value ) {
			continue;
		}
		printf(
			"<meta %s=\"%s\" content=\"%s\">\n",
			esc_attr( $attr ),
			esc_attr( $key ),
			esc_attr( $value )
		);
	}
}
add_action( 'wp_head', 'brandsure_services_head_meta', 1 );

/**
 * Output the page's JSON-LD graph, with URLs pointed at the live permalinks.
 *
 * @return void
 */
function brandsure_services_schema() {
	$slug = brandsure_services_current_slug();
	if ( '' === $slug ) {
		return;
	}

	$defaults = brandsure_services_defaults();
	if ( empty( $defaults[ $slug ]['jsonld'] ) ) {
		return;
	}

	/**
	 * Filter the JSON-LD graph for a BrandSure service page.
	 *
	 * @param array  $graph Schema graph.
	 * @param string $slug  Service slug.
	 */
	$graph = apply_filters(
		'brandsure_services_schema',
		$defaults[ $slug ]['jsonld'],
		$slug
	);

	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'brandsure_services_schema', 2 );

/* -------------------------------------------------------------------------
 * Admin — a native metabox for the SEO fields. No extra plugin needed.
 * ---------------------------------------------------------------------- */

/**
 * Editable SEO fields and their labels.
 *
 * @return array<string,string>
 */
function brandsure_services_editable_fields() {
	return array(
		'title'       => __( 'SEO title (document <title>)', 'brandsure-services' ),
		'description' => __( 'Meta description', 'brandsure-services' ),
		'canonical'   => __( 'Canonical URL', 'brandsure-services' ),
	);
}

/**
 * Register the SEO metabox on the four service pages.
 *
 * @param string  $post_type Current post type.
 * @param WP_Post $post      Current post.
 * @return void
 */
function brandsure_services_add_metabox( $post_type, $post ) {
	if ( 'page' !== $post_type || ! $post instanceof WP_Post ) {
		return;
	}
	if ( ! in_array( $post->post_name, brandsure_services_slugs(), true ) ) {
		return;
	}
	add_meta_box(
		'brandsure-services-seo',
		__( 'BrandSure service page SEO', 'brandsure-services' ),
		'brandsure_services_render_metabox',
		'page',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'brandsure_services_add_metabox', 10, 2 );

/**
 * Render the SEO metabox.
 *
 * @param WP_Post $post Current post.
 * @return void
 */
function brandsure_services_render_metabox( $post ) {
	$defaults = brandsure_services_defaults();
	$slug     = $post->post_name;

	wp_nonce_field( 'brandsure_services_seo', 'brandsure_services_seo_nonce' );

	echo '<p style="margin-top:0">';
	esc_html_e(
		'Leave a field empty to use the original value from the launch version (shown as the placeholder).',
		'brandsure-services'
	);
	echo '</p>';

	foreach ( brandsure_services_editable_fields() as $field => $label ) {
		$value       = get_post_meta( $post->ID, '_brandsure_' . $field, true );
		$placeholder = isset( $defaults[ $slug ][ $field ] ) ? $defaults[ $slug ][ $field ] : '';
		$input_id    = 'brandsure_' . $field;

		printf(
			'<p><label for="%1$s"><strong>%2$s</strong></label><br>',
			esc_attr( $input_id ),
			esc_html( $label )
		);

		if ( 'description' === $field ) {
			printf(
				'<textarea id="%1$s" name="%1$s" rows="3" class="widefat" placeholder="%2$s">%3$s</textarea>',
				esc_attr( $input_id ),
				esc_attr( $placeholder ),
				esc_textarea( $value )
			);
		} else {
			printf(
				'<input type="text" id="%1$s" name="%1$s" class="widefat" placeholder="%2$s" value="%3$s">',
				esc_attr( $input_id ),
				esc_attr( $placeholder ),
				esc_attr( $value )
			);
		}
		echo '</p>';
	}
}

/**
 * Persist the SEO metabox values.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 * @return void
 */
function brandsure_services_save_metabox( $post_id, $post ) {
	if ( ! isset( $_POST['brandsure_services_seo_nonce'] ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['brandsure_services_seo_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'brandsure_services_seo' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! $post instanceof WP_Post || ! in_array( $post->post_name, brandsure_services_slugs(), true ) ) {
		return;
	}

	foreach ( array_keys( brandsure_services_editable_fields() ) as $field ) {
		$key = 'brandsure_' . $field;
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw = wp_unslash( $_POST[ $key ] );
		if ( 'canonical' === $field ) {
			$value = esc_url_raw( trim( $raw ) );
		} else {
			$value = sanitize_text_field( $raw );
		}

		if ( '' === $value ) {
			delete_post_meta( $post_id, '_brandsure_' . $field );
		} else {
			update_post_meta( $post_id, '_brandsure_' . $field, $value );
		}
	}
}
add_action( 'save_post_page', 'brandsure_services_save_metabox', 10, 2 );

require_once BRANDSURE_SERVICES_DIR . 'includes/class-header.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-copy.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-blog.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-legal.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-footer.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-social.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-mainsite.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-enquiry.php';
require_once BRANDSURE_SERVICES_DIR . 'includes/class-links.php';

/**
 * Every page this plugin knows how to create, keyed by slug.
 *
 * Used only by the WP-CLI migration; nothing here runs on a page load.
 *
 * @return array<string,array{title:string,content:string,excerpt:string,group:string}>
 */
function brandsure_managed_pages() {
	$pages = array();

	foreach ( brandsure_services_defaults() as $slug => $data ) {
		$pages[ $slug ] = array(
			'title'   => $data['service'],
			'content' => 'content/' . $slug . '.blocks.html',
			'excerpt' => isset( $data['description'] ) ? $data['description'] : '',
			'group'   => 'service',
		);
	}

	foreach ( brandsure_main_pages() as $slug => $data ) {
		$pages[ $slug ] = array(
			'title'   => $data['title'],
			'content' => 'content/' . $slug . '.blocks.html',
			'excerpt' => '',
			'group'   => 'main',
		);
	}

	// The listing is rendered by the blog template, so the page body stays
	// empty; the excerpt is used as the archive intro.
	$pages[ BRANDSURE_BLOG_SLUG ] = array(
		'title'   => __( 'Blog', 'brandsure-services' ),
		'content' => '',
		'excerpt' => __( 'Notes on growth, product, pricing and operations for Indian furniture manufacturers and showroom-led furniture businesses.', 'brandsure-services' ),
		'group'   => 'blog',
	);

	foreach ( brandsure_legal_pages() as $slug => $title ) {
		$pages[ $slug ] = array(
			'title'   => $title,
			'content' => 'content/' . $slug . '.blocks.html',
			'excerpt' => '',
			'group'   => 'legal',
		);
	}

	return $pages;
}

/* -------------------------------------------------------------------------
 * One-time migration — WP-CLI only, so nothing runs on a normal page load.
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once BRANDSURE_SERVICES_DIR . 'includes/class-cli-command.php';
	WP_CLI::add_command( 'brandsure services', 'BrandSure_Services_CLI' );
}
