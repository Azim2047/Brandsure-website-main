<?php
/**
 * Blog listing: the posts page, and category / tag / author / date archives.
 *
 * Supplied by the BrandSure Service Pages plugin through `template_include`.
 * Header and footer come from the active theme.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

get_header();

$brandsure_ctx   = brandsure_blog_listing_context();
$brandsure_query = brandsure_blog_listing_query();
$brandsure_root  = ! is_category() && ! is_tag() && ! is_author() && ! is_date();
?>
<main id="brandsure-blog" class="band">
	<div class="wrap">

		<?php brandsure_blog_breadcrumb( $brandsure_ctx['crumb'], ! $brandsure_root ); ?>

		<div class="sec-head" style="margin-top:16px">
			<?php if ( '' !== $brandsure_ctx['eyebrow'] ) : ?>
				<p class="eyebrow"><?php echo esc_html( $brandsure_ctx['eyebrow'] ); ?></p>
			<?php endif; ?>

			<h1 class="h1"><?php echo esc_html( $brandsure_ctx['title'] ); ?></h1>

			<?php if ( '' !== $brandsure_ctx['intro'] ) : ?>
				<p class="lead"><?php echo esc_html( $brandsure_ctx['intro'] ); ?></p>
			<?php endif; ?>
		</div>

		<?php if ( $brandsure_query->have_posts() ) : ?>

			<ul class="bgrid">
				<?php
				while ( $brandsure_query->have_posts() ) :
					$brandsure_query->the_post();

					$brandsure_id    = get_the_ID();
					$brandsure_link  = get_permalink();
					$brandsure_cat   = brandsure_blog_primary_category( $brandsure_id );
					$brandsure_thumb = get_the_post_thumbnail( $brandsure_id, 'brandsure-card', array( 'loading' => 'lazy' ) );
					?>
					<li class="card bcard">

						<?php if ( $brandsure_thumb ) : ?>
							<a class="bcard__media" href="<?php echo esc_url( $brandsure_link ); ?>" tabindex="-1" aria-hidden="true">
								<?php echo wp_kses_post( $brandsure_thumb ); ?>
							</a>
						<?php else : ?>
							<span class="bcard__media bcard__media--none" aria-hidden="true">
								<span><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
							</span>
						<?php endif; ?>

						<div class="bcard__body">
							<p class="bcard__meta">
								<?php if ( $brandsure_cat ) : ?>
									<a class="bcard__cat" href="<?php echo esc_url( (string) get_term_link( $brandsure_cat ) ); ?>">
										<?php echo esc_html( $brandsure_cat->name ); ?>
									</a>
								<?php endif; ?>
								<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
									<?php echo esc_html( get_the_date() ); ?>
								</time>
							</p>

							<h2 class="h3 bcard__title">
								<a href="<?php echo esc_url( $brandsure_link ); ?>"><?php the_title(); ?></a>
							</h2>

							<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
								<p class="bcard__ex"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 26, '…' ) ); ?></p>
							<?php endif; ?>

							<a class="bcard__cta" href="<?php echo esc_url( $brandsure_link ); ?>">
								<?php esc_html_e( 'Read Article', 'brandsure-services' ); ?>
								<span class="arw" aria-hidden="true">&rarr;</span>
							</a>
						</div>
					</li>
					<?php
				endwhile;
				?>
			</ul>

			<?php
			brandsure_blog_pagination( $brandsure_query );

			// Only a secondary query needs resetting; the main query must not be touched.
			if ( brandsure_blog_is_standalone_listing() ) {
				wp_reset_postdata();
			}
			?>

		<?php else : ?>

			<div class="bempty">
				<p><?php esc_html_e( 'No articles have been published here yet.', 'brandsure-services' ); ?></p>
			</div>

		<?php endif; ?>

	</div>
</main>
<?php
get_footer();
