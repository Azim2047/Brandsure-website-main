<?php
/**
 * The gauge — how much of the growth work you keep doing yourself.
 *
 * One track from "You do more" to "We do more", with the three plans as
 * markers along it. The review called this the clearest structure in the
 * whole system, and it works because it reframes the choice: not three
 * products to evaluate, but one question with three answers.
 *
 * It shows no money. The prices live in the cards below it, and Growth
 * Partnership has none to show.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $low      Label at the "you" end.
 *     @type string $high     Label at the "we" end.
 *     @type array  $markers  [{ name, note }] in order along the track.
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_markers = (array) ( $args['markers'] ?? array() );
$bs_count   = count( $bs_markers );

if ( $bs_count < 2 ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-gauge' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-gauge__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<?php
/*
 * The wrapper is what lets the same markup read two ways. On a phone it is a
 * flex column: "you do more" at the top, the three stops down a vertical
 * rail, "we do more" at the bottom. From 40rem it becomes the horizontal
 * gauge. Stacking the horizontal version instead — a bar with three items
 * underneath it — loses the one thing the diagram is for, which is showing
 * that the plans sit at points along a single scale.
 */
?>
<div class="bs-gauge__body">
	<div class="bs-gauge__ends">
		<span class="bs-gauge__end bs-gauge__end--low"><?php echo esc_html( (string) ( $args['low'] ?? '' ) ); ?></span>
		<span class="bs-gauge__end bs-gauge__end--high"><?php echo esc_html( (string) ( $args['high'] ?? '' ) ); ?></span>
	</div>

	<div class="bs-gauge__track" aria-hidden="true"></div>

	<ol class="bs-gauge__markers" style="--bs-gauge-count:<?php echo esc_attr( (string) $bs_count ); ?>">
		<?php foreach ( $bs_markers as $bs_marker ) : ?>
			<li class="bs-gauge__marker">
				<span class="bs-gauge__dot" aria-hidden="true"></span>
				<span class="bs-gauge__name"><?php echo esc_html( (string) ( $bs_marker['name'] ?? '' ) ); ?></span>
				<?php if ( ! empty( $bs_marker['note'] ) ) : ?>
					<span class="bs-gauge__note"><?php echo esc_html( (string) $bs_marker['note'] ); ?></span>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
