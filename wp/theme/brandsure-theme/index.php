<?php
/**
 * Required fallback: archives, search and anything without a closer match.
 * The blog listing and single article have their own templates in the plugin.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="band">
	<div class="wrap">
		<?php if ( have_posts() ) : ?>
			<h1 class="h2">
				<?php
				if ( is_search() ) {
					printf(
						/* translators: %s: search term. */
						esc_html__( 'Results for “%s”', 'brandsure-theme' ),
						esc_html( get_search_query() )
					);
				} elseif ( is_archive() ) {
					the_archive_title();
				} else {
					echo esc_html( get_bloginfo( 'name' ) );
				}
				?>
			</h1>
			<div class="jsteps">
				<?php while ( have_posts() ) : the_post(); ?>
					<article class="jstep">
						<i aria-hidden="true">›</i>
						<div>
							<b><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></b>
							<p><?php echo esc_html( get_the_excerpt() ); ?></p>
						</div>
					</article>
				<?php endwhile; ?>
			</div>
			<?php
			the_posts_pagination( array( 'mid_size' => 1 ) );
			?>
		<?php else : ?>
			<h1 class="h2"><?php esc_html_e( 'Nothing found', 'brandsure-theme' ); ?></h1>
			<p class="lead" style="margin-top:16px">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Return to BrandSure', 'brandsure-theme' ); ?></a>
			</p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
