<?php
/**
 * Who publishes the challenge result posts — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * Whether an account is a moderator who publishes challenge result posts.
 *
 * Either the account can edit others' posts, or its login is on the committed list in
 * `migration-data/result-post-authors.csv`. The list matters because the moderator account
 * `Inkmoderator` is only a subscriber once the user-reclassification step has run, so a
 * capability test alone would file its result posts as submitted writing and lose their wins.
 * Lives in Content so Migration and Challenges can share it without depending on each other.
 *
 * @package Ink\Core
 */
final class ResultPostAuthors {

	/**
	 * Whether an account counts. Pure.
	 *
	 * @param string       $login    The account login.
	 * @param bool         $can_edit Whether the account can edit others' posts.
	 * @param list<string> $logins   The committed moderator logins.
	 */
	public static function matches( string $login, bool $can_edit, array $logins ): bool {
		if ( $can_edit ) {
			return true;
		}

		return in_array( strtolower( $login ), array_map( 'strtolower', $logins ), true );
	}

	/**
	 * Whether an account is a result-post author.
	 *
	 * @param int $user_id The account id.
	 */
	public static function isModerator( int $user_id ): bool {
		$user = get_userdata( $user_id );

		if ( false === $user ) {
			return false;
		}

		return self::matches( (string) $user->user_login, user_can( $user, 'edit_others_posts' ), self::logins() );
	}

	/**
	 * The committed moderator logins.
	 *
	 * @return list<string>
	 */
	public static function logins(): array {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		$cache = array();
		$path  = dirname( __DIR__, 2 ) . '/migration-data/result-post-authors.csv';

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- once-off CLI reader of a committed data file.
		$handle = is_readable( $path ) ? fopen( $path, 'rb' ) : false;

		if ( false !== $handle ) {
			fgetcsv( $handle ); // Header.
			$line = fgetcsv( $handle );

			while ( false !== $line ) {
				if ( isset( $line[0] ) && '' !== trim( (string) $line[0] ) ) {
					$cache[] = trim( (string) $line[0] );
				}

				$line = fgetcsv( $handle );
			}

			fclose( $handle );
		}
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $cache;
	}
}
