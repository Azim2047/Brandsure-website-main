<?php
/**
 * Other Services — the index.
 *
 * A routing surface, which is why it stays controlled while the four pages
 * it points at are editable: the list of what BrandSure offers is a
 * structural fact about the site, and the description of each offer is
 * editorial.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

get_header();

?>
<main id="main" class="bs-main" role="main">
	<?php bs_render_sections( 'pages/services-index' ); ?>
</main>
<?php

get_footer();
