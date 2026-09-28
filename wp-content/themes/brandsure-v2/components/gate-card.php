<?php
/**
 * Gate card — the four numbers, as a card.
 *
 * The same ask that ends Video 1, in a form that works with no video at all.
 * It appears at the end of a blog post, and it stands in for the player
 * wherever a video is specified but not yet recorded — which is why the Home
 * page is complete today despite Video 1 not existing
 * (docs/OPEN-ITEMS.md item 7).
 *
 * The four numbers are exactly the AI's qualification fields, so nothing is
 * asked twice once the conversation moves to WhatsApp.
 *
 * @param array $args {
 *     @type string $headline Override the default.
 *     @type string $label    Button label.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_numbers = array(
	__( 'Your monthly sales', 'brandsure-v2' ),
	__( 'What you spend on ads now', 'brandsure-v2' ),
	__( 'How many showrooms you have', 'brandsure-v2' ),
	__( 'How much you can produce in a month', 'brandsure-v2' ),
);

?>
<div class="bs-gate">
	<h2 class="bs-gate__headline">
		<?php echo esc_html( (string) ( $args['headline'] ?? __( 'Send us four numbers.', 'brandsure-v2' ) ) ); ?>
	</h2>

	<ol class="bs-gate__list">
		<?php foreach ( $bs_numbers as $bs_index => $bs_number ) : ?>
			<li class="bs-gate__item">
				<span class="bs-gate__num"><?php echo esc_html( (string) ( $bs_index + 1 ) ); ?></span>
				<?php echo esc_html( $bs_number ); ?>
			</li>
		<?php endforeach; ?>
	</ol>

	<?php
	bs_the_button(
		array(
			'label'   => (string) ( $args['label'] ?? __( 'Send the four numbers', 'brandsure-v2' ) ),
			'url'     => (string) bs_site( 'nav_cta.url', '/start/' ),
			'variant' => 'primary',
			'block'   => true,
		)
	);
	?>

	<p class="bs-gate__note">
		<?php esc_html_e( 'We will tell you honestly whether we can help, and which plan fits.', 'brandsure-v2' ); ?>
	</p>
</div>
