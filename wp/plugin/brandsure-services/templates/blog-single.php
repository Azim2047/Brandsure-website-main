<?php
/**
 * Single blog article.
 *
 * Supplied by the BrandSure Service Pages plugin through `template_include`.
 * Header and footer come from the active theme.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$brandsure_id  = get_the_ID();
	$brandsure_cat = brandsure_blog_primary_category( $brandsure_id );
	?>
	<main id="brandsure-post" class="band">
		<div class="wrap">

			<?php brandsure_blog_breadcrumb( get_the_title() ); ?>

			<article <?php post_class(); ?>>

				<header class="bpost__head" style="margin-top:16px">
					<?php if ( $brandsure_cat ) : ?>
						<p class="eyebrow">
							<a href="<?php echo esc_url( (string) get_term_link( $brandsure_cat ) ); ?>">
								<?php echo esc_html( $brandsure_cat->name ); ?>
							</a>
						</p>
					<?php endif; ?>

					<h1 class="h1"><?php the_title(); ?></h1>

					<p class="bpost__meta">
						<span>
							<?php
							printf(
								/* translators: %s: author name. */
								esc_html__( 'By %s', 'brandsure-services' ),
								esc_html( get_the_author() )
							);
							?>
						</span>
						<span aria-hidden="true">&middot;</span>
						<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>">
							<?php echo esc_html( get_the_date() ); ?>
						</time>
						<?php if ( get_the_modified_date() !== get_the_date() ) : ?>
							<span aria-hidden="true">&middot;</span>
							<span>
								<?php
								printf(
									/* translators: %s: last updated date. */
									esc_html__( 'Updated %s', 'brandsure-services' ),
									esc_html( get_the_modified_date() )
								);
								?>
							</span>
						<?php endif; ?>
					</p>
				</header>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="bpost__media">
						<?php
						the_post_thumbnail(
							'brandsure-wide',
							array( 'fetchpriority' => 'high' )
						);
						?>
					</figure>
				<?php endif; ?>

				<div class="bprose" style="margin-top:28px">
					<?php the_content(); ?>
				</div>

				<footer class="bpost__foot">
					<a class="btn btn--sec btn--sm" href="<?php echo esc_url( brandsure_blog_url() ); ?>">
						<?php esc_html_e( 'All articles', 'brandsure-services' ); ?>
					</a>
					<?php
					$brandsure_terms = get_the_category( $brandsure_id );
					foreach ( $brandsure_terms as $brandsure_term ) :
						?>
						<a class="chip" href="<?php echo esc_url( (string) get_term_link( $brandsure_term ) ); ?>">
							<?php echo esc_html( $brandsure_term->name ); ?>
						</a>
						<?php
					endforeach;
					?>
				</footer>

			</article>

			<?php
			$brandsure_prev = get_previous_post();
			$brandsure_next = get_next_post();
			if ( $brandsure_prev || $brandsure_next ) :
				?>
				<nav class="rel" style="margin-top:30px" aria-label="<?php esc_attr_e( 'More articles', 'brandsure-services' ); ?>">
					<?php if ( $brandsure_prev ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $brandsure_prev ) ); ?>">
							<span>
								<b><?php echo esc_html( get_the_title( $brandsure_prev ) ); ?></b>
								<span><?php esc_html_e( 'Previous article', 'brandsure-services' ); ?></span>
							</span>
							<i aria-hidden="true">&rarr;</i>
						</a>
					<?php endif; ?>
					<?php if ( $brandsure_next ) : ?>
						<a href="<?php echo esc_url( (string) get_permalink( $brandsure_next ) ); ?>">
							<span>
								<b><?php echo esc_html( get_the_title( $brandsure_next ) ); ?></b>
								<span><?php esc_html_e( 'Next article', 'brandsure-services' ); ?></span>
							</span>
							<i aria-hidden="true">&rarr;</i>
						</a>
					<?php endif; ?>
				</nav>
				<?php
			endif;
			?>

		</div>
	</main>
	<?php
endwhile;

get_footer();
