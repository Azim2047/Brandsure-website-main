<?php
/**
 * Home.
 *
 * A section loop and nothing else. Every word is in data/pages/home.json and
 * every piece of markup is in components/ — if this file ever grows a
 * heading, a wrapper or a conditional about copy, something has been put in
 * the wrong place.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/home' ); ?>
</main>
<?php

get_footer();
