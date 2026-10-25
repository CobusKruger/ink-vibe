<?php
/**
 * Links migrated challenge result posts to their uitdaging — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\Content\ResultPostAuthors;

defined( 'ABSPATH' ) || exit;

/**
 * Prod's moderator result posts ("<Maand> <jaar> - <naam> projek - Kommentaar en uitslae")
 * are, on the new site, winners announcements: core `post`s linked to their round by
 * {@see WinnersPost::UITDAGING_META}, which feeds the home slot and the archives.
 * {@see PostReclassifier} leaves them as `post`; this links each to its uitdaging by
 * (month, year) — only when exactly one uitdaging matches. Ambiguous or year-less titles
 * are reported, never guessed. Never overwrites an existing link.
 * `wp ink link-result-posts`.
 *
 * @package Ink\Core
 */
class ResultPostLinker {

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink link-result-posts';

	/**
	 * Register the command — ONLY under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function (): void {
				$summary = $this->run();

				foreach ( $summary['unmatched'] as $title ) {
					\WP_CLI::warning( sprintf( 'Geen enkele ooreenstemmende uitdaging vir: %s', $title ) );
				}

				\WP_CLI::success( sprintf( 'Uitslagplasings gekoppel: %d gekoppel, %d reeds gekoppel, %d sonder pasmaat.', $summary['linked'], $summary['already'], count( $summary['unmatched'] ) ) );
			}
		);
	}

	/**
	 * Link every result post. Idempotent.
	 *
	 * @return array{linked:int, already:int, unmatched:list<string>}
	 */
	public function run(): array {
		$uitdagings = $this->uitdagingTitles();
		$linked     = 0;
		$already    = 0;
		$unmatched  = array();

		foreach ( $this->resultPosts() as $post ) {
			if ( $this->existingLink( (int) $post->id ) > 0 ) {
				++$already;
				continue;
			}

			$match = ResultPostFinder::matchUitdaging( ResultPostFinder::monthYear( (string) $post->title ), $uitdagings );

			if ( null === $match ) {
				$unmatched[] = (string) $post->title;
				continue;
			}

			$this->link( (int) $post->id, $match );
			++$linked;
		}

		return array(
			'linked'    => $linked,
			'already'   => $already,
			'unmatched' => $unmatched,
		);
	}

	/**
	 * Uitdaging id → title. Overridable seam.
	 *
	 * @return array<int, string>
	 */
	protected function uitdagingTitles(): array {
		$posts = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			$out[ (int) $post->ID ] = (string) $post->post_title;
		}

		return $out;
	}

	/**
	 * Staff-authored core posts that look like result posts, as `{id, title}`. Overridable seam.
	 *
	 * @return list<object>
	 */
	protected function resultPosts(): array {
		$posts = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);

		$out = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			if ( ResultPostFinder::isResultTitle( (string) $post->post_title ) && ResultPostAuthors::isModerator( (int) $post->post_author ) ) {
				$out[] = (object) array(
					'id'    => (int) $post->ID,
					'title' => (string) $post->post_title,
				);
			}
		}

		return $out;
	}

	/**
	 * The uitdaging a post is already linked to, or 0. Overridable seam.
	 *
	 * @param int $post_id The post.
	 */
	protected function existingLink( int $post_id ): int {
		return (int) get_post_meta( $post_id, WinnersPost::UITDAGING_META, true );
	}

	/**
	 * Link a post to an uitdaging. Overridable seam.
	 *
	 * @param int $post_id      The result post.
	 * @param int $uitdaging_id The uitdaging.
	 */
	protected function link( int $post_id, int $uitdaging_id ): void {
		update_post_meta( $post_id, WinnersPost::UITDAGING_META, $uitdaging_id );
	}
}
