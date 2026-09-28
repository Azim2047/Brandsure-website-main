<?php
/**
 * Plans.
 *
 * A section loop. The masking of Growth Partnership is enforced in
 * components/plan-cards.php and in the guard, not here — a template is the
 * wrong place for a rule that has to hold everywhere.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/plans' ); ?>
</main>
<?php

get_footer();
