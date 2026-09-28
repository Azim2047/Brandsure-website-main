<?php
/**
 * Document head and the opening of <body>.
 *
 * This file is required at theme root because WordPress's get_header() looks
 * for it here. It stays a thin shell: the actual header markup — logo, the
 * five nav items, the Start button — is one component,
 * components/site-header.php, built in Chat 1 and shared by every template.
 *
 * No template may ever contain its own copy of the header.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="bs-skip-link" href="#main"><?php esc_html_e( 'Skip to content', 'brandsure-v2' ); ?></a>

<?php
// Chat 1 replaces this with: get_template_part( 'components/site-header' );
if ( file_exists( BRANDSURE_DIR . '/components/site-header.php' ) ) {
	get_template_part( 'components/site-header' );
}
