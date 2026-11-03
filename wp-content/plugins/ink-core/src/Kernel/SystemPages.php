<?php
/**
 * The Pages the theme's page templates need in order to be reachable.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * A block theme's `page-{slug}.html` template only decorates a Page that already
 * exists at that slug; a template cannot create a URL. The theme's header, hero
 * and footer link to these slugs, so on a database without the Pages every one of
 * those links 404s or, worse, is rescued by WordPress's slug guesser and lands on
 * an unrelated poem. The first migration rehearsal found exactly that: the Pages
 * had only ever been made by hand in a database that a fresh prod copy replaced.
 *
 * This is the one committed list of those Pages. It is created idempotently (on
 * `init` when the list changes, and by `wp ink ensure-pages`, which `migrate-all`
 * runs), the content stays empty (the template supplies everything), and the
 * Pages are protected from being trashed or re-slugged by accident.
 *
 * A test asserts that every template in the theme is accounted for ({@see self::routeOf()}):
 * a Page listed here, a post type's archive or single, an endpoint view, core's own
 * hierarchy, or an explicit exclusion. A template nothing routes to fails CI instead of
 * being discovered at rehearsal.
 *
 * @package Ink\Core
 */
final class SystemPages {

	/**
	 * Post meta flagging a Page as owned by this registry.
	 */
	public const META = '_ink_system_page';

	/**
	 * Option holding the registry version already applied.
	 */
	public const VERSION_OPTION = 'ink_system_pages_version';

	/**
	 * Slug to page title. Titles are placeholders until the owner reviews them.
	 *
	 * @var array<string, string>
	 */
	public const PAGES = array(
		'teken-in'         => 'Teken in',
		'wagwoord-herstel' => 'Wagwoord herstel',
		'skryf'            => 'Skryf',
		'ontdek'           => 'Ontdek',
		'gemeenskap'       => 'Gemeenskap',
		'my-profiel'       => 'My profiel',
		'lidmaatskap'      => 'Lidmaatskap',
		'onboarding'       => 'Onboarding',
		'oor-ink'          => 'Oor INK',
		'kontak'           => 'Kontak',
		'ledegids'         => 'Ledegids',
		'kompetisies'      => 'Kompetisies',
	);

	/**
	 * Adopted Pages whose legacy body the template replaces. It is emptied on every run,
	 * with its `_simple_css` meta, which Simple CSS would otherwise still print on the page.
	 *
	 * @var list<string>
	 */
	public const EMPTY_BODY = array( 'kompetisies' );

	/**
	 * The Simple CSS plugin's per-post meta.
	 */
	private const SIMPLE_CSS_META = '_simple_css';

	/**
	 * Endpoint views: a template a rewrite endpoint or rule routes to, and its query var.
	 * `kompetisies-gala` is `/kompetisies/gala/<jaar>/` (`Ink\Competitions\GalaPage`).
	 *
	 * @var array<string, string>
	 */
	public const ENDPOINT_TEMPLATES = array(
		'single-uitdaging-uitslae' => 'uitslae',
		'kompetisies-gala'         => 'ink_gala_jaar',
	);

	/**
	 * Templates core's own hierarchy routes to, with the reason each is accounted for.
	 *
	 * @var array<int|string, string>
	 */
	public const CORE_TEMPLATES = array(
		'404'        => 'Core: a request with no match.',
		'index'      => 'Core: the fallback, and how core posts render.',
		'front-page' => 'Core: the static front page.',
		'page'       => 'Core: any Page without its own template.',
		'author'     => 'Core: the author archive, the public writer profile.',
	);

	/**
	 * Templates whose Page another class owns.
	 *
	 * @var list<string>
	 */
	public const MANAGED_ELSEWHERE = array( 'registreer' );

	/**
	 * Templates deliberately without a system Page, with the reason.
	 *
	 * @var array<string, string>
	 */
	public const EXCLUDED = array(
		'qa-bloks'               => 'Developer QA gallery; must not exist on a live site.',
		'my-profiel-lidmaatskap' => 'Interim host chosen by hand as a custom template, retired when My profiel embeds the tab.',
	);

	/**
	 * A legacy login/profile page body that holds only a UPME shortcode.
	 */
	private const LEGACY_SHORTCODE = '/^\s*\[upme_[a-z_]+[^\]]*\]\s*$/';

	/**
	 * How a theme template (name without `.html`) gets routed to, or null when nothing does. Pure.
	 *
	 * @param string       $template   The template name.
	 * @param list<string> $post_types The registered post types.
	 * @return string|null `page`, `managed`, `excluded`, `post-type`, `endpoint`, `core`.
	 */
	public static function routeOf( string $template, array $post_types ): ?string {
		if ( isset( self::ENDPOINT_TEMPLATES[ $template ] ) ) {
			return 'endpoint';
		}

		if ( isset( self::CORE_TEMPLATES[ $template ] ) ) {
			return 'core';
		}

		if ( 1 === preg_match( '/^page-(.+)$/', $template, $page ) ) {
			if ( isset( self::PAGES[ $page[1] ] ) ) {
				return 'page';
			}

			if ( isset( self::EXCLUDED[ $page[1] ] ) ) {
				return 'excluded';
			}

			return in_array( $page[1], self::MANAGED_ELSEWHERE, true ) ? 'managed' : null;
		}

		if ( 1 === preg_match( '/^(?:archive|single)-(.+)$/', $template, $type ) && in_array( $type[1], $post_types, true ) ) {
			return 'post-type';
		}

		return null;
	}

	/**
	 * Wire the guards and the self-healing ensure step.
	 */
	public static function register(): void {
		add_action( 'init', self::ensureIfStale( ... ), 30 );
		add_filter( 'map_meta_cap', self::protectDeletion( ... ), 10, 4 );
		add_filter( 'wp_insert_post_data', self::keepSlug( ... ), 10, 2 );

		add_action( 'init', self::registerCli( ... ), 31 );
	}

	/**
	 * Register `wp ink ensure-pages`. On `init`, so the `ink` command group exists.
	 */
	public static function registerCli(): void {
		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command(
				'ink ensure-pages',
				static function (): void {
					$r = self::ensure();
					update_option( self::VERSION_OPTION, self::version() );
					if ( array() !== $r['failed'] ) {
						\WP_CLI::error( 'Stelselbladsye kon nie geskep word nie: ' . implode( ', ', $r['failed'] ) );
					}
					\WP_CLI::success(
						sprintf(
							'Stelselbladsye: %d geskep, %d reeds daar, %d herstel.',
							count( $r['created'] ),
							count( $r['existing'] ),
							count( $r['repaired'] )
						)
					);
				}
			);
		}
	}

	/**
	 * Hash of the registry; changes when a Page is added or retitled.
	 */
	public static function version(): string {
		return md5( http_build_query( self::PAGES ) );
	}

	/**
	 * Run {@see self::ensure()} once per registry version.
	 */
	public static function ensureIfStale(): void {
		if ( get_option( self::VERSION_OPTION ) === self::version() ) {
			return;
		}

		$result = self::ensure();

		// Leave the option unset on failure so the next request retries.
		if ( array() === $result['failed'] ) {
			update_option( self::VERSION_OPTION, self::version() );
		}
	}

	/**
	 * Create or repair every system Page. Idempotent.
	 *
	 * @return array{created:list<string>, existing:list<string>, repaired:list<string>, failed:list<string>}
	 */
	public static function ensure(): array {
		// Inserting a Page makes WordPress load the block theme's JSON to validate
		// its template, which exhausts the 128 MB WP-CLI default on a big site.
		if ( function_exists( 'wp_raise_memory_limit' ) ) {
			wp_raise_memory_limit( 'admin' );
		}

		$out = array(
			'created'  => array(),
			'existing' => array(),
			'repaired' => array(),
			'failed'   => array(),
		);

		foreach ( self::PAGES as $slug => $title ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );

			if ( null === $page ) {
				$id = wp_insert_post(
					array(
						'post_type'    => 'page',
						'post_status'  => 'publish',
						'post_name'    => $slug,
						'post_title'   => $title,
						'post_content' => '',
						'meta_input'   => array( self::META => 1 ),
					)
				);

				if ( is_wp_error( $id ) || ! is_int( $id ) || $id <= 0 ) {
					$out['failed'][] = $slug;
					continue;
				}

				$out['created'][] = $slug;
				continue;
			}

			$fix = array();

			if ( 'publish' !== $page->post_status ) {
				$fix['post_status'] = 'publish';
			}

			$empties = in_array( $slug, self::EMPTY_BODY, true );

			if ( 1 === preg_match( self::LEGACY_SHORTCODE, (string) $page->post_content ) || ( $empties && '' !== (string) $page->post_content ) ) {
				$fix['post_content'] = '';
			}

			if ( array() !== $fix ) {
				wp_update_post( array_merge( array( 'ID' => (int) $page->ID ), $fix ) );
			}

			$css_removed = $empties && '' !== (string) get_post_meta( (int) $page->ID, self::SIMPLE_CSS_META, true );

			if ( $css_removed ) {
				delete_post_meta( (int) $page->ID, self::SIMPLE_CSS_META );
			}

			if ( '1' !== (string) get_post_meta( (int) $page->ID, self::META, true ) ) {
				update_post_meta( (int) $page->ID, self::META, 1 );
			}

			$out[ array() === $fix && ! $css_removed ? 'existing' : 'repaired' ][] = $slug;
		}

		return $out;
	}

	/**
	 * Nobody may delete a system Page.
	 *
	 * @param list<string> $caps    Required caps.
	 * @param string       $cap     The capability being checked.
	 * @param int          $user_id The user.
	 * @param list<mixed>  $args    Extra args; the post id comes first.
	 * @return list<string>
	 */
	public static function protectDeletion( array $caps, string $cap, int $user_id, array $args ): array {
		unset( $user_id );

		if ( 'delete_post' !== $cap || ! isset( $args[0] ) ) {
			return $caps;
		}

		if ( '1' === (string) get_post_meta( (int) $args[0], self::META, true ) ) {
			return array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * Keep a system Page's slug when an edit tries to change it.
	 *
	 * @param array<string, mixed> $data    Sanitised post data about to be saved.
	 * @param array<string, mixed> $postarr The raw arguments.
	 * @return array<string, mixed>
	 */
	public static function keepSlug( array $data, array $postarr ): array {
		$id = (int) ( $postarr['ID'] ?? 0 );

		if ( $id <= 0 || 'page' !== ( $data['post_type'] ?? '' ) ) {
			return $data;
		}

		if ( '1' !== (string) get_post_meta( $id, self::META, true ) ) {
			return $data;
		}

		$page = get_post( $id );

		if ( $page instanceof \WP_Post && '' !== $page->post_name ) {
			$data['post_name'] = $page->post_name;
		}

		return $data;
	}
}
