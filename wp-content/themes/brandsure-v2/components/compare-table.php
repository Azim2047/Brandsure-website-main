<?php
/**
 * The compare table.
 *
 * Deliberately a table. The Master Build Architecture calls this one of the
 * two "photograph slides" — owners screenshot it and send it to a partner or
 * an accountant, and "a diagram would lose the numbers" (§1.7 C15). So it
 * stays dense, scannable and screenshot-friendly rather than being prettied
 * into cards.
 *
 * On a phone it does NOT scroll sideways. Until Chat 15 it did — a 40rem
 * table in an overflow-x container with a "swipe to see all three plans"
 * hint. That is a desktop table squeezed onto a phone, and the brief rules it
 * out twice over: comparisons must read as stacked cards or labelled rows,
 * and important information must not need swiping to reach. With 99% of
 * visitors on phones, the swipe was hiding two of the three plans from almost
 * everybody.
 *
 * So below 44rem each row becomes its own small card: the row label as a
 * heading, then one labelled line per plan. Every cell carries a data-label
 * with its column heading, which is what makes that possible in CSS alone —
 * there is one table in the markup, not a table and a phone copy of it.
 * Above 44rem the table is a table again, unchanged, because it is still one
 * of the two "photograph slides" owners screenshot for a partner.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $note      Line under the table.
 *     @type array  $columns   Column headings, first is the row-label column.
 *     @type array  $rows      [{ label, cells: [string] }]
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_columns = (array) ( $args['columns'] ?? array() );
$bs_rows    = (array) ( $args['rows'] ?? array() );

if ( ! $bs_columns || ! $bs_rows ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-compare' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-compare__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

?>
<div class="bs-compare__scroll" tabindex="0" role="region"
	aria-label="<?php esc_attr_e( 'Plan comparison', 'brandsure-v2' ); ?>">
	<table class="bs-compare__table">
		<thead>
			<tr>
				<?php foreach ( $bs_columns as $bs_index => $bs_column ) : ?>
					<th scope="col" class="<?php echo 0 === $bs_index ? 'bs-compare__rowhead' : ''; ?>">
						<?php echo esc_html( (string) $bs_column ); ?>
					</th>
				<?php endforeach; ?>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $bs_rows as $bs_row ) : ?>
				<tr>
					<th scope="row" class="bs-compare__rowhead">
						<?php echo esc_html( (string) ( $bs_row['label'] ?? '' ) ); ?>
					</th>
					<?php foreach ( (array) ( $bs_row['cells'] ?? array() ) as $bs_cell_index => $bs_cell ) : ?>
						<?php
						// A cell may name a fact key instead of a literal, so the
						// table quotes the Numbers Sheet rather than repeating it.
						$bs_value = (string) $bs_cell;

						if ( str_starts_with( $bs_value, 'fact:' ) ) {
							$bs_value = bs_fact( substr( $bs_value, 5 ) );
						}

						// The column heading travels with the cell. On a phone
						// the table header row is gone, so this is the only
						// thing that says which plan the value belongs to.
						$bs_col = (string) ( $bs_columns[ $bs_cell_index + 1 ] ?? '' );
						?>
						<td data-label="<?php echo esc_attr( $bs_col ); ?>"><?php echo esc_html( $bs_value ); ?></td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
</div>

<?php if ( ! empty( $args['note'] ) ) : ?>
	<p class="bs-compare__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
<?php endif; ?>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
