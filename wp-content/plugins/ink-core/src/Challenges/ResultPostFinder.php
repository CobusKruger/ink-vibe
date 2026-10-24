<?php
/**
 * Finds the challenge result posts on a migrated site — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Report-only finder for the moderators' "Kommentaar en uitslae" posts — the authority
 * for who won (and so for gradering), since the hand-kept title markers and the
 * winners pages are unreliable.
 *
 * The wording drifted over the years (2016–18 "Wenners: … projek", 2018–19 "UITSLAE EN
 * KOMMENTAAR", 2020+ "<Maand> <jaar> - <naam> projek - Kommentaar en uitslae"), so the
 * match is two-part: a RESULT word (uitslae/uitslag/wenner) AND a CHALLENGE word
 * (projek/kompetisie/kommentaar). It only reports; a human confirms the list, which is
 * then committed. `wp ink report-result-posts [--file=<csv>]`.
 *
 * Pure matching; the post query sits behind an overridable seam.
 *
 * @package Ink\Core
 */
class ResultPostFinder {

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink report-result-posts';

	/**
	 * Whether a title looks like a challenge result post. Pure.
	 *
	 * @param string $title The post title.
	 */
	public static function isResultTitle( string $title ): bool {
		$plain = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		$has_result    = 1 === preg_match( '/\b(uitsla[eg]|wenners?)\b/iu', $plain );
		$has_challenge = 1 === preg_match( '/(projek|kompetisie|kom+entaa?[rl]e?)/iu', $plain );

		return $has_result && $has_challenge;
	}

	/**
	 * The grade named in a title marker — "(Brons)", "(SILWER)", "(Goud)" — or null. Pure.
	 *
	 * Used only for the cross-check report ("title says Goud, no result post confirms").
	 *
	 * @param string $title The post title.
	 */
	public static function titleMarker( string $title ): ?string {
		if ( 1 === preg_match( '/\(\s*(brons|silwer|goud)\s*\)/iu', $title, $m ) ) {
			return strtolower( $m[1] );
		}

		return null;
	}

	/**
	 * Register the report command — ONLY under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$rows = $this->report();
				$file = isset( $assoc['file'] ) ? (string) $assoc['file'] : '';

				if ( '' !== $file ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- once-off CLI report written to an operator-chosen path.
					$handle = fopen( $file, 'w' );

					if ( false !== $handle ) {
						fputcsv( $handle, array( 'id', 'date', 'author', 'type', 'title' ) );

						foreach ( $rows as $row ) {
							fputcsv( $handle, $row );
						}

						fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
					}
				}

				\WP_CLI::success( sprintf( '%d moontlike uitslag-plasings gevind%s.', count( $rows ), '' !== $file ? ' (CSV: ' . $file . ')' : '' ) );
			}
		);
	}

	/**
	 * Candidate result posts as `[id, date, author, type, title]` rows.
	 *
	 * @return list<array{0:int,1:string,2:string,3:string,4:string}>
	 */
	public function report(): array {
		$rows = array();

		foreach ( $this->candidatePosts() as $post ) {
			if ( self::isResultTitle( (string) $post->title ) ) {
				$rows[] = array( (int) $post->id, (string) $post->date, (string) $post->author, (string) $post->type, (string) $post->title );
			}
		}

		return $rows;
	}

	/**
	 * Published posts to scan. Overridable seam.
	 *
	 * @return list<object>
	 */
	protected function candidatePosts(): array {
		$posts = get_posts(
			array(
				'post_type'        => array( 'post', 'skryfwerk', 'artikel' ),
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'suppress_filters' => false,
				'orderby'          => 'date',
				'order'            => 'ASC',
			)
		);

		$out = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			$out[] = (object) array(
				'id'     => (int) $post->ID,
				'date'   => (string) $post->post_date,
				'author' => (string) get_the_author_meta( 'user_login', (int) $post->post_author ),
				'type'   => (string) $post->post_type,
				'title'  => (string) $post->post_title,
			);
		}

		return $out;
	}
}
