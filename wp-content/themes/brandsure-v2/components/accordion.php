<?php
/**
 * FAQ accordion.
 *
 * Built on <details>/<summary>, so it opens and closes with no JavaScript,
 * is keyboard operable for free, and is announced correctly by screen
 * readers without a single ARIA attribute. A div-and-click implementation
 * would need all three written by hand and would still be worse.
 *
 * This is the ONLY component that may cause `FAQPage` schema to be emitted.
 * Guard rule R6 checks the pairing in both directions: a page rendering an
 * accordion must have entries under its slug in data/faq.json, and entries
 * in faq.json must correspond to a page that visibly renders them. Schema
 * claiming content the reader cannot see is the thing being prevented.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type string $key   Key into data/faq.json. Defaults to the page slug.
 *     @type string $tone
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_key   = (string) ( $args['key'] ?? bs_slug() );
$bs_faqs  = bs_data( 'faq' );
$bs_items = (array) ( $bs_faqs[ $bs_key ] ?? array() );

if ( ! $bs_items ) {
	bs_data_fail( sprintf( 'An accordion was requested for "%s" but data/faq.json has no such key', $bs_key ) );
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-faq' )
);

if ( ! empty( $args['headline'] ) ) {
	printf( '<h2 class="bs-faq__headline">%s</h2>', esc_html( (string) $args['headline'] ) );
}

if ( ! empty( $args['body'] ) ) {
	printf( '<p class="bs-faq__body">%s</p>', esc_html( (string) $args['body'] ) );
}

?>
<div class="bs-faq__list">
	<?php foreach ( $bs_items as $bs_item ) : ?>
		<?php
		$bs_question = (string) ( $bs_item['q'] ?? '' );
		$bs_answer   = (string) ( $bs_item['a'] ?? '' );

		if ( '' === $bs_question || '' === $bs_answer ) {
			continue;
		}
		?>
		<details class="bs-faq__item">
			<summary class="bs-faq__q">
				<span><?php echo esc_html( $bs_question ); ?></span>
				<span class="bs-faq__chevron" aria-hidden="true"></span>
			</summary>
			<div class="bs-faq__a">
				<p><?php echo esc_html( $bs_answer ); ?></p>
			</div>
		</details>
	<?php endforeach; ?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
