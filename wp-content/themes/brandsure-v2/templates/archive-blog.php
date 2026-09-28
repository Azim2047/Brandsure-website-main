<?php
/**
 * Blog index.
 *
 * An SEO surface and a place to publish. It carries the page-foot CTA like
 * every other page, so a reader who arrived from search has a route to the
 * gate without the index itself doing any selling.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php get_template_part( 'components/section', null, array( 'open' => true ) ); ?>

		<h1 class="bs-page__title"><?php esc_html_e( 'Writing', 'brandsure-v2' ); ?></h1>
		<p class="bs-page__intro">
			<?php esc_html_e( 'Notes on selling furniture in India — what works, what leaks, and what we have seen inside real factories.', 'brandsure-v2' ); ?>
		</p>

		<?php if ( have_posts() ) : ?>
			<div class="bs-posts">
				<?php
				while ( have_posts() ) {
					the_post();
					get_template_part( 'components/post-card' );
				}
				?>
			</div>

			<?php
			the_posts_pagination(
				array(
					'mid_size'  => 1,
					'prev_text' => __( 'Newer', 'brandsure-v2' ),
					'next_text' => __( 'Older', 'brandsure-v2' ),
				)
			);
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing published yet.', 'brandsure-v2' ); ?></p>
		<?php endif; ?>

	<?php
	get_template_part( 'components/section', null, array( 'close' => true ) );

	get_template_part( 'components/cta' );
	?>
</main>
<?php

get_footer();
