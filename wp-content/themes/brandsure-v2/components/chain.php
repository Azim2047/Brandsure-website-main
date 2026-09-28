<?php
/**
 * The chain — the one diagram this site uses.
 *
 * Ad → Enquiry → Call → Appointment → Showroom Visit → Order → Production →
 * Delivery. Language Sheet B1: this order, always, and no second diagram is
 * ever introduced. The owner learns it once on Home and recognises it
 * everywhere else, which is the entire reason it is defined here rather than
 * drawn per page.
 *
 * The `active` count is how many leading nodes BrandSure runs on a given
 * plan. That is the whole argument of the plans page made as a picture: the
 * blue section of the same chain grows as you move down the page.
 *
 * @param array $args {
 *     @type int    $active  Leading nodes BrandSure runs. 0-8.
 *     @type bool   $compact Small version, for inside a plan card.
 *     @type string $label   Accessible summary. Strongly recommended.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_nodes   = bs_chain_nodes();
$bs_active  = max( 0, min( count( $bs_nodes ), (int) ( $args['active'] ?? 0 ) ) );
$bs_compact = ! empty( $args['compact'] );

/*
 * Leaks hang off the same chain rather than getting a diagram of their own.
 * Language Sheet B1 allows exactly one journey diagram on the whole site, and
 * the argument is stronger for it: the owner is not shown a new picture of a
 * broken process, he is shown the process he already recognises, with the
 * places it drips marked on it.
 *
 * Shape: [{ node: <index>, label: <string> }]. Several may share a node.
 */
$bs_leaks = array();

foreach ( (array) ( $args['leaks'] ?? array() ) as $bs_leak ) {
	$bs_index = (int) ( $bs_leak['node'] ?? -1 );

	if ( isset( $bs_nodes[ $bs_index ] ) && ! empty( $bs_leak['label'] ) ) {
		$bs_leaks[ $bs_index ][] = (string) $bs_leak['label'];
	}
}

$bs_classes = 'bs-chain'
	. ( $bs_compact ? ' bs-chain--compact' : '' )
	. ( $bs_leaks ? ' bs-chain--leaks' : '' );

?>
<div class="<?php echo esc_attr( $bs_classes ); ?>"
	role="img"
	aria-label="<?php
		echo esc_attr(
			! empty( $args['label'] )
				? (string) $args['label']
				/* translators: %d: number of steps BrandSure runs. */
				: sprintf( __( 'BrandSure runs the first %d steps of the sale.', 'brandsure-v2' ), $bs_active )
		);
	?>">
	<ol class="bs-chain__list">
		<?php foreach ( $bs_nodes as $bs_index => $bs_node ) : ?>
			<?php $bs_is_active = $bs_index < $bs_active; ?>
			<?php $bs_node_leaks = $bs_leaks[ $bs_index ] ?? array(); ?>
			<li class="bs-chain__node<?php echo $bs_is_active ? ' is-active' : ''; ?><?php echo $bs_node_leaks ? ' has-leak' : ''; ?>">
				<span class="bs-chain__tile">
					<?php get_template_part( 'components/icon', null, array( 'name' => $bs_node['icon'] ) ); ?>
				</span>
				<?php if ( ! $bs_compact ) : ?>
					<span class="bs-chain__label"><?php echo esc_html( $bs_node['label'] ); ?></span>
				<?php endif; ?>

				<?php if ( $bs_node_leaks ) : ?>
					<ul class="bs-chain__leaks">
						<?php foreach ( $bs_node_leaks as $bs_label ) : ?>
							<li class="bs-chain__leak"><?php echo esc_html( $bs_label ); ?></li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</li>
		<?php endforeach; ?>
	</ol>
</div>
