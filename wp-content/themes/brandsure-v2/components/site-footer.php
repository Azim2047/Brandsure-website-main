<?php
/**
 * Site footer — the one implementation.
 *
 * This is where Other Services lives, and the only place it appears. Keeping
 * it out of the header keeps the primary journey to five items; keeping it in
 * the footer means a visitor who landed on a labour-hiring page and turns out
 * to be a manufacturer can still reach the main journey — via the footer,
 * never via a competing CTA in the body.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_footer = bs_site( 'footer', array() );
$bs_email  = bs_site( 'contact.email', '' );
$bs_phone  = bs_site( 'contact.phone_display', '' );
$bs_tel    = bs_site( 'contact.phone_tel', '' );

$bs_columns = array(
	'other_services' => __( 'Other Services', 'brandsure-v2' ),
	'company'        => __( 'Company', 'brandsure-v2' ),
	'legal'          => __( 'Legal', 'brandsure-v2' ),
);

?>
<footer class="bs-footer">
	<div class="bs-container bs-footer__inner">

		<div class="bs-footer__brand">
			<img
				class="bs-footer__logo"
				src="<?php echo esc_url( BRANDSURE_URI . '/assets/img/brandsure-logo.png' ); ?>"
				alt="<?php echo esc_attr( bs_site( 'brand.name', 'BrandSure' ) ); ?>"
				width="128" height="34" loading="lazy" decoding="async">

			<p class="bs-footer__line">
				<?php esc_html_e( 'Furniture-only growth partner. Bengaluru, India.', 'brandsure-v2' ); ?>
			</p>

			<?php if ( '' !== $bs_phone && '' !== $bs_tel ) : ?>
				<p class="bs-footer__line">
					<a class="bs-footer__link" href="tel:<?php echo esc_attr( $bs_tel ); ?>">
						<?php echo esc_html( $bs_phone ); ?>
					</a>
				</p>
			<?php endif; ?>

			<?php if ( '' !== $bs_email ) : ?>
				<p class="bs-footer__line">
					<a class="bs-footer__link" href="mailto:<?php echo esc_attr( $bs_email ); ?>">
						<?php echo esc_html( $bs_email ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>

		<?php foreach ( $bs_columns as $bs_key => $bs_heading ) : ?>
			<?php
			$bs_links = $bs_footer[ $bs_key ] ?? array();

			if ( empty( $bs_links ) ) {
				continue;
			}
			?>
			<nav class="bs-footer__col" aria-label="<?php echo esc_attr( $bs_heading ); ?>">
				<h2 class="bs-footer__heading"><?php echo esc_html( $bs_heading ); ?></h2>
				<ul class="bs-footer__list">
					<?php foreach ( $bs_links as $bs_link ) : ?>
						<li>
							<a class="bs-footer__link" href="<?php echo esc_url( (string) ( $bs_link['url'] ?? '' ) ); ?>">
								<?php echo esc_html( (string) ( $bs_link['label'] ?? '' ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</nav>
		<?php endforeach; ?>

	</div>

	<div class="bs-container bs-footer__base">
		<p class="bs-footer__fine">
			&copy; <?php echo esc_html( (string) gmdate( 'Y' ) ); ?>
			<?php echo esc_html( bs_site( 'brand.legal_name', 'BrandSure Consultancy Pvt. Ltd.' ) ); ?>
			<?php if ( '' !== (string) bs_site( 'brand.cin', '' ) ) : ?>
				&middot; CIN <?php echo esc_html( bs_site( 'brand.cin', '' ) ); ?>
			<?php endif; ?>
		</p>
	</div>
</footer>
