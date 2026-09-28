<?php
/**
 * Any other single post or page. The plugin supplies its own templates for the
 * blog, so this is the fallback for anything outside it.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<section class="band">
	<div class="wrap">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<h1 class="h2"><?php the_title(); ?></h1>
			<div class="lead" style="margin-top:22px">
				<?php brandsure_theme_render_content(); ?>
			</div>
			<?php
		endwhile;
		?>
	</div>
</section>
<?php
get_footer();
