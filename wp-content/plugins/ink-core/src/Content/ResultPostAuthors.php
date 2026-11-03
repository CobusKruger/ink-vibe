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
 * Either the account can edit others' posts, or it is the author of a post with a result title
 * ({@see ChallengeResultTitle::isResultTitle()}). The second part is read from the posts, not from a
 * file: the moderator account `Inkmoderator` is only a subscriber once the user-reclassification step
 * has run, so a capability test alone would file its result posts as submitted writing and lose their
 * wins. Lives in Content so Migration and Challenges can share it without depending on each other.
 *
 * @package Ink\Core
 */
final class ResultPostAuthors {

	/**
	 * Whether an account counts. Pure.
	 *
	 * @param string       $login    The account login.
	 * @param bool         $can_edit Whether the account can edit others' posts.
	 * @param list<string> $logins   The logins that authored a result post.
	 */
	public static function matches( string $login, bool $can_edit, array $logins ): bool {
		if ( $can_edit ) {
			return true;
		}

		return in_array( strtolower( $login ), array_map( 'strtolower', $logins ), true );
	}

	/**
	 * The distinct logins that authored a post with a result title. Pure.
	 *
	 * @param list<array{login:string, title:string}> $rows Post author login and title.
	 * @return list<string>
	 */
	public static function loginsFromPosts( array $rows ): array {
		$logins = array();

		foreach ( $rows as $row ) {
			if ( '' !== $row['login'] && ChallengeResultTitle::isResultTitle( $row['title'] ) && ! in_array( $row['login'], $logins, true ) ) {
				$logins[] = $row['login'];
			}
		}

		return $logins;
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
	 * The logins that authored a result post, read once from the posts.
	 *
	 * @return list<string>
	 */
	public static function logins(): array {
		static $cache = null;

		if ( null !== $cache ) {
			return $cache;
		}

		global $wpdb;

		$cache = array();

		if ( ! $wpdb instanceof \wpdb ) {
			return $cache;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- once-off migration read; the title test is applied in PHP.
		$rows = $wpdb->get_results(
			"SELECT u.user_login AS login, p.post_title AS title FROM {$wpdb->posts} p JOIN {$wpdb->users} u ON u.ID = p.post_author WHERE p.post_type IN ('post','skryfwerk','artikel') AND p.post_status = 'publish' AND (p.post_title LIKE '%itsla%' OR p.post_title LIKE '%enner%')",
			ARRAY_A
		);

		$cache = self::loginsFromPosts( is_array( $rows ) ? $rows : array() );

		return $cache;
	}
}
