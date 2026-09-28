<?php
/**
 * Content ownership — the registry and its locks.
 *
 * Override 4: there must never be two editable copies of the same content.
 * This file is what makes that structural rather than a convention someone
 * remembers. Three locks, and each closes a different route to duplication:
 *
 *   1. Editor lock    — a controlled page cannot be opened in the editor, so
 *                       nobody can type a second copy.
 *   2. Content lock   — a controlled page's post_content is forced empty on
 *                       save, so an importer or a direct database write
 *                       cannot leave one behind either.
 *   3. Template lock  — a controlled slug is bound to its template in code,
 *                       so the binding cannot be changed from wp-admin and
 *                       the page cannot be pointed at something that renders
 *                       post_content.
 *
 * The fourth check — a data file existing for an editable slug, or missing
 * for a controlled one — lives in guard.php and runs at deploy.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * The registry. Slug => ownership.
 *
 * The front page is the empty-string key. Every public page belongs to
 * exactly one class; a slug absent from this list is treated as editable,
 * which is the safe default — an unknown page keeps working in wp-admin
 * rather than becoming uneditable with no explanation.
 *
 * @return array<string,string>
 */
function bs_ownership_registry(): array {
	return array(
		// CONTROLLED — copy lives in data/, editor disabled.
		''                                        => 'controlled',
		'home'                                    => 'controlled',
		'plans'                                   => 'controlled',
		'digital-partner'                         => 'controlled',
		'growth-partnership'                      => 'controlled',
		'how-it-works'                            => 'controlled',
		'proof'                                   => 'controlled',
		'start'                                   => 'controlled',
		'built-by-brandsure'                      => 'controlled',
		'services'                                => 'controlled',

		// EDITABLE — copy lives in WordPress, no data file may exist.
		'about'                                   => 'editable',
		'furniture-growth-marketing-india'        => 'editable',
		'furniture-business-consulting-india'     => 'editable',
		'furniture-product-design-services-india' => 'editable',
		'furniture-labour-hiring-india'           => 'editable',
		'privacy-policy'                          => 'editable',
		'terms-and-conditions'                    => 'editable',
		'cookie-policy'                           => 'editable',
		'disclaimer'                              => 'editable',
	);
}

/**
 * Slug => template file, relative to the theme.
 *
 * A slug listed here is bound to its template in code (lock 3). Slugs whose
 * template is not yet built are absent, and fall through to WordPress's
 * normal resolution until their chat lands.
 *
 * Editable slugs may be bound too, and the four service pages are. Binding a
 * template is not the same lock as disabling the editor: those pages stay
 * fully editable, but their enquiry path and their Growth Partnership bridge
 * live in the template rather than in the content, so neither can be lost by
 * an edit or by someone switching the page to a different layout.
 *
 * @return array<string,string>
 */
function bs_ownership_templates(): array {
	return array(
		// The front page resolves through front-page.php, which delegates to
		// the same template. This entry covers the page when it is reached at
		// /home/ instead.
		'home'               => 'templates/page-home.php',
		'plans'              => 'templates/page-plans.php',
		'digital-partner'    => 'templates/page-digital-partner.php',
		'growth-partnership' => 'templates/page-growth-partnership.php',
		'how-it-works'       => 'templates/page-how-it-works.php',
		'proof'              => 'templates/page-proof.php',
		'start'              => 'templates/page-start.php',
		'built-by-brandsure' => 'templates/page-built-by.php',
		'services'           => 'templates/page-services-index.php',

		// Editable pages, bound so the enquiry path and the bridge always render.
		'furniture-growth-marketing-india'        => 'templates/page-service.php',
		'furniture-business-consulting-india'     => 'templates/page-service.php',
		'furniture-product-design-services-india' => 'templates/page-service.php',
		'furniture-labour-hiring-india'           => 'templates/page-service.php',
	);
}

/**
 * The slug for a post, normalised the way the registry expects.
 *
 * @param int|WP_Post|null $post Post or id. Defaults to the current post.
 * @return string Empty string for the front page.
 */
function bs_slug( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	if ( (int) get_option( 'page_on_front' ) === $post->ID ) {
		return '';
	}

	return (string) $post->post_name;
}

/**
 * Is this page developer-owned?
 *
 * @param int|WP_Post|null $post Post or id.
 * @return bool
 */
function bs_is_controlled( $post = null ): bool {
	$post = get_post( $post );

	// Posts are always editable. Only pages are ever controlled.
	if ( ! $post instanceof WP_Post || 'page' !== $post->post_type ) {
		return false;
	}

	$registry = bs_ownership_registry();
	$slug     = bs_slug( $post );

	return isset( $registry[ $slug ] ) && 'controlled' === $registry[ $slug ];
}

/* -------------------------------------------------------------------------
 * Lock 1 — the editor
 * ---------------------------------------------------------------------- */

/**
 * Disable the block editor on a controlled page.
 *
 * @param bool    $use  Whether to use the block editor.
 * @param WP_Post $post The post.
 * @return bool
 */
function bs_lock_block_editor( bool $use, $post ): bool {
	return bs_is_controlled( $post ) ? false : $use;
}
add_filter( 'use_block_editor_for_post', 'bs_lock_block_editor', 10, 2 );

/**
 * Disable the classic visual editor too, so disabling blocks does not simply
 * hand the page to TinyMCE instead.
 *
 * @param bool $can Whether the user can use rich editing.
 * @return bool
 */
function bs_lock_rich_edit( bool $can ): bool {
	global $post;
	return bs_is_controlled( $post ) ? false : $can;
}
add_filter( 'user_can_richedit', 'bs_lock_rich_edit' );

/**
 * Remove the editor UI entirely on controlled pages.
 */
function bs_lock_editor_support(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	global $post;

	if ( bs_is_controlled( $post ) ) {
		remove_post_type_support( 'page', 'editor' );
		remove_meta_box( 'pageparentdiv', 'page', 'side' );
	}
}
add_action( 'admin_head', 'bs_lock_editor_support' );

/**
 * Tell the editor where the copy actually lives.
 *
 * A lock without an explanation just reads as a broken screen. Naming the
 * file turns "I can't edit this" into "I know who to ask and what to ask for".
 */
function bs_lock_notice(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type || 'post' !== $screen->base ) {
		return;
	}

	global $post;

	if ( ! bs_is_controlled( $post ) ) {
		return;
	}

	$slug = bs_slug( $post );
	$file = 'data/pages/' . ( '' === $slug ? 'home' : $slug ) . '.json';

	printf(
		'<div class="notice notice-info"><p><strong>%s</strong> %s <code>%s</code>. %s</p></div>',
		esc_html__( 'This page is managed by the development team.', 'brandsure-v2' ),
		esc_html__( 'Its wording lives in', 'brandsure-v2' ),
		esc_html( $file ),
		esc_html__(
			'It is kept there so the same words cannot exist in two places and drift apart. To change this page, send the new wording to the development team. Service pages, About, blog posts and the legal pages are all editable here as normal.',
			'brandsure-v2'
		)
	);
}
add_action( 'admin_notices', 'bs_lock_notice' );

/* -------------------------------------------------------------------------
 * Lock 2 — the content
 * ---------------------------------------------------------------------- */

/**
 * Force a controlled page's post_content empty on save.
 *
 * Lock 1 stops a person typing a second copy. This stops everything else: an
 * importer, a migration script, a REST call, a plugin. If the field is always
 * empty then there is nothing for a template to accidentally render, and no
 * second copy can exist even in principle.
 *
 * @param array $data    Sanitised post data about to be written.
 * @param array $postarr Raw post data.
 * @return array
 */
function bs_lock_content( array $data, array $postarr ): array {
	if ( 'page' !== ( $data['post_type'] ?? '' ) ) {
		return $data;
	}

	$registry = bs_ownership_registry();
	$slug     = (string) ( $data['post_name'] ?? '' );

	// On the very first save WordPress may not have generated the slug yet, so
	// fall back to the title's slug form to catch a page created by hand.
	if ( '' === $slug && ! empty( $data['post_title'] ) ) {
		$slug = sanitize_title( $data['post_title'] );
	}

	$front = (int) get_option( 'page_on_front' );
	if ( $front && $front === (int) ( $postarr['ID'] ?? 0 ) ) {
		$slug = '';
	}

	if ( isset( $registry[ $slug ] ) && 'controlled' === $registry[ $slug ] ) {
		$data['post_content'] = '';
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'bs_lock_content', 10, 2 );

/* -------------------------------------------------------------------------
 * Lock 3 — the template
 * ---------------------------------------------------------------------- */

/**
 * Bind a controlled slug to its template in code.
 *
 * Not via the page-attributes dropdown, which an admin can change, and not
 * via the slug-based template hierarchy, which a slug edit can break.
 *
 * @param string $template Template WordPress resolved to.
 * @return string
 */
function bs_lock_template( string $template ): string {
	/*
	 * The blog templates live in templates/ with everything else rather than
	 * as home.php and single.php at the theme root. WordPress's hierarchy
	 * cannot find them there, so they are routed explicitly — one directory
	 * holding every page template is worth one filter.
	 */
	if ( is_home() && is_readable( BRANDSURE_DIR . '/templates/archive-blog.php' ) ) {
		return BRANDSURE_DIR . '/templates/archive-blog.php';
	}

	if ( is_singular( 'post' ) && is_readable( BRANDSURE_DIR . '/templates/single-post.php' ) ) {
		return BRANDSURE_DIR . '/templates/single-post.php';
	}

	if ( ! is_page() ) {
		return $template;
	}

	$slug = bs_slug( get_queried_object() );
	$map  = bs_ownership_templates();

	if ( ! isset( $map[ $slug ] ) ) {
		return $template;
	}

	$bound = BRANDSURE_DIR . '/' . $map[ $slug ];

	return is_readable( $bound ) ? $bound : $template;
}
add_filter( 'template_include', 'bs_lock_template', 20 );

/**
 * Hide the page-template dropdown for controlled pages, so the admin is not
 * offered a choice that lock 3 would silently ignore.
 *
 * @param array $templates Page templates.
 * @return array
 */
function bs_lock_template_dropdown( array $templates ): array {
	global $post;
	return bs_is_controlled( $post ) ? array() : $templates;
}
add_filter( 'theme_page_templates', 'bs_lock_template_dropdown' );
