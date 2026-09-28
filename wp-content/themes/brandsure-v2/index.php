<?php
/**
 * Fallback template.
 *
 * Every real page in this theme has its own template (see templates/), and
 * every controlled slug is bound to one in code. This file exists because
 * WordPress requires it, and to give an honest, on-brand page if a request
 * ever reaches a route the theme does not model.
 *
 * No copy and no markup beyond the shell is added here — the shared header,
 * footer and sticky bar components land in Chat 1 and this file will call
 * them. Until then it renders a minimal valid document.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			the_title( '<h1 class="bs-h1">', '</h1>' );
			the_content();
		}
	}
	?>
</main>
<?php

get_footer();
