<?php
/**
 * Repairs for page bodies that were pasted in as whole static documents.
 *
 * Two of the live pages (the front page and About) hold a complete static HTML
 * document in post_content. That was the quickest way to get them up, and it
 * left two defects behind that no amount of editing the source files fixes:
 *
 *   1. Every link still points at a .html filename. WordPress serves pretty
 *      permalinks, so "how-it-works.html" is a 404.
 *   2. The ampersands inside the pasted <script> were entity-escaped on save
 *      ("&&" became "&#038;&#038;"). Character references are not decoded
 *      inside a script element, so the file is a syntax error and none of it
 *      runs -- including the reveal observer that makes .rv sections visible.
 *
 * Both are fixed on output, so the stored content is left exactly as the owner
 * pasted it and re-pasting a page cannot undo the fix. Re-pasting a cleaned
 * file simply leaves these filters with nothing to match.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reduce a pasted whole HTML document to something that can live inside a page.
 *
 * Pages 42 and About hold a complete document in post_content — doctype, <html>,
 * <head>, <body>. That was harmless while the old theme bypassed WordPress and
 * echoed the file as the whole response. Rendered inside a real theme it nests a
 * second document inside the first: a stray <head> in the body, a duplicate
 * <title>, and font <link>s that browsers only honour by accident.
 *
 * The <style> block is the one part of the head that must survive — it is the
 * page's stylesheet. Everything else structural is dropped.
 *
 * Exact-match on the wrapper tags, so a page whose body is ordinary content is
 * left untouched, and re-pasting a content-only body makes this a no-op.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_links_unwrap_document( $content ) {
	if ( is_admin() || false === stripos( $content, '<html' ) ) {
		return $content;
	}

	$styles = array();
	if ( preg_match( '~<head\b[^>]*>(.*?)</head>~is', $content, $head ) ) {
		// The head's <noscript> holds the reveal fallback, which shows every .rv
		// section outright. Lifting its <style> out of the <noscript> would apply
		// it unconditionally and kill the fade-in for everyone. header.php emits
		// that fallback itself, so drop it here.
		$inner   = preg_replace( '~<noscript\b[^>]*>.*?</noscript>~is', '', $head[1] );
		preg_match_all( '~<style\b[^>]*>.*?</style>~is', (string) $inner, $found );
		$styles  = $found[0];
		$content = str_replace( $head[0], '', $content );
	}

	$content = preg_replace(
		array(
			'~<!doctype[^>]*>~i',
			'~</?html\b[^>]*>~i',
			'~</?body\b[^>]*>~i',
		),
		'',
		$content
	);

	return implode( "\n", $styles ) . "\n" . trim( (string) $content );
}
add_filter( 'the_content', 'brandsure_links_unwrap_document', 7 );

/**
 * Static filename -> path on this site.
 *
 * @return array<string,string>
 */
function brandsure_link_map() {
	$map = array(
		'index.html'      => '/',
		'services.html'   => '/services/',
		'how-it-works.html' => '/how-it-works/',
		'pricing.html'    => '/pricing/',
		'about.html'      => '/about/',
		'blog.html'       => '/' . BRANDSURE_BLOG_SLUG . '/',
		'privacy-policy.html' => '/privacy-policy/',
		'terms.html'      => '/terms-and-conditions/',
	);

	foreach ( brandsure_services_slugs() as $slug ) {
		$map[ $slug . '.html' ] = '/' . $slug . '/';
	}

	return $map;
}

/**
 * Point pasted .html links at their real permalinks.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_links_rewrite( $content ) {
	if ( is_admin() || false === strpos( $content, '.html"' ) ) {
		return $content;
	}

	foreach ( brandsure_link_map() as $file => $path ) {
		$url = home_url( $path );
		$content = str_replace(
			array( 'href="' . $file . '"', 'href="' . $file . '#' ),
			array( 'href="' . $url . '"', 'href="' . $url . '#' ),
			$content
		);
	}

	return $content;
}
add_filter( 'the_content', 'brandsure_links_rewrite', 20 );

/**
 * Replace the placeholder social links in a pasted footer.
 *
 * The URLs come from the site's own configuration, or from the front page,
 * which carries the real profiles. Nothing is guessed: with no configured
 * value the placeholder is left alone.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_links_social( $content ) {
	if ( is_admin() ) {
		return $content;
	}

	$replacements = array();

	$instagram = function_exists( 'brandsure_instagram_url' ) ? brandsure_instagram_url() : '';
	if ( '' !== $instagram ) {
		$replacements['href="https://www.instagram.com/"'] = 'href="' . esc_url( $instagram ) . '"';
	}

	$linkedin = function_exists( 'brandsure_linkedin_url' ) ? brandsure_linkedin_url() : '';
	if ( '' !== $linkedin ) {
		$replacements['href="https://www.linkedin.com/"'] = 'href="' . esc_url( $linkedin ) . '"';
	}

	if ( empty( $replacements ) ) {
		return $content;
	}

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
}
add_filter( 'the_content', 'brandsure_links_social', 20 );

/**
 * Undo the entity-escaping of "&" inside a pasted <script>.
 *
 * Runs after the core content filters, which are what introduce it. Only "&"
 * is decoded: decoding "&lt;" could manufacture a closing script tag out of
 * ordinary text.
 *
 * @param string $content Rendered content.
 * @return string
 */
function brandsure_links_repair_scripts( $content ) {
	if ( is_admin() || false === stripos( $content, '<script' ) ) {
		return $content;
	}

	return (string) preg_replace_callback(
		'~(<script\b(?![^>]*\bld\+json)[^>]*>)(.*?)(</script>)~is',
		static function ( $m ) {
			$body = str_replace( array( '&#038;', '&#38;', '&amp;' ), '&', $m[2] );
			return $m[1] . $body . $m[3];
		},
		$content
	);
}
add_filter( 'the_content', 'brandsure_links_repair_scripts', 21 );
