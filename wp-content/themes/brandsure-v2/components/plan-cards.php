<?php
/**
 * The three plan cards.
 *
 * Reads data/plans.json. Prices are fact keys into data/commercial.json, so
 * the Numbers Sheet stays the one source and a price cannot be typed into a
 * page file.
 *
 * The masking rule is enforced here rather than trusted to the data: a plan
 * whose `disclosure` is `masked` renders its `commercial_note` and can show
 * no price at all, even if a fee key is present on it. That is the shape the
 * Growth Partnership mistake would take — someone adds a price key to the GP
 * entry to "make the card consistent" — so the component refuses it.
 *
 * @param array $args {
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_plans = bs_data( 'plans' );
$bs_list  = (array) ( $bs_plans['plans'] ?? array() );

if ( ! $bs_list ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'tint' ), 'class' => 'bs-plans' )
);

?>
<div class="bs-plans__grid" data-bs-view="plan_viewed">
	<?php foreach ( $bs_list as $bs_plan ) : ?>
		<?php
		$bs_masked = 'masked' === (string) ( $bs_plan['disclosure'] ?? '' );
		$bs_route  = (string) ( $bs_plan['route'] ?? '' );
		$bs_config = $bs_route ? (array) bs_site( 'routing.' . $bs_route, array() ) : array();

		// The plan CTA goes to /start/ carrying the self-selected route, so the
		// AI knows what they picked before the first message is read. Built
		// here from site.json rather than written into plans.json, which is
		// what keeps Override 3 in one place.
		$bs_url = (string) bs_site( 'nav_cta.url', '/start/' );

		if ( $bs_config && ! empty( $bs_config['param'] ) && ! empty( $bs_config['value'] ) ) {
			$bs_param = (string) $bs_config['param'];
			$bs_value = (string) $bs_config['value'];

			// Override 3, in code as well as in the data.
			if ( 'plan' === $bs_param && false !== stripos( $bs_value, 'growth partnership' ) ) {
				$bs_param = 'interest';
			}

			$bs_url = add_query_arg( rawurlencode( $bs_param ), rawurlencode( $bs_value ), $bs_url );
		}
		?>

		<article class="bs-plan<?php echo ! empty( $bs_plan['featured'] ) ? ' bs-plan--featured' : ''; ?>">

			<?php if ( ! empty( $bs_plan['badge'] ) ) : ?>
				<p class="bs-plan__badge"><?php echo esc_html( (string) $bs_plan['badge'] ); ?></p>
			<?php endif; ?>

			<h3 class="bs-plan__name"><?php echo esc_html( (string) ( $bs_plan['name'] ?? '' ) ); ?></h3>

			<div class="bs-plan__price">
				<?php if ( $bs_masked ) : ?>
					<p class="bs-plan__masked">
						<?php echo esc_html( (string) ( $bs_plan['commercial_note'] ?? '' ) ); ?>
					</p>
				<?php else : ?>
					<?php if ( ! empty( $bs_plan['fee_fact'] ) ) : ?>
						<p class="bs-plan__fee"><?php echo esc_html( bs_fact( (string) $bs_plan['fee_fact'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( ! empty( $bs_plan['setup_fact'] ) ) : ?>
						<p class="bs-plan__setup">
							<?php
							printf(
								/* translators: %s: the one-time setup fee. */
								esc_html__( 'plus %s to set up', 'brandsure-v2' ),
								esc_html( bs_fact( (string) $bs_plan['setup_fact'] ) )
							);
							?>
						</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $bs_plan['promise'] ) ) : ?>
				<p class="bs-plan__promise"><?php echo esc_html( (string) $bs_plan['promise'] ); ?></p>
			<?php endif; ?>

			<?php
			get_template_part(
				'components/chain',
				null,
				array(
					'active'  => (int) ( $bs_plan['chain_active'] ?? 0 ),
					'compact' => true,
					'label'   => (string) ( $bs_plan['chain_label'] ?? '' ),
				)
			);
			?>

			<?php if ( ! empty( $bs_plan['includes'] ) ) : ?>
				<ul class="bs-plan__includes">
					<?php foreach ( (array) $bs_plan['includes'] as $bs_item ) : ?>
						<li>
							<?php get_template_part( 'components/icon', null, array( 'name' => 'check' ) ); ?>
							<span><?php echo esc_html( (string) $bs_item ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php
			bs_the_button(
				array(
					'label'   => (string) ( $bs_config['cta'] ?? ( $bs_plan['cta'] ?? '' ) ),
					'url'     => $bs_url,
					'variant' => ! empty( $bs_plan['featured'] ) ? 'primary' : 'secondary',
					'block'   => true,
					'event'   => 'plan_selected',
					'meta'    => array( 'plan' => (string) ( $bs_plan['name'] ?? '' ) ),
				)
			);

			/*
			 * The link to the plan's own page, where it has one.
			 *
			 * Until Chat 13's audit this did not exist, and the consequence
			 * was that /digital-partner/ had no inbound link anywhere on the
			 * site: the card's only control goes straight to the gate, and
			 * the page is not in the header. A reader who wanted to know what
			 * Digital Partner actually is before messaging had nowhere to go
			 * — on the plan that site.json's own nav note describes as
			 * "reached from /plans/".
			 *
			 * Derived from the ownership map rather than from a key in
			 * plans.json, so it cannot point at a page that does not exist:
			 * Agency has a slug but no page, and correctly gets no link. The
			 * day an /agency/ page is built, this appears on its own.
			 */
			$bs_detail = ( '' !== bs_page_data_key( (string) ( $bs_plan['slug'] ?? '' ) ) )
				? '/' . trim( (string) $bs_plan['slug'], '/' ) . '/'
				: '';

			if ( '' !== $bs_detail ) :
				?>
				<a class="bs-plan__detail" href="<?php echo esc_url( $bs_detail ); ?>">
					<?php esc_html_e( 'See what’s included', 'brandsure-v2' ); ?>
					<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right' ) ); ?>
				</a>
				<?php
			endif;
			?>
		</article>
	<?php endforeach; ?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
