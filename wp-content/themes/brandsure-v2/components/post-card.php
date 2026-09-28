<?php
/**
 * Post card — one entry in the blog index.
 *
 * Deliberately plain: title, date, and the excerpt WordPress already
 * generates. A blog index that tries to sell competes with the pages that
 * exist to, and this one only has to get somebody into the post.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

?>
<article class="bs-postcard">
	<a class="bs-postcard__link" href="<?php the_permalink(); ?>">
		<h2 class="bs-postcard__title"><?php the_title(); ?></h2>
	</a>

	<p class="bs-postcard__meta">
		<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
			<?php echo esc_html( get_the_date() ); ?>
		</time>
	</p>

	<?php if ( has_excerpt() || get_the_excerpt() ) : ?>
		<p class="bs-postcard__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 32 ) ); ?></p>
	<?php endif; ?>

	<a class="bs-postcard__more" href="<?php the_permalink(); ?>">
		<?php esc_html_e( 'Read', 'brandsure-v2' ); ?>
		<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right' ) ); ?>
	</a>
</article>
