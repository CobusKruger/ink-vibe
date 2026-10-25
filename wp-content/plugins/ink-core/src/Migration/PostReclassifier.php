<?php
/**
 * Once-off post → CPT reclassification — Story 16.5 (FL 16.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Content\ChallengeResultTitle;
use Ink\Content\PostTypes;
use Ink\Content\ResultPostAuthors;

defined( 'ABSPATH' ) || exit;

/**
 * Reclassifies legacy flat `post` content into the typed INK CPTs — a once-off,
 * idempotent DB update (FL 16.5).
 *
 * The content-type category is the source of truth. A post's own categories AND
 * their ancestors are matched against {@see CATEGORY_CPT_MAP}; when several
 * content types match, the most specific wins (gedig > storie > artikel, see
 * {@see TYPE_PRIORITY}). A post with NO recognised category falls through to the
 * `skryfwerk` catch-all — the deliberate fallback for what cannot be categorised,
 * never hand-classified at volume. Legacy `inkpols`
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
		// Poems (musiek-liriek is a poem on the front end; see GENRE_CATEGORIES).
		'gedig'                   => PostTypes::GEDIG,
		'gedigte'                 => PostTypes::GEDIG,
		'poesie'                  => PostTypes::GEDIG,
		'musiek-liriek'           => PostTypes::GEDIG,
		// Stories. `algemeen` is the child of algemeen-verhale whose NAME is "Verhale".
		'verhaal'                 => PostTypes::STORIE,
		'verhale'                 => PostTypes::STORIE,
		'storie'                  => PostTypes::STORIE,
		'stories'                 => PostTypes::STORIE,
		'kortverhaal'             => PostTypes::STORIE,
		'kortverhale'             => PostTypes::STORIE,
		'verhale-kortverhale'     => PostTypes::STORIE,
		'algemeen-verhale'        => PostTypes::STORIE,
		'algemeen'                => PostTypes::STORIE,
		'verhale-liefde'          => PostTypes::STORIE,
		'verhale-jeugkinders'     => PostTypes::STORIE,
		'verhale-liegstories'     => PostTypes::STORIE,
		'verhale-geskiedenis'     => PostTypes::STORIE,
		'verhale-reis'            => PostTypes::STORIE,
		'verhale-wetenskapfiksie' => PostTypes::STORIE,
		// Articles: everything that is neither a poem nor a story.
		'artikel'                 => PostTypes::ARTIKEL,
		'artikels'                => PostTypes::ARTIKEL,
		'algemeengorrel'          => PostTypes::ARTIKEL,
		'rubrieke'                => PostTypes::ARTIKEL,
		'prosa'                   => PostTypes::ARTIKEL,
		'prosa-lede-bydraes'      => PostTypes::ARTIKEL,
	);

	/**
	 * Conflict resolution when a post matches several content types: first wins.
	 *
	 * @var list<string>
	 */
	public const TYPE_PRIORITY = array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );

	/**
	 * Legacy categories that also mark a backend `genre` term (category slug → genre
	 * term name). Front end and gradering still treat these posts as poems.
	 *
	 * @var array<string, string>
	 */
	public const GENRE_CATEGORIES = array(
		'musiek-liriek' => 'Musiek & Liriek',
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
						'Plasings herklassifiseer: %d na tipe-CPT, %d na skryfwerk, %d hernoem, %d oorgeslaan, %d duplikate verwyder, %d uitslagplasings behou%s.',
						(int) $summary['reassigned'],
						(int) $summary['to_skryfwerk'],
						(int) $summary['renamed'],
						(int) $summary['skipped'],
						(int) $summary['dropped'],
						(int) $summary['results'],
						! empty( $summary['already_done'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The CPT for a post's category slugs. Pure.
	 *
	 * Any recognised content-type category (own or ancestor) decides the type; with
	 * several, the most specific wins ({@see TYPE_PRIORITY}). None recognised →
	 * `skryfwerk`. Challenge-round / topic categories are unrecognised and never force a type.
	 *
	 * @param array<int, string> $slugs The post's category slugs (including ancestors).
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

		foreach ( self::TYPE_PRIORITY as $type ) {
			if ( isset( $matched[ $type ] ) ) {
				return $type;
			}
		}

		return PostTypes::SKRYFWERK;
	}

	/**
	 * The backend genre term names implied by a post's category slugs. Pure.
	 *
	 * @param array<int, string> $slugs The post's category slugs.
	 * @return list<string>
	 */
	public static function genresForCategorySlugs( array $slugs ): array {
		$genres = array();

		foreach ( $slugs as $slug ) {
			$key = strtolower( trim( (string) $slug ) );

			if ( isset( self::GENRE_CATEGORIES[ $key ] ) ) {
				$genres[ self::GENRE_CATEGORIES[ $key ] ] = true;
			}
		}

		return array_keys( $genres );
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
	 * @return array{already_done:bool, reassigned:int, to_skryfwerk:int, renamed:int, skipped:int, dropped:int, results:int, collisions:list<string>}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'already_done' => true,
				'reassigned'   => 0,
				'to_skryfwerk' => 0,
				'renamed'      => 0,
				'skipped'      => 0,
				'dropped'      => 0,
				'results'      => 0,
				'collisions'   => array(),
			);
		}

		$reassigned   = 0;
		$to_skryfwerk = 0;
		$renamed      = 0;
		$skipped      = 0;
		$dropped      = 0;
		$results      = 0;

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

			// A moderator's challenge result post ("... Kommentaar en uitslae") is an editorial
			// announcement, not submitted writing: it stays a core `post` (the winners-post
			// representation, linked to its challenge by ink link-result-posts), never skryfwerk.
			if ( 'post' === $type && ! empty( $post->staff ) && ChallengeResultTitle::isResultTitle( (string) ( $post->title ?? '' ) ) ) {
				++$results;
				continue;
			}

			// A committed duplicate (published twice on prod): keep the original, trash this
			// copy. Its old URL is recorded first so RedirectGenerator can 301 to the original.
			if ( StaticRedirects::isDroppedDuplicate( $post_id ) ) {
				$this->recordSourceUrl( $post_id );
				$this->trashPost( $post_id );
				++$dropped;
				continue;
			}

			// R16 review: a `/biblioteek/` or `/opleiding/` flat post belongs to
			// Story 16.6 (LibraryTrainingMigrator). Skip it here so the two commands
			// are disjoint REGARDLESS of run order — otherwise whichever runs second
			// re-records SOURCE_URL_META with the already-changed permalink and the
			// 301 is silently lost. Library/training migration is the authoritative
			// owner of the kept-prefix content.
			if ( 'post' === $type && null !== LibraryTrainingMigrator::cptForPath( (string) ( $post->url ?? '' ) ) ) {
				++$skipped;
				continue;
			}

			$rename = self::renamedPostType( $type );

			if ( null !== $rename ) {
				$target    = $rename;
				$is_rename = true;
			} elseif ( 'post' === $type ) {
				$cat_slugs = $this->categorySlugsFor( $post );
				$target    = self::cptForCategorySlugs( $cat_slugs );
				$is_rename = false;
			} else {
				// Not a flat post and not a known rename — leave it untouched.
				continue;
			}

			// Record the pre-migration permalink BEFORE the type change (16.7 301 source).
			$this->recordSourceUrl( $post_id );
			$this->setPostType( $post_id, $target );

			if ( ! $is_rename ) {
				foreach ( self::genresForCategorySlugs( $cat_slugs ) as $genre ) {
					$this->assignGenre( $post_id, $genre );
				}
			}

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
			'dropped'      => $dropped,
			'results'      => $results,
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

						foreach ( get_ancestors( (int) $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
							$ancestor = get_term( (int) $ancestor_id, 'category' );

							if ( $ancestor instanceof \WP_Term ) {
								$slugs[] = (string) $ancestor->slug;
							}
						}
					}
				}
			}

			$posts[] = (object) array(
				'id'             => $id,
				'post_type'      => (string) get_post_type( $id ),
				'category_slugs' => $slugs,
				'url'            => (string) get_permalink( $id ),
				'title'          => (string) get_the_title( $id ),
				'staff'          => ResultPostAuthors::isModerator( (int) get_post_field( 'post_author', $id ) ),
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
	 * Attach a backend genre term to a post, creating the term when missing.
	 * Overridable seam.
	 *
	 * @param int    $post_id The post id.
	 * @param string $genre   The genre term name.
	 */
	protected function assignGenre( int $post_id, string $genre ): void {
		wp_set_object_terms( $post_id, $genre, 'genre', true );
	}

	/**
	 * Record a post's pre-migration permalink (the 301 source). Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function recordSourceUrl( int $post_id ): void {
		// Write-once: never overwrite an already-recorded source permalink (R16
		// review) — a `--force` re-run after a later slug edit, or a sibling
		// migrator that already recorded it, must not clobber the original 301 source.
		if ( '' !== (string) get_post_meta( $post_id, self::SOURCE_URL_META, true ) ) {
			return;
		}

		$url = get_permalink( $post_id );

		if ( is_string( $url ) && '' !== $url ) {
			update_post_meta( $post_id, self::SOURCE_URL_META, $url );
		}
	}

	/**
	 * Trash a dropped duplicate. Overridable seam.
	 *
	 * @param int $post_id The post id.
	 */
	protected function trashPost( int $post_id ): void {
		wp_trash_post( $post_id );
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
