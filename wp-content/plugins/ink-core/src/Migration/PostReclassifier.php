<?php
/**
 * Once-off post → CPT reclassification — Story 16.5 (FL 16.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Reclassifies legacy flat `post` content into the typed INK CPTs — a once-off,
 * idempotent DB update (FL 16.5).
 *
 * The content-type category is the source of truth: a post with exactly ONE
 * recognised content-type category maps to that CPT (`gedig`/`storie`/`artikel`,
 * honouring the legacy `verhaal`→`storie` rename); a post with ZERO recognised
 * OR CONFLICTING (2+) content-type categories falls through to the `skryfwerk`
 * catch-all automatically — never hand-classified at volume. Legacy `inkpols`
 * posts are renamed to `inkpols_uitgawe`; `monthly_challenge` posts are SKIPPED
 * (left for {@see \Ink\Challenges\Migration} to build uitdaging records from round
 * categories — real data folded in, else dropped — NOT a 1:1 conversion here).
 *
 * Every reassigned post's pre-migration permalink is recorded in
 * {@see SOURCE_URL_META} BEFORE its `post_type` changes, so {@see RedirectGenerator}
 * (Story 16.7) can emit the mandatory 301. After reassignment the rewrite rules
 * are flushed (the activation flush does not cover post-activation slug changes),
 * and any CPT-archive ↔ page slug collision is surfaced as a warning.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a
 * no-op (`--force` re-runs); WP-CLI only (`wp ink migrate-posts`) — never a web
 * request. Conflation-clean: reads the `Content\PostTypes` slug registry + WP
 * core; zero `Tiers`/`Entitlement` coupling.
 *
 * Not `final`: the post/DB methods are overridable seams so the orchestration is
 * unit-testable without the WordPress post API.
 *
 * @package Ink\Core
 */
class PostReclassifier {

	/**
	 * The completion flag option — set once the reclassification has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_posts_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-posts`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-posts';

	/**
	 * The meta key recording a post's pre-migration permalink (the 301 source).
	 * Shared with {@see RedirectGenerator} (Story 16.7).
	 *
	 * @var string
	 */
	public const SOURCE_URL_META = 'ink_migration_source_url';

	/**
	 * Legacy post type SKIPPED by this reclassification (built elsewhere).
	 *
	 * @var string
	 */
	public const SKIPPED_TYPE = 'monthly_challenge';

	/**
	 * Content-type category slug → CPT. Honours the legacy `verhaal`→`storie`
	 * rename and common plurals; anything not here is unrecognised (→ skryfwerk).
	 *
	 * @var array<string, string>
	 */
	public const CATEGORY_CPT_MAP = array(
		'gedig'       => PostTypes::GEDIG,
		'gedigte'     => PostTypes::GEDIG,
		'poesie'      => PostTypes::GEDIG,
		'verhaal'     => PostTypes::STORIE,
		'verhale'     => PostTypes::STORIE,
		'storie'      => PostTypes::STORIE,
		'stories'     => PostTypes::STORIE,
		'kortverhaal' => PostTypes::STORIE,
		'artikel'     => PostTypes::ARTIKEL,
		'artikels'    => PostTypes::ARTIKEL,
	);

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );

				foreach ( $summary['collisions'] as $slug ) {
					\WP_CLI::warning( sprintf( 'Slug-botsing: argief "%s" oorvleuel met \'n bestaande bladsy.', (string) $slug ) );
				}

				\WP_CLI::success(
					sprintf(
						'Plasings herklassifiseer: %d na tipe-CPT, %d na skryfwerk, %d hernoem, %d oorgeslaan%s.',
						(int) $summary['reassigned'],
						(int) $summary['to_skryfwerk'],
						(int) $summary['renamed'],
						(int) $summary['skipped'],
						! empty( $summary['already_done'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The CPT for a post's category slugs. Pure.
	 *
	 * Exactly one recognised content-type category → that CPT; zero recognised OR
	 * conflicting (2+ distinct) → `skryfwerk`. Challenge-round / topic categories
	 * are simply unrecognised here and never force a type.
	 *
	 * @param array<int, string> $slugs The post's category slugs.
	 * @return string A CPT slug (`gedig`/`storie`/`artikel`/`skryfwerk`).
	 */
	public static function cptForCategorySlugs( array $slugs ): string {
		$matched = array();

		foreach ( $slugs as $slug ) {
			$key = strtolower( trim( (string) $slug ) );

			if ( isset( self::CATEGORY_CPT_MAP[ $key ] ) ) {
				$matched[ self::CATEGORY_CPT_MAP[ $key ] ] = true;
			}
		}

		$distinct = array_keys( $matched );

		return ( 1 === count( $distinct ) ) ? (string) $distinct[0] : PostTypes::SKRYFWERK;
	}

	/**
	 * The renamed CPT for a legacy post type, or null when it is not a rename. Pure.
	 *
	 * @param string $legacy The legacy `post_type`.
	 * @return string|null
	 */
	public static function renamedPostType( string $legacy ): ?string {
		return 'inkpols' === $legacy ? PostTypes::INKPOLS_UITGAWE : null;
	}

	/**
	 * Whether a legacy post type is skipped by this reclassification. Pure.
	 *
	 * @param string $legacy The legacy `post_type`.
	 * @return bool
	 */
	public static function isSkippedType( string $legacy ): bool {
		return self::SKIPPED_TYPE === $legacy;
	}

	/**
	 * The CPT archive base slugs that could collide with a page slug. Pure.
	 *
	 * @return list<string>
	 */
	public static function archiveSlugs(): array {
		return array(
			PostTypes::GEDIG,
			PostTypes::STORIE,
			PostTypes::ARTIKEL,
			PostTypes::SKRYFWERK,
			'biblioteek',
			'opleiding',
			PostTypes::UITDAGING,
			'inkpols',
		);
	}

	/**
	 * The page slugs that collide with a CPT archive base. Pure.
	 *
	 * @param array<int, string> $page_slugs The existing page slugs.
	 * @return list<string>
	 */
	public static function slugCollisions( array $page_slugs ): array {
		$archives   = self::archiveSlugs();
		$normalised = array_map(
			static fn ( $slug ): string => strtolower( trim( (string) $slug ) ),
			$page_slugs
		);

		return array_values( array_intersect( $archives, $normalised ) );
	}

	/**
	 * Run the once-off reclassification. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{already_done:bool, reassigned:int, to_skryfwerk:int, renamed:int, skipped:int, collisions:list<string>}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'already_done' => true,
				'reassigned'   => 0,
				'to_skryfwerk' => 0,
				'renamed'      => 0,
				'skipped'      => 0,
				'collisions'   => array(),
			);
		}

		$reassigned   = 0;
		$to_skryfwerk = 0;
		$renamed      = 0;
		$skipped      = 0;

		foreach ( $this->legacyPosts() as $post ) {
			$post_id = (int) ( $post->id ?? 0 );
			$type    = (string) ( $post->post_type ?? '' );

			if ( $post_id <= 0 || '' === $type ) {
				continue;
			}

			if ( self::isSkippedType( $type ) ) {
				++$skipped;
				continue;
			}

			$rename = self::renamedPostType( $type );

			if ( null !== $rename ) {
				$target    = $rename;
				$is_rename = true;
			} elseif ( 'post' === $type ) {
				$target    = self::cptForCategorySlugs( $this->categorySlugsFor( $post ) );
				$is_rename = false;
			} else {
				// Not a flat post and not a known rename — leave it untouched.
				continue;
			}

			// Record the pre-migration permalink BEFORE the type change (16.7 301 source).
			$this->recordSourceUrl( $post_id );
			$this->setPostType( $post_id, $target );

			if ( $is_rename ) {
				++$renamed;
			} elseif ( PostTypes::SKRYFWERK === $target ) {
				++$to_skryfwerk;
			} else {
				++$reassigned;
			}
		}

		$collisions = self::slugCollisions( $this->existingPageSlugs() );

		$this->flushRewrites();
		$this->markDone();

		return array(
			'already_done' => false,
			'reassigned'   => $reassigned,
			'to_skryfwerk' => $to_skryfwerk,
			'renamed'      => $renamed,
			'skipped'      => $skipped,
			'collisions'   => $collisions,
		);
	}

	/**
	 * Whether the reclassification has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the reclassification complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The legacy posts to reclassify. Overridable seam.
	 *
	 * Default: every `post` + legacy `inkpols` post (the renamable types). Each row
	 * carries an `id`, `post_type`, and `category_slugs` list.
	 *
	 * @return array<int, object>
	 */
	protected function legacyPosts(): array {
		$ids = get_posts(
			array(
				'post_type'        => array( 'post', 'inkpols' ),
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$posts = array();

		foreach ( $ids as $id ) {
			$id    = (int) $id;
			$terms = get_the_terms( $id, 'category' );
			$slugs = array();

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $term instanceof \WP_Term ) {
						$slugs[] = (string) $term->slug;
					}
				}
			}

			$posts[] = (object) array(
				'id'             => $id,
				'post_type'      => (string) get_post_type( $id ),
				'category_slugs' => $slugs,
			);
		}

		return $posts;
	}

	/**
	 * The category slugs carried on a legacy-post row. Overridable seam.
	 *
	 * @param object $post A legacy-post row.
	 * @return list<string>
	 */
	protected function categorySlugsFor( object $post ): array {
		$slugs = $post->category_slugs ?? array();

		return is_array( $slugs ) ? array_values( array_map( 'strval', $slugs ) ) : array();
	}

	/**
	 * Record a post's pre-migration permalink (the 301 source). Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function recordSourceUrl( int $post_id ): void {
		$url = get_permalink( $post_id );

		if ( is_string( $url ) && '' !== $url ) {
			update_post_meta( $post_id, self::SOURCE_URL_META, $url );
		}
	}

	/**
	 * Change a post's type. Overridable seam.
	 *
	 * @param int    $post_id The post id.
	 * @param string $type    The target CPT slug.
	 */
	protected function setPostType( int $post_id, string $type ): void {
		set_post_type( $post_id, $type );
	}

	/**
	 * The existing page slugs (for the collision guard). Overridable seam.
	 *
	 * @return list<string>
	 */
	protected function existingPageSlugs(): array {
		$pages = get_posts(
			array(
				'post_type'        => 'page',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $pages ) ) {
			return array();
		}

		$slugs = array();

		foreach ( $pages as $id ) {
			$slug = get_post_field( 'post_name', (int) $id );

			if ( is_string( $slug ) && '' !== $slug ) {
				$slugs[] = $slug;
			}
		}

		return $slugs;
	}

	/**
	 * Flush the rewrite rules after reassignment. Overridable seam.
	 */
	protected function flushRewrites(): void {
		flush_rewrite_rules( false );
	}
}
