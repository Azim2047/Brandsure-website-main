<?php
/**
 * A page. The design owns its own headings, so the theme adds no title and no
 * sidebar — only the content, between the global header and the footer.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	brandsure_theme_render_content();
endwhile;

get_footer();
