<?php
/**
 * Digital Partner.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/digital-partner' ); ?>
</main>
<?php

get_footer();
