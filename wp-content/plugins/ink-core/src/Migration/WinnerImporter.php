<?php
/**
 * `wp ink import-winners`: the old challenge wins, from the old result posts.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Challenges\Entry;
use Ink\Challenges\Placements;
use Ink\Challenges\WinnersPost;
use Ink\Challenges\WinRecords;
use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the wins {@see TierDeriver} reads out of the old result posts into the records the site
 * shows: the `winners` step of `migrate-all`, after the rounds, their entries and the result-post
 * links exist. **After go-live this step is finished**: new wins arrive through the results
 * ingestion ({@see \Ink\Challenges\Ingestion}). `--force` is for rehearsals only.
 *
 * For every win, in every category:
 *  - the round is the result post's stored round link, or, when it has none, the round that holds
 *    most of the winners' posts ({@see WinnerMatcher::chooseRound()}); that link is then stored;
 *  - the winning post is the writer's own post with that title, through {@see TextKey}
 *    ({@see WinnerMatcher}); when found it gets its placement and, for a Gedig under a grade
 *    heading, its entry-Gradering snapshot (the grade the writer held then);
 *  - a record is stored on the round ({@see WinRecords}) **whether or not the post was found**: a win
 *    with no post still counts. A win by a writer with no account (a deleted prod user) is not
 *    recorded: it is shown nowhere and counts toward nothing.
 *
 * Gradering itself (Gedigte only) is `derive-tiers`, from the same rows. Result posts about
 * competitions are never read: competitions are not challenges. Someone who has posted nothing in a
 * year is best effort: a win count slightly off for them is accepted.
 *
 * @package Ink\Core
 */
class WinnerImporter {

	/**
	 * The completion flag option.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_winners_done';

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink import-winners';

	/**
	 * The `source` marker on the rows this step writes.
	 *
	 * @var string
	 */
	public const SOURCE = 'import';

	/**
	 * Register the WP-CLI trigger, only under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );

				foreach ( array_merge( $summary['notes'], $summary['problems'] ) as $line ) {
					\WP_CLI::warning( $line );
				}

				\WP_CLI::success( self::summaryLine( $summary ) );
			}
		);
	}

	/**
	 * The one-line result `migrate-all` shows and judges. Pure.
	 *
	 * @param array<string, mixed> $summary A {@see run()} summary.
	 * @return string
	 */
	public static function summaryLine( array $summary ): string {
		return sprintf(
			'Wenners ingevoer: %d wenne uit %d uitslagplasings, %d aangeteken, %d met die wenpos gevind, %d sonder rekening (nie aangeteken nie), %d sonder ronde (nie ingevoer nie), %d uitslagplasings aan ’n ronde gekoppel%s.',
			(int) $summary['rows'],
			(int) $summary['result_posts'],
			(int) $summary['recorded'],
			(int) $summary['matched'],
			(int) $summary['no_account'],
			(int) $summary['no_round'],
			(int) $summary['linked'],
			$summary['skipped'] ? ' (oorgeslaan — reeds gedoen)' : ''
		);
	}

	/**
	 * Whether the step already ran.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Run the import. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when done. Rehearsals only.
	 * @return array{skipped:bool, rows:int, result_posts:int, recorded:int, matched:int, no_account:int, no_round:int, linked:int, notes:list<string>, problems:list<string>}
	 */
	public function run( bool $force = false ): array {
		$summary = array(
			'skipped'      => false,
			'rows'         => 0,
			'result_posts' => 0,
			'recorded'     => 0,
			'matched'      => 0,
			'no_account'   => 0,
			'no_round'     => 0,
			'linked'       => 0,
			'notes'        => array(),
			'problems'     => array(),
		);

		if ( $this->hasRun() && ! $force ) {
			$summary['skipped'] = true;
			return $summary;
		}

		if ( $force ) {
			$this->clearImported();
		}

		$by_post = array();

		foreach ( $this->winRows() as $row ) {
			$by_post[ $row['result_post'] ][] = $row;
			++$summary['rows'];
		}

		$summary['result_posts'] = count( $by_post );
		$round_dates             = $this->roundDates();

		foreach ( $by_post as $result_post => $rows ) {
			$matches = array();

			foreach ( $rows as $index => $row ) {
				$matches[ $index ] = $row['user'] > 0 ? WinnerMatcher::matches( $row['title'], $this->userPosts( $row['user'] ) ) : array();
			}

			$round = $this->existingLink( $result_post );

			if ( $round <= 0 ) {
				$round = WinnerMatcher::chooseRound( self::votes( $matches ), $round_dates, $rows[0]['result_date'] );

				if ( $round > 0 ) {
					$this->link( $result_post, $round );
					++$summary['linked'];
					$summary['notes'][] = sprintf( 'Uitslagplasing "%s" aan ronde %d gekoppel (die ronde met die meeste wenposte).', $this->postTitle( $result_post ), $round );
				}
			}

			if ( $round <= 0 ) {
				$summary['no_round']  += count( $rows );
				$summary['problems'][] = sprintf( 'Uitslagplasing "%s": geen ronde gevind nie; %d wenne nie ingevoer nie.', $this->postTitle( $result_post ), count( $rows ) );
				continue;
			}

			foreach ( $rows as $index => $row ) {
				$this->importRow( $round, $row, $matches[ $index ], $summary );
			}
		}

		update_option( self::OPTION_DONE, true, false );

		return $summary;
	}

	/**
	 * Store one win. Impure through the seams.
	 *
	 * @param int                                                                                                                              $round   The round.
	 * @param array{result_post:int, result_date:string, writer:string, user:int, title:string, position:int, category:string, grading:string} $row     The parsed win.
	 * @param list<array{id:int, title:string, date:string, rounds:list<int>, score:int}>                                                      $matches The writer's matching posts.
	 * @param array<string, mixed>                                                                                                             $summary The summary, by reference.
	 */
	private function importRow( int $round, array $row, array $matches, array &$summary ): void {
		$category = WinCategory::postTypeFor( $row['category'] );

		if ( null === $category ) {
			$summary['problems'][] = sprintf( 'Onbekende kategorie "%s" by "%s"; as kategorielose wen aangeteken.', $row['category'], $row['title'] );
			$category              = '';
		}

		if ( ! Placements::isValidRank( $row['position'] ) ) {
			$summary['problems'][] = sprintf( 'Wen "%s" (%s) het posisie %d, nie 1 tot 3 nie; nie aangeteken nie.', $row['title'], $row['writer'], $row['position'] );
			return;
		}

		$grade   = self::gradeValue( $row['grading'] );
		$post_id = (int) WinnerMatcher::pick( $matches, $round, $row['result_date'] );

		// A writer with no account (a deleted prod user) is shown nowhere and counts toward nothing:
		// the win is not recorded.
		if ( $row['user'] <= 0 ) {
			++$summary['no_account'];
			return;
		}

		if ( $post_id > 0 ) {
			++$summary['matched'];
			$this->recordPlacement( $post_id, $row['position'], PostTypes::GEDIG === $category ? $grade : '' );
		}

		$stored = WinRecords::row( $round, $post_id, $row['user'], $row['position'], $category, PostTypes::GEDIG === $category ? $grade : '', $row['title'], $row['user'] > 0 ? '' : $row['writer'], self::SOURCE );

		if ( $this->recordWin( $stored ) ) {
			++$summary['recorded'];
		}
	}

	/**
	 * Winning posts per round, over the rows' matches. Pure.
	 *
	 * @param array<int, list<array{id:int, title:string, date:string, rounds:list<int>, score:int}>> $matches Matches per row.
	 * @return array<int, int> Round id => number of rows with a matching post in it.
	 */
	public static function votes( array $matches ): array {
		$votes = array();

		foreach ( $matches as $row_matches ) {
			$rounds = array();

			foreach ( $row_matches as $match ) {
				foreach ( $match['rounds'] as $round ) {
					$rounds[ $round ] = true;
				}
			}

			foreach ( array_keys( $rounds ) as $round ) {
				$votes[ $round ] = ( $votes[ $round ] ?? 0 ) + 1;
			}
		}

		return $votes;
	}

	/**
	 * The stored grade for a heading ("Goud"), or ''. Pure.
	 *
	 * @param string $grading The heading the parser read.
	 * @return string
	 */
	public static function gradeValue( string $grading ): string {
		$tier = Tier::tryFrom( strtolower( trim( $grading ) ) );

		return null !== $tier && Tier::Meester !== $tier ? $tier->value : '';
	}

	// --- Overridable seams (I/O) -------------------------------------------------------

	/**
	 * Every win the result posts hold. Same reading as `derive-tiers`.
	 *
	 * @return list<array{result_post:int, result_date:string, writer:string, user:int, title:string, position:int, category:string, grading:string}>
	 */
	protected function winRows(): array {
		return ( new TierDeriver() )->derive()['win_rows'];
	}

	/**
	 * Uitdaging id => the date it started. Overridable seam.
	 *
	 * @return array<int, string>
	 */
	protected function roundDates(): array {
		$posts = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'any',
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);
		$out   = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			$out[ (int) $post->ID ] = (string) $post->post_date;
		}

		return $out;
	}

	/**
	 * A writer's published posts, with the rounds each is an entry of. Overridable seam; cached.
	 *
	 * @param int $user_id The writer.
	 * @return list<array{id:int, title:string, date:string, rounds:list<int>}>
	 */
	protected function userPosts( int $user_id ): array {
		static $cache = array();

		if ( isset( $cache[ $user_id ] ) ) {
			return $cache[ $user_id ];
		}

		$posts = get_posts(
			array(
				'post_type'        => array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL, PostTypes::SKRYFWERK ),
				'post_status'      => 'publish',
				'author'           => $user_id,
				'numberposts'      => -1,
				'suppress_filters' => false,
			)
		);
		$out   = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			$rounds = array();
			$terms  = get_the_terms( (int) $post->ID, Taxonomies::UITDAGINGSRONDTE );

			foreach ( is_array( $terms ) ? $terms : array() as $term ) {
				$id = ChallengeRound::uitdagingIdFromSlug( (string) $term->slug );

				if ( null !== $id ) {
					$rounds[] = $id;
				}
			}

			$out[] = array(
				'id'     => (int) $post->ID,
				'title'  => (string) $post->post_title,
				'date'   => (string) $post->post_date,
				'rounds' => $rounds,
			);
		}

		$cache[ $user_id ] = $out;

		return $out;
	}

	/**
	 * The round a result post is already linked to, or 0. Overridable seam.
	 *
	 * @param int $post_id The result post.
	 */
	protected function existingLink( int $post_id ): int {
		return (int) get_post_meta( $post_id, WinnersPost::UITDAGING_META, true );
	}

	/**
	 * Link a result post to its round. Overridable seam.
	 *
	 * @param int $post_id      The result post.
	 * @param int $uitdaging_id The round.
	 */
	protected function link( int $post_id, int $uitdaging_id ): void {
		update_post_meta( $post_id, WinnersPost::UITDAGING_META, $uitdaging_id );
	}

	/**
	 * A post's title, for the report. Overridable seam.
	 *
	 * @param int $post_id The post.
	 */
	protected function postTitle( int $post_id ): string {
		return (string) get_the_title( $post_id );
	}

	/**
	 * Write a placement and, for a graded Gedig, its Gradering snapshot. Overridable seam.
	 *
	 * @param int    $post_id  The winning post.
	 * @param int    $position The position.
	 * @param string $grade    The grade it won under, or ''.
	 */
	protected function recordPlacement( int $post_id, int $position, string $grade ): void {
		Placements::record( $post_id, $position );

		if ( '' !== $grade ) {
			update_post_meta( $post_id, Entry::GRADERING_META_KEY, $grade );
		}
	}

	/**
	 * Store a win record. Overridable seam.
	 *
	 * @param array{round_id:int, post_id:int, user_id:int, position:int, category:string, grade:string, title:string, byline?:string, source?:string} $row The {@see WinRecords::row()}.
	 * @return bool Whether one was added.
	 */
	protected function recordWin( array $row ): bool {
		return WinRecords::record( $row );
	}

	/**
	 * Delete the rows an earlier run wrote. Overridable seam; rehearsals only.
	 */
	protected function clearImported(): void {
		WinRecords::clearSource( self::SOURCE );
		delete_option( self::OPTION_DONE );
	}
}
