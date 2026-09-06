<?php
/**
 * My Profiel identity-strip tagline user meta — My Profiel rebuild (§5.1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Owns a skrywer's optional short tagline (user-meta `ink_skrywer_leuse`).
 *
 * The My Profiel identity strip shows an italic quoted line under the writer's
 * name (Lovable `Profile.tsx`) — deliberately a NEW, short field, separate from
 * BuddyPress/WP core's `description` meta (the longer "Oor my" bio, which stays
 * exactly as-is; see {@see SkrywerProfiel::render()}'s `bio` mapping). Stored as
 * a single `ink_skrywer_leuse` user-meta, following the same "one small class
 * owns one meta key" convention as {@see CoverImage} (`ink_skrywer_omslag_id`)
 * and {@see PinnedWorks} (`ink_vasgespelde_werke`).
 *
 * Unlike `CoverImage`, this field is edited through the real inline edit-profile
 * modal + its REST route ({@see ProfileController}), not a wp-admin profile-edit
 * field — so `register()` only registers the meta itself (sanitize + REST
 * shape), no admin-screen render/save hooks.
 *
 * @package Ink\Core
 */
final class Tagline {

	/**
	 * User-meta key: the short tagline (empty string = none set).
	 */
	public const META = 'ink_skrywer_leuse';

	/**
	 * The maximum stored length — a short line under the name, not a bio.
	 */
	public const MAX_LENGTH = 160;

	/**
	 * Register the meta field.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so this runs directly, no nested `add_action( 'init', … )`.
	 */
	public function register(): void {
		register_meta(
			'user',
			self::META,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * A skrywer's current tagline ('' when unset).
	 *
	 * @param int $user_id The skrywer.
	 * @return string
	 */
	public static function get( int $user_id ): string {
		return (string) get_user_meta( $user_id, self::META, true );
	}

	/**
	 * Set a skrywer's tagline (sanitized + length-capped).
	 *
	 * @param int    $user_id The skrywer.
	 * @param string $tagline The new tagline (raw; sanitized here).
	 * @return bool Whether the underlying `update_user_meta()` call reported success.
	 */
	public static function set( int $user_id, string $tagline ): bool {
		$clean = self::sanitize( $tagline );

		return (bool) update_user_meta( $user_id, self::META, $clean );
	}

	/**
	 * Pure: sanitize + length-cap a raw tagline. No WordPress state beyond the
	 * core sanitizer.
	 *
	 * @param string $tagline The raw tagline.
	 * @return string
	 */
	public static function sanitize( string $tagline ): string {
		$clean = sanitize_text_field( $tagline );

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $clean, 0, self::MAX_LENGTH );
		}

		return substr( $clean, 0, self::MAX_LENGTH );
	}
}
