<?php
/**
 * Shared shell for editable pages.
 *
 * Serves About and the four legal pages, and is the fallback for any page
 * WordPress resolves here. The theme supplies the shell and the conversion
 * furniture; WordPress supplies the words. That division is the whole of
 * Override 4 in one template.
 *
 * The four service pages get their own template in Chat 9 — same principle,
 * with the per-service enquiry path and the Growth Partnership bridge added.
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

		<div class="bs-prose">
			<?php the_content(); ?>
		</div>

		<?php
		get_template_part( 'components/section', null, array( 'close' => true ) );

	endwhile;

	// The legal pages deliberately carry no CTA: someone reading the privacy
	// policy is not in the market, and a conversion prompt there reads badly.
	if ( ! bs_is_legal_page() ) {
		get_template_part( 'components/cta' );
	}
	?>
</main>
<?php

get_footer();
