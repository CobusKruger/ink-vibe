<?php
/**
 * Folded search index maintenance — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\Migration\TextKey;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the comparison-key indexes the {@see Search} block matches on.
 *
 * Every index value is a {@see TextKey::spaced()} key: lowercase, no accents, no
 * punctuation, and any run of spaces or line breaks as one space (words stay apart).
 * Search keys the query the same way and matches `LIKE`. A work has two indexes, its
 * title and its body, so title and content matches can be ranked apart. A writer has
 * one: display name, nice name and user name, each wrapped in `|` so an exact name
 * can be told from a partial one. The assembly is pure (testable); the hooks are
 * thin glue.
 *
 * Conflation-clean: `PostTypes` + WP core + the pure `TextKey` only; zero
 * `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class SearchIndex {

	/**
	 * Post-meta: a work's title key.
	 *
	 * @var string
	 */
	public const TITLE_META = '_ink_soek_titel';

	/**
	 * Post-meta: a work's body key (tags stripped).
	 *
	 * @var string
	 */
	public const BODY_META = '_ink_soek_inhoud';

	/**
	 * User-meta: a writer's name keys (display name, nice name, user name), each wrapped in `|`.
	 *
	 * @var string
	 */
	public const SKRYWER_META = 'ink_skrywer_soek_indeks';

	/**
	 * Hook the work + writer index maintenance.
	 */
	public function register(): void {
		add_action( 'save_post', array( self::class, 'onSavePost' ), 20, 2 );
		add_action( 'profile_update', array( self::class, 'onProfileChange' ) );
		add_action( 'user_register', array( self::class, 'onProfileChange' ) );

		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) && class_exists( '\WP_CLI' ) ) {
			\WP_CLI::add_command(
				self::CLI_COMMAND,
				function (): void {
					$done = self::rebuildAll();
					\WP_CLI::success( sprintf( 'Soekindeks gebou: %d werke, %d skrywers.', $done['works'], $done['skrywers'] ) );
				}
			);
		}
	}

	/**
	 * The WP-CLI command name (`wp ink rebuild-search-index`) — the `search-index` step of `migrate-all`.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink rebuild-search-index';

	/**
	 * Build the folded index for everything that already exists. Migrated content
	 * never fires the save hooks, so without this the search finds nothing.
	 *
	 * @return array{works:int, skrywers:int}
	 */
	public static function rebuildAll(): array {
		$works = 0;

		$ids = get_posts(
			array(
				'post_type'        => self::readableTypes(),
				'post_status'      => 'publish',
				'posts_per_page'   => -1,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
				// Oldest first: SkrywerIndex sets "first publication" once and refreshes "last" each time.
				'orderby'          => 'date',
				'order'            => 'ASC',
			)
		);

		foreach ( $ids as $id ) {
			$post = get_post( (int) $id );

			if ( $post instanceof \WP_Post ) {
				self::indexWork( $post->ID, $post->post_title, $post->post_content );

				// The publish-transition denormalisations migrated posts never got
				// (Skrywers tab flags/dates/read seed; "Mees geliefd" reaction seed).
				SkrywerIndex::onTransition( 'publish', 'new', $post );
				\Ink\Engagement\ReactionTotalInit::onTransition( 'publish', 'new', $post );
				++$works;
			}
		}

		$skrywers = 0;

		foreach ( (array) get_users( array( 'fields' => 'ID' ) ) as $uid ) {
			self::rebuildSkrywer( (int) $uid );
			++$skrywers;
		}

		return array(
			'works'    => $works,
			'skrywers' => $skrywers,
		);
	}

	/**
	 * The readable bydrae types whose body feeds the works index.
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * A work's title key. Pure.
	 *
	 * @param string $title The work title.
	 * @return string
	 */
	public static function titleKey( string $title ): string {
		return TextKey::spaced( $title );
	}

	/**
	 * A work's body key: the text only, never the HTML. Block tags and `<br>` count as a space (so
	 * lines and paragraphs don't run together), all other tags, comments, scripts and styles go,
	 * and entities are decoded. Pure.
	 *
	 * @param string $body The work body (HTML).
	 * @return string
	 */
	public static function bodyKey( string $body ): string {
		$spaced = (string) preg_replace( '#</?(?:br|p|div|li|ul|ol|h[1-6]|blockquote|pre|tr|td|th|figure|figcaption|hr)\b[^>]*>#i', ' ', $body );

		return TextKey::spaced( wp_strip_all_tags( $spaced ) );
	}

	/**
	 * A writer's name index: the distinct non-empty keys of display name, nice name and user name,
	 * each wrapped in `|` (`|anna visser|anna|`). Pure.
	 *
	 * @param string $display_name The display name.
	 * @param string $nicename     The nice name (`user_nicename`).
	 * @param string $login        The user name (`user_login`).
	 * @return string
	 */
	public static function skrywerIndexValue( string $display_name, string $nicename, string $login ): string {
		$keys = array_values( array_unique( array_filter( array( TextKey::spaced( $display_name ), TextKey::spaced( $nicename ), TextKey::spaced( $login ) ), static fn ( string $k ): bool => '' !== $k ) ) );

		return array() === $keys ? '' : '|' . implode( '|', $keys ) . '|';
	}

	/**
	 * Persist a work's title and body keys.
	 *
	 * @param int    $post_id The post id.
	 * @param string $title   The title.
	 * @param string $body    The body.
	 */
	private static function indexWork( int $post_id, string $title, string $body ): void {
		update_post_meta( $post_id, self::TITLE_META, self::titleKey( $title ) );
		update_post_meta( $post_id, self::BODY_META, self::bodyKey( $body ) );
	}

	/**
	 * Maintain a work's index on save (skip autosave/revision; readable bydrae only).
	 *
	 * @param int    $post_id The post id.
	 * @param object $post    The post (a `WP_Post` at runtime).
	 */
	public static function onSavePost( int $post_id, $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->post_type ) || ! in_array( (string) $post->post_type, self::readableTypes(), true ) ) {
			return;
		}

		$title = isset( $post->post_title ) ? (string) $post->post_title : '';
		$body  = isset( $post->post_content ) ? (string) $post->post_content : '';

		self::indexWork( $post_id, $title, $body );
	}

	/**
	 * Rebuild a writer's index on profile change / registration.
	 *
	 * @param int $user_id The user id.
	 */
	public static function onProfileChange( $user_id ): void {
		self::rebuildSkrywer( (int) $user_id );
	}

	/**
	 * Rebuild + persist a writer's name index.
	 *
	 * @param int $user_id The writer.
	 */
	public static function rebuildSkrywer( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! $user instanceof \WP_User ) {
			return;
		}

		update_user_meta( $user_id, self::SKRYWER_META, self::skrywerIndexValue( (string) $user->display_name, (string) $user->user_nicename, (string) $user->user_login ) );
	}
}
