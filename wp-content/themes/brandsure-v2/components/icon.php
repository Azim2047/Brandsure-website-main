<?php
/**
 * Icon — one <use> into the sprite.
 *
 * @param array $args {
 *     @type string $name  Sprite symbol id, without the bs-icon- prefix.
 *     @type string $class Extra classes.
 *     @type string $label Accessible label. Omit for a decorative icon, which
 *                         is then hidden from screen readers.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_name  = isset( $args['name'] ) ? sanitize_key( $args['name'] ) : '';
$bs_class = isset( $args['class'] ) ? sanitize_html_class( $args['class'] ) : '';
$bs_label = isset( $args['label'] ) ? (string) $args['label'] : '';

if ( '' === $bs_name ) {
	return;
}

$bs_uri = get_template_directory_uri() . '/assets/img/icons.svg#bs-icon-' . $bs_name;

?>
<svg class="bs-icon <?php echo esc_attr( $bs_class ); ?>"
	<?php if ( '' !== $bs_label ) : ?>
		role="img" aria-label="<?php echo esc_attr( $bs_label ); ?>"
	<?php else : ?>
		aria-hidden="true" focusable="false"
	<?php endif; ?>
	><use href="<?php echo esc_url( $bs_uri ); ?>"></use></svg>
