<?php
/**
 * The WhatsApp panel — a bottom sheet on phones, a corner panel on desktop.
 *
 * One component, one set of markup, two presentations. It is rendered once
 * per page from the footer and opened by two different launchers: the sticky
 * bar's "Chat on WhatsApp" on phones, and a floating button on desktop where
 * the sticky bar is hidden. There is deliberately no second floating button
 * on mobile — two controls that do the same thing is the thing the brief
 * rules out, and on a 390px screen it is also a thumb-trap.
 *
 * WHAT IT IS NOT. It is not a chat window. Every option leaves the site and
 * opens WhatsApp on the visitor's own device with a message they can read
 * before sending. The footnote says exactly that, because implying the
 * conversation is happening on the website is the specific thing Layer 4 v4
 * forbids. There is no "online now", no response-time promise, no unread
 * badge and no scripted agent.
 *
 * PROGRESSIVE ENHANCEMENT. Every option is a real wa.me link with a real
 * prefilled message. With JavaScript off the panel is not a dialog — it is a
 * plain list at the end of the document, reachable from the launcher, which
 * is itself an ordinary link to WhatsApp. Nothing here needs script to work;
 * script only turns it into a sheet.
 *
 * WHERE IT DOES NOT GO.
 *   - /start/ — the four-number gate is the action there, and its handoff
 *     carries the visitor's own figures. A generic panel next to it would
 *     compete with the site's primary KPI and throw away answers already
 *     typed. bs_whatsapp_panel_enabled() enforces this rather than the
 *     footer template remembering to.
 *   - The four legal pages — no CTA there at all, by the same rule that keeps
 *     the sticky bar off them.
 *   - Private token pages — they have their own stage-specific action. This
 *     theme renders none of them, so it is a boundary written down rather
 *     than a branch, and bs_whatsapp_panel_enabled() is where it goes when
 *     they arrive.
 *
 * CONTEXT. On one of the four service pages the panel leads with that
 * service. On /furniture-labour-hiring-india/ it offers only labour hiring:
 * Layer 4 v4 §5 says not to push that buyer into the main growth
 * qualification, and offering them a plan is exactly that.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

if ( ! bs_whatsapp_panel_enabled() ) {
	return;
}

$bs_panel   = (array) bs_site( 'whatsapp_panel', array() );
$bs_options = bs_whatsapp_panel_options();
$bs_invite  = (array) ( $bs_panel['invite'] ?? array() );

if ( ! $bs_options ) {
	return;
}

$bs_services = array();

foreach ( $bs_options as $bs_option ) {
	if ( 'services' === ( $bs_option['children'] ?? '' ) ) {
		$bs_services = (array) ( $bs_panel['services'] ?? array() );
		break;
	}
}

?>
<div class="bs-wa" data-bs-wa>

	<?php
	/*
	 * The desktop launcher. Hidden below the sticky bar's breakpoint by CSS,
	 * because below it the sticky bar is the launcher. It is an <a> to
	 * WhatsApp, not a <button>: with no script it is still useful.
	 */
	?>
	<a class="bs-wa__fab"
		href="<?php echo esc_url( bs_whatsapp_link( bs_whatsapp_panel_default_route() ) ); ?>"
		rel="noopener"
		data-bs-wa-open="desktop"
		aria-haspopup="dialog"
		aria-controls="bs-wa-panel">
		<?php get_template_part( 'components/icon', null, array( 'name' => 'whatsapp' ) ); ?>
		<span class="bs-wa__fab-label"><?php esc_html_e( 'Chat on WhatsApp', 'brandsure-v2' ); ?></span>
	</a>

	<?php
	/*
	 * The one-time invitation. Rendered hidden and shown by script after real
	 * engagement — never on load, and never while somebody is mid-paragraph.
	 * Dismissal lasts the browsing session. With no script it never appears
	 * at all, which is the correct fallback for a nudge.
	 */
	?>
	<?php if ( ! empty( $bs_invite['text'] ) ) : ?>
		<div class="bs-wa__invite" data-bs-wa-invite hidden>
			<p class="bs-wa__invite-text"><?php echo esc_html( (string) $bs_invite['text'] ); ?></p>
			<div class="bs-wa__invite-actions">
				<button type="button" class="bs-wa__invite-yes" data-bs-wa-open="invite">
					<?php esc_html_e( 'Yes, help me', 'brandsure-v2' ); ?>
				</button>
				<button type="button" class="bs-wa__invite-no" data-bs-wa-invite-dismiss>
					<?php echo esc_html( (string) ( $bs_invite['dismiss_label'] ?? __( 'No thanks', 'brandsure-v2' ) ) ); ?>
				</button>
			</div>
		</div>
	<?php endif; ?>

	<?php
	/*
	 * Neither the scrim nor the panel carries hidden here, and that is the
	 * whole progressive-enhancement story in one attribute. Rendered hidden,
	 * a visitor without JavaScript would get nothing at all — the launcher
	 * would still reach WhatsApp, but the options would be gone. Rendered
	 * visible, they get a plain list of every option at the end of the page.
	 * brandsure.js hides both the moment it runs, before it makes the panel a
	 * dialog. The role and aria-modal are likewise only true once it is one,
	 * so the script sets them too rather than the markup claiming a dialog
	 * that does not exist yet.
	 */
	?>
	<div class="bs-wa__scrim" data-bs-wa-scrim></div>

	<div class="bs-wa__panel"
		id="bs-wa-panel"
		aria-labelledby="bs-wa-heading"
		data-bs-wa-panel>

		<div class="bs-wa__grip" aria-hidden="true"></div>

		<header class="bs-wa__head">
			<?php
			/*
			 * The supplied lockup already carries the wordmark, so the name is
			 * in the image rather than beside it — printing "BrandSure" next
			 * to a logo that says BrandSure reads as a mistake. The alt text
			 * is where the name lives for anyone not seeing the image.
			 */
			?>
			<img class="bs-wa__logo"
				src="<?php echo esc_url( BRANDSURE_URI . '/assets/img/brandsure-logo-dark-text.png' ); ?>"
				alt="<?php echo esc_attr( bs_site( 'brand.name', 'BrandSure' ) ); ?>"
				width="104"
				height="28"
				decoding="async">

			<button type="button"
				class="bs-wa__close"
				data-bs-wa-close
				aria-label="<?php echo esc_attr( (string) ( $bs_panel['close_label'] ?? __( 'Close', 'brandsure-v2' ) ) ); ?>">
				<?php get_template_part( 'components/icon', null, array( 'name' => 'close' ) ); ?>
			</button>
		</header>

		<div class="bs-wa__body">
			<h2 class="bs-wa__heading" id="bs-wa-heading">
				<?php echo esc_html( (string) ( $bs_panel['heading'] ?? '' ) ); ?>
			</h2>

			<?php if ( ! empty( $bs_panel['intro'] ) ) : ?>
				<p class="bs-wa__intro"><?php echo esc_html( (string) $bs_panel['intro'] ); ?></p>
			<?php endif; ?>

			<ul class="bs-wa__options" data-bs-wa-step="root">
				<?php foreach ( $bs_options as $bs_option ) : ?>
					<?php
					$bs_children = (string) ( $bs_option['children'] ?? '' );
					$bs_id       = (string) ( $bs_option['id'] ?? '' );
					?>
					<li class="bs-wa__option">
						<?php if ( '' !== $bs_children ) : ?>
							<?php
							/*
							 * A step, not a link. With no script the sub-list
							 * below is simply already visible, so this control
							 * has nothing to reveal and is hidden by CSS that
							 * only applies once script has enhanced the panel.
							 */
							?>
							<button type="button" class="bs-wa__choice" data-bs-wa-step-to="<?php echo esc_attr( $bs_children ); ?>">
								<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_option['icon'] ?? 'grid' ) ) ); ?>
								<span class="bs-wa__choice-text">
									<span class="bs-wa__choice-label"><?php echo esc_html( (string) ( $bs_option['label'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $bs_option['hint'] ) ) : ?>
										<span class="bs-wa__choice-hint"><?php echo esc_html( (string) $bs_option['hint'] ); ?></span>
									<?php endif; ?>
								</span>
								<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right', 'class' => 'bs-wa__chevron' ) ); ?>
							</button>
						<?php else : ?>
							<a class="bs-wa__choice"
								href="<?php echo esc_url( bs_whatsapp_panel_href( $bs_option ) ); ?>"
								rel="noopener"
								data-bs-wa-choice="<?php echo esc_attr( $bs_id ); ?>">
								<?php get_template_part( 'components/icon', null, array( 'name' => (string) ( $bs_option['icon'] ?? 'whatsapp' ) ) ); ?>
								<span class="bs-wa__choice-text">
									<span class="bs-wa__choice-label"><?php echo esc_html( (string) ( $bs_option['label'] ?? '' ) ); ?></span>
									<?php if ( ! empty( $bs_option['hint'] ) ) : ?>
										<span class="bs-wa__choice-hint"><?php echo esc_html( (string) $bs_option['hint'] ); ?></span>
									<?php endif; ?>
								</span>
								<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right', 'class' => 'bs-wa__chevron' ) ); ?>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $bs_services ) : ?>
				<div class="bs-wa__step" data-bs-wa-step="services">
					<button type="button" class="bs-wa__back" data-bs-wa-step-to="root">
						<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right', 'class' => 'bs-wa__back-icon' ) ); ?>
						<?php echo esc_html( (string) ( $bs_panel['back_label'] ?? __( 'Back', 'brandsure-v2' ) ) ); ?>
					</button>

					<h3 class="bs-wa__step-heading"><?php echo esc_html( (string) ( $bs_panel['services_heading'] ?? '' ) ); ?></h3>

					<ul class="bs-wa__options">
						<?php foreach ( $bs_services as $bs_service ) : ?>
							<li class="bs-wa__option">
								<a class="bs-wa__choice bs-wa__choice--compact"
									href="<?php echo esc_url( bs_whatsapp_panel_href( $bs_service ) ); ?>"
									rel="noopener"
									data-bs-wa-choice="service:<?php echo esc_attr( (string) ( $bs_service['id'] ?? '' ) ); ?>">
									<span class="bs-wa__choice-text">
										<span class="bs-wa__choice-label"><?php echo esc_html( (string) ( $bs_service['label'] ?? '' ) ); ?></span>
									</span>
									<?php get_template_part( 'components/icon', null, array( 'name' => 'arrow-right', 'class' => 'bs-wa__chevron' ) ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>

			<?php if ( ! empty( $bs_panel['footnote'] ) ) : ?>
				<p class="bs-wa__footnote"><?php echo esc_html( (string) $bs_panel['footnote'] ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
