<?php
/**
 * WP-CLI migration for the BrandSure pages.
 *
 * Creates the service, blog and legal pages if — and only if — they are
 * missing. Run by hand:
 *
 *     wp brandsure services status
 *     wp brandsure services migrate --dry-run
 *     wp brandsure services migrate --group=legal
 *
 * Nothing here is hooked to a page load, so the theme cannot create duplicates.
 *
 * @package BrandSure_Services
 */

defined( 'ABSPATH' ) || exit;

/**
 * Page management commands.
 */
class BrandSure_Services_CLI {

	/**
	 * Create any missing page, matching on slug.
	 *
	 * A page is only ever created when get_page_by_path() finds nothing for the
	 * slug, so re-running is safe and never produces a second copy or a
	 * "-2" permalink.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would happen without writing anything.
	 *
	 * [--status=<status>]
	 * : Status for newly created pages. Default: draft.
	 *
	 * [--group=<group>]
	 * : Limit to one group: service, main, blog or legal. Default: all.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Flags.
	 * @return void
	 */
	public function migrate( $args, $assoc_args ) {
		$dry_run = isset( $assoc_args['dry-run'] );
		$status  = isset( $assoc_args['status'] ) ? sanitize_key( $assoc_args['status'] ) : 'draft';
		$group   = isset( $assoc_args['group'] ) ? sanitize_key( $assoc_args['group'] ) : '';

		$allowed = array( 'draft', 'publish', 'pending', 'private' );
		if ( ! in_array( $status, $allowed, true ) ) {
			WP_CLI::error( 'Unsupported --status. Use one of: ' . implode( ', ', $allowed ) );
		}

		$groups = array( 'service', 'main', 'blog', 'legal' );
		if ( '' !== $group && ! in_array( $group, $groups, true ) ) {
			WP_CLI::error( 'Unsupported --group. Use one of: ' . implode( ', ', $groups ) );
		}

		$created = 0;
		$skipped = 0;

		foreach ( brandsure_managed_pages() as $slug => $data ) {
			if ( '' !== $group && $data['group'] !== $group ) {
				continue;
			}

			$existing = get_page_by_path( $slug, OBJECT, 'page' );

			if ( $existing instanceof WP_Post ) {
				WP_CLI::log(
					sprintf(
						'skip    %-40s already exists (ID %d, %s)',
						$slug,
						$existing->ID,
						$existing->post_status
					)
				);
				++$skipped;
				continue;
			}

			$content = '';
			if ( '' !== $data['content'] ) {
				$content = $this->load_content( $data['content'] );
				if ( is_wp_error( $content ) ) {
					WP_CLI::warning( sprintf( '%s — %s', $slug, $content->get_error_message() ) );
					continue;
				}
			}

			if ( $dry_run ) {
				WP_CLI::log(
					sprintf(
						'create  %-40s would create "%s" (%s, %d bytes)',
						$slug,
						$data['title'],
						$status,
						strlen( $content )
					)
				);
				++$created;
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'    => 'page',
					'post_name'    => $slug,
					'post_title'   => $data['title'],
					'post_content' => $content,
					'post_excerpt' => $data['excerpt'],
					'post_status'  => $status,
					// Deliberately top level: a real parent would move the
					// permalink and break the published URL.
					'post_parent'  => 0,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				WP_CLI::warning( sprintf( '%s — %s', $slug, $post_id->get_error_message() ) );
				continue;
			}

			WP_CLI::log( sprintf( 'created %-40s ID %d (%s)', $slug, $post_id, $status ) );
			++$created;
		}

		WP_CLI::success(
			sprintf(
				'%s%d created, %d already present.',
				$dry_run ? '[dry run] ' : '',
				$created,
				$skipped
			)
		);

		if ( ! $dry_run ) {
			$this->blog_reading_notice();
		}
	}

	/**
	 * Report the current state of every managed page.
	 *
	 * @return void
	 */
	public function status() {
		$rows = array();

		foreach ( brandsure_managed_pages() as $slug => $data ) {
			$page   = get_page_by_path( $slug, OBJECT, 'page' );
			$rows[] = array(
				'group'  => $data['group'],
				'slug'   => $slug,
				'title'  => $data['title'],
				'id'     => $page ? $page->ID : '—',
				'status' => $page ? $page->post_status : 'missing',
				'url'    => $page ? get_permalink( $page ) : '—',
			);
		}

		WP_CLI\Utils\format_items(
			'table',
			$rows,
			array( 'group', 'slug', 'title', 'id', 'status', 'url' )
		);

		$this->blog_reading_notice();

		$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
		WP_CLI::log(
			$privacy
				? sprintf( 'Privacy page wired to ID %d (Settings -> Privacy).', $privacy )
				: 'Settings -> Privacy is not pointing at a page yet.'
		);
	}

	/**
	 * Warn when Settings -> Reading has not been pointed at the blog page.
	 *
	 * @return void
	 */
	private function blog_reading_notice() {
		$page = brandsure_blog_page();
		if ( ! $page ) {
			return;
		}
		if ( (int) get_option( 'page_for_posts' ) === (int) $page->ID ) {
			WP_CLI::log( 'Posts page is set to the Blog page — pagination and archives are native.' );
			return;
		}
		WP_CLI::warning(
			sprintf(
				'Settings -> Reading -> Posts page is not set to "%s" (ID %d). '
				. 'The listing still renders, but set it so WordPress owns /blog/ pagination: '
				. 'wp option update page_for_posts %d',
				$page->post_title,
				$page->ID,
				$page->ID
			)
		);
	}

	/**
	 * Read a generated block-content file shipped with the plugin.
	 *
	 * @param string $relative Path relative to the plugin directory.
	 * @return string|WP_Error
	 */
	private function load_content( $relative ) {
		$path = BRANDSURE_SERVICES_DIR . $relative;
		if ( ! file_exists( $path ) ) {
			return new WP_Error( 'missing_content', 'no generated content at ' . $path );
		}
		$content = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		if ( false === $content || '' === trim( $content ) ) {
			return new WP_Error( 'empty_content', 'generated content is empty' );
		}
		return $content;
	}
}
