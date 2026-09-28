<?php
/**
 * Titles, descriptions, canonicals, robots and Open Graph.
 *
 * One rule shapes this whole file: SEO metadata is not a second copy of the
 * page. A controlled page's title and description already exist, once, in its
 * data file; an editable page's already exist in WordPress. This reads
 * whichever one owns the page and never introduces a third place to edit —
 * which is Override 4 applied to the parts of a page nobody sees.
 *
 * The consequence worth stating: there is no SEO settings screen, and there
 * will not be one. A plugin that adds per-page title and description fields
 * would put a second editable copy of the page's own words behind a tab
 * nobody opens, and the two would drift within a month.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pages that must never be indexed.
 *
 * One list, used by three things that would otherwise be edited separately
 * and drift: the robots tag, the XML sitemap, and robots.txt. A page that is
 * noindex but still listed in the sitemap is a mixed signal, and the way that
 * happens is exactly this rule living in three files.
 *
 * @return array<int,string>
 */
function bs_noindex_slugs(): array {
	return array( 'built-by-brandsure' );
}

/**
 * The data key for a controlled page, derived from its bound template.
 *
 * templates/page-built-by.php owns data/pages/built-by.json; the naming
 * already matches everywhere, so the existing template map is the mapping
 * and no second registry is needed.
 *
 * @param string $slug Page slug. Empty string for the front page.
 * @return string Data key, e.g. 'pages/built-by', or '' when not controlled.
 */
function bs_page_data_key( string $slug ): string {
	$map = bs_ownership_templates();

	// The front page is reached at '' but is the 'home' entry in both maps.
	if ( '' === $slug ) {
		$slug = 'home';
	}

	if ( ! isset( $map[ $slug ] ) ) {
		return '';
	}

	$name = basename( $map[ $slug ], '.php' );

	if ( ! str_starts_with( $name, 'page-' ) ) {
		return '';
	}

	$key = 'pages/' . substr( $name, 5 );

	return is_readable( BRANDSURE_DIR . '/data/' . $key . '.json' ) ? $key : '';
}

/**
 * Title and description for the current request.
 *
 * @return array{title:string,description:string}
 */
function bs_seo_meta(): array {
	$name = (string) bs_site( 'brand.name', 'BrandSure' );

	// The blog index. is_home() is checked before is_singular because the
	// posts page is a page object too.
	if ( is_home() && ! is_front_page() ) {
		return array(
			'title'       => (string) bs_site( 'seo.blog.title', $name ),
			'description' => (string) bs_site( 'seo.blog.meta_description', '' ),
		);
	}

	if ( is_404() ) {
		$page = bs_data( 'pages/404' );

		return array(
			'title'       => (string) ( $page['title'] ?? $name ),
			'description' => (string) ( $page['meta_description'] ?? '' ),
		);
	}

	if ( ! is_singular() ) {
		return array( 'title' => $name, 'description' => '' );
	}

	$slug = bs_slug();
	$key  = is_page() ? bs_page_data_key( $slug ) : '';

	// Controlled page: the data file owns both.
	if ( '' !== $key ) {
		$page = bs_data( $key );

		return array(
			'title'       => (string) ( $page['title'] ?? get_the_title() ),
			'description' => (string) ( $page['meta_description'] ?? '' ),
		);
	}

	// Editable page or post: WordPress owns both.
	return array(
		'title'       => bs_seo_title_with_brand( wp_strip_all_tags( get_the_title() ), $name ),
		'description' => bs_seo_description_from_post(),
	);
}

/**
 * A page title with the brand appended — unless that would push it past what
 * a search result shows.
 *
 * The suffix is worth having on a short title and actively harmful on a long
 * one: past roughly 60 characters Google truncates, so " — BrandSure" buys
 * nothing and costs the end of the actual title. Chat 13's audit found this
 * on a blog post whose own headline was already 56 characters. Google appends
 * the site name itself when it wants to, so dropping it loses nothing.
 *
 * Controlled pages are unaffected — their titles are written whole in
 * data/pages/*.json and guard rule R10 holds them to the same limit.
 *
 * @param string $title Page title.
 * @param string $brand Brand name.
 * @return string
 */
function bs_seo_title_with_brand( string $title, string $brand ): string {
	$suffixed = sprintf( '%s — %s', $title, $brand );

	return mb_strlen( $suffixed ) <= 60 ? $suffixed : $title;
}

/**
 * A description for a page whose words live in WordPress.
 *
 * Prefers the excerpt, because an excerpt is written; falls back to the
 * opening of the content, because a page with no excerpt still deserves a
 * sentence in the search result.
 *
 * The content path deliberately runs through the content guard. `the_content`
 * filters do not apply to a description built from the raw post, so without
 * this a private fact removed from the visible page would still be published
 * in the meta description — which is precisely the "not through metadata"
 * clause of Override 1. On a hit the description is dropped rather than
 * redacted: half a sentence ending in an ellipsis is worse in a search result
 * than no description at all.
 *
 * @return string
 */
function bs_seo_description_from_post(): string {
	$post = get_post();

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$text = has_excerpt( $post )
		? (string) $post->post_excerpt
		: bs_seo_opening_prose( (string) $post->post_content );

	$text = wp_strip_all_tags( strip_shortcodes( $text ), true );

	// The content is stored with entities (&amp;, &#x27;). Left encoded they
	// are escaped a second time on output and reach the search result as
	// "&amp;amp;".
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = trim( preg_replace( '/\s+/', ' ', $text ) ?? '' );

	if ( '' === $text ) {
		return '';
	}

	if ( function_exists( 'bs_content_guard_scan' ) && bs_content_guard_scan( $text, bs_slug( $post ) ) ) {
		error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions
			sprintf( 'BrandSure SEO: withheld the meta description for post %d — it contains a private fact.', $post->ID )
		);
		return '';
	}

	return bs_seo_shorten( $text );
}

/**
 * Cut a description to what a search result actually shows.
 *
 * Characters, not words: Google measures pixels and truncates at roughly 160
 * characters, and a 30-word cut ran to 250 on the legal pages. Prefers a full
 * stop, so the snippet ends as a sentence rather than mid-clause; falls back
 * to a word boundary with an ellipsis, which at least reads as deliberate.
 *
 * Guard rule R10 holds the hand-written descriptions in data/pages/*.json to
 * the same limit. This is the same rule applied to the ones nobody writes.
 *
 * @param string $text  Description text.
 * @param int    $limit Character limit.
 * @return string
 */
function bs_seo_shorten( string $text, int $limit = 155 ): string {
	if ( mb_strlen( $text ) <= $limit ) {
		return $text;
	}

	$window = mb_substr( $text, 0, $limit );
	$stop   = max( mb_strrpos( $window, '. ' ), mb_strrpos( $window, '? ' ), mb_strrpos( $window, '! ' ) );

	/*
	 * A sentence break, but only a late one. A full stop is not reliably the
	 * end of a sentence — "BrandSure Consultancy Pvt. Ltd." contains two, and
	 * cutting at either leaves a description that says nothing. Requiring the
	 * break to fall in the last third of the window means an abbreviation
	 * early in the text cannot swallow the description; the worst case is a
	 * word-boundary cut, which is what Google would have done anyway.
	 */
	if ( false !== $stop && $stop >= (int) ( $limit * 0.65 ) ) {
		return trim( mb_substr( $window, 0, $stop + 1 ) );
	}

	$space = mb_strrpos( $window, ' ' );

	return trim( false === $space ? $window : mb_substr( $window, 0, $space ) ) . '…';
}

/**
 * The opening prose of a page, for a page with no excerpt.
 *
 * Not simply the first 30 words of the content, and not simply the first
 * paragraph. Both fail on real pages here:
 *
 *   - taking the content wholesale swept in the headings, so a service page's
 *     description began "Furniture Growth & Marketing — IndiaRunning Google
 *     or Facebook…";
 *   - taking literally the first paragraph gives the legal pages "Last
 *     updated: 2 September 2026", which tells a searcher nothing.
 *
 * So: paragraphs only, skipping any too short to be a sentence, joined until
 * there is enough for a description. That is what a person would pick.
 *
 * @param string $content Raw post content.
 * @return string
 */
function bs_seo_opening_prose( string $content ): string {
	if ( ! preg_match_all( '#<p[^>]*>(.*?)</p>#is', $content, $paragraphs ) ) {
		return $content;
	}

	$collected = array();
	$words     = 0;

	foreach ( $paragraphs[1] as $paragraph ) {
		$plain = trim( wp_strip_all_tags( $paragraph, true ) );
		$count = str_word_count( $plain );

		// A date line, a caption, a one-line label. Not a description.
		if ( $count < 8 ) {
			continue;
		}

		$collected[] = $plain;
		$words      += $count;

		if ( $words >= 30 ) {
			break;
		}
	}

	return implode( ' ', $collected );
}

/**
 * The canonical URL: the clean path, never the query string.
 *
 * `?plan=`, `?interest=`, `?ref=` and every `utm_*` produce the same page,
 * and each one Google treats as its own URL splits the ranking of the page
 * they all point at.
 *
 * @return string
 */
function bs_seo_canonical(): string {
	if ( is_front_page() ) {
		return user_trailingslashit( home_url( '/' ) );
	}

	if ( is_home() ) {
		$blog = (int) get_option( 'page_for_posts' );
		return $blog ? (string) get_permalink( $blog ) : user_trailingslashit( home_url( '/' ) );
	}

	if ( is_singular() ) {
		return (string) get_permalink();
	}

	return '';
}

/**
 * Emit the title tag from bs_seo_meta().
 *
 * @param string $title WordPress's title.
 * @return string
 */
function bs_seo_document_title( string $title ): string {
	$meta = bs_seo_meta();

	return '' !== $meta['title'] ? $meta['title'] : $title;
}
add_filter( 'pre_get_document_title', 'bs_seo_document_title' );

/**
 * Robots directives.
 *
 * @param array $robots Directives.
 * @return array
 */
function bs_seo_robots( array $robots ): array {
	// A large image preview is worth having on a blog post, and costs nothing
	// on a page with no image.
	$robots['max-image-preview'] = 'large';

	if ( is_404() || is_search() ) {
		return wp_robots_no_robots( $robots );
	}

	if ( is_page() && in_array( bs_slug(), bs_noindex_slugs(), true ) ) {
		// follow, not nofollow: the links out of a referral page are worth
		// crawling even though the page itself is not worth indexing.
		return wp_robots_no_robots( $robots );
	}

	return $robots;
}
add_filter( 'wp_robots', 'bs_seo_robots' );

/**
 * The head: description, canonical, Open Graph.
 *
 * WordPress's own rel_canonical is removed rather than left alongside this
 * one — two canonical tags is a worse signal than none, and core's version
 * does not cover the blog index.
 */
function bs_seo_head(): void {
	$meta      = bs_seo_meta();
	$canonical = bs_seo_canonical();
	$name      = (string) bs_site( 'brand.name', 'BrandSure' );

	if ( '' !== $meta['description'] ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $meta['description'] ) );
	}

	if ( '' !== $canonical ) {
		printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $canonical ) );
	}

	/*
	 * No Open Graph on the 404. It has no canonical URL, so og:url cannot be
	 * filled, and a card with a title and no address is worse than no card —
	 * nobody should ever be sharing a "page not found" anyway.
	 */
	if ( is_404() ) {
		return;
	}

	$og = array(
		'og:type'      => is_singular( 'post' ) ? 'article' : 'website',
		'og:site_name' => $name,
		'og:locale'    => 'en_IN',
		'og:title'     => $meta['title'],
		'og:url'       => $canonical,
	);

	if ( '' !== $meta['description'] ) {
		$og['og:description'] = $meta['description'];
	}

	foreach ( $og as $property => $content ) {
		if ( '' === (string) $content ) {
			continue;
		}
		printf( "<meta property=\"%s\" content=\"%s\">\n", esc_attr( $property ), esc_attr( (string) $content ) );
	}

	/*
	 * No og:image. The only image asset in the theme is the logo at 128×34,
	 * which is below every platform's minimum and would render as a broken or
	 * silently dropped card — worse than the text-only card a missing image
	 * produces. A real 1200×630 share image is docs/OPEN-ITEMS.md item 12.
	 */
	printf( "<meta name=\"twitter:card\" content=\"summary\">\n" );
}
add_action( 'wp_head', 'bs_seo_head', 2 );
remove_action( 'wp_head', 'rel_canonical' );

/* -------------------------------------------------------------------------
 * Sitemap
 * ---------------------------------------------------------------------- */

/**
 * Drop the sitemap providers that describe nothing worth crawling.
 *
 * The site has no categories or tags in use and one author, so those three
 * sitemaps would list archive pages that are thin duplicates of the blog
 * index — which is the most common way a small site's crawl budget is spent
 * on nothing.
 *
 * @param WP_Sitemaps_Provider|null $provider Provider.
 * @param string                    $name     Provider name.
 * @return WP_Sitemaps_Provider|false
 */
function bs_sitemap_providers( $provider, string $name ) {
	return in_array( $name, array( 'users', 'taxonomies' ), true ) ? false : $provider;
}
add_filter( 'wp_sitemaps_add_provider', 'bs_sitemap_providers', 10, 2 );

/**
 * Keep noindex pages out of the sitemap.
 *
 * Listing a page in the sitemap while telling robots not to index it is a
 * contradiction Google reports as an error, and it is the exact mistake that
 * happens when the two lists are maintained separately. They are not: both
 * read bs_noindex_slugs().
 *
 * @param array  $args      Query args.
 * @param string $post_type Post type.
 * @return array
 */
function bs_sitemap_exclude( array $args, string $post_type ): array {
	if ( 'page' !== $post_type ) {
		return $args;
	}

	$exclude = array();

	foreach ( bs_noindex_slugs() as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page instanceof WP_Post ) {
			$exclude[] = $page->ID;
		}
	}

	if ( $exclude ) {
		$args['post__not_in'] = array_merge( (array) ( $args['post__not_in'] ?? array() ), $exclude );
	}

	return $args;
}
add_filter( 'wp_sitemaps_posts_query_args', 'bs_sitemap_exclude', 10, 2 );

/*
 * robots.txt is left as WordPress writes it — allow everything, plus the
 * sitemap line core adds.
 *
 * docs/REDIRECTS.md §5 originally said to disallow /built-by-brandsure/
 * there. That is wrong, and this is where it gets corrected rather than
 * implemented: a disallowed URL is never crawled, so the noindex tag on it is
 * never read, and Google may index the bare URL anyway on the strength of the
 * inbound links from client websites. It also cancels the "follow" half of
 * the rule, which is the half worth keeping — the links out of that page lead
 * to /start/. A crawlable page carrying noindex, follow does exactly what was
 * intended; a blocked one does neither. The document has been updated.
 */
