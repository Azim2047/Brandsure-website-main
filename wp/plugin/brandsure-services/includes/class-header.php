<?php
/**
 * The global BrandSure header — one component, every public page.
 *
 * SINGLE SOURCE OF TRUTH: brandsure_render_header(). Nothing else prints
 * navigation. The menu, its order, its links and the dropdown are defined once
 * in brandsure_nav_items() and cannot vary per template.
 *
 * The theme is not editable from here, so the component is attached with
 * wp_body_open (the standard hook a header.php-era theme fires right after
 * <body>), with an automatic wp_footer fallback for a theme that does not fire
 * it. When theme files become editable, a real header.php should simply call
 * brandsure_render_header() — the source of truth does not move.
 *
 * Legacy per-page navigation markup is suppressed in the content, so no page
 * renders two headers.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/** The existing BrandSure WhatsApp destination, defined once. */
const BRANDSURE_WA_URL = 'https://wa.me/918884446668?text=Hi%20BrandSure.%20I%20am%20a%20furniture%20manufacturer.%20I%20want%20to%20discuss%20my%20business.';

/**
 * The primary menu: exactly five entries, in order.
 *
 * @return array<int,array<string,mixed>>
 */
function brandsure_nav_items() {
	$items = array(
		array(
			'label' => __( 'How It Works', 'brandsure-services' ),
			'url'   => home_url( '/how-it-works/' ),
			'slugs' => array( 'how-it-works' ),
		),
		array(
			'label' => __( 'Prices', 'brandsure-services' ),
			'url'   => home_url( '/pricing/' ),
			'slugs' => array( 'pricing' ),
		),
		array(
			'label'    => __( 'Other Services', 'brandsure-services' ),
			'url'      => home_url( '/services/' ),
			'slugs'    => array(
				'services',
				'furniture-growth-marketing-india',
				'furniture-business-consulting-india',
				'furniture-product-design-services-india',
				'furniture-labour-hiring-india',
			),
			'children' => array(
				array(
					'label' => __( 'All Other Services', 'brandsure-services' ),
					'url'   => home_url( '/services/' ),
					'desc'  => __( 'Everything BrandSure supports', 'brandsure-services' ),
				),
				array(
					'label' => __( 'Growth &amp; Marketing', 'brandsure-services' ),
					'url'   => home_url( '/furniture-growth-marketing-india/' ),
					'desc'  => __( 'More enquiries and orders for your furniture', 'brandsure-services' ),
				),
				array(
					'label' => __( 'Business Consulting', 'brandsure-services' ),
					'url'   => home_url( '/furniture-business-consulting-india/' ),
					'desc'  => __( 'Pricing, showroom, production, profit', 'brandsure-services' ),
				),
				array(
					'label' => __( 'Product Design', 'brandsure-services' ),
					'url'   => home_url( '/furniture-product-design-services-india/' ),
					'desc'  => __( 'From reference photo to factory-ready', 'brandsure-services' ),
				),
				array(
					'label' => __( 'Labour Hiring Help', 'brandsure-services' ),
					'url'   => home_url( '/furniture-labour-hiring-india/' ),
					'desc'  => __( 'Find sofa carpenters, upholsterers &amp; teams', 'brandsure-services' ),
				),
			),
		),
		array(
			'label' => __( 'About the Team', 'brandsure-services' ),
			'url'   => home_url( '/about/' ),
			'slugs' => array( 'about' ),
		),
		array(
			'label'    => __( 'Talk to Us', 'brandsure-services' ),
			'url'      => BRANDSURE_WA_URL,
			'slugs'    => array(),
			'cta'      => true,
			'external' => true,
		),
	);

	/**
	 * Filter the global primary menu.
	 *
	 * @param array $items Menu definition.
	 */
	return (array) apply_filters( 'brandsure_nav_items', $items );
}

/**
 * Whether a menu entry represents the page being viewed.
 *
 * @param array $item Menu entry.
 * @return bool
 */
function brandsure_nav_is_active( $item ) {
	if ( empty( $item['slugs'] ) || ! is_page() ) {
		return false;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return false;
	}
	return in_array( $post->post_name, (array) $item['slugs'], true );
}

/**
 * Whether the current page embeds the old per-page header markup.
 *
 * The legacy nav must be suppressed wherever this is true.
 *
 * @return bool
 */
function brandsure_nav_page_has_legacy_header() {
	return false !== strpos( brandsure_nav_page_content(), '<header class="nav"' );
}

/**
 * The stored body of the page being viewed, or '' when there is not one.
 *
 * @return string
 */
function brandsure_nav_page_content() {
	if ( ! is_singular() ) {
		return '';
	}
	$post = get_queried_object();
	return $post instanceof WP_Post ? (string) $post->post_content : '';
}

/**
 * Whether the page opens with the dark full-bleed hero.
 *
 * Those heroes pad their own top by the header's 70px, so the header sits over
 * them: no spacer, and white nav text on the dark background until the hero
 * scrolls past. It is true of the pages that still embed the legacy header and
 * of the main-site templates, whose first section is .pagehero or .hero.
 *
 * The service pages are the exception: their .hero is a white band, so they
 * are handled by the fall-through below.
 *
 * @return bool
 */
function brandsure_nav_page_has_dark_hero() {
	if ( '' !== brandsure_main_current_slug() ) {
		return true;
	}
	return false !== strpos( brandsure_nav_page_content(), '<header class="nav"' );
}

/**
 * Whether the header should start in its light state.
 *
 * A page without the dark hero has a white background from the first pixel,
 * where white nav text would be invisible, so it starts light.
 *
 * @return bool
 */
function brandsure_nav_starts_light() {
	return ! brandsure_nav_page_has_dark_hero();
}

/**
 * The header's brand lockup: the real logo where one is available.
 *
 * Three sources, in order. The site's own Customizer logo comes first, because
 * that is where an owner expects to change it. Then the logo the site has been
 * serving since August, which is a genuine media-library upload — read off the
 * live pages, not invented. The lettermark is the last resort so the header is
 * never empty.
 *
 * @return void
 */
function brandsure_nav_brand() {
	$logo = '';

	$custom = get_theme_mod( 'custom_logo' );
	if ( $custom ) {
		$logo = (string) wp_get_attachment_image_url( (int) $custom, 'full' );
	}

	if ( '' === $logo ) {
		$attachment = attachment_url_to_postid( 'https://brandsure.in/wp-content/uploads/2026/04/brandsure-web-logo.png' );
		if ( $attachment ) {
			$logo = (string) wp_get_attachment_image_url( $attachment, 'full' );
		}
	}

	/**
	 * Filter the header logo URL. Return '' to fall back to the lettermark.
	 *
	 * @param string $logo Logo image URL.
	 */
	$logo = (string) apply_filters( 'brandsure_nav_logo', $logo );

	if ( '' !== $logo ) {
		printf(
			'<img class="bsnav__logo" src="%s" alt="%s" width="160" height="43" decoding="async">',
			esc_url( $logo ),
			esc_attr( get_bloginfo( 'name' ) )
		);
		return;
	}
	?>
		<span class="bsnav__mark" aria-hidden="true"><span>B</span></span>
		<span class="bsnav__name">
			<b>BrandSure</b>
			<i><?php esc_html_e( 'Furniture Growth Systems', 'brandsure-services' ); ?></i>
		</span>
	<?php
}

/**
 * Render the global header. The one place navigation is produced.
 *
 * @return void
 */
function brandsure_render_header() {
	if ( ! empty( $GLOBALS['brandsure_header_done'] ) ) {
		return;
	}
	$GLOBALS['brandsure_header_done'] = true;

	$items  = brandsure_nav_items();
	$light  = brandsure_nav_starts_light();
	$spacer = ! brandsure_nav_page_has_dark_hero();
	$classes = 'bsnav' . ( $light ? ' bsnav--light is-light' : '' );
	?>
<header class="<?php echo esc_attr( $classes ); ?>" id="bsnav" data-static-light="<?php echo $light ? '1' : '0'; ?>">
	<div class="bsnav__wrap">
		<a class="bsnav__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'BrandSure home', 'brandsure-services' ); ?>">
			<?php brandsure_nav_brand(); ?>
		</a>

		<nav class="bsnav__links" aria-label="<?php esc_attr_e( 'Primary', 'brandsure-services' ); ?>">
			<?php foreach ( $items as $i => $item ) : ?>
				<?php if ( ! empty( $item['children'] ) ) : ?>
					<div class="bsnav__dd" data-bsnav-dd>
						<button type="button" class="bsnav__ddbtn<?php echo brandsure_nav_is_active( $item ) ? ' is-active' : ''; ?>"
							aria-expanded="false" aria-controls="bsnav-dd-<?php echo (int) $i; ?>">
							<?php echo esc_html( wp_specialchars_decode( $item['label'] ) ); ?>
							<svg class="bsnav__car" width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</button>
						<div class="bsnav__ddmenu" id="bsnav-dd-<?php echo (int) $i; ?>">
							<?php foreach ( $item['children'] as $child ) : ?>
								<a href="<?php echo esc_url( $child['url'] ); ?>">
									<?php echo esc_html( wp_specialchars_decode( $child['label'] ) ); ?>
									<span><?php echo esc_html( wp_specialchars_decode( $child['desc'] ) ); ?></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php elseif ( ! empty( $item['cta'] ) ) : ?>
					<a class="bsnav__cta" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener" data-cta="nav">
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php else : ?>
					<a class="bsnav__link<?php echo brandsure_nav_is_active( $item ) ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $item['url'] ); ?>"
						<?php echo brandsure_nav_is_active( $item ) ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</nav>

		<button class="bsnav__burger" id="bsnav-burger" type="button"
			aria-label="<?php esc_attr_e( 'Open menu', 'brandsure-services' ); ?>"
			aria-expanded="false" aria-controls="bsnav-mobile"><i></i><i></i><i></i></button>
	</div>

	<div class="bsnav__mobile" id="bsnav-mobile">
		<div class="bsnav__wrap">
			<?php foreach ( $items as $i => $item ) : ?>
				<?php if ( ! empty( $item['children'] ) ) : ?>
					<div class="bsnav__msec">
						<button type="button" class="bsnav__mbtn<?php echo brandsure_nav_is_active( $item ) ? ' is-active' : ''; ?>"
							aria-expanded="false" aria-controls="bsnav-m-<?php echo (int) $i; ?>">
							<?php echo esc_html( wp_specialchars_decode( $item['label'] ) ); ?>
							<svg class="bsnav__car" width="10" height="6" viewBox="0 0 10 6" fill="none" aria-hidden="true"><path d="M1 1l4 4 4-4" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
						</button>
						<div class="bsnav__msub" id="bsnav-m-<?php echo (int) $i; ?>">
							<?php foreach ( $item['children'] as $child ) : ?>
								<a href="<?php echo esc_url( $child['url'] ); ?>"><?php echo esc_html( wp_specialchars_decode( $child['label'] ) ); ?></a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php elseif ( ! empty( $item['cta'] ) ) : ?>
					<a class="bsnav__mcta" href="<?php echo esc_url( $item['url'] ); ?>" target="_blank" rel="noopener" data-cta="nav">
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php else : ?>
					<a class="bsnav__mlink<?php echo brandsure_nav_is_active( $item ) ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $item['url'] ); ?>"
						<?php echo brandsure_nav_is_active( $item ) ? ' aria-current="page"' : ''; ?>>
						<?php echo esc_html( $item['label'] ); ?>
					</a>
				<?php endif; ?>
			<?php endforeach; ?>
		</div>
	</div>
</header>
	<?php
	if ( $spacer ) {
		// Templates without the legacy dark hero do not reserve room for a
		// fixed header, so the spacer keeps content out from under it.
		echo '<div class="bsnav-spacer" aria-hidden="true"></div>';
	}
}

/**
 * Preferred attachment point: immediately after <body>.
 *
 * @return void
 */
function brandsure_header_body_open() {
	if ( is_admin() || is_embed() || is_feed() ) {
		return;
	}
	brandsure_render_header();
}
add_action( 'wp_body_open', 'brandsure_header_body_open', 5 );

/**
 * Fallback for a theme that never fires wp_body_open.
 *
 * The header is fixed-position, so it renders identically from here; only DOM
 * order differs. wp_footer always runs after wp_body_open would have, so this
 * cannot double up.
 *
 * @return void
 */
function brandsure_header_footer_fallback() {
	if ( is_admin() || is_embed() || is_feed() ) {
		return;
	}
	if ( ! empty( $GLOBALS['brandsure_header_done'] ) ) {
		return;
	}
	brandsure_render_header();
}
add_action( 'wp_footer', 'brandsure_header_footer_fallback', 1 );

/**
 * Assets for the global header, loaded on every public page.
 *
 * @return void
 */
function brandsure_header_enqueue() {
	if ( is_admin() ) {
		return;
	}
	$css = 'assets/brandsure-nav.css';
	$js  = 'assets/brandsure-nav.js';

	wp_enqueue_style(
		'brandsure-nav',
		BRANDSURE_SERVICES_URL . $css,
		array(),
		brandsure_services_asset_version( $css )
	);
	wp_enqueue_script(
		'brandsure-nav',
		BRANDSURE_SERVICES_URL . $js,
		array(),
		brandsure_services_asset_version( $js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_header_enqueue' );

/**
 * Suppress legacy per-page navigation so no page renders two headers.
 *
 * The old markup is replaced by a hidden shell that keeps the element IDs the
 * legacy inline script looks up (nav, burger, dd, mnav). Without it that script
 * would throw on a null element and take the reveal animations, the sticky
 * WhatsApp pill and the click tracking down with it.
 *
 * Re-pasting a cleaned static file removes the legacy markup outright, at which
 * point this filter finds nothing to do.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_header_strip_legacy( $content ) {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}
	if ( false === strpos( $content, '<header class="nav"' ) ) {
		return $content;
	}

	// Every ID a legacy inline script on any pasted page looks up. Two
	// different pasted layouts exist: the .nav__in one (burger/dd/mnav) and
	// the homepage one (menu-toggle/.nav-links). A missing element makes the
	// script throw, and on the homepage that would stop the reveal observer
	// and leave every .reveal element at opacity:0 — a blank page.
	$stub = '<header class="nav bsnav-legacy" id="nav" hidden aria-hidden="true">'
		. '<button type="button" id="burger" tabindex="-1" aria-hidden="true"></button>'
		. '<button type="button" id="menu-toggle" tabindex="-1" aria-hidden="true"><span></span></button>'
		. '<div class="dd" id="dd"><button type="button" tabindex="-1" aria-hidden="true"></button></div>'
		. '<div class="mnav" id="mnav"></div>'
		. '<ul class="nav-links"></ul>'
		. '</header>';

	return preg_replace(
		'~<header class="nav".*?</header>~s',
		$stub,
		$content,
		1
	);
}
add_filter( 'the_content', 'brandsure_header_strip_legacy', 9 );
