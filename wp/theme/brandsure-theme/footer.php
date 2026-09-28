<?php
/**
 * The site footer and the sticky WhatsApp pill.
 *
 * Both are conditional. Pages whose body is still a self-contained document
 * carry their own copy, and brandsure_theme_render_content() has already
 * recorded that, so nothing is printed twice.
 *
 * Company details are only ever reused, never invented: the address, phone,
 * email and CIN are the ones already published on the site.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

$brandsure_instagram = function_exists( 'brandsure_instagram_url' ) ? brandsure_instagram_url() : '';
$brandsure_linkedin  = function_exists( 'brandsure_linkedin_url' ) ? brandsure_linkedin_url() : '';
?>

<?php if ( empty( $GLOBALS['brandsure_body_has_footer'] ) ) : ?>
<footer class="ft" id="ft">
	<div class="wrap">
		<div class="ft__top">
			<div class="ft__b">
				<b>BrandSure Consultancy Pvt. Ltd.</b>
				<span class="tag"><?php esc_html_e( 'Furniture Growth Systems. Built in India.', 'brandsure-theme' ); ?></span>
				<p>
					<?php esc_html_e( 'Bengaluru, Karnataka, India', 'brandsure-theme' ); ?><br>
					<a href="tel:+918884446668">+91 8884 446 668</a><br>
					<a href="mailto:info@brandsure.in">info@brandsure.in</a>
				</p>
			</div>
			<div>
				<h4><?php esc_html_e( 'Company', 'brandsure-theme' ); ?></h4>
				<nav class="ft__links" aria-label="<?php esc_attr_e( 'Footer', 'brandsure-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/how-it-works/' ) ); ?>"><?php esc_html_e( 'How It Works', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>"><?php esc_html_e( 'Prices', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/services/' ) ); ?>"><?php esc_html_e( 'Other Services', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/about/' ) ); ?>"><?php esc_html_e( 'About the Team', 'brandsure-theme' ); ?></a>
					<?php if ( '' !== $brandsure_linkedin ) : ?>
						<a href="<?php echo esc_url( $brandsure_linkedin ); ?>" target="_blank" rel="noopener">LinkedIn</a>
					<?php endif; ?>
					<?php if ( '' !== $brandsure_instagram ) : ?>
						<a href="<?php echo esc_url( $brandsure_instagram ); ?>" target="_blank" rel="noopener">Instagram</a>
					<?php endif; ?>
				</nav>
			</div>
			<div>
				<h4><?php esc_html_e( 'Other Services', 'brandsure-theme' ); ?></h4>
				<nav class="ft__links" aria-label="<?php esc_attr_e( 'Other services', 'brandsure-theme' ); ?>">
					<a href="<?php echo esc_url( home_url( '/furniture-labour-hiring-india/' ) ); ?>"><?php esc_html_e( 'Labour Hiring Help', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/furniture-growth-marketing-india/' ) ); ?>"><?php esc_html_e( 'Growth &amp; Marketing', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/furniture-business-consulting-india/' ) ); ?>"><?php esc_html_e( 'Business Consulting', 'brandsure-theme' ); ?></a>
					<a href="<?php echo esc_url( home_url( '/furniture-product-design-services-india/' ) ); ?>"><?php esc_html_e( 'Product Design', 'brandsure-theme' ); ?></a>
				</nav>
			</div>
		</div>

		<?php
		// Blog and the four policies, from the plugin. Marking them rendered here
		// stops its own wp_footer fallback from adding a second row.
		if ( function_exists( 'brandsure_footer_links' ) ) {
			brandsure_footer_links();
		}
		?>

		<div class="ft__bar">
			<span>CIN: U73100KA2025PTC199105</span>
			<span class="sep">
				<?php
				printf(
					/* translators: %s: current year. */
					esc_html__( '© %s BrandSure Consultancy Pvt. Ltd. All rights reserved.', 'brandsure-theme' ),
					esc_html( gmdate( 'Y', current_time( 'timestamp' ) ) ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-local year.
				);
				?>
			</span>
		</div>
	</div>
</footer>
<?php endif; ?>

<?php if ( empty( $GLOBALS['brandsure_body_has_pill'] ) ) : ?>
<a class="wapill" id="wapill" href="<?php echo esc_url( brandsure_theme_wa_url() ); ?>"
	target="_blank" rel="noopener" data-cta="sticky"
	aria-label="<?php esc_attr_e( 'Chat with BrandSure on WhatsApp', 'brandsure-theme' ); ?>">
	<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.75-.86-2.02-.96-.27-.1-.47-.15-.67.15-.2.3-.77.96-.94 1.16-.17.2-.35.22-.65.07-.3-.15-1.25-.46-2.39-1.47-.88-.79-1.48-1.76-1.65-2.06-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.61-.92-2.2-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.06 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.63.71.22 1.36.19 1.87.12.57-.09 1.75-.72 2-1.41.25-.69.25-1.28.17-1.41-.07-.13-.27-.2-.57-.35zM12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.28-1.38a9.87 9.87 0 004.75 1.21h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0012.04 2zm0 18.13h-.01a8.2 8.2 0 01-4.18-1.15l-.3-.18-3.13.82.84-3.05-.2-.31a8.16 8.16 0 01-1.25-4.35c0-4.53 3.7-8.23 8.24-8.23 2.2 0 4.27.86 5.82 2.42a8.17 8.17 0 012.41 5.82c0 4.54-3.7 8.21-8.24 8.21z"/></svg>
	WhatsApp
</a>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
