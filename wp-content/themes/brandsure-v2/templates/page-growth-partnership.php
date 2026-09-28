<?php
/**
 * Growth Partnership.
 *
 * The page with the strictest disclosure rules in the project. Guard rule R2
 * is scoped to this template and its data file specifically: no rupee figure,
 * no duration, no deposit, no setup fee, no percentage. Everything that makes
 * the plan desirable here is structural — where the boundary sits, what gets
 * built, what stays yours — and none of it touches the mechanism.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/growth-partnership' ); ?>
</main>
<?php

get_footer();
