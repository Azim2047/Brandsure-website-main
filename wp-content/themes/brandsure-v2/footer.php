<?php
/**
 * Closing of <body>.
 *
 * Required at theme root by get_footer(). Like header.php it stays a thin
 * shell: the footer itself — the Other Services column, Company, Legal — and
 * the sticky mobile WhatsApp + Call bar are components, built in Chat 1 and
 * shared by every template.
 *
 * Other Services appears here and nowhere else. It is never in the primary
 * navigation.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

// Chat 1 replaces these with the real components.
if ( file_exists( BRANDSURE_DIR . '/components/site-footer.php' ) ) {
	get_template_part( 'components/site-footer' );
}

if ( file_exists( BRANDSURE_DIR . '/components/sticky-bar.php' ) ) {
	get_template_part( 'components/sticky-bar' );
}

/*
 * The WhatsApp panel last, so it is the final thing in the document and sits
 * above the sticky bar without either needing a z-index fight. The component
 * decides for itself whether this page may have it.
 */
if ( file_exists( BRANDSURE_DIR . '/components/whatsapp-panel.php' ) ) {
	get_template_part( 'components/whatsapp-panel' );
}

wp_footer();
?>
</body>
</html>
