<?php
/**
 * "Behind BrandSure" Instagram gallery and the team photograph.
 *
 * Instagram is resolved in this order, and the page renders whatever it gets:
 *
 *   1. the official Instagram Graph API, when an access token has been saved,
 *      with the result cached server-side so the page never waits on Meta;
 *   2. the six admin-managed cards, editable in wp-admin with no code change;
 *   3. nothing at all — the section simply does not render.
 *
 * Instagram HTML is never scraped. A Graph API failure is cached briefly so a
 * broken token cannot slow every request, and it falls straight through to the
 * admin-managed cards.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/** Option holding every social/about setting. */
const BRANDSURE_SOCIAL_OPTION = 'brandsure_social';

/** How long a good Graph API response is reused. */
const BRANDSURE_IG_TTL = 3600;

/** How long a failure is remembered, so a bad token is not retried per request. */
const BRANDSURE_IG_FAIL_TTL = 600;

/** Number of cards the gallery shows. */
const BRANDSURE_IG_COUNT = 6;

/**
 * Saved settings, with defaults filled in.
 *
 * @return array<string,mixed>
 */
function brandsure_social_settings() {
	$saved = get_option( BRANDSURE_SOCIAL_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	$defaults = array(
		'instagram_url'   => '',
		'linkedin_url'    => '',
		'instagram_token' => '',
		'posts'           => array(),
		'team_image'      => '',
		'team_caption'    => '',
		'team_alt'        => '',
	);

	$settings = array_merge( $defaults, $saved );

	// Always expose exactly six card slots so the admin form is stable.
	$posts = is_array( $settings['posts'] ) ? $settings['posts'] : array();
	for ( $i = 0; $i < BRANDSURE_IG_COUNT; $i++ ) {
		$posts[ $i ] = wp_parse_args(
			isset( $posts[ $i ] ) && is_array( $posts[ $i ] ) ? $posts[ $i ] : array(),
			array(
				'image' => '',
				'url'   => '',
				'alt'   => '',
			)
		);
	}
	$settings['posts'] = array_slice( $posts, 0, BRANDSURE_IG_COUNT );

	return $settings;
}

/**
 * The BrandSure Instagram profile URL.
 *
 * One place, reused by every template — no handle is hard-coded anywhere.
 *
 * @return string Empty string when no URL has been configured.
 */
function brandsure_instagram_url() {
	$settings = brandsure_social_settings();
	$url      = trim( (string) $settings['instagram_url'] );

	/**
	 * Filter the BrandSure Instagram profile URL.
	 *
	 * @param string $url Configured URL, or '' when unset.
	 */
	return (string) apply_filters( 'brandsure_instagram_url', $url );
}

/**
 * The BrandSure LinkedIn company URL.
 *
 * Same contract as the Instagram URL: configured once, never hard-coded, and
 * an empty value simply leaves the footer link as it was.
 *
 * @return string Empty string when no URL has been configured.
 */
function brandsure_linkedin_url() {
	$settings = brandsure_social_settings();
	$url      = trim( (string) $settings['linkedin_url'] );

	/**
	 * Filter the BrandSure LinkedIn company URL.
	 *
	 * @param string $url Configured URL, or '' when unset.
	 */
	return (string) apply_filters( 'brandsure_linkedin_url', $url );
}

/**
 * Resolve an image reference that may be an attachment ID or a URL.
 *
 * @param string|int $ref   Attachment ID or image URL.
 * @param string     $size  Image size to use for an attachment.
 * @return string Image URL, or '' when unresolvable.
 */
function brandsure_image_url( $ref, $size = 'large' ) {
	$ref = is_string( $ref ) ? trim( $ref ) : $ref;
	if ( '' === $ref || null === $ref ) {
		return '';
	}
	if ( is_numeric( $ref ) ) {
		$url = wp_get_attachment_image_url( (int) $ref, $size );
		return $url ? $url : '';
	}
	return esc_url_raw( (string) $ref );
}

/**
 * The admin-managed cards, filtered down to the usable ones.
 *
 * @return array<int,array{image:string,url:string,alt:string}>
 */
function brandsure_instagram_manual_items() {
	$settings = brandsure_social_settings();
	$profile  = brandsure_instagram_url();
	$items    = array();

	foreach ( $settings['posts'] as $post ) {
		$image = brandsure_image_url( $post['image'] );
		if ( '' === $image ) {
			continue; // A card with no image is not a card.
		}
		$items[] = array(
			'image' => $image,
			// Fall back to the profile when a specific post URL is not set.
			'url'   => '' !== trim( (string) $post['url'] ) ? trim( (string) $post['url'] ) : $profile,
			'alt'   => trim( (string) $post['alt'] ),
		);
	}

	return $items;
}

/**
 * Fetch recent media from the official Instagram Graph API.
 *
 * @param string $token Access token.
 * @return array<int,array{image:string,url:string,alt:string}>|WP_Error
 */
function brandsure_instagram_fetch( $token ) {
	$endpoint = add_query_arg(
		array(
			'fields'       => 'id,caption,media_type,media_url,permalink,thumbnail_url',
			'limit'        => BRANDSURE_IG_COUNT,
			'access_token' => $token,
		),
		'https://graph.instagram.com/me/media'
	);

	$response = wp_remote_get(
		$endpoint,
		array(
			'timeout'     => 5, // Never hold the page open on Meta.
			'redirection' => 2,
		)
	);

	if ( is_wp_error( $response ) ) {
		return $response;
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error(
			'brandsure_ig_http',
			'Instagram returned HTTP ' . wp_remote_retrieve_response_code( $response )
		);
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $body ) || empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
		return new WP_Error( 'brandsure_ig_shape', 'Unexpected Instagram response' );
	}

	$items = array();
	foreach ( $body['data'] as $media ) {
		$type  = isset( $media['media_type'] ) ? $media['media_type'] : '';
		$image = 'VIDEO' === $type && ! empty( $media['thumbnail_url'] )
			? $media['thumbnail_url']
			: ( isset( $media['media_url'] ) ? $media['media_url'] : '' );

		if ( '' === $image ) {
			continue;
		}

		$caption = isset( $media['caption'] ) ? wp_strip_all_tags( (string) $media['caption'] ) : '';
		$items[] = array(
			'image' => esc_url_raw( $image ),
			'url'   => isset( $media['permalink'] ) ? esc_url_raw( $media['permalink'] ) : brandsure_instagram_url(),
			'alt'   => '' !== $caption ? wp_trim_words( $caption, 14, '…' ) : '',
		);

		if ( count( $items ) >= BRANDSURE_IG_COUNT ) {
			break;
		}
	}

	return $items;
}

/**
 * The gallery items actually rendered.
 *
 * @return array<int,array{image:string,url:string,alt:string}>
 */
function brandsure_instagram_items() {
	$settings = brandsure_social_settings();
	$token    = trim( (string) $settings['instagram_token'] );

	if ( '' === $token ) {
		return brandsure_instagram_manual_items();
	}

	$cached = get_transient( 'brandsure_ig_items' );
	if ( is_array( $cached ) ) {
		return $cached; // Served from cache; Meta is not contacted.
	}
	if ( 'failed' === $cached ) {
		return brandsure_instagram_manual_items(); // Recent failure remembered.
	}

	$fetched = brandsure_instagram_fetch( $token );

	if ( is_wp_error( $fetched ) || empty( $fetched ) ) {
		set_transient( 'brandsure_ig_items', 'failed', BRANDSURE_IG_FAIL_TTL );
		return brandsure_instagram_manual_items();
	}

	set_transient( 'brandsure_ig_items', $fetched, BRANDSURE_IG_TTL );
	return $fetched;
}

/**
 * [brandsure_instagram] — the "Behind BrandSure" gallery.
 *
 * Renders nothing when there is neither a working API nor a single card, so a
 * missing or broken integration can never break the page.
 *
 * @return string
 */
function brandsure_instagram_shortcode() {
	$items   = brandsure_instagram_items();
	$profile = brandsure_instagram_url();

	if ( empty( $items ) ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return '';
		}
		return '<p class="ignote">'
			. esc_html__(
				'Instagram gallery: add the profile URL and up to six cards under Settings → BrandSure.',
				'brandsure-services'
			) . '</p>';
	}

	$out = '<ul class="igrid">';
	foreach ( $items as $item ) {
		$alt  = '' !== $item['alt']
			? $item['alt']
			: __( 'BrandSure furniture work on Instagram', 'brandsure-services' );
		$href = '' !== $item['url'] ? $item['url'] : $profile;

		$out .= '<li class="igrid__i">';
		if ( '' !== $href ) {
			$out .= sprintf(
				'<a href="%s" target="_blank" rel="noopener" data-cta="instagram-post">',
				esc_url( $href )
			);
		}
		$out .= sprintf(
			'<img src="%s" alt="%s" loading="lazy" decoding="async" width="600" height="600">',
			esc_url( $item['image'] ),
			esc_attr( $alt )
		);
		if ( '' !== $href ) {
			$out .= '</a>';
		}
		$out .= '</li>';
	}
	$out .= '</ul>';

	if ( '' !== $profile ) {
		$out .= sprintf(
			'<p class="iglink"><a href="%s" target="_blank" rel="noopener" data-cta="instagram-profile">%s <span class="arw" aria-hidden="true">&rarr;</span></a></p>',
			esc_url( $profile ),
			esc_html__( 'Follow BrandSure on Instagram', 'brandsure-services' )
		);
	}

	return $out;
}
add_shortcode( 'brandsure_instagram', 'brandsure_instagram_shortcode' );

/**
 * [brandsure_team_photo] — the single group photograph.
 *
 * @return string
 */
function brandsure_team_photo_shortcode() {
	$settings = brandsure_social_settings();
	$image    = brandsure_image_url( $settings['team_image'], 'full' );
	$caption  = trim( (string) $settings['team_caption'] );
	$alt      = trim( (string) $settings['team_alt'] );

	if ( '' === $image ) {
		$out = '<div class="teamfig teamfig--empty"><span>'
			. esc_html__( 'Team photograph', 'brandsure-services' )
			. '</span></div>';
		if ( current_user_can( 'manage_options' ) ) {
			$out .= '<p class="ignote">'
				. esc_html__(
					'Upload the team photograph under Settings → BrandSure.',
					'brandsure-services'
				) . '</p>';
		}
		return $out;
	}

	if ( '' === $alt ) {
		$alt = __( 'The BrandSure team', 'brandsure-services' );
	}

	$out = '<figure class="teamfig">';
	$out .= sprintf(
		'<img src="%s" alt="%s" loading="lazy" decoding="async" width="1600" height="900">',
		esc_url( $image ),
		esc_attr( $alt )
	);
	if ( '' !== $caption ) {
		$out .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
	}
	$out .= '</figure>';

	return $out;
}
add_shortcode( 'brandsure_team_photo', 'brandsure_team_photo_shortcode' );

/**
 * Load the about-section styles wherever either shortcode is used.
 *
 * The stylesheet only references design tokens that the page already defines,
 * so it adds components without introducing a second visual system.
 *
 * @return void
 */
function brandsure_social_enqueue() {
	if ( ! is_singular() ) {
		return;
	}
	$post = get_queried_object();
	if ( ! $post instanceof WP_Post ) {
		return;
	}
	if ( ! has_shortcode( $post->post_content, 'brandsure_instagram' )
		&& ! has_shortcode( $post->post_content, 'brandsure_team_photo' ) ) {
		return;
	}

	$css = 'assets/brandsure-about.css';
	wp_enqueue_style(
		'brandsure-about',
		BRANDSURE_SERVICES_URL . $css,
		array(),
		brandsure_services_asset_version( $css )
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_social_enqueue' );

/* -------------------------------------------------------------------------
 * Settings screen — Settings → BrandSure
 * ---------------------------------------------------------------------- */

/**
 * Register the settings page.
 *
 * @return void
 */
function brandsure_social_menu() {
	add_options_page(
		__( 'BrandSure', 'brandsure-services' ),
		__( 'BrandSure', 'brandsure-services' ),
		'manage_options',
		'brandsure-social',
		'brandsure_social_render_page'
	);
}
add_action( 'admin_menu', 'brandsure_social_menu' );

/**
 * Register the option and its sanitiser.
 *
 * @return void
 */
function brandsure_social_register() {
	register_setting(
		'brandsure_social_group',
		BRANDSURE_SOCIAL_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'brandsure_social_sanitize',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'brandsure_social_register' );

/**
 * Sanitise the submitted settings.
 *
 * @param mixed $input Raw submitted value.
 * @return array<string,mixed>
 */
function brandsure_social_sanitize( $input ) {
	$input = is_array( $input ) ? $input : array();
	$clean = array();

	$clean['instagram_url']   = isset( $input['instagram_url'] ) ? esc_url_raw( trim( $input['instagram_url'] ) ) : '';
	$clean['linkedin_url']    = isset( $input['linkedin_url'] ) ? esc_url_raw( trim( $input['linkedin_url'] ) ) : '';
	$clean['instagram_token'] = isset( $input['instagram_token'] ) ? sanitize_text_field( trim( $input['instagram_token'] ) ) : '';

	$clean['posts'] = array();
	$posts          = isset( $input['posts'] ) && is_array( $input['posts'] ) ? $input['posts'] : array();
	for ( $i = 0; $i < BRANDSURE_IG_COUNT; $i++ ) {
		$row               = isset( $posts[ $i ] ) && is_array( $posts[ $i ] ) ? $posts[ $i ] : array();
		$clean['posts'][]  = array(
			'image' => isset( $row['image'] ) ? brandsure_social_sanitize_ref( $row['image'] ) : '',
			'url'   => isset( $row['url'] ) ? esc_url_raw( trim( $row['url'] ) ) : '',
			'alt'   => isset( $row['alt'] ) ? sanitize_text_field( $row['alt'] ) : '',
		);
	}

	$clean['team_image']   = isset( $input['team_image'] ) ? brandsure_social_sanitize_ref( $input['team_image'] ) : '';
	$clean['team_caption'] = isset( $input['team_caption'] ) ? sanitize_text_field( $input['team_caption'] ) : '';
	$clean['team_alt']     = isset( $input['team_alt'] ) ? sanitize_text_field( $input['team_alt'] ) : '';

	// A settings change must not leave a stale Instagram cache behind.
	delete_transient( 'brandsure_ig_items' );

	return $clean;
}

/**
 * Sanitise an image reference (attachment ID or URL).
 *
 * @param string $value Raw value.
 * @return string
 */
function brandsure_social_sanitize_ref( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	return is_numeric( $value ) ? (string) absint( $value ) : esc_url_raw( $value );
}

/**
 * Enqueue the media picker on the settings screen only.
 *
 * @param string $hook Current admin page.
 * @return void
 */
function brandsure_social_admin_assets( $hook ) {
	if ( 'settings_page_brandsure-social' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	$js = 'assets/brandsure-admin.js';
	wp_enqueue_script(
		'brandsure-admin',
		BRANDSURE_SERVICES_URL . $js,
		array( 'jquery' ),
		brandsure_services_asset_version( $js ),
		true
	);
}
add_action( 'admin_enqueue_scripts', 'brandsure_social_admin_assets' );

/**
 * Render one image field with a media-library picker.
 *
 * @param string $name  Field name attribute.
 * @param string $value Current value.
 * @param string $label Field label.
 * @return void
 */
function brandsure_social_image_field( $name, $value, $label ) {
	$preview = brandsure_image_url( $value, 'thumbnail' );
	printf(
		'<div class="brandsure-img"><label>%1$s<br>
			<input type="text" name="%2$s" value="%3$s" class="regular-text brandsure-img__val" placeholder="%4$s">
		</label>
		<button type="button" class="button brandsure-img__pick">%5$s</button>
		<span class="brandsure-img__prev">%6$s</span></div>',
		esc_html( $label ),
		esc_attr( $name ),
		esc_attr( $value ),
		esc_attr__( 'Media Library ID or image URL', 'brandsure-services' ),
		esc_html__( 'Choose image', 'brandsure-services' ),
		$preview ? '<img src="' . esc_url( $preview ) . '" alt="" style="max-height:60px;vertical-align:middle">' : ''
	);
}

/**
 * Render the settings page.
 *
 * @return void
 */
function brandsure_social_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = brandsure_social_settings();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'BrandSure', 'brandsure-services' ); ?></h1>
		<p>
			<?php
			esc_html_e(
				'Controls the "Behind BrandSure" Instagram gallery and the team photograph on the About page. Nothing here changes page copy.',
				'brandsure-services'
			);
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'brandsure_social_group' ); ?>
			<?php $opt = BRANDSURE_SOCIAL_OPTION; ?>

			<h2><?php esc_html_e( 'Instagram', 'brandsure-services' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Profile URL', 'brandsure-services' ); ?></th>
					<td>
						<input type="url" class="regular-text"
							name="<?php echo esc_attr( $opt ); ?>[instagram_url]"
							value="<?php echo esc_attr( $s['instagram_url'] ); ?>"
							placeholder="https://www.instagram.com/yourhandle/">
						<p class="description">
							<?php
							esc_html_e(
								'Used by the gallery and every "Follow us" link. Leave empty and no Instagram links are shown anywhere.',
								'brandsure-services'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Graph API access token', 'brandsure-services' ); ?></th>
					<td>
						<input type="text" class="large-text"
							name="<?php echo esc_attr( $opt ); ?>[instagram_token]"
							value="<?php echo esc_attr( $s['instagram_token'] ); ?>"
							autocomplete="off">
						<p class="description">
							<?php
							esc_html_e(
								'Optional. With a token the six cards come from the official Instagram Graph API and are cached for an hour. Without one — or if Instagram fails — the cards below are used instead.',
								'brandsure-services'
							);
							?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'LinkedIn', 'brandsure-services' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Company page URL', 'brandsure-services' ); ?></th>
					<td>
						<input type="url" class="regular-text"
							name="<?php echo esc_attr( $opt ); ?>[linkedin_url]"
							value="<?php echo esc_attr( $s['linkedin_url'] ); ?>"
							placeholder="https://www.linkedin.com/company/yourcompany/">
						<p class="description">
							<?php
							esc_html_e(
								'Used by the LinkedIn link in the footer. Leave empty and the link is left exactly as the page has it.',
								'brandsure-services'
							);
							?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Gallery cards (fallback)', 'brandsure-services' ); ?></h2>
			<p>
				<?php
				esc_html_e(
					'Up to six cards. A card needs an image; the post URL falls back to the profile, and the alt text describes the image for search engines and screen readers.',
					'brandsure-services'
				);
				?>
			</p>
			<?php for ( $i = 0; $i < BRANDSURE_IG_COUNT; $i++ ) : ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php printf( esc_html__( 'Card %d', 'brandsure-services' ), $i + 1 ); ?></th>
						<td>
							<?php
							brandsure_social_image_field(
								$opt . '[posts][' . $i . '][image]',
								$s['posts'][ $i ]['image'],
								__( 'Image', 'brandsure-services' )
							);
							?>
							<p>
								<label><?php esc_html_e( 'Instagram post URL', 'brandsure-services' ); ?><br>
									<input type="url" class="regular-text"
										name="<?php echo esc_attr( $opt . '[posts][' . $i . '][url]' ); ?>"
										value="<?php echo esc_attr( $s['posts'][ $i ]['url'] ); ?>">
								</label>
							</p>
							<p>
								<label><?php esc_html_e( 'Alt text / caption', 'brandsure-services' ); ?><br>
									<input type="text" class="regular-text"
										name="<?php echo esc_attr( $opt . '[posts][' . $i . '][alt]' ); ?>"
										value="<?php echo esc_attr( $s['posts'][ $i ]['alt'] ); ?>">
								</label>
							</p>
						</td>
					</tr>
				</table>
			<?php endfor; ?>

			<h2><?php esc_html_e( 'Team photograph', 'brandsure-services' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Photo', 'brandsure-services' ); ?></th>
					<td>
						<?php
						brandsure_social_image_field(
							$opt . '[team_image]',
							$s['team_image'],
							__( 'Group photograph', 'brandsure-services' )
						);
						?>
						<p class="description">
							<?php esc_html_e( 'A wide group photo works best — around 1600×900.', 'brandsure-services' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Caption', 'brandsure-services' ); ?></th>
					<td>
						<input type="text" class="large-text"
							name="<?php echo esc_attr( $opt ); ?>[team_caption]"
							value="<?php echo esc_attr( $s['team_caption'] ); ?>">
						<p class="description"><?php esc_html_e( 'Optional, shown under the photo.', 'brandsure-services' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Alt text', 'brandsure-services' ); ?></th>
					<td>
						<input type="text" class="large-text"
							name="<?php echo esc_attr( $opt ); ?>[team_alt]"
							value="<?php echo esc_attr( $s['team_alt'] ); ?>">
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * About page sections
 *
 * The live /about/ page is a single Custom HTML block holding the whole
 * pasted document, so the three new sections are injected into the rendered
 * content just before </main> rather than by rewriting that block.
 *
 * The injection is self-deactivating: as soon as the page content itself
 * contains the sections (id="behind"), this filter steps aside. So pasting the
 * updated about.html into the page later is safe and changes nothing visually.
 * ---------------------------------------------------------------------- */

/**
 * Markup for the Instagram, team and closing-CTA sections.
 *
 * Shortcodes here are expanded by do_shortcode, which runs on the_content
 * after this filter.
 *
 * @return string
 */
function brandsure_about_sections() {
	$wa = 'https://wa.me/918884446668?text=Hi%20BrandSure%2C%20I%20want%20to%20talk%20about%20my%20furniture%20business.';

	ob_start();
	?>
<section class="band band--tint" id="behind" aria-labelledby="behind-h">
  <div class="wrap">
    <p class="eyebrow"><?php esc_html_e( 'Behind BrandSure', 'brandsure-services' ); ?></p>
    <h2 class="h2" id="behind-h"><?php echo esc_html__( 'See what we', 'brandsure-services' ) . '&rsquo;' . esc_html__( 're building.', 'brandsure-services' ); ?></h2>
    <p class="lead" style="margin-top:14px"><?php esc_html_e( 'A look at our work, ideas, furniture projects and what happens behind the scenes.', 'brandsure-services' ); ?></p>
    [brandsure_instagram]
  </div>
</section>

<section class="band" aria-labelledby="team-h">
  <div class="wrap">
    <p class="eyebrow"><?php esc_html_e( 'The Team', 'brandsure-services' ); ?></p>
    <h2 class="h2" id="team-h"><?php esc_html_e( 'Built by people who understand the work.', 'brandsure-services' ); ?></h2>
    <p class="lead" style="margin-top:14px"><?php echo esc_html__( 'Strategy, marketing, sales systems, technology, product and operations', 'brandsure-services' ) . ' &mdash; ' . esc_html__( 'working as one furniture-focused team.', 'brandsure-services' ); ?></p>
    [brandsure_team_photo]
  </div>
</section>

<section class="band band--dark" aria-labelledby="talk-h">
  <div class="wrap center">
    <h2 class="h2" id="talk-h"><?php esc_html_e( 'Talk to Us About Your Furniture Business', 'brandsure-services' ); ?></h2>
    <p class="lead" style="margin:16px auto 0"><?php esc_html_e( 'Tell us your city, what you manufacture and where growth is stuck. We will tell you honestly whether we can help.', 'brandsure-services' ); ?></p>
    <div class="acts" style="justify-content:center">
      <a class="btn btn--primary" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener" data-cta="about-final"><?php esc_html_e( 'Talk to Us on WhatsApp', 'brandsure-services' ); ?> <span class="arw" aria-hidden="true">&rarr;</span></a>
    </div>
  </div>
</section>
	<?php
	return (string) ob_get_clean();
}

/**
 * Inject the sections into the About page content.
 *
 * @param string $content Rendered post content.
 * @return string
 */
function brandsure_about_inject( $content ) {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$post = get_post();
	if ( ! $post instanceof WP_Post || 'about' !== $post->post_name ) {
		return $content;
	}

	// Already present in the page itself — leave the content alone.
	if ( false !== strpos( $content, 'id="behind"' ) ) {
		return $content;
	}

	// The pasted document closes with </main>; without it there is no safe
	// insertion point, so append rather than guess.
	$marker = '</main>';
	$at     = strrpos( $content, $marker );
	if ( false === $at ) {
		return $content . brandsure_about_sections();
	}

	return substr( $content, 0, $at ) . brandsure_about_sections() . substr( $content, $at );
}
add_filter( 'the_content', 'brandsure_about_inject', 10 );

/**
 * The About page needs the component styles even though its shortcodes are
 * injected rather than stored in the content.
 *
 * @return void
 */
function brandsure_about_enqueue() {
	if ( ! is_page( 'about' ) ) {
		return;
	}
	$css = 'assets/brandsure-about.css';
	wp_enqueue_style(
		'brandsure-about',
		BRANDSURE_SERVICES_URL . $css,
		array(),
		brandsure_services_asset_version( $css )
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_about_enqueue' );
