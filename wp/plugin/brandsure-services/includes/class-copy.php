<?php
/**
 * Public-copy corrections for pasted pages.
 *
 * Several pages are single Custom HTML blocks holding a whole pasted document,
 * and some are classic/freeform content that block-level editing cannot target.
 * Rewriting a 24-58 KB blob through the API to change one paragraph risks
 * corrupting a live page, so the corrections are applied to the rendered
 * content as exact-string replacements instead.
 *
 * Every pair is exact-match and therefore self-deactivating: once a page's
 * stored content no longer holds the old string, the pair simply never fires.
 * Re-pasting a corrected static file makes this file a no-op.
 *
 * Nothing here invents commercial terms. The replacements defer the partnership
 * commercials to the business review, which is a disclosure-timing change, and
 * translate marketing jargon into furniture-business language.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * The correction table: old public string => new public string.
 *
 * @return array<string,string>
 */
function brandsure_copy_corrections() {
	$partner = 'Selected furniture manufacturers can work with BrandSure under a longer-term '
		. 'Growth Partnership covering growth, product, sales systems and business development. '
		. 'Partnership commercials are explained during the business review.';

	$corrections = array(

		/* ---- Rule 3: the service-page CTA implied funded ads for everyone ---- */
		'<p>BrandSure also has a model where we work on the complete selling and growth side of the furniture business. No fixed monthly agency fee. Google, Facebook and Instagram ad budget paid by BrandSure under the applicable plan. The complete calculation is shown with your numbers before you decide.</p>'
			=> '<p>' . $partner . '</p>',

		/* ---- Rule 3: the services overview carried the same claim ---- */
		'<p>No fixed monthly agency fee. Google, Facebook and Instagram ad budget paid by BrandSure under the applicable plan.'
			=> '<p>' . $partner,

		/* ---- Rule 4: jargon on the live homepage ---- */
		'We run marketing, websites, CRM, sales systems, and product design for a select group of furniture brands across India.'
			=> 'We run advertising, websites, enquiry follow-up, sales systems and product design for a select group of furniture brands across India.',

		'running marketing, websites, CRM, sales systems, and product design support for a select group of brands across the country.'
			=> 'running advertising, websites, enquiry follow-up, sales systems and product design support for a select group of brands across the country.',

		'<span class="marquee-item">CRM, WhatsApp &amp; lead automation</span>'
			=> '<span class="marquee-item">Every enquiry recorded, called and followed up on WhatsApp</span>',

		'<span class="marquee-item">Sales playbooks &amp; floor enablement</span>'
			=> '<span class="marquee-item">Showroom sales training &amp; what your team says to a walk-in</span>',

		'<span class="marquee-item">AI-leveraged delivery operations</span>'
			=> '<span class="marquee-item">Faster day-to-day work using the right tools</span>',

		'<span><b>Marketing &amp; sales</b> — performance ads, lead nurture, calling, CRM, attribution</span>'
			=> '<span><b>Marketing &amp; sales</b> — Google and Meta ads, calling every enquiry, WhatsApp follow-up, and tracking which ad brought the order</span>',

		'nobody runs the operating system underneath them.'
			=> 'nobody runs the day-to-day systems underneath them.',
	);

	/**
	 * Filter the public-copy correction table.
	 *
	 * @param array $corrections old => new.
	 */
	return (array) apply_filters( 'brandsure_copy_corrections', $corrections );
}

/**
 * Apply the corrections to rendered content.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_copy_apply( $content ) {
	if ( is_admin() || ! is_main_query() || ! in_the_loop() ) {
		return $content;
	}

	$corrections = brandsure_copy_corrections();

	return str_replace(
		array_keys( $corrections ),
		array_values( $corrections ),
		$content
	);
}
add_filter( 'the_content', 'brandsure_copy_apply', 8 );
