<?php
/**
 * Not found. The previous theme printed a bare unstyled document here for every
 * page it did not know about, which is what broke the four legal pages.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="pagehero">
	<span class="hero__aura" aria-hidden="true"></span>
	<div class="wrap" style="position:relative">
		<p class="eyebrow eyebrow--light"><?php esc_html_e( '404', 'brandsure-theme' ); ?></p>
		<h1 class="h1"><?php esc_html_e( 'That page has moved.', 'brandsure-theme' ); ?></h1>
		<p class="hero__sub"><?php esc_html_e( 'Nothing is lost — start from the homepage, or tell us on WhatsApp what you were looking for.', 'brandsure-theme' ); ?></p>
		<div class="acts">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Go to the homepage', 'brandsure-theme' ); ?></a>
			<a class="btn btn--onDark" href="<?php echo esc_url( brandsure_theme_wa_url() ); ?>" target="_blank" rel="noopener" data-cta="404"><?php esc_html_e( 'Ask us on WhatsApp', 'brandsure-theme' ); ?></a>
		</div>
	</div>
</section>
<?php
get_footer();
