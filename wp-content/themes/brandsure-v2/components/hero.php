<?php
/**
 * Hero.
 *
 * The five seconds the whole site is judged in. An owner on a phone should
 * finish the headline knowing this company is about furniture and about the
 * part of his business he has not built.
 *
 * @param array $args {
 *     @type string $eyebrow  Short line above the headline.
 *     @type string $headline Required.
 *     @type string $body     One paragraph.
 *     @type array  $primary  { label, url, event }
 *     @type array  $secondary { label, url }
 *     @type string $tone     Section tone. Defaults to 'dark'.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_headline = (string) ( $args['headline'] ?? '' );

if ( '' === $bs_headline ) {
	return;
}

$bs_primary   = (array) ( $args['primary'] ?? array() );
$bs_secondary = (array) ( $args['secondary'] ?? array() );

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'dark' ), 'class' => 'bs-hero' )
);

?>
<div class="bs-hero__inner">

	<?php if ( ! empty( $args['eyebrow'] ) ) : ?>
		<p class="bs-hero__eyebrow"><?php echo esc_html( (string) $args['eyebrow'] ); ?></p>
	<?php endif; ?>

	<h1 class="bs-hero__headline"><?php echo esc_html( $bs_headline ); ?></h1>

	<?php if ( ! empty( $args['body'] ) ) : ?>
		<p class="bs-hero__body"><?php echo esc_html( (string) $args['body'] ); ?></p>
	<?php endif; ?>

	<?php if ( $bs_primary || $bs_secondary ) : ?>
		<div class="bs-hero__actions">
			<?php
			bs_the_button(
				array(
					'label'   => (string) ( $bs_primary['label'] ?? '' ),
					'url'     => (string) ( $bs_primary['url'] ?? '' ),
					'variant' => 'primary',
					'block'   => true,
				)
			);

			bs_the_button(
				array(
					'label'   => (string) ( $bs_secondary['label'] ?? '' ),
					'url'     => (string) ( $bs_secondary['url'] ?? '' ),
					'variant' => 'secondary',
					'block'   => true,
				)
			);
			?>
		</div>
	<?php endif; ?>

</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
