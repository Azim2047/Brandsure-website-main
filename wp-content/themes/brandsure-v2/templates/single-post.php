<?php
/**
 * A blog post.
 *
 * The body is the author's. The gate card underneath it is the theme's, and
 * that division is deliberate: Architecture v3 §2 routes blog readers to
 * Video 1 and the four numbers, and an ask that depends on each author
 * remembering to write one is an ask that will be missing from half the
 * posts. Appending it here means every post ends the same way, and no author
 * has to think about conversion while writing.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php
	while ( have_posts() ) :
		the_post();

		get_template_part( 'components/section', null, array( 'open' => true, 'width' => 'prose' ) );
		?>

		<h1 class="bs-page__title"><?php the_title(); ?></h1>

		<p class="bs-postcard__meta">
			<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
				<?php echo esc_html( get_the_date() ); ?>
			</time>
		</p>

		<div class="bs-prose">
			<?php the_content(); ?>
		</div>

		<?php
		get_template_part( 'components/section', null, array( 'close' => true ) );

	endwhile;

	// The theme's ask, not the author's.
	get_template_part(
		'components/section',
		null,
		array( 'open' => true, 'tone' => 'tint', 'width' => 'prose' )
	);
	get_template_part(
		'components/gate-card',
		null,
		array( 'headline' => __( 'Is this happening in your business?', 'brandsure-v2' ) )
	);
	get_template_part( 'components/section', null, array( 'close' => true ) );
	?>
</main>
<?php

get_footer();
