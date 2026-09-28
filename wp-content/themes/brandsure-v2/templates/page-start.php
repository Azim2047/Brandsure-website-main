<?php
/**
 * The gate.
 *
 * Deliberately has no page-foot CTA. Every other page ends with a route to
 * here; this page has exactly one action on it, and a second competing
 * WhatsApp button would split the site's primary measurement
 * (gate_whatsapp_click) as well as the visitor's attention. The sticky bar
 * hides itself here for the same reason.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main bs-main--gate" role="main">
	<?php bs_render_sections( 'pages/start' ); ?>
</main>
<?php

get_footer();
