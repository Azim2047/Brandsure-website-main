<?php
/**
 * Case result — the one client outcome, and its footnote.
 *
 * The Numbers Sheet marks this result verified and usable, and requires the
 * footnote every time it appears. So the component renders both together and
 * offers no way to render one without the other: the footnote is not a prop,
 * it is part of the claim. A future edit cannot drop it by accident, because
 * there is nothing to drop.
 *
 * Both strings come from data/commercial.json, which is the mirror of the
 * Numbers Sheet.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $context One line of setup before the numbers.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_result   = bs_fact( 'client_result' );
$bs_footnote = bs_fact( 'client_result_footnote' );

// Neither half is renderable alone. If the footnote is missing from the data,
// the claim does not go on the page.
if ( '' === $bs_result || '' === $bs_footnote ) {
	bs_data_fail( 'The client result and its footnote must both exist in data/commercial.json — neither renders without the other' );
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-case' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-case__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<figure class="bs-case__figure">
	<?php if ( ! empty( $args['context'] ) ) : ?>
		<p class="bs-case__context"><?php echo esc_html( (string) $args['context'] ); ?></p>
	<?php endif; ?>

	<blockquote class="bs-case__result">
		<p><?php echo esc_html( (string) $bs_result ); ?></p>
	</blockquote>

	<figcaption class="bs-case__footnote">
		<?php echo esc_html( (string) $bs_footnote ); ?>
	</figcaption>
</figure>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
