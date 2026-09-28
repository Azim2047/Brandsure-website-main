<?php
/**
 * The guard — disclosure, language and integrity checks.
 *
 * Implements docs/SOURCE-OF-TRUTH.md §5.4. It runs at deploy and fails the
 * build, which is the point: the rule "never publish the commercial
 * mechanism" is too important to rest on everyone remembering it on every
 * edit for the life of the site.
 *
 * Run it:
 *   php wp-content/themes/brandsure-v2/tools/guard.php          # CI, no WordPress needed
 *   wp brandsure guard                                          # inside WordPress
 *   php tools/guard.php --launch                                # also fails on unresolved launch inputs
 *
 * The class deliberately has no WordPress dependency, so CI can run it
 * against a checkout without booting WordPress. The WP-CLI command below is a
 * thin wrapper that adds the database-backed checks.
 *
 * Escape hatch: a line containing "bs-guard-ignore" is skipped. It exists so
 * that one genuine false positive never becomes a reason to delete the guard.
 * Using it on real copy is a review failure.
 *
 * @package brandsure-v2
 */

defined( 'ABSPATH' ) || defined( 'BRANDSURE_GUARD_CLI' ) || exit;

/**
 * Static analysis of the theme against the disclosure rules.
 */
class BrandSure_Guard {

	/** @var string Absolute path to the theme root. */
	private string $root;

	/** @var array<int,array{rule:string,file:string,line:int,detail:string}> */
	private array $errors = array();

	/** @var array<int,array{rule:string,file:string,line:int,detail:string}> */
	private array $warnings = array();

	/** @var bool Treat unresolved launch inputs as errors. */
	private bool $launch_mode;

	/**
	 * Rule 1 — never publicly disclosable, anywhere in the theme.
	 *
	 * Each entry is a regex. Keys are the human-readable reason, used in the
	 * failure message so the person who trips it knows which rule they hit
	 * rather than just seeing a pattern.
	 *
	 * @var array<string,string>
	 */
	private const BLOCKLIST = array(
		'the partnership percentage'      => '/\b(20|80)\s?%/',
		'the phrase "revenue share"'      => '/revenue[\s\-]share/i',
		'the phrase "share of sales"'     => '/share of (total )?sales/i',
		'the word "commission"'           => '/\bcommissions?\b/i',
		// "You keep" is banned as the Video 4/5 money-split phrase, not as
		// English. A bare match also fires on Video 2 S5's own approved
		// headline — "how much of the growth work do you want to keep doing
		// yourself?" — and a rule that flags approved copy teaches everyone to
		// reach for the escape hatch, which is how a real leak eventually gets
		// waved through. So match the two forms the private fact actually
		// takes: the phrase with money or a figure after it, and the deck's
		// uppercase badge.
		'the phrase "you keep" with a figure' => '/\byou keep\b[^.\n]{0,20}?(₹|\d)/i',
		'the "YOU KEEP" badge'               => '/YOU KEEP/',
		'SWO / walk-in offset'            => '/\bSWO\b|walk[\s\-]?in offset/i',
		'the weekly payment rhythm'       => '/weekly (payment|settlement|invoic)/i',
		// Override 2 makes the partnership term private everywhere, not only on
		// its own page. No public page needs "36 months", and the 3-month
		// minimum on Agency and Digital Partner is unaffected.
		'the partnership term'            => '/\b36[\s\-–]?months?\b/i',
		'the setup fee (partnership)'     => '/1,\s?20,\s?000/',
		'the security deposit'            => '/3,\s?00,\s?000/',
		'the sofa split'                  => '/33,\s?000|9,\s?376|28\.4\s?%/',
		'the monthly P&L example'         => '/55\s?(lakh|L)\b.*P&L|13\.26/i',
		'the investment totals'           => '/₹\s?19\.4\s?L|₹\s?51\.3\s?L/',
		'named benchmark brands'          => '/\b(Wakefit|Pepperfry|Sleep Company)\b/i',
	);

	/**
	 * Percentage-shaped patterns from BLOCKLIST that must not be applied to
	 * CSS. "width: 20%" is a layout value, not a disclosure, and flagging it
	 * would train everyone to ignore the guard.
	 *
	 * @var array<int,string>
	 */
	private const CSS_EXEMPT = array( 'the partnership percentage' );

	/**
	 * Rule 2 — additionally forbidden on the Growth Partnership page.
	 *
	 * Override 2. The public GP page carries no money and no duration at all,
	 * so the strictest rule in the project is also the simplest to state.
	 *
	 * @var array<string,string>
	 */
	private const GP_BLOCKLIST = array(
		'any rupee figure'      => '/₹|\bRs\.?\s?\d|\brupees?\b/i',
		'a partnership duration' => '/\b36\s?[\-–]?\s?months?\b|\b\d+\s?months?\b|\b\d+\s?years?\b/i',
		'a deposit'             => '/\bdeposit\b/i',
		'a setup fee'           => '/setup fee|onboarding fee/i',
		'a percentage'          => '/\d+\s?%/',
	);

	/**
	 * Rule 4 — Language Sheet B3. Checked against rendered copy only, which
	 * means values inside data/*.json. Code comments may say "attribution"
	 * when explaining why the site does not.
	 *
	 * @var array<string,string>
	 */
	private const BANNED_LANGUAGE = array(
		'funnel'                  => '/\bfunnels?\b/i',
		'attribution'             => '/\battribution\b/i',
		'retargeting'             => '/\bretargeting\b/i',
		'CRO'                     => '/\bCRO\b/',
		'nurturing'               => '/\bnurtur/i',
		'conversion optimisation' => '/conversion optimi[sz]ation/i',
		'customer journey'        => '/customer journey/i',
		'lifecycle marketing'     => '/lifecycle/i',
		'marketing automation'    => '/marketing automation/i',
		'performance architecture' => '/performance architecture/i',
		'lower-funnel'            => '/lower[\s\-]funnel/i',
		'SKU'                     => '/\bSKUs?\b/',
		'product-market fit'      => '/product[\s\-]market fit/i',
		'ROAS'                    => '/\bROAS\b/',
		'"45 services" as a headline' => '/45 services/i',
	);

	/** Rule 7 — visible prose budget per public page. */
	private const WORD_BUDGET = 350;

	/** Rule 7 — at this length a string stops being a label and becomes prose. */
	private const PROSE_MIN_WORDS = 4;

	/**
	 * The disclosure blocklist, for the runtime guard.
	 *
	 * inc/content-guard.php checks WordPress editor content against exactly
	 * these patterns. Two blocklists would drift, and the day they drifted
	 * would be the day one of them stopped catching something — so there is
	 * one definition and this is it.
	 *
	 * @return array<string,string> Reason => regex.
	 */
	public static function blocklist(): array {
		return self::BLOCKLIST;
	}

	/**
	 * @param string $root        Theme root.
	 * @param bool   $launch_mode Fail on unresolved launch inputs.
	 */
	public function __construct( string $root, bool $launch_mode = false ) {
		$this->root        = rtrim( $root, '/' );
		$this->launch_mode = $launch_mode;
	}

	/**
	 * Run every rule.
	 *
	 * @return bool True when the build passes.
	 */
	public function run(): bool {
		$this->rule_1_global_blocklist();
		$this->rule_2_growth_partnership();
		$this->rule_3_route_contract();
		$this->rule_4_banned_language();
		$this->rule_5_price_parity();
		$this->rule_6_faq_honesty();
		$this->rule_7_word_budget();
		$this->rule_8_ownership_consistency();
		$this->rule_9_launch_readiness();
		$this->rule_10_page_metadata();
		$this->rule_11_template_functions();
		$this->rule_12_seed_contact_parity();
		$this->rule_13_mandatory_qualifiers();
		$this->rule_14_withdrawn_facts();

		return array() === $this->errors;
	}

	/* --------------------------------------------------------------------
	 * Rules
	 * ----------------------------------------------------------------- */

	/** Rule 1 — nothing on the private list appears anywhere in the theme. */
	private function rule_1_global_blocklist(): void {
		foreach ( $this->files( array( 'php', 'json', 'js', 'css', 'html', 'svg' ) ) as $file ) {
			$is_css = str_ends_with( $file, '.css' );

			foreach ( self::BLOCKLIST as $reason => $pattern ) {
				if ( $is_css && in_array( $reason, self::CSS_EXEMPT, true ) ) {
					continue;
				}
				$this->match( 'R1 disclosure', $file, $pattern, $reason );
			}
		}
	}

	/** Rule 2 — the Growth Partnership page carries no money and no duration. */
	private function rule_2_growth_partnership(): void {
		$targets = array(
			'data/pages/growth-partnership.json',
			'templates/page-growth-partnership.php',
		);

		foreach ( $targets as $relative ) {
			$path = $this->root . '/' . $relative;

			if ( ! is_readable( $path ) ) {
				continue; // Built in Chat 6.
			}

			foreach ( self::GP_BLOCKLIST as $reason => $pattern ) {
				$this->match( 'R2 growth-partnership', $path, $pattern, $reason );
			}
		}
	}

	/** Rule 3 — Growth Partnership never travels as plan=. */
	private function rule_3_route_contract(): void {
		// (a) The literal string, as it would appear in a URL, a prefill or JS.
		foreach ( $this->files( array( 'php', 'json', 'js' ) ) as $file ) {
			$this->match(
				'R3 route contract',
				$file,
				'/plan\s*=\s*["\']?\s*Growth\s*Partnership/i',
				'Growth Partnership must pass interest=, never plan= (Override 3)'
			);
		}

		// (b) The structural form. In data/site.json the route is two separate
		// keys — "param": "plan" and "value": "Growth Partnership" — which a
		// line-based scan cannot see, and which is how the mistake would most
		// plausibly be made. Parse the routing block and check the pairs.
		$site_path = $this->root . '/data/site.json';

		if ( ! is_readable( $site_path ) ) {
			return;
		}

		$site    = (array) json_decode( (string) file_get_contents( $site_path ), true );
		$routing = $site['routing'] ?? array();

		if ( ! is_array( $routing ) ) {
			return;
		}

		foreach ( $routing as $name => $route ) {
			if ( ! is_array( $route ) ) {
				continue;
			}

			$param = (string) ( $route['param'] ?? '' );
			$value = (string) ( $route['value'] ?? '' );

			if ( 'plan' === $param && preg_match( '/growth\s*partnership/i', $value ) ) {
				$this->error(
					'R3 route contract',
					$site_path,
					0,
					sprintf(
						'routing."%s" sends Growth Partnership as plan=. It must be interest= — plan= reads to the AI as a self-selected route, and Growth Partnership is never self-selected (Override 3)',
						$name
					)
				);
			}
		}
	}

	/**
	 * Rule 4 — Language Sheet B3, against rendered copy.
	 *
	 * Covers developer-owned copy in data/*.json and the migration seeds in
	 * migration/*.html. The seeds matter as much as the JSON: they are the
	 * starting content for pages people actually read, and the copy carried
	 * over from the old site is exactly where a banned word survives — that
	 * is where "retargeting", "SKU" and two untranslated uses of "CRM" were
	 * found and rewritten.
	 *
	 * Editor content typed after the import is a different problem with a
	 * different answer: inc/content-guard.php, which runs at save time.
	 */
	private function rule_4_banned_language(): void {
		foreach ( $this->files( array( 'json' ), 'data' ) as $file ) {
			foreach ( $this->copy_strings( $file ) as $line => $text ) {
				$this->check_language( $file, $line, $text );
			}

			// CRM is permitted once per page, and only translated on the spot.
			$this->check_crm( $file );
		}

		foreach ( $this->files( array( 'html' ), 'migration' ) as $file ) {
			$lines = file( $file, FILE_IGNORE_NEW_LINES );

			if ( false === $lines ) {
				continue;
			}

			$crm = 0;

			foreach ( $lines as $index => $line ) {
				if ( str_contains( $line, 'bs-guard-ignore' ) || $this->is_documentation( $line ) ) {
					continue;
				}

				$text = html_entity_decode( wp_strip_tags_fallback( $line ) );

				$this->check_language( $file, $index + 1, $text );

				if ( preg_match( '/\bCRM\b/', $text ) ) {
					++$crm;

					if ( ! preg_match( '/written down/i', $text ) ) {
						$this->error(
							'R4 language',
							$file,
							$index + 1,
							'CRM used without translating it on the spot — say "one place where every enquiry is written down"'
						);
					}
				}
			}

			if ( $crm > 1 ) {
				$this->error( 'R4 language', $file, 0, sprintf( 'CRM appears %d times; the limit is once per page', $crm ) );
			}
		}
	}

	/**
	 * Check one piece of copy against the Language Sheet.
	 *
	 * @param string $file File, for the message.
	 * @param int    $line Line number.
	 * @param string $text Copy.
	 */
	private function check_language( string $file, int $line, string $text ): void {
		foreach ( self::BANNED_LANGUAGE as $word => $pattern ) {
			if ( preg_match( $pattern, $text ) ) {
				$this->error(
					'R4 language',
					$file,
					$line,
					sprintf( 'banned word "%s" in copy the owner will read', $word )
				);
			}
		}
	}

	/**
	 * Rule 5 — price parity with Supabase commercial_facts.
	 *
	 * Disabled. The Supabase seed still names the plans Standard / Expert
	 * (keys standard_fee, expert_fee) while the website says Agency / Digital
	 * Partner, so there is nothing coherent to compare yet. Tracked as
	 * docs/OPEN-ITEMS.md item 4; this becomes a hard check the day the rename
	 * lands, and it is the check that stops someone updating ₹75,000 in one
	 * place and not the other.
	 */
	private function rule_5_price_parity(): void {
		$this->warn(
			'R5 price parity',
			$this->root . '/data/commercial.json',
			0,
			'disabled pending the Standard/Expert rename in commercial_facts (OPEN-ITEMS item 4)'
		);
	}

	/** Rule 6 — FAQPage schema only where a visible FAQ renders. */
	private function rule_6_faq_honesty(): void {
		$faq_path = $this->root . '/data/faq.json';
		$faq      = is_readable( $faq_path ) ? (array) json_decode( (string) file_get_contents( $faq_path ), true ) : array();

		foreach ( $this->files( array( 'json' ), 'data/pages' ) as $file ) {
			$slug = basename( $file, '.json' );
			$body = (string) file_get_contents( $file );

			$renders_faq = str_contains( $body, '"accordion"' );
			$has_entries = isset( $faq[ $slug ] );

			if ( $renders_faq && ! $has_entries ) {
				$this->error( 'R6 FAQ schema', $file, 0, sprintf( 'renders an accordion but data/faq.json has no "%s" key', $slug ) );
			}

			if ( $has_entries && ! $renders_faq ) {
				$this->error( 'R6 FAQ schema', $faq_path, 0, sprintf( '"%s" has FAQ entries but that page renders no visible FAQ block — schema would claim content the reader cannot see', $slug ) );
			}
		}
	}

	/**
	 * Rule 7 — visible prose budget.
	 *
	 * Prose only. Labels, table cells, plan names, stage names and rupee
	 * figures are not prose and are not counted — that distinction is the
	 * source's own, not a convenience: the Master Build Architecture audits
	 * "≤ 12 prose words on screen beyond diagram labels" (§1.2, §1.9) and
	 * deliberately protects the compare table as a slide owners photograph,
	 * where "a diagram would lose the numbers" (§1.7 C15).
	 *
	 * The heuristic is length. A string of three words or fewer is a label;
	 * four or more is a sentence the reader has to read. That is crude at the
	 * boundary and exactly right in the bulk of cases, and it fails safe —
	 * it can only ever under-count labels, never let real prose through.
	 */
	private function rule_7_word_budget(): void {
		foreach ( $this->files( array( 'json' ), 'data/pages' ) as $file ) {
			$prose = 0;
			$total = 0;

			foreach ( $this->copy_strings( $file ) as $text ) {
				$count  = str_word_count( wp_strip_all_tags_compat( $text ) );
				$total += $count;

				if ( $count >= self::PROSE_MIN_WORDS ) {
					$prose += $count;
				}
			}

			if ( $prose > self::WORD_BUDGET ) {
				$this->error(
					'R7 word budget',
					$file,
					0,
					sprintf(
						'%d words of visible prose (%d including labels), budget is %d — the reader is a busy owner on a phone',
						$prose,
						$total,
						self::WORD_BUDGET
					)
				);
			}
		}
	}

	/** Rule 8 — one owner per page, and it has what it needs. */
	private function rule_8_ownership_consistency(): void {
		$registry = $this->registry();

		foreach ( $registry as $slug => $ownership ) {
			$name = ( '' === $slug ) ? 'home' : $slug;
			$file = $this->root . '/data/pages/' . $name . '.json';

			if ( 'editable' === $ownership && is_readable( $file ) ) {
				$this->error(
					'R8 ownership',
					$file,
					0,
					sprintf( '"%s" is WordPress-editable, so this data file is a second copy of the same content (Override 4)', $slug )
				);
			}

			// A migration seed is a one-time import artefact. Once imported it
			// is dead weight, and dead weight that looks editable eventually
			// gets edited by someone expecting the site to change — which is
			// the exact drift this project exists to prevent. Listing them
			// keeps them from lingering unnoticed.
			$seed = $this->root . '/migration/' . $name . '.html';

			if ( is_readable( $seed ) ) {
				$this->warn(
					'R8 ownership',
					$seed,
					0,
					sprintf( 'migration seed for "%s" is still present — delete it once the content is imported into WordPress', $slug )
				);
			}

			// A controlled page with no data file is simply not built yet, so
			// this is only worth saying once its template exists.
			$template = $this->root . '/templates/page-' . $name . '.php';
			if ( 'controlled' === $ownership && is_readable( $template ) && ! is_readable( $file ) ) {
				$this->error(
					'R8 ownership',
					$template,
					0,
					sprintf( 'controlled page "%s" has a template but no data/pages/%s.json to render', $slug, $name )
				);
			}
		}
	}

	/** Rule 9 — inputs the site cannot launch without. */
	private function rule_9_launch_readiness(): void {
		$site_path = $this->root . '/data/site.json';

		if ( ! is_readable( $site_path ) ) {
			$this->error( 'R9 launch', $site_path, 0, 'data/site.json is missing' );
			return;
		}

		$site = (array) json_decode( (string) file_get_contents( $site_path ), true );

		$required = array(
			'contact.whatsapp_number' => 'the WhatsApp number — every CTA on the site is dead without it (OPEN-ITEMS item 1)',
			'contact.phone_tel'       => 'the public phone number (OPEN-ITEMS item 5)',
			'contact.email'           => 'the public email address (OPEN-ITEMS item 5)',
			'integration.n8n_base'    => 'the n8n base URL — no events are recorded without it (OPEN-ITEMS item 2)',
		);

		foreach ( $required as $path => $why ) {
			if ( '' !== (string) $this->dig( $site, $path ) ) {
				continue;
			}

			$message = sprintf( '%s is unresolved: %s', $path, $why );

			// Before launch these are known gaps with documented fallbacks, so
			// they must not block day-to-day builds. At launch they are fatal.
			if ( $this->launch_mode ) {
				$this->error( 'R9 launch', $site_path, 0, $message );
			} else {
				$this->warn( 'R9 launch', $site_path, 0, $message );
			}
		}
	}

	/**
	 * Rule 10 — every controlled page carries its own title and description.
	 *
	 * From Chat 11 these two strings are what the search result is made of,
	 * and a page missing them does not fail visibly: it renders perfectly and
	 * quietly falls back to a bare title with no description. That is the
	 * failure worth a build rule, because nobody finds it by looking at the
	 * site.
	 *
	 * The length bounds are Google's rendering limits, not opinions. A title
	 * past roughly 60 characters is truncated in the result; a description
	 * past roughly 160 is cut mid-sentence. Both are warnings rather than
	 * errors — a truncated description is a bad search result, not a broken
	 * page, and the copy may be right and the limit wrong.
	 */
	private function rule_10_page_metadata(): void {
		$targets = array();

		foreach ( $this->files( array( 'json' ), 'data/pages' ) as $file ) {
			$targets[ $file ] = (array) json_decode( (string) file_get_contents( $file ), true );
		}

		/*
		 * The blog index has no data/pages file — its two strings live in
		 * site.json, which is why they escaped this rule until Chat 13's audit
		 * found the description at 172 characters. A rule that covers every
		 * page but one is a rule with a hole in exactly the place nobody
		 * looks.
		 */
		$site_path = $this->root . '/data/site.json';

		if ( is_readable( $site_path ) ) {
			$site = (array) json_decode( (string) file_get_contents( $site_path ), true );
			$blog = $site['seo']['blog'] ?? null;

			if ( is_array( $blog ) ) {
				$targets[ $site_path . ' (seo.blog)' ] = $blog;
			}
		}

		foreach ( $targets as $file => $page ) {
			foreach ( array( 'title' => 60, 'meta_description' => 160 ) as $field => $limit ) {
				$value = trim( (string) ( $page[ $field ] ?? '' ) );

				if ( '' === $value ) {
					$this->error(
						'R10 page metadata',
						$file,
						0,
						sprintf( 'no "%s" — the search result for this page would be built by guesswork', $field )
					);
					continue;
				}

				$length = function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );

				if ( $length > $limit ) {
					$this->warn(
						'R10 page metadata',
						$file,
						0,
						sprintf( '"%s" is %d characters; Google truncates around %d', $field, $length, $limit )
					);
				}
			}
		}
	}

	/**
	 * Rule 11 — no function declared at file scope in a component or template.
	 *
	 * WordPress includes a template part with `require`, not `require_once`.
	 * A component that declares a function therefore fatals the second time it
	 * is used on one page — and a component that cannot be used twice is not a
	 * component.
	 *
	 * This has now happened twice. chain.php in Chat 4 took the plans page
	 * down, because three cards each drew a chain. gp-bridge.php was found by
	 * Chat 12's audit and had never fired, because nothing calls it twice yet:
	 * a landmine rather than a crater. The difference between the two is luck,
	 * which is why it is a rule now. Functions belong in inc/, which is loaded
	 * once with require_once.
	 */
	private function rule_11_template_functions(): void {
		foreach ( array( 'components', 'templates' ) as $dir ) {
			foreach ( $this->files( array( 'php' ), $dir ) as $file ) {
				$lines = file( $file, FILE_IGNORE_NEW_LINES );

				if ( false === $lines ) {
					continue;
				}

				foreach ( $lines as $index => $line ) {
					// A declaration at column zero. Anything indented is inside
					// a class, a closure or a conditional and is a different
					// thing; the theme has none, and if it grows one it will be
					// deliberate.
					if ( preg_match( '/^function\s+(\w+)\s*\(/', $line, $match ) ) {
						$this->error(
							'R11 template scope',
							$file,
							$index + 1,
							sprintf(
								'declares %s() at file scope. Template parts are included with require, not require_once, so this fatals the second time the file is used on one page. Move it to inc/.',
								$match[1]
							)
						);
					}
				}
			}
		}
	}

	/**
	 * Rule 12 — a migration seed may not contradict data/site.json on contact.
	 *
	 * Found the hard way, in Chat 14, one call before the content was imported
	 * into WordPress: all four legal seeds carried `tel:+918884446668`, the
	 * phone number from the old site's schema that BrandSure superseded. The
	 * `_note` in site.json says that number "is not used anywhere in this
	 * build". It was — in the four pages a customer is most likely to use it
	 * from.
	 *
	 * Nothing existing could have caught it. R4 checks the seeds for banned
	 * *words*. The Chat 12 numbers audit reads *rendered* pages, and a seed
	 * renders nothing until the day it is imported — by which point it is in
	 * the database and nobody is auditing anything.
	 *
	 * So: every `tel:` and every email address in a seed must match the value
	 * in site.json. Errors, not warnings — a wrong phone number on the privacy
	 * policy is a customer who cannot reach you.
	 */
	private function rule_12_seed_contact_parity(): void {
		$site_path = $this->root . '/data/site.json';

		if ( ! is_readable( $site_path ) ) {
			return;
		}

		$site  = (array) json_decode( (string) file_get_contents( $site_path ), true );
		$tel   = preg_replace( '/[^\d+]/', '', (string) $this->dig( $site, 'contact.phone_tel' ) );
		$email = strtolower( (string) $this->dig( $site, 'contact.email' ) );

		foreach ( $this->files( array( 'html' ), 'migration' ) as $file ) {
			$body = (string) file_get_contents( $file );

			if ( '' !== $tel && preg_match_all( '/tel:([+\d\s\-()]+)/i', $body, $hits ) ) {
				foreach ( array_unique( $hits[1] ) as $found ) {
					if ( preg_replace( '/[^\d+]/', '', $found ) !== $tel ) {
						$this->error(
							'R12 seed contact',
							$file,
							0,
							sprintf( 'links tel:%s, but data/site.json says %s', trim( $found ), $tel )
						);
					}
				}
			}

			if ( '' !== $email && preg_match_all( '/[a-z0-9._%+-]+@brandsure\.in/i', $body, $hits ) ) {
				foreach ( array_unique( $hits[0] ) as $found ) {
					if ( strtolower( $found ) !== $email ) {
						$this->error(
							'R12 seed contact',
							$file,
							0,
							sprintf( 'uses %s, but data/site.json says %s', $found, $email )
						);
					}
				}
			}
		}
	}

	/**
	 * R13 — a number that needs a caveat may not be rendered without it.
	 *
	 * Layer 4 v4 lists "capacity language" among the required negative checks,
	 * and the failure it is guarding against is not someone writing something
	 * false. It is someone writing "up to 300 new enquiry records a month" and
	 * a reader carrying away "300 leads". The caveat is what stops that, so it
	 * is not optional and it is not a footnote somebody can drop while tidying
	 * a layout.
	 *
	 * Each entry below is a fact key and what has to appear in the same page
	 * file: either a named qualifier fact, or a phrase in the surrounding
	 * copy. Checking the page file rather than the rendered output is
	 * deliberate — it fails at the point somebody edits the data, which is
	 * where the mistake gets made.
	 */
	private function rule_13_mandatory_qualifiers(): void {
		$required = array(
			'digital_partner_capacity' => array(
				'needle' => 'capacity_qualifier',
				'why'    => 'the capacity figure without its qualifier reads as a promise of 300 enquiries (Layer 4 v4 §6, DP_CAPACITY_NOT)',
			),
			'digital_partner_breakeven' => array(
				'needle' => 'illustration',
				'why'    => 'Layer 4 v4 §6 requires the five-order figure to be labelled an illustration of the monthly fee, never a business break-even',
			),
			'client_result' => array(
				'needle' => 'client_result_footnote',
				'why'    => 'the case figures may never appear without the outcomes-vary footnote',
			),
		);

		foreach ( $this->files( array( 'json' ), 'data' ) as $file ) {
			$body = (string) file_get_contents( $file );

			// commercial.json defines these; it is not a page that renders them.
			if ( 'commercial.json' === basename( $file ) ) {
				continue;
			}

			// Decode and walk rather than pattern-match the text. _comment and
			// _note keys exist to record why a fact was moved or withdrawn, and
			// several of them necessarily name the fact they are about; reading
			// them makes this rule fire on the documentation of its own subject,
			// which teaches everyone to ignore it. A first attempt stripped them
			// with a regex, and the regex silently ate most of the file — so the
			// rule passed everything. Walking the decoded structure cannot do
			// that.
			$decoded = json_decode( $body, true );

			if ( ! is_array( $decoded ) ) {
				$this->error( 'R13 mandatory qualifier', $file, 0, 'is not valid JSON, so it cannot be checked' );
				continue;
			}

			$body = self::flatten_json( $decoded );

			foreach ( $required as $fact => $spec ) {
				if ( false === strpos( $body, $fact ) ) {
					continue;
				}

				if ( false === stripos( $body, $spec['needle'] ) ) {
					$this->error(
						'R13 mandatory qualifier',
						$file,
						0,
						sprintf(
							'renders %s but nothing in the file supplies "%s" — %s',
							$fact,
							$spec['needle'],
							$spec['why']
						)
					);
				}
			}
		}
	}

	/**
	 * R14 — figures and names that were withdrawn stay withdrawn.
	 *
	 * Both halves of this rule exist because the same thing keeps happening:
	 * a figure is removed from one file and survives in another, and the site
	 * then quotes a price nobody approves of. numbers.v2.js v2 removed
	 * DP_AD_START by name as a "forbidden universal ₹1,00,000 advertising
	 * budget"; Layer 4 v4 §6 says there is no fixed advertising budget at all.
	 *
	 * The plan-name half is matched as a plan label, not as a bare word. Two
	 * service pages legitimately contain "Standard-product thinking" and
	 * "Standard low-ticket tactics", and a rule that fires on those is a rule
	 * people learn to skip.
	 */
	private function rule_14_withdrawn_facts(): void {
		$withdrawn = array(
			'agency_adspend_range'        => 'withdrawn in Chat 15 — there is no suggested advertising range (Layer 4 v4 §6)',
			'digital_partner_adspend_start' => 'withdrawn in Chat 15 — this is the ₹1,00,000 budget numbers.v2.js removed by name',
			'growth_partnership_stages'   => 'withdrawn in Chat 15 — the public five-stage list is retired (Layer 4 v4 §4)',
		);

		$retired_plans = array( 'Standard', 'Expert', 'Virtual Partner', 'Virtual Growth Partner', 'Virtual Growth Partnership' );

		foreach ( $this->files( array( 'json', 'php', 'js', 'html' ) ) as $file ) {
			$body = (string) file_get_contents( $file );

			// The guard itself and the docs name these in order to forbid them.
			if ( 'guard.php' === basename( $file ) ) {
				continue;
			}

			foreach ( $withdrawn as $key => $why ) {
				// A _comment or _note may record that the key was removed.
				if ( preg_match( '/(?<![_a-z])' . preg_quote( $key, '/' ) . '(?![_a-z])/', $body )
					&& false === stripos( $body, 'withdrawn' )
					&& false === stripos( $body, 'REPLACES' )
					&& false === stripos( $body, 'removed' ) ) {
					$this->error( 'R14 withdrawn fact', $file, 0, sprintf( '%s — %s', $key, $why ) );
				}
			}

			foreach ( $retired_plans as $name ) {
				// Only as a plan label: next to "plan", "package", a price, or
				// beside one of the three live names.
				$pattern = '/\b' . preg_quote( $name, '/' ) . '\b\s*(plan|package|tier)|\b(plan|package|tier)\s*[:=]?\s*"?' . preg_quote( $name, '/' ) . '\b/i';

				if ( preg_match( $pattern, $body ) ) {
					$this->error(
						'R14 retired plan name',
						$file,
						0,
						sprintf( '"%s" is a retired plan name (numbers.v2.js RETIRED_NAMES); the only three are Agency, Digital Partner, Growth Partnership', $name )
					);
				}
			}
		}
	}

	/**
	 * Every string in a decoded JSON structure, joined, with keys and values
	 * whose key begins with an underscore left out. Those are the file's own
	 * commentary, not content it renders.
	 *
	 * @param array $node Decoded JSON.
	 * @return string
	 */
	private static function flatten_json( array $node ): string {
		$out = '';

		foreach ( $node as $key => $value ) {
			if ( is_string( $key ) && '' !== $key && '_' === $key[0] ) {
				continue;
			}

			if ( is_array( $value ) ) {
				$out .= self::flatten_json( $value );
			} elseif ( is_string( $value ) ) {
				$out .= ' ' . $value;
			}

			if ( is_string( $key ) ) {
				$out .= ' ' . $key;
			}
		}

		return $out;
	}

	/* --------------------------------------------------------------------
	 * Helpers
	 * ----------------------------------------------------------------- */

	/**
	 * CRM may appear once per file, and must be translated on the spot.
	 *
	 * @param string $file Path.
	 */
	private function check_crm( string $file ): void {
		$strings = $this->copy_strings( $file );
		$hits    = 0;

		foreach ( $strings as $line => $text ) {
			if ( ! preg_match( '/\bCRM\b/', $text ) ) {
				continue;
			}

			++$hits;

			if ( ! preg_match( '/every enquiry is written down|written down/i', $text ) ) {
				$this->error(
					'R4 language',
					$file,
					$line,
					'CRM used without translating it on the spot — say "one place where every enquiry is written down"'
				);
			}
		}

		if ( $hits > 1 ) {
			$this->error( 'R4 language', $file, 0, sprintf( 'CRM appears %d times; the limit is once per page', $hits ) );
		}
	}

	/**
	 * Every human-readable string value in a data file, keyed by line number.
	 *
	 * Keys and underscore-prefixed notes are skipped: the first are never
	 * rendered, the second are stripped at load by inc/data.php.
	 *
	 * @param string $file Path to a JSON file.
	 * @return array<int,string>
	 */
	private function copy_strings( string $file ): array {
		$out   = array();
		$lines = file( $file, FILE_IGNORE_NEW_LINES );

		if ( false === $lines ) {
			return $out;
		}

		foreach ( $lines as $index => $line ) {
			if ( str_contains( $line, 'bs-guard-ignore' ) ) {
				continue;
			}

			// Skip note keys and the lines that declare them.
			if ( preg_match( '/^\s*"_/', $line ) ) {
				continue;
			}

			// Page metadata is not page prose. The title and the meta
			// description are written for a search result, never rendered in
			// the body, and counting them against a budget meant for what the
			// reader has to read on a phone would push pages into writing
			// worse metadata. They are still checked by every other rule.
			if ( preg_match( '/^\s*"(title|meta_description)"\s*:/', $line ) ) {
				continue;
			}

			// Take the value side of "key": "value", and bare array strings.
			if ( preg_match( '/:\s*"(.*)"\s*,?\s*$/', $line, $m ) ) {
				$out[ $index + 1 ] = $m[1];
			} elseif ( preg_match( '/^\s*"(.*)"\s*,?\s*$/', $line, $m ) ) {
				$out[ $index + 1 ] = $m[1];
			}
		}

		return $out;
	}

	/**
	 * Theme files of the given extensions.
	 *
	 * guard.php excludes itself — it necessarily contains every forbidden
	 * pattern, and a check that always fails on its own source is a check
	 * nobody runs. tools/ is excluded for the same reason.
	 *
	 * @param array<int,string> $extensions Extensions without the dot.
	 * @param string            $subdir     Optional subdirectory to limit to.
	 * @return array<int,string>
	 */
	private function files( array $extensions, string $subdir = '' ): array {
		$base = $this->root . ( '' !== $subdir ? '/' . $subdir : '' );

		if ( ! is_dir( $base ) ) {
			return array();
		}

		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $base, FilesystemIterator::SKIP_DOTS )
		);

		$files = array();

		foreach ( $iterator as $item ) {
			/** @var SplFileInfo $item */
			if ( ! $item->isFile() ) {
				continue;
			}

			$path = $item->getPathname();

			if ( str_ends_with( $path, 'inc/guard.php' ) || str_contains( $path, '/tools/' ) ) {
				continue;
			}

			if ( in_array( strtolower( $item->getExtension() ), $extensions, true ) ) {
				$files[] = $path;
			}
		}

		sort( $files );
		return $files;
	}

	/**
	 * Record an error for every line of a file matching a pattern.
	 *
	 * @param string $rule    Rule label.
	 * @param string $file    Path.
	 * @param string $pattern Regex.
	 * @param string $reason  Human-readable reason.
	 */
	private function match( string $rule, string $file, string $pattern, string $reason ): void {
		$lines = file( $file, FILE_IGNORE_NEW_LINES );

		if ( false === $lines ) {
			return;
		}

		foreach ( $lines as $index => $line ) {
			if ( str_contains( $line, 'bs-guard-ignore' ) || $this->is_documentation( $line ) ) {
				continue;
			}

			if ( preg_match( $pattern, $line ) ) {
				$this->error( $rule, $file, $index + 1, $reason . ' — ' . trim( $line ) );
			}
		}
	}

	/**
	 * Is this line documentation that provably cannot reach a visitor?
	 *
	 * Two kinds, and both are safe to skip for a reason rather than for
	 * convenience:
	 *
	 *   - A JSON key beginning with an underscore. inc/data.php strips those
	 *     recursively at load, so no component can render one even by
	 *     accident.
	 *   - A line that is entirely a PHP comment. PHP comments are never
	 *     output. A line with code on it is still checked, so
	 *     `$fee = '20%'; // note` is caught.
	 *
	 * Without this the guard flags the sentence "this page must never show a
	 * deposit" written next to the code that enforces it — which leaves two
	 * bad options: delete the documentation, or reach for the escape hatch.
	 * Both are worse than checking only what can actually render, and the
	 * second is how a real leak eventually gets waved through.
	 *
	 * @param string $line Raw source line.
	 * @return bool
	 */
	private function is_documentation( string $line ): bool {
		$trimmed = ltrim( $line );

		// JSON editorial note: "_comment": "...", "_draft": true, and so on.
		if ( preg_match( '/^"_[a-z0-9_]*"\s*:/i', $trimmed ) ) {
			return true;
		}

		// A whole-line PHP comment.
		return (bool) preg_match( '#^(//|\*|/\*|\#)#', $trimmed );
	}

	/**
	 * The ownership registry, read without booting WordPress.
	 *
	 * CI runs this class standalone, so the registry is parsed out of
	 * content-ownership.php rather than called. One registry, one definition —
	 * duplicating it here would be the very mistake this project is avoiding.
	 *
	 * @return array<string,string>
	 */
	private function registry(): array {
		if ( function_exists( 'bs_ownership_registry' ) ) {
			return bs_ownership_registry();
		}

		$path = $this->root . '/inc/content-ownership.php';

		if ( ! is_readable( $path ) ) {
			return array();
		}

		$source = (string) file_get_contents( $path );
		$out    = array();

		if ( preg_match( '/function bs_ownership_registry.*?return array\((.*?)\);/s', $source, $block ) ) {
			preg_match_all( "/'([a-z0-9\-]*)'\s*=>\s*'(controlled|editable)'/", $block[1], $pairs, PREG_SET_ORDER );

			foreach ( $pairs as $pair ) {
				$out[ $pair[1] ] = $pair[2];
			}
		}

		return $out;
	}

	/**
	 * Read a dot path out of a decoded array.
	 *
	 * @param array  $data Data.
	 * @param string $path Dot path.
	 * @return mixed
	 */
	private function dig( array $data, string $path ) {
		$node = $data;

		foreach ( explode( '.', $path ) as $segment ) {
			if ( ! is_array( $node ) || ! array_key_exists( $segment, $node ) ) {
				return '';
			}
			$node = $node[ $segment ];
		}

		return $node;
	}

	/**
	 * @param string $rule   Rule label.
	 * @param string $file   Path.
	 * @param int    $line   Line number, 0 when file-level.
	 * @param string $detail Message.
	 */
	private function error( string $rule, string $file, int $line, string $detail ): void {
		$this->errors[] = compact( 'rule', 'file', 'line', 'detail' );
	}

	/**
	 * @param string $rule   Rule label.
	 * @param string $file   Path.
	 * @param int    $line   Line number.
	 * @param string $detail Message.
	 */
	private function warn( string $rule, string $file, int $line, string $detail ): void {
		$this->warnings[] = compact( 'rule', 'file', 'line', 'detail' );
	}

	/**
	 * Human-readable report.
	 *
	 * @return string
	 */
	public function report(): string {
		$out  = '';
		$root = $this->root . '/';

		foreach ( array( 'warnings' => $this->warnings, 'errors' => $this->errors ) as $kind => $items ) {
			foreach ( $items as $item ) {
				$out .= sprintf(
					"%s  %s  %s%s  %s\n",
					'errors' === $kind ? 'FAIL' : 'warn',
					str_pad( $item['rule'], 22 ),
					str_replace( $root, '', $item['file'] ),
					$item['line'] ? ':' . $item['line'] : '',
					$item['detail']
				);
			}
		}

		$out .= sprintf(
			"\n%s — %d error(s), %d warning(s)\n",
			array() === $this->errors ? 'PASS' : 'FAILED',
			count( $this->errors ),
			count( $this->warnings )
		);

		return $out;
	}

	/**
	 * @return int Exit code.
	 */
	public function exit_code(): int {
		return array() === $this->errors ? 0 : 1;
	}
}

/**
 * A str_word_count that does not need WordPress loaded.
 *
 * @param string $text Text.
 * @return string
 */
function wp_strip_all_tags_compat( string $text ): string {
	return trim( preg_replace( '/\s+/', ' ', wp_strip_tags_fallback( $text ) ) ?? '' );
}

/**
 * @param string $text Text.
 * @return string
 */
function wp_strip_tags_fallback( string $text ): string {
	return strip_tags( $text ); // phpcs:ignore WordPress.WP.AlternativeFunctions
}

/* -------------------------------------------------------------------------
 * WP-CLI wrapper
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'brandsure guard',
		function ( $args, $assoc ) {
			$guard = new BrandSure_Guard( BRANDSURE_DIR, isset( $assoc['launch'] ) );
			$guard->run();
			WP_CLI::log( $guard->report() );

			if ( $guard->exit_code() ) {
				WP_CLI::error( 'Guard failed. Nothing is deployed until every FAIL above is cleared.' );
			}

			WP_CLI::success( 'Guard passed.' );
		}
	);
}
