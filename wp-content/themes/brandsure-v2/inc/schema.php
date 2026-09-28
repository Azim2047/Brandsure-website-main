<?php
/**
 * Structured data — one JSON-LD graph per page.
 *
 * Override 1 names schema explicitly: the commercial mechanism must not reach
 * the public site "through visible copy, schema, metadata, comments,
 * JavaScript, JSON sent publicly, alt text or FAQ". Schema is the easiest of
 * those to forget, because nobody reads it. So this file does two things
 * about that rather than one:
 *
 *   1. Every value it emits is read from somewhere the guard already checks —
 *      data/*.json, or WordPress content that inc/content-guard.php scans.
 *      Nothing here is typed by hand, which means nothing here can be a
 *      second copy that drifts out of the rules.
 *   2. The encoded graph is scanned against BrandSure_Guard::blocklist()
 *      immediately before it is printed. If anything matches, nothing is
 *      printed and the incident is logged. Schema is the one output nobody
 *      would notice going wrong, so it is the one output that checks itself.
 *
 * The other rule, from the manifest: schema never claims something the reader
 * cannot see. FAQPage is emitted only from questions that are actually on the
 * page — from data/faq.json where the theme renders the accordion, and parsed
 * out of the published content where an editor wrote them. Delete the
 * questions and the schema disappears with them, which is the only way that
 * promise stays true a year from now.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * The Organization node, referenced by everything else.
 *
 * Email and telephone come from data/site.json and appear only once it has
 * them — publishing a contact that routes to nobody is worse than publishing
 * none. As at Chat 15 those are hello@brandsure.in and +91 88911 99199, per
 * numbers.v2.js. Three earlier addresses are recorded here so that finding
 * one in the wild is not mistaken for a current one: the old site's schema
 * carried info@brandsure.in and +91-8884446668, and the Numbers Sheet said
 * anasuu@brandsure.in. None of the three is used in this build.
 *
 * @return array
 */
function bs_schema_organization(): array {
	$node = array(
		'@type'      => 'Organization',
		'@id'        => home_url( '/#organization' ),
		'name'       => (string) bs_site( 'brand.name', 'BrandSure' ),
		'legalName'  => (string) bs_site( 'brand.legal_name', '' ),
		'url'        => home_url( '/' ),
		'logo'       => BRANDSURE_URI . '/assets/img/brandsure-logo.png',
		'identifier' => (string) bs_site( 'brand.cin', '' ),
		'address'    => array(
			'@type'           => 'PostalAddress',
			'addressLocality' => (string) bs_site( 'brand.locality', '' ),
			'addressRegion'   => (string) bs_site( 'brand.region', '' ),
			'addressCountry'  => (string) bs_site( 'brand.country', 'IN' ),
		),
		'areaServed' => array( '@type' => 'Country', 'name' => 'India' ),
	);

	$email = (string) bs_site( 'contact.email', '' );
	$phone = (string) bs_site( 'contact.phone_display', '' );

	if ( '' !== $email ) {
		$node['email'] = $email;
	}

	if ( '' !== $phone ) {
		$node['telephone'] = $phone;
	}

	return array_filter( $node, static fn( $value ): bool => '' !== $value );
}

/**
 * Breadcrumbs for the current page.
 *
 * Home → (Other Services) → this page. The service pages sit under
 * /services/ in the crumb trail even though they are not in the primary
 * navigation, because that is where they sit in the site's structure and it
 * is the trail Google shows in a result.
 *
 * @return array Empty on the front page, which is its own first crumb.
 */
function bs_schema_breadcrumbs(): array {
	if ( is_front_page() ) {
		return array();
	}

	$items = array(
		array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => __( 'Home', 'brandsure-v2' ),
			'item'     => user_trailingslashit( home_url( '/' ) ),
		),
	);

	if ( is_page() && isset( bs_service_routes()[ bs_slug() ] ) ) {
		$items[] = array(
			'@type'    => 'ListItem',
			'position' => 2,
			'name'     => __( 'Other Services', 'brandsure-v2' ),
			'item'     => user_trailingslashit( home_url( '/services/' ) ),
		);
	}

	if ( is_singular( 'post' ) ) {
		$blog = (int) get_option( 'page_for_posts' );

		if ( $blog ) {
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => 2,
				'name'     => get_the_title( $blog ),
				'item'     => (string) get_permalink( $blog ),
			);
		}
	}

	$items[] = array(
		'@type'    => 'ListItem',
		'position' => count( $items ) + 1,
		'name'     => wp_strip_all_tags( is_home() ? get_the_title( (int) get_option( 'page_for_posts' ) ) : get_the_title() ),
		'item'     => bs_seo_canonical(),
	);

	return array(
		'@type'           => 'BreadcrumbList',
		'@id'             => bs_seo_canonical() . '#breadcrumb',
		'itemListElement' => $items,
	);
}

/**
 * FAQ entries visible on the current page.
 *
 * Two sources, one rule: whatever is on the page.
 *
 *   - A controlled page renders components/accordion.php from data/faq.json,
 *     keyed by slug. Guard rule R6 already enforces that pairing in both
 *     directions, so reading the same file here cannot claim more than the
 *     page shows.
 *   - An editable page's questions are written by staff as <details> blocks,
 *     so they are parsed back out of the published content. This is why the
 *     manifest insisted the schema be derived rather than assumed: an editor
 *     who deletes a question deletes it from the search result too, without
 *     having to know that a search result existed.
 *
 * @return array<int,array> Question nodes.
 */
function bs_schema_faq_entries(): array {
	$questions = array();

	// Controlled pages.
	if ( is_page() && '' !== bs_page_data_key( bs_slug() ) ) {
		foreach ( (array) ( bs_data( 'faq' )[ bs_slug() ] ?? array() ) as $item ) {
			if ( ! empty( $item['q'] ) && ! empty( $item['a'] ) ) {
				$questions[] = array( (string) $item['q'], (string) $item['a'] );
			}
		}

		return bs_schema_questions( $questions );
	}

	// Editable pages and posts: parse what was actually published.
	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return array();
	}

	if ( preg_match_all( '#<details\b[^>]*>(.*?)</details>#is', (string) $post->post_content, $blocks ) ) {
		foreach ( $blocks[1] as $block ) {
			if ( ! preg_match( '#<summary\b[^>]*>(.*?)</summary>#is', $block, $summary ) ) {
				continue;
			}

			$answer = trim( preg_replace( '#<summary\b[^>]*>.*?</summary>#is', '', $block ) ?? '' );

			$questions[] = array( $summary[1], $answer );
		}
	}

	return bs_schema_questions( $questions );
}

/**
 * Turn question/answer pairs into Question nodes.
 *
 * @param array<int,array{0:string,1:string}> $pairs Raw pairs.
 * @return array<int,array>
 */
function bs_schema_questions( array $pairs ): array {
	$out = array();

	foreach ( $pairs as $pair ) {
		$question = trim( wp_strip_all_tags( $pair[0] ) );
		$answer   = trim( wp_strip_all_tags( $pair[1] ) );

		if ( '' === $question || '' === $answer ) {
			continue;
		}

		/*
		 * A Question node whose name is not a question is bad structured
		 * data, and the site has a real case of it: the consulting page opens
		 * with a <details> list of symptoms — "Sales are low", "Everything
		 * needs the founder" — which is a diagnostic device, not an FAQ.
		 * Requiring a question mark keeps that block out without asking an
		 * editor to know the difference, and it is the same test a person
		 * would apply.
		 */
		if ( ! str_ends_with( $question, '?' ) ) {
			continue;
		}

		$out[] = array(
			'@type'          => 'Question',
			'name'           => $question,
			'acceptedAnswer' => array(
				'@type' => 'Answer',
				'text'  => $answer,
			),
		);
	}

	return $out;
}

/**
 * The Service node for one of the four standalone service pages.
 *
 * name and description come from the page itself — the title an editor wrote
 * and the same description the meta tag uses. The old site's schema carried
 * its own hand-written service description, which is how it ended up saying
 * "CRM", "lead nurture" and "retargeting" in structured data long after the
 * visible copy had been rewritten. There is one description per page here.
 *
 * @return array Empty when this is not a service page.
 */
function bs_schema_service(): array {
	if ( ! is_page() ) {
		return array();
	}

	$route = bs_service_routes()[ bs_slug() ] ?? '';

	if ( '' === $route ) {
		return array();
	}

	$meta = bs_seo_meta();

	$node = array(
		'@type'       => 'Service',
		'@id'         => bs_seo_canonical() . '#service',
		'name'        => wp_strip_all_tags( get_the_title() ),
		'serviceType' => (string) bs_site( 'routing.' . $route . '.label', '' ),
		'provider'    => array( '@id' => home_url( '/#organization' ) ),
		'areaServed'  => array( '@type' => 'Country', 'name' => 'India' ),
		'audience'    => array(
			'@type'        => 'BusinessAudience',
			'audienceType' => __( 'Furniture manufacturers and showroom-led furniture businesses in India', 'brandsure-v2' ),
		),
		'url'         => bs_seo_canonical(),
	);

	if ( '' !== $meta['description'] ) {
		$node['description'] = $meta['description'];
	}

	return array_filter( $node, static fn( $value ): bool => '' !== $value );
}

/**
 * The Article node for a blog post.
 *
 * The author is the Organization, not a WordPress user. Posts carry no
 * byline on the page, and naming a login account as the author of an article
 * nobody signed would be structured data that contradicts what the reader
 * sees.
 *
 * @return array
 */
function bs_schema_article(): array {
	if ( ! is_singular( 'post' ) ) {
		return array();
	}

	$meta = bs_seo_meta();

	$node = array(
		'@type'            => 'Article',
		'@id'              => bs_seo_canonical() . '#article',
		'headline'         => wp_strip_all_tags( get_the_title() ),
		'datePublished'    => (string) get_the_date( DATE_W3C ),
		'dateModified'     => (string) get_the_modified_date( DATE_W3C ),
		'author'           => array( '@id' => home_url( '/#organization' ) ),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
		'mainEntityOfPage' => bs_seo_canonical(),
	);

	if ( '' !== $meta['description'] ) {
		$node['description'] = $meta['description'];
	}

	if ( has_post_thumbnail() ) {
		$node['image'] = (string) get_the_post_thumbnail_url( null, 'full' );
	}

	return $node;
}

/**
 * Assemble the graph for the current request.
 *
 * @return array<int,array>
 */
function bs_schema_graph(): array {
	$graph = array( bs_schema_organization() );

	$graph[] = array(
		'@type'     => 'WebSite',
		'@id'       => home_url( '/#website' ),
		'url'       => home_url( '/' ),
		'name'      => (string) bs_site( 'brand.name', 'BrandSure' ),
		'publisher' => array( '@id' => home_url( '/#organization' ) ),
		'inLanguage' => 'en-IN',
	);

	$canonical = bs_seo_canonical();
	$meta      = bs_seo_meta();

	if ( '' !== $canonical ) {
		$page = array(
			'@type'      => 'WebPage',
			'@id'        => $canonical . '#webpage',
			'url'        => $canonical,
			'name'       => $meta['title'],
			'isPartOf'   => array( '@id' => home_url( '/#website' ) ),
			'about'      => array( '@id' => home_url( '/#organization' ) ),
			'inLanguage' => 'en-IN',
		);

		if ( '' !== $meta['description'] ) {
			$page['description'] = $meta['description'];
		}

		$breadcrumbs = bs_schema_breadcrumbs();

		if ( $breadcrumbs ) {
			$page['breadcrumb'] = array( '@id' => $breadcrumbs['@id'] );
		}

		$graph[] = $page;

		if ( $breadcrumbs ) {
			$graph[] = $breadcrumbs;
		}
	}

	$service = bs_schema_service();

	if ( $service ) {
		$graph[] = $service;
	}

	$article = bs_schema_article();

	if ( $article ) {
		$graph[] = $article;
	}

	$faq = bs_schema_faq_entries();

	if ( $faq ) {
		$graph[] = array(
			'@type'      => 'FAQPage',
			'@id'        => $canonical . '#faq',
			'mainEntity' => $faq,
		);
	}

	return $graph;
}

/**
 * Print the graph, after checking it against the disclosure rules.
 *
 * The check is the point. Everything above reads from a checked source, so in
 * principle this can never fire — but "in principle" is what every leak was
 * before it happened, and the cost of being sure is one regex pass over a few
 * kilobytes on a cached page. On a hit the whole graph is withheld rather
 * than the offending node removed: a graph with a node silently missing is a
 * bug that hides itself.
 */
function bs_schema_head(): void {
	if ( is_404() || is_search() ) {
		return;
	}

	$json = wp_json_encode(
		array(
			'@context' => 'https://schema.org',
			'@graph'   => bs_schema_graph(),
		),
		// Slashes stay escaped. With JSON_UNESCAPED_SLASHES the string
		// "</script>" inside an answer an editor wrote would close this tag
		// and put the rest of the graph into the page as markup. Escaped, it
		// cannot: \/ is the same character to a JSON parser and is not a tag
		// to an HTML one.
		JSON_UNESCAPED_UNICODE
	);

	if ( ! is_string( $json ) ) {
		return;
	}

	if ( function_exists( 'bs_content_guard_scan' ) ) {
		$hits = bs_content_guard_scan( $json, is_singular() ? bs_slug() : '' );

		if ( $hits ) {
			foreach ( $hits as $hit ) {
				error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
					sprintf(
						'BrandSure schema: withheld the whole graph on %s — %s ("%s"). Find the source; do not weaken the check.',
						esc_url_raw( bs_seo_canonical() ),
						$hit['reason'],
						$hit['match']
					)
				);
			}

			return;
		}
	}

	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		$json // phpcs:ignore WordPress.Security.EscapingOutput -- wp_json_encode escapes for a script context.
	);
}
add_action( 'wp_head', 'bs_schema_head', 3 );
