<?php
/**
 * Not found.
 *
 * BUILD-MANIFEST §F: "Routes to Home and /start/. Never a dead end." It was
 * listed there in Chat 0 and no chat built it — WordPress was serving
 * index.php's empty loop instead, which is a blank page under a header. Found
 * by Chat 12's audit, which is what an audit is for.
 *
 * A section loop like every other controlled page. The copy is in
 * data/pages/404.json.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/404' ); ?>
</main>
<?php

get_footer();
