<?php
/**
 * Trust strip — the factory credentials.
 *
 * Directly under the hero. The claim that earns the next ten seconds is not
 * that BrandSure does marketing; it is that BrandSure has run furniture
 * factories. That is the one thing an owner cannot say about the agencies
 * who call him.
 *
 * Values come from data/commercial.json by fact key, never as literals in the
 * page file — so the Numbers Sheet stays the single source and the guard can
 * check them mechanically.
 *
 * @param array $args {
 *     @type array $facts List of fact keys from data/commercial.json.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_facts = (array) ( $args['facts'] ?? array() );

if ( ! $bs_facts ) {
	return;
}

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'class' => 'bs-trust' )
);

?>
<ul class="bs-trust__list">
	<?php foreach ( $bs_facts as $bs_key ) : ?>
		<?php
		$bs_value = bs_fact( (string) $bs_key );

		if ( '' === $bs_value ) {
			continue;
		}
		?>
		<li class="bs-trust__item"><?php echo esc_html( (string) $bs_value ); ?></li>
	<?php endforeach; ?>
</ul>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
