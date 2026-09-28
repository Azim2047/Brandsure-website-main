<?php
/**
 * Site header — the one implementation.
 *
 * Five nav items and the Start button, from data/site.json. Other Services is
 * deliberately absent; it is footer-only (Architecture v3 §4). Digital
 * Partner and Growth Partnership are reached from /plans/, not from here —
 * the header stays short enough to read on a 390px screen without a
 * second row.
 *
 * No template may contain its own copy of this markup.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_nav = bs_site( 'nav_primary', array() );
$bs_cta = bs_site( 'nav_cta', array() );

?>
<header class="bs-header" data-bs-header>
	<div class="bs-header__inner bs-container">

		<a class="bs-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<img
				class="bs-header__logo"
				src="<?php echo esc_url( BRANDSURE_URI . '/assets/img/brandsure-logo-dark-text.png' ); ?>"
				alt="<?php echo esc_attr( bs_site( 'brand.name', 'BrandSure' ) ); ?>"
				width="128" height="34" fetchpriority="high" decoding="async">
		</a>

		<button
			class="bs-header__toggle"
			type="button"
			aria-expanded="false"
			aria-controls="bs-nav"
			data-bs-nav-toggle>
			<span class="bs-header__toggle-label"><?php esc_html_e( 'Menu', 'brandsure-v2' ); ?></span>
			<?php get_template_part( 'components/icon', null, array( 'name' => 'menu', 'class' => 'bs-header__toggle-open' ) ); ?>
			<?php get_template_part( 'components/icon', null, array( 'name' => 'close', 'class' => 'bs-header__toggle-close' ) ); ?>
		</button>

		<nav class="bs-header__nav" id="bs-nav" aria-label="<?php esc_attr_e( 'Main', 'brandsure-v2' ); ?>">
			<ul class="bs-header__list">
				<?php foreach ( $bs_nav as $bs_item ) : ?>
					<?php
					$bs_url     = (string) ( $bs_item['url'] ?? '' );
					$bs_current = bs_is_current_path( $bs_url );
					?>
					<li class="bs-header__item">
						<a
							class="bs-header__link<?php echo $bs_current ? ' is-current' : ''; ?>"
							href="<?php echo esc_url( $bs_url ); ?>"
							<?php echo $bs_current ? 'aria-current="page"' : ''; ?>>
							<?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( ! empty( $bs_cta['url'] ) ) : ?>
				<a class="bs-btn bs-btn--primary bs-header__cta" href="<?php echo esc_url( (string) $bs_cta['url'] ); ?>">
					<?php echo esc_html( (string) ( $bs_cta['label'] ?? 'Start' ) ); ?>
				</a>
			<?php endif; ?>
		</nav>

	</div>
</header>
