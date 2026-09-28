<?php
/**
 * Figures — the numbers, stated plainly.
 *
 * Every value is a fact key into data/commercial.json, so a price cannot be
 * typed into a page file and the Numbers Sheet stays the one source. A key
 * that is not public returns empty from bs_fact() and the tile is dropped
 * rather than rendered blank.
 *
 * A tile may name a qualifier_fact. It renders inside the same tile, directly
 * under the value, because Layer 4 v4 treats the caveat as part of the number
 * rather than as a footnote: "up to 300 new enquiry records" read on its own
 * has become "300 leads" by the time it reaches the second person who hears
 * it. Guard rule R13 fails the build if a fact that requires one is rendered
 * without it.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $items [{ fact, label, qualifier_fact }]
 *     @type string $note
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_items = (array) ( $args['items'] ?? array() );

if ( ! $bs_items ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-figures' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-figures__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-figures__body">%s</p>', esc_html( (string) $args['body'] ) );
}

?>
<dl class="bs-figures__grid">
	<?php foreach ( $bs_items as $bs_item ) : ?>
		<?php
		$bs_value = bs_fact( (string) ( $bs_item['fact'] ?? '' ) );

		if ( '' === $bs_value ) {
			continue;
		}
		?>
		<?php
		// Only look the qualifier up when the tile declares one. bs_fact('')
		// warns about an unknown fact, which on a page of six tiles meant six
		// warnings in the log for entirely correct data.
		$bs_qual_key = (string) ( $bs_item['qualifier_fact'] ?? '' );
		$bs_qual     = '' !== $bs_qual_key ? bs_fact( $bs_qual_key ) : '';
		?>
		<div class="bs-figure">
			<dt class="bs-figure__label"><?php echo esc_html( (string) ( $bs_item['label'] ?? '' ) ); ?></dt>
			<dd class="bs-figure__value"><?php echo esc_html( $bs_value ); ?></dd>
			<?php if ( '' !== $bs_qual ) : ?>
				<dd class="bs-figure__qualifier"><?php echo esc_html( $bs_qual ); ?></dd>
			<?php endif; ?>
		</div>
	<?php endforeach; ?>
</dl>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-figures__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
