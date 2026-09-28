<?php
/**
 * The document head and the opening of the body.
 *
 * Navigation is not printed here. It comes from brandsure_render_header() in the
 * plugin, which fires on wp_body_open — the plugin has been the single source of
 * truth for the menu since the global-header work, and its own comment says a
 * real header.php should simply let it run. Calling wp_body_open() below is what
 * finally lets it: the previous theme exited before this point was ever reached.
 *
 * @package BrandSure_Theme
 */

defined( 'ABSPATH' ) || exit;

$brandsure_slug = brandsure_theme_current_slug();
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#17232B">
<?php wp_head(); ?>
<noscript><style>.rv{opacity:1!important;transform:none!important}</style></noscript>
</head>
<body <?php body_class(); ?><?php echo '' !== $brandsure_slug ? ' data-page-slug="' . esc_attr( $brandsure_slug ) . '"' : ''; ?>>
<?php wp_body_open(); ?>
