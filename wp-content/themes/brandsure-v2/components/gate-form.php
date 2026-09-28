<?php
/**
 * The gate — four numbers into a prefilled WhatsApp message.
 *
 * The whole public site exists to get someone here, so this component is
 * built to fail softly at every point:
 *
 *   1. It is a LINK, not a form submission. The href is a valid WhatsApp
 *      deep link from the moment the page renders; JavaScript only rewrites
 *      it as the owner types. With no JavaScript the button still opens
 *      WhatsApp — without the four numbers, which the AI then asks for. That
 *      is a worse conversation, not a broken one.
 *   2. Nothing is ever POSTed. The four numbers reach BrandSure only inside
 *      the message the owner sends from their own device. The site records
 *      that the gate was used, never what was in it.
 *   3. With no WhatsApp number configured yet (docs/OPEN-ITEMS.md item 1)
 *      the component degrades to whatever contact route does exist, and
 *      tells an administrator what is missing.
 *
 * @param array $args {
 *     @type string $headline
 *     @type string $body
 *     @type array  $fields  [{ key, label, hint, example }]
 *     @type string $cta     Button label.
 *     @type string $note    Reassurance line under the button.
 * }
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

$bs_fields = (array) ( $args['fields'] ?? array() );

if ( ! $bs_fields ) {
	return;
}

$bs_template = (string) bs_site( 'whatsapp_templates.gate', '' );
$bs_link     = bs_whatsapp_link( '', bs_gate_default_message() );
$bs_fallback = '';

// No WhatsApp number yet. Offer the next best route rather than a dead
// control; the guard blocks launch while this is the case.
if ( '' === $bs_link ) {
	$bs_email = (string) bs_site( 'contact.email', '' );
	$bs_tel   = (string) bs_site( 'contact.phone_tel', '' );

	if ( '' !== $bs_email ) {
		$bs_fallback = 'mailto:' . $bs_email . '?subject=' . rawurlencode( __( 'My four numbers', 'brandsure-v2' ) );
	} elseif ( '' !== $bs_tel ) {
		$bs_fallback = 'tel:' . $bs_tel;
	}
}

$bs_href = '' !== $bs_link ? $bs_link : $bs_fallback;

get_template_part(
	'components/section',
	null,
	array( 'open' => true, 'tone' => (string) ( $args['tone'] ?? 'light' ), 'width' => 'prose', 'class' => 'bs-gateform' )
);

?>
<div
	class="bs-gateform__box"
	data-bs-gate
	data-bs-gate-number="<?php echo esc_attr( (string) bs_site( 'contact.whatsapp_number', '' ) ); ?>"
	data-bs-gate-template="<?php echo esc_attr( $bs_template ); ?>">

	<?php if ( ! empty( $args['headline'] ) ) : ?>
		<h2 class="bs-gateform__headline"><?php echo esc_html( (string) $args['headline'] ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $args['body'] ) ) : ?>
		<p class="bs-gateform__body"><?php echo esc_html( (string) $args['body'] ); ?></p>
	<?php endif; ?>

	<div class="bs-gateform__fields">
		<?php foreach ( $bs_fields as $bs_index => $bs_field ) : ?>
			<?php
			$bs_key  = sanitize_key( (string) ( $bs_field['key'] ?? '' ) );
			$bs_id   = 'bs-gate-' . $bs_key;
			$bs_hint = (string) ( $bs_field['hint'] ?? '' );

			if ( '' === $bs_key ) {
				continue;
			}
			?>
			<div class="bs-gateform__field">
				<label class="bs-gateform__label" for="<?php echo esc_attr( $bs_id ); ?>">
					<span class="bs-gateform__num"><?php echo esc_html( (string) ( $bs_index + 1 ) ); ?></span>
					<?php echo esc_html( (string) ( $bs_field['label'] ?? '' ) ); ?>
				</label>

				<input
					class="bs-gateform__input"
					type="text"
					id="<?php echo esc_attr( $bs_id ); ?>"
					name="<?php echo esc_attr( $bs_key ); ?>"
					data-bs-gate-field="<?php echo esc_attr( $bs_key ); ?>"
					placeholder="<?php echo esc_attr( (string) ( $bs_field['example'] ?? '' ) ); ?>"
					autocomplete="off"
					<?php if ( '' !== $bs_hint ) : ?>
						aria-describedby="<?php echo esc_attr( $bs_id . '-hint' ); ?>"
					<?php endif; ?>
					>

				<?php if ( '' !== $bs_hint ) : ?>
					<p class="bs-gateform__hint" id="<?php echo esc_attr( $bs_id . '-hint' ); ?>">
						<?php echo esc_html( $bs_hint ); ?>
					</p>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>

	<?php if ( '' !== $bs_href ) : ?>
		<a
			class="bs-btn bs-btn--primary bs-btn--block bs-gateform__send"
			href="<?php echo esc_url( $bs_href ); ?>"
			rel="noopener"
			data-bs-gate-send
			data-bs-event="gate_whatsapp_click">
			<?php get_template_part( 'components/icon', null, array( 'name' => 'whatsapp' ) ); ?>
			<?php echo esc_html( (string) ( $args['cta'] ?? __( 'Send on WhatsApp', 'brandsure-v2' ) ) ); ?>
		</a>
	<?php elseif ( current_user_can( 'edit_posts' ) ) : ?>
		<p class="bs-gateform__admin">
			<?php
			esc_html_e(
				'No WhatsApp number, email or phone number is configured in data/site.json, so this page has no way to send anything. The guard blocks launch until one is set.',
				'brandsure-v2'
			);
			?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $args['note'] ) ) : ?>
		<p class="bs-gateform__note"><?php echo esc_html( (string) $args['note'] ); ?></p>
	<?php endif; ?>
</div>
<?php

get_template_part( 'components/section', null, array( 'close' => true ) );
