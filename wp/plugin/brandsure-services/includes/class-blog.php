<?php
/**
 * Blog infrastructure: native WordPress Posts, BrandSure presentation.
 *
 * Templates are supplied through the `template_include` filter rather than by
 * adding files to the theme, so the active theme is left untouched and its
 * header and footer are reused via get_header() / get_footer().
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/** Slug of the blog listing page. */
const BRANDSURE_BLOG_SLUG = 'blog';

/**
 * Featured-image sizes: a 16:10 card crop and a 1200x630 social/hero crop.
 *
 * @return void
 */
function brandsure_blog_image_sizes() {
	add_image_size( 'brandsure-card', 800, 500, true );
	add_image_size( 'brandsure-wide', 1200, 630, true );
}
add_action( 'after_setup_theme', 'brandsure_blog_image_sizes' );

/**
 * The blog listing page, if it exists.
 *
 * @return WP_Post|null
 */
function brandsure_blog_page() {
	static $page = null;
	if ( null === $page ) {
		$found = get_page_by_path( BRANDSURE_BLOG_SLUG, OBJECT, 'page' );
		$page  = $found instanceof WP_Post ? $found : false;
	}
	return $page ?: null;
}

/**
 * URL of the blog listing.
 *
 * Prefers the page WordPress is configured to use for posts, then the /blog/
 * page, then the posts archive.
 *
 * @return string
 */
function brandsure_blog_url() {
	$for_posts = (int) get_option( 'page_for_posts' );
	if ( $for_posts ) {
		return (string) get_permalink( $for_posts );
	}
	$page = brandsure_blog_page();
	if ( $page ) {
		return (string) get_permalink( $page );
	}
	return home_url( '/' );
}

/**
 * Whether the /blog/ page is standing in for the posts archive because
 * Settings -> Reading has not been pointed at it yet.
 *
 * @return bool
 */
function brandsure_blog_is_standalone_listing() {
	$page = brandsure_blog_page();
	if ( ! $page ) {
		return false;
	}
	if ( (int) get_option( 'page_for_posts' ) === (int) $page->ID ) {
		return false;
	}
	return is_page( $page->ID );
}

/**
 * Whether the current request should render the BrandSure post listing.
 *
 * @return bool
 */
function brandsure_blog_is_listing() {
	return is_home()
		|| is_category()
		|| is_tag()
		|| is_author()
		|| is_date()
		|| brandsure_blog_is_standalone_listing();
}

/**
 * Whether the current request is a single blog post.
 *
 * @return bool
 */
function brandsure_blog_is_single() {
	return is_singular( 'post' );
}

/**
 * Route listings and single posts at the plugin's templates.
 *
 * @param string $template Template chosen by WordPress.
 * @return string
 */
function brandsure_blog_template_include( $template ) {
	if ( is_admin() || is_embed() || is_feed() ) {
		return $template;
	}

	$ours = '';
	if ( brandsure_blog_is_single() ) {
		$ours = BRANDSURE_SERVICES_DIR . 'templates/blog-single.php';
	} elseif ( brandsure_blog_is_listing() ) {
		$ours = BRANDSURE_SERVICES_DIR . 'templates/blog-archive.php';
	}

	if ( '' === $ours || ! file_exists( $ours ) ) {
		return $template;
	}

	/**
	 * Filter the template used for BrandSure blog views.
	 *
	 * Return the original $template to hand rendering back to the theme.
	 *
	 * @param string $ours     Plugin template path.
	 * @param string $template Template WordPress had chosen.
	 */
	return (string) apply_filters( 'brandsure_blog_template', $ours, $template );
}
add_filter( 'template_include', 'brandsure_blog_template_include', 20 );

/**
 * Enqueue the shared design system plus the blog layer on blog views.
 *
 * @return void
 */
function brandsure_blog_enqueue() {
	if ( ! brandsure_blog_is_listing() && ! brandsure_blog_is_single() && ! brandsure_is_legal_page() ) {
		return;
	}

	$shared = 'assets/brandsure-services.css';
	$blog   = 'assets/brandsure-blog.css';

	// The shared file carries the tokens and components; the blog file is additive.
	wp_enqueue_style(
		'brandsure-services',
		BRANDSURE_SERVICES_URL . $shared,
		array(),
		brandsure_services_asset_version( $shared )
	);
	wp_enqueue_style(
		'brandsure-blog',
		BRANDSURE_SERVICES_URL . $blog,
		array( 'brandsure-services' ),
		brandsure_services_asset_version( $blog )
	);
}
add_action( 'wp_enqueue_scripts', 'brandsure_blog_enqueue' );

/**
 * The query to list posts from.
 *
 * On the posts page and on term archives this is the main query, so WordPress
 * keeps ownership of pagination. On a standalone /blog/ page it is a secondary
 * query built to match.
 *
 * @return WP_Query
 */
function brandsure_blog_listing_query() {
	if ( ! brandsure_blog_is_standalone_listing() ) {
		global $wp_query;
		return $wp_query;
	}

	$paged = max(
		1,
		(int) get_query_var( 'paged' ),
		(int) get_query_var( 'page' )
	);

	return new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'paged'               => $paged,
			'posts_per_page'      => (int) get_option( 'posts_per_page' ),
			'ignore_sticky_posts' => true,
		)
	);
}

/**
 * The post's primary category, for the card label.
 *
 * Respects Rank Math's primary-category choice when one is set.
 *
 * @param int $post_id Post ID.
 * @return WP_Term|null
 */
function brandsure_blog_primary_category( $post_id ) {
	$primary = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
	if ( $primary ) {
		$term = get_term( $primary, 'category' );
		if ( $term instanceof WP_Term ) {
			return $term;
		}
	}

	$terms = get_the_category( $post_id );
	if ( empty( $terms ) ) {
		return null;
	}

	// Skip the default "Uncategorized" bucket when a real category exists.
	$default = (int) get_option( 'default_category' );
	foreach ( $terms as $term ) {
		if ( (int) $term->term_id !== $default ) {
			return $term;
		}
	}
	return $terms[0];
}

/**
 * Heading, intro and breadcrumb label for the current listing.
 *
 * @return array{title:string,intro:string,eyebrow:string,crumb:string}
 */
function brandsure_blog_listing_context() {
	$blog_name = get_bloginfo( 'name' );

	if ( is_category() || is_tag() ) {
		$term = get_queried_object();
		return array(
			'eyebrow' => is_category()
				? __( 'Category', 'brandsure-services' )
				: __( 'Topic', 'brandsure-services' ),
			'title'   => single_term_title( '', false ),
			'intro'   => term_description() ? wp_strip_all_tags( term_description() ) : '',
			'crumb'   => $term instanceof WP_Term ? $term->name : '',
		);
	}

	if ( is_author() ) {
		return array(
			'eyebrow' => __( 'Author', 'brandsure-services' ),
			'title'   => (string) get_the_author(),
			'intro'   => (string) get_the_author_meta( 'description' ),
			'crumb'   => (string) get_the_author(),
		);
	}

	if ( is_date() ) {
		return array(
			'eyebrow' => __( 'Archive', 'brandsure-services' ),
			'title'   => (string) get_the_archive_title(),
			'intro'   => '',
			'crumb'   => (string) get_the_archive_title(),
		);
	}

	$page  = brandsure_blog_page();
	$title = $page ? get_the_title( $page ) : __( 'Blog', 'brandsure-services' );
	$intro = $page && $page->post_excerpt ? $page->post_excerpt : '';

	return array(
		'eyebrow' => $blog_name,
		'title'   => (string) $title,
		'intro'   => (string) $intro,
		'crumb'   => (string) $title,
	);
}

/**
 * Render the shared breadcrumb for blog views.
 *
 * @param string $current Label for the current, non-linked crumb.
 * @param bool   $via_blog Whether to include the blog listing crumb.
 * @return void
 */
function brandsure_blog_breadcrumb( $current, $via_blog = true ) {
	?>
	<nav class="bc" aria-label="<?php esc_attr_e( 'Breadcrumb', 'brandsure-services' ); ?>">
		<ol>
			<li><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'brandsure-services' ); ?></a></li>
			<?php if ( $via_blog ) : ?>
				<li><a href="<?php echo esc_url( brandsure_blog_url() ); ?>"><?php esc_html_e( 'Blog', 'brandsure-services' ); ?></a></li>
			<?php endif; ?>
			<li><span aria-current="page"><?php echo esc_html( $current ); ?></span></li>
		</ol>
	</nav>
	<?php
}

/**
 * Render pagination for a listing query.
 *
 * @param WP_Query $query Listing query.
 * @return void
 */
function brandsure_blog_pagination( WP_Query $query ) {
	$total = (int) $query->max_num_pages;
	if ( $total < 2 ) {
		return;
	}

	$args = array(
		'total'     => $total,
		'mid_size'  => 1,
		'prev_text' => __( 'Previous', 'brandsure-services' ),
		'next_text' => __( 'Next', 'brandsure-services' ),
		'type'      => 'plain',
	);

	if ( brandsure_blog_is_standalone_listing() ) {
		$page_id        = (int) brandsure_blog_page()->ID;
		$args['base']   = trailingslashit( get_permalink( $page_id ) ) . 'page/%#%/';
		$args['format'] = '';
		$args['current'] = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );
	}

	$links = paginate_links( $args );
	if ( ! $links ) {
		return;
	}
	printf(
		'<nav class="bpag" aria-label="%s">%s</nav>',
		esc_attr__( 'Posts pagination', 'brandsure-services' ),
		wp_kses_post( $links )
	);
}

/**
 * BlogPosting schema for single posts.
 *
 * Rank Math (or any other SEO plugin) already emits Article/BlogPosting
 * schema, so this only runs when none is active — never a second graph.
 *
 * @return void
 */
function brandsure_blog_schema() {
	if ( ! brandsure_blog_is_single() || brandsure_services_seo_plugin_active() ) {
		return;
	}

	$post_id = get_the_ID();
	if ( ! $post_id ) {
		return;
	}

	$graph = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'headline'         => wp_strip_all_tags( get_the_title( $post_id ) ),
		'datePublished'    => get_the_date( DATE_W3C, $post_id ),
		'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) get_post_field( 'post_author', $post_id ) ),
		),
		'publisher'        => array(
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
		),
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink( $post_id ),
		),
		'url'              => get_permalink( $post_id ),
		'inLanguage'       => 'en-IN',
	);

	$excerpt = wp_strip_all_tags( get_the_excerpt( $post_id ) );
	if ( '' !== $excerpt ) {
		$graph['description'] = $excerpt;
	}

	$image = get_the_post_thumbnail_url( $post_id, 'brandsure-wide' );
	if ( $image ) {
		$graph['image'] = $image;
	}

	printf(
		"<script type=\"application/ld+json\">%s</script>\n",
		wp_json_encode( $graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'brandsure_blog_schema', 2 );

/**
 * Canonical, description and Open Graph tags for blog views.
 *
 * Skipped entirely when an SEO plugin owns the head.
 *
 * @return void
 */
function brandsure_blog_head_meta() {
	if ( brandsure_services_seo_plugin_active() ) {
		return;
	}
	if ( ! brandsure_blog_is_single() && ! brandsure_blog_is_listing() ) {
		return;
	}

	if ( brandsure_blog_is_single() ) {
		$post_id     = (int) get_the_ID();
		$canonical   = (string) get_permalink( $post_id );
		$description = wp_strip_all_tags( get_the_excerpt( $post_id ) );
		$image       = get_the_post_thumbnail_url( $post_id, 'brandsure-wide' );
		$og_type     = 'article';
	} else {
		$canonical   = brandsure_blog_is_standalone_listing()
			? (string) get_permalink( brandsure_blog_page() )
			: (string) brandsure_blog_url();
		$context     = brandsure_blog_listing_context();
		$description = $context['intro'];
		$image       = '';
		$og_type     = 'website';
	}

	printf( "<link rel=\"canonical\" href=\"%s\">\n", esc_url( $canonical ) );
	if ( '' !== $description ) {
		printf( "<meta name=\"description\" content=\"%s\">\n", esc_attr( $description ) );
		printf( "<meta property=\"og:description\" content=\"%s\">\n", esc_attr( $description ) );
	}
	printf( "<meta property=\"og:type\" content=\"%s\">\n", esc_attr( $og_type ) );
	printf( "<meta property=\"og:url\" content=\"%s\">\n", esc_url( $canonical ) );
	printf( "<meta property=\"og:site_name\" content=\"%s\">\n", esc_attr( get_bloginfo( 'name' ) ) );
	if ( $image ) {
		printf( "<meta property=\"og:image\" content=\"%s\">\n", esc_url( $image ) );
		echo "<meta name=\"twitter:card\" content=\"summary_large_image\">\n";
	}
}
add_action( 'wp_head', 'brandsure_blog_head_meta', 1 );
