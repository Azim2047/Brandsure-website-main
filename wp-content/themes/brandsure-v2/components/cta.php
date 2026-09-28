<?php
/**
 * Page-foot CTA — the route to /start/.
 *
 * Appears at the bottom of every public page. Architecture v3 §2: no page is
 * a dead end, and every page ends with a route to the gate.
 *
 * The default wording asks for the four numbers rather than selling, because
 * the website is the front door and the gate is the handoff — the explaining
 * happens after, in the private funnel, not here.
 *
 * @param array $args {
 *     @type string $headline Override the default headline.
 *     @type string $body     Override the default supporting line.
 *     @type string $label    Override the button label.
 *     @type string $url      Override the destination.
 *     @type string $tone     Section tone. Defaults to 'tint'.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_headline = (string) ( $args['headline'] ?? __( 'Send us four numbers.', 'brandsure-v2' ) );
$bs_body     = (string) ( $args['body'] ?? __( 'Monthly sales, what you spend on ads now, how many showrooms, and how much you can produce in a month. We will tell you honestly whether we can help.', 'brandsure-v2' ) );
$bs_label    = (string) ( $args['label'] ?? bs_site( 'nav_cta.label', __( 'Start', 'brandsure-v2' ) ) );
$bs_url      = (string) ( $args['url'] ?? bs_site( 'nav_cta.url', '/start/' ) );
$bs_tone     = (string) ( $args['tone'] ?? 'tint' );

get_template_part( 'components/section', null, array( 'open' => true, 'tone' => $bs_tone, 'width' => 'prose', 'class' => 'bs-cta' ) );

?>
<h2 class="bs-cta__headline"><?php echo esc_html( $bs_headline ); ?></h2>
<p class="bs-cta__body"><?php echo esc_html( $bs_body ); ?></p>

<div class="bs-cta__actions">
	<?php
	bs_the_button(
		array(
			'label'   => $bs_label,
			'url'     => $bs_url,
			'variant' => 'primary',
			'block'   => true,
		)
	);
	?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
