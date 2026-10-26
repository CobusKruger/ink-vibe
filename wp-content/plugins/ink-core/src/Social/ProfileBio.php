<?php
/**
 * The one place a writer's bio is read from.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Prod members wrote their bio in the BuddyPress "Biografie" profile field; the new
 * site stores it in the user's `description`. Nothing is copied across at migration:
 * a bio is read as the member's own `description`, and, until they have saved one on
 * My Profiel, falls back to the BuddyPress field. That covers members who join after
 * cutover too, and every surface (public profile, My Profiel and its edit form,
 * author cards, the following list) reads it through here so they cannot disagree.
 *
 * Saving a bio on My Profiel marks it as the member's own ({@see self::markEdited()}),
 * so clearing it on purpose is respected rather than refilled from the old field.
 *
 * @package Ink\Core
 */
final class ProfileBio {

	/**
	 * The BuddyPress extended-profile field holding the prod members' bio.
	 */
	public const BIO_FIELD = 'Biografie';

	/**
	 * User meta set once a member has saved their bio on My Profiel.
	 */
	public const EDITED_META = 'ink_bio_edited';

	/**
	 * The writer's bio as plain text. Side-effecting (reads user and profile data).
	 *
	 * @param int $user_id The writer.
	 */
	public static function for( int $user_id ): string {
		$own = trim( (string) get_the_author_meta( 'description', $user_id ) );

		if ( '' !== $own || '1' === (string) get_user_meta( $user_id, self::EDITED_META, true ) ) {
			return $own;
		}

		if ( function_exists( 'xprofile_get_field_data' ) ) {
			return self::clean( (string) xprofile_get_field_data( self::BIO_FIELD, $user_id ) );
		}

		return '';
	}

	/**
	 * Record that the member has saved their own bio (even an empty one).
	 *
	 * @param int $user_id The writer.
	 */
	public static function markEdited( int $user_id ): void {
		update_user_meta( $user_id, self::EDITED_META, '1' );
	}

	/**
	 * A profile-field bio as plain text. Pure.
	 *
	 * @param string $raw The stored field value (may carry markup and entities).
	 */
	public static function clean( string $raw ): string {
		$plain = html_entity_decode( strip_tags( $raw ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- pure helper, runs without WordPress loaded.

		return trim( (string) preg_replace( '/[ \t]+/', ' ', $plain ) );
	}
}
