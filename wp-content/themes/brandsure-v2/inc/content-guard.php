<?php
/**
 * The runtime content guard — the disclosure rules, applied to what people type.
 *
 * inc/guard.php protects developer-owned content: it reads files, runs in CI,
 * and fails the build. It cannot see a word of WordPress editor content,
 * because that lives in the database and is written long after any build.
 *
 * From the moment `/about/` exists, that is the largest hole in Override 1.
 * A staff member editing a service page in good faith — pasting a sentence
 * out of a proposal, answering a customer question in a blog post — can put
 * the partnership percentage on the public site, and nothing in the project
 * would notice. This closes it.
 *
 * Two layers, in the order that matters:
 *
 *   1. SAVE TIME is the real defence. A post containing a private fact will
 *      not publish. It is held as a draft and the editor is told, in plain
 *      words, which phrase stopped it and where the rule comes from. This is
 *      the only moment a human is present, holding the context, able to
 *      rewrite the sentence — so it is the right moment to intervene.
 *
 *   2. RENDER TIME is the last resort, for content that got into the
 *      database another way: an import, a migration, a direct SQL edit, a
 *      plugin. The matched phrase is redacted from the output and the
 *      incident logged. A page with one elided phrase is embarrassing;
 *      publishing the commercial mechanism is not recoverable.
 *
 * The blocklist is BrandSure_Guard::blocklist() — one definition, shared, so
 * the build check and the runtime check can never disagree.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extra rules that apply only on certain pages.
 *
 * The four service pages are PUBLIC-NEUTRAL: no plan pricing, ever. The fee
 * is not a private fact — it is published in full on /plans/ — so the
 * disclosure blocklist has no reason to catch it, and yet a fee stated on a
 * service page is still a real problem. It is a second place the price
 * lives, editable by staff, and it will go stale: the page this rule was
 * written for still carried ₹80,000, a figure that is not in the Numbers
 * Sheet at all.
 *
 * "Customers keep asking the price" is a reasonable thing for an editor to
 * think, which is exactly why the answer needs to be a rule rather than a
 * convention.
 *
 * @return array<string,array<string,string>> Slug => (reason => regex).
 */
function bs_content_guard_page_rules(): array {
	/*
	 * The figures are the plan fees themselves: ₹45,000 and ₹75,000 monthly,
	 * ₹25,000 setup, ₹1,00,000 month one. Chat 12's audit found the list had
	 * carried ₹90,000, which is not a fee in any Numbers Sheet, and had
	 * omitted the month-one figure, which is — so the rule was guarding one
	 * number that does not exist and missing one that does.
	 *
	 * Deliberately not on the list: any other rupee figure. The growth-
	 * marketing page says "a ₹80,000 sofa" — a customer's ticket size, not a
	 * BrandSure price, and exactly the concrete detail that makes an owner
	 * feel the page was written for him. A rule that flagged it would be
	 * training people to ignore the rule.
	 */
	$no_plan_pricing = array(
		'a plan fee — those live on /plans/ only' => '/₹\s?(45|75|25)[,.]?0{3}|₹\s?1[,.]?00[,.]?000/',
		'a plan name with a price beside it'      => '/(Agency|Digital Partner)[^.\n]{0,40}₹/i',
	);

	return array(
		'furniture-growth-marketing-india'          => $no_plan_pricing,
		'furniture-business-consulting-india'       => $no_plan_pricing,
		'furniture-product-design-services-india'   => $no_plan_pricing,
		'furniture-labour-hiring-india'             => $no_plan_pricing,
	);
}

/**
 * Find private facts in a piece of content.
 *
 * @param string $content Raw post content or rendered HTML.
 * @param string $slug    Page slug, for the page-scoped rules. Optional.
 * @return array<int,array{reason:string,match:string}>
 */
function bs_content_guard_scan( string $content, string $slug = '' ): array {
	if ( '' === trim( $content ) || ! class_exists( 'BrandSure_Guard' ) ) {
		return array();
	}

	$patterns = BrandSure_Guard::blocklist();

	if ( '' !== $slug ) {
		$patterns += bs_content_guard_page_rules()[ $slug ] ?? array();
	}

	$hits = array();

	foreach ( $patterns as $reason => $pattern ) {
		if ( preg_match( $pattern, $content, $match ) ) {
			$hits[] = array(
				'reason' => $reason,
				'match'  => (string) $match[0],
			);
		}
	}

	return $hits;
}

/* -------------------------------------------------------------------------
 * Layer 1 — save time
 * ---------------------------------------------------------------------- */

/**
 * Refuse to publish content carrying a private fact.
 *
 * The post is kept — nobody's writing is thrown away — but held as a draft
 * until the phrase is removed. Auto-drafts, revisions and trashed posts are
 * left alone; so is anything already not public, because holding a draft as
 * a draft helps nobody.
 *
 * @param array $data    Sanitised post data about to be written.
 * @param array $postarr Raw post data.
 * @return array
 */
function bs_content_guard_on_save( array $data, array $postarr ): array {
	$skip_types = array( 'revision', 'auto-draft', 'nav_menu_item', 'wp_global_styles' );

	if ( in_array( (string) ( $data['post_type'] ?? '' ), $skip_types, true ) ) {
		return $data;
	}

	// Only public statuses can leak anything.
	if ( ! in_array( (string) ( $data['post_status'] ?? '' ), array( 'publish', 'future', 'private' ), true ) ) {
		return $data;
	}

	$hits = bs_content_guard_scan(
		(string) ( $data['post_content'] ?? '' ) . "\n" . (string) ( $data['post_title'] ?? '' ) . "\n" . (string) ( $data['post_excerpt'] ?? '' ),
		(string) ( $data['post_name'] ?? '' )
	);

	if ( ! $hits ) {
		return $data;
	}

	$data['post_status'] = 'draft';

	set_transient(
		'bs_content_guard_' . get_current_user_id(),
		array(
			'title' => (string) ( $data['post_title'] ?? '' ),
			'hits'  => $hits,
		),
		120
	);

	foreach ( $hits as $hit ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			sprintf(
				'BrandSure content guard: held post %d as a draft — %s ("%s")',
				(int) ( $postarr['ID'] ?? 0 ),
				$hit['reason'],
				$hit['match']
			)
		);
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'bs_content_guard_on_save', 20, 2 );

/**
 * Explain what happened, in words an editor can act on.
 *
 * A page that silently refuses to publish is a bug report waiting to happen.
 * This names the phrase, says why it cannot be public, and says who to ask.
 */
function bs_content_guard_notice(): void {
	$held = get_transient( 'bs_content_guard_' . get_current_user_id() );

	if ( ! is_array( $held ) || empty( $held['hits'] ) ) {
		return;
	}

	delete_transient( 'bs_content_guard_' . get_current_user_id() );

	$phrases = array();

	foreach ( $held['hits'] as $hit ) {
		$phrases[] = sprintf( '<code>%s</code> (%s)', esc_html( $hit['match'] ), esc_html( $hit['reason'] ) );
	}

	printf(
		'<div class="notice notice-error"><p><strong>%s</strong></p><p>%s %s</p><p>%s</p></div>',
		esc_html__( 'This page was saved as a draft instead of being published.', 'brandsure-v2' ),
		esc_html__( 'It contains wording that BrandSure does not publish on the public website:', 'brandsure-v2' ),
		wp_kses_post( implode( ', ', $phrases ) ),
		esc_html__(
			'These are commercial details that are only explained privately, after a business review. Remove or reword the phrase and publish again. If you believe it should be allowed, ask the development team rather than working around it.',
			'brandsure-v2'
		)
	);
}
add_action( 'admin_notices', 'bs_content_guard_notice' );

/* -------------------------------------------------------------------------
 * Layer 2 — render time
 * ---------------------------------------------------------------------- */

/**
 * Redact a private fact that reached the front end anyway.
 *
 * Only runs on the public side. The elision keeps the sentence standing
 * rather than removing it, so the page still reads; the incident is logged
 * so it can be found and fixed properly.
 *
 * @param string $content Rendered content.
 * @return string
 */
function bs_content_guard_on_render( string $content ): string {
	if ( is_admin() ) {
		return $content;
	}

	$slug = function_exists( 'bs_slug' ) ? bs_slug() : '';
	$hits = bs_content_guard_scan( $content, $slug );

	if ( ! $hits ) {
		return $content;
	}

	$patterns = BrandSure_Guard::blocklist() + ( bs_content_guard_page_rules()[ $slug ] ?? array() );

	foreach ( $patterns as $reason => $pattern ) {
		$content = preg_replace( $pattern, '&hellip;', $content );
	}

	error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
		sprintf(
			'BrandSure content guard: redacted %d private fact(s) while rendering %s. This content should never have been published — find and fix the source.',
			count( $hits ),
			esc_url_raw( home_url( add_query_arg( array() ) ) )
		)
	);

	return $content;
}
add_filter( 'the_content', 'bs_content_guard_on_render', 999 );
add_filter( 'the_excerpt', 'bs_content_guard_on_render', 999 );
add_filter( 'widget_text', 'bs_content_guard_on_render', 999 );
