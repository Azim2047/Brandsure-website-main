<?php
/**
 * The Growth Partnership bridge — once, quietly, at the bottom.
 *
 * Architecture v3 §6. A service page's job is to satisfy the search that
 * brought someone to it; the bridge exists so a reader who turns out to want
 * the whole growth side has somewhere to go. It appears once, below the
 * enquiry path, and never competes with it.
 *
 * NEVER on furniture-labour-hiring-india. That is a genuinely different
 * buyer with a different need — a factory owner short two upholsterers is
 * not a partnership prospect — and Architecture v3 §6 is explicit that its
 * only link out is the footer. The old site did bridge from that page; this
 * component refuses to, wherever it is called from, so the rule cannot be
 * lost in a template edit.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/*
 * The exclusion list itself is bs_gp_bridge_excluded() in inc/helpers.php.
 * It was declared here until Chat 12's audit found it: WordPress includes a
 * template part with require, not require_once, so a component that declares
 * a function at file scope fatals the second time it is used on one page.
 * Nothing calls this twice today, which is exactly why it would have gone
 * unnoticed until someone put two bridges on a page. The check stays here —
 * that is the part that must not be lost in a template edit.
 */
if ( in_array( bs_slug(), bs_gp_bridge_excluded(), true ) ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => 'light', 'width' => 'prose', 'class' => 'bs-bridge' )
);

?>
<div class="bs-bridge__box">
	<h2 class="bs-bridge__headline">
		<?php esc_html_e( 'Need this as part of a bigger change?', 'brandsure-v2' ); ?>
	</h2>
	<p class="bs-bridge__body">
		<?php
		esc_html_e(
			'Product, pricing and catalogue work are also built in stages under BrandSure\'s Growth Partnership, for a small number of furniture businesses.',
			'brandsure-v2'
		);
		?>
	</p>
	<a class="bs-btn bs-btn--secondary" href="/growth-partnership/">
		<?php esc_html_e( 'Explore Growth Partnership', 'brandsure-v2' ); ?>
	</a>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
