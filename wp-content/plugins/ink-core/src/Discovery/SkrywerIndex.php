<?php
/**
 * Per-writer discovery denormalization — Story 8.3 (FR-34, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the denormalized per-writer user-meta the skrywers tab queries on.
 *
 * The skrywers tab is a `WP_User_Query` (AD-7 — Query Loop cannot query users),
 * which can only filter/order on user-meta — so a writer's published FORMS, their
 * FIRST-publication time ("Nuwe stemme"), and their read-total seed are
 * denormalized here on a bydrae's publish. "genre via the writer's published
 * works" (AD-7), never per-item editorial linking (Principle 8).
 *
 * Conflation-clean: references only `Ink\Content\PostTypes` (slug source) + WP
 * user-meta — zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class SkrywerIndex {

	/**
	 * User-meta: the writer's first-publication GMT unix timestamp (set once).
	 *
	 * @var string
	 */
	public const FIRST_PUBLISH_META = 'ink_skrywer_eerste_publikasie';

	/**
	 * User-meta: the writer's denormalized total read count (seed + increment).
	 *
	 * @var string
	 */
	public const READ_TOTAL_META = 'ink_skrywer_gelees_telling';

	/**
	 * Per-form "has published" flag-key prefix (e.g. `ink_skrywer_het_gedig`).
	 *
	 * @var string
	 */
	private const FLAG_PREFIX = 'ink_skrywer_het_';

	/**
	 * Hook the publish transition.
	 */
	public function register(): void {
		add_action( 'transition_post_status', array( self::class, 'onTransition' ), 10, 3 );
	}

	/**
	 * The readable bydrae types whose publication makes a member a "skrywer".
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * The "has published this form" user-meta flag key for a bydrae type. Pure.
	 *
	 * @param string $type A bydrae type.
	 * @return string
	 */
	public static function formFlagKey( string $type ): string {
		return self::FLAG_PREFIX . $type;
	}

	/**
	 * Map a skrywers-tab genre filter to its bydrae type, or null. Pure.
	 *
	 * @param string $genre `digkuns` | `prosa` | `artikels`.
	 * @return string|null
	 */
	public static function genreToType( string $genre ): ?string {
		switch ( $genre ) {
			case 'digkuns':
				return PostTypes::GEDIG;
			case 'prosa':
				return PostTypes::STORIE;
			case 'artikels':
				return PostTypes::ARTIKEL;
			default:
				return null;
		}
	}

	/**
	 * Maintain the author's denormalized fields on a bydrae's publish.
	 *
	 * @param string $new_status The new post status.
	 * @param string $old_status The previous post status.
	 * @param object $post       The post (a `WP_Post` at runtime).
	 */
	public static function onTransition( string $new_status, string $old_status, $post ): void {
		if ( 'publish' !== $new_status || ! is_object( $post ) || ! isset( $post->post_type, $post->post_author ) ) {
			return;
		}

		$type = (string) $post->post_type;

		if ( ! in_array( $type, self::readableTypes(), true ) ) {
			return;
		}

		$author = (int) $post->post_author;

		if ( $author <= 0 ) {
			return;
		}

		// Form flag — this writer has published in this form.
		update_user_meta( $author, self::formFlagKey( $type ), '1' );

		// First publication — set ONCE (a later publish never moves it back/forward).
		if ( '' === (string) get_user_meta( $author, self::FIRST_PUBLISH_META, true ) ) {
			$ts = isset( $post->post_date_gmt ) ? (int) strtotime( (string) $post->post_date_gmt ) : 0;
			update_user_meta( $author, self::FIRST_PUBLISH_META, max( 0, $ts ) );
		}

		// Read-total seed — so the "Meeste gelees" meta-ordered query includes a
		// writer with zero reads yet (the meta join would otherwise drop them).
		if ( '' === (string) get_user_meta( $author, self::READ_TOTAL_META, true ) ) {
			update_user_meta( $author, self::READ_TOTAL_META, 0 );
		}
	}
}
