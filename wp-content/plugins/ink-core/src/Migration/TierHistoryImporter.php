<?php
/**
 * `wp ink import-tier-history`: the Gradering history the old wins imply.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Challenges\WinnersPost;
use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Ink\Tiers\PromotionEngine;

defined( 'ABSPATH' ) || exit;

/**
 * Gives migrated members the Gradering history they would have if the live engine had seen their
 * wins. `derive-tiers` sets each baseline grade (no audit row, no event); this step adds the missing
 * promotion rows, so the public profile and the audit trail show when each grade was reached.
 *
 * The same dated Gedigte wins `derive-tiers` counts are replayed per account, oldest first, through
 * {@see PromotionEngine::promotionsForTotalWins()} (the live thresholds, the single source). Each
 * promotion is dated by the result post of the win that triggered it and linked to that post's
 * round, so it is the same row {@see Api::promote()} would have written. The grade after the last
 * one is exactly the baseline grade. No event fires: nobody is emailed about the past.
 *
 * Runs after `import-winners` (the result posts are linked to their rounds by then). **After go-live
 * this step is finished**: promotions are written by the live engine. Not recoverable from the old
 * posts, so not written: manual staff promotions (Meester) and anything before the result posts.
 * `--force` clears this step's own rows and rebuilds them (rehearsals only).
 *
 * @package Ink\Core
 */
class TierHistoryImporter {

	/**
	 * The completion flag option.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_tier_history_done';

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink import-tier-history';

	/**
	 * The `reason` on the rows this step writes; it is also how a re-run finds them.
	 *
	 * @var string
	 */
	public const REASON = 'Migrasie: afgelei uit uitslagplasings';

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

				\WP_CLI::success( self::summaryLine( $summary ) );
			}
		);
	}

	/**
	 * The one-line result `migrate-all` shows and judges. Pure.
	 *
	 * @param array{skipped:bool, writers:int, promotions:int, written:int, no_round:int} $summary A {@see run()} summary.
	 */
	public static function summaryLine( array $summary ): string {
		return sprintf(
			'Gradering-geskiedenis ingevoer: %d bevorderings vir %d skrywers, %d geskryf, %d sonder gekoppelde ronde%s.',
			$summary['promotions'],
			$summary['writers'],
			$summary['written'],
			$summary['no_round'],
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
	 * The promotions the dated Gedigte wins imply, per account. Pure.
	 *
	 * Only Gedigte wins with an account count (as in `derive-tiers`). Oldest win first, ties by the
	 * result post id, so the same input always gives the same history.
	 *
	 * @param list<array{result_post:int, result_date:string, writer:string, user:int, title:string, position:int, category:string, grading:string}> $win_rows The parsed wins.
	 * @return list<array{user:int, from:Tier, to:Tier, result_post:int, result_date:string}>
	 */
	public static function plan( array $win_rows ): array {
		$by_user = array();

		foreach ( $win_rows as $row ) {
			if ( 'Gedigte' === $row['category'] && $row['user'] > 0 ) {
				$by_user[ $row['user'] ][] = $row;
			}
		}

		ksort( $by_user );

		$plan = array();

		foreach ( $by_user as $user => $wins ) {
			usort(
				$wins,
				static fn ( array $a, array $b ): int => array( $a['result_date'], $a['result_post'] ) <=> array( $b['result_date'], $b['result_post'] )
			);

			foreach ( PromotionEngine::promotionsForTotalWins( count( $wins ) ) as $promotion ) {
				$trigger = $wins[ $promotion['win'] - 1 ];
				$plan[]  = array(
					'user'        => (int) $user,
					'from'        => $promotion['from'],
					'to'          => $promotion['to'],
					'result_post' => $trigger['result_post'],
					'result_date' => $trigger['result_date'],
				);
			}
		}

		return $plan;
	}

	/**
	 * Write the history. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when done. Rehearsals only.
	 * @return array{skipped:bool, writers:int, promotions:int, written:int, no_round:int}
	 */
	public function run( bool $force = false ): array {
		$summary = array(
			'skipped'    => false,
			'writers'    => 0,
			'promotions' => 0,
			'written'    => 0,
			'no_round'   => 0,
		);

		if ( $this->hasRun() && ! $force ) {
			$summary['skipped'] = true;
			return $summary;
		}

		if ( $force ) {
			$this->clearImported();
		}

		$writers = array();

		foreach ( self::plan( $this->winRows() ) as $step ) {
			++$summary['promotions'];
			$writers[ $step['user'] ] = true;

			$round = $this->roundOf( $step['result_post'] );

			if ( $round <= 0 ) {
				++$summary['no_round']; // Still written: dated, just not linked to a challenge.
			}

			if ( $this->writePromotion( $step['user'], $step['from'], $step['to'], $step['result_date'], $round ) ) {
				++$summary['written'];
			}
		}

		$summary['writers'] = count( $writers );

		update_option( self::OPTION_DONE, true, false );

		return $summary;
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
	 * The round a result post is linked to, or 0. Overridable seam.
	 *
	 * @param int $result_post The result post.
	 */
	protected function roundOf( int $result_post ): int {
		return (int) get_post_meta( $result_post, WinnersPost::UITDAGING_META, true );
	}

	/**
	 * Write one backfilled promotion. Overridable seam.
	 *
	 * @param int    $user_id     The writer.
	 * @param Tier   $from        The grade before.
	 * @param Tier   $to          The grade after.
	 * @param string $result_date The result post's date (site time).
	 * @param int    $round       The linked round, or 0.
	 * @return bool Whether a row was written.
	 */
	protected function writePromotion( int $user_id, Tier $from, Tier $to, string $result_date, int $round ): bool {
		return Api::importHistoricalPromotion( $user_id, $from, $to, get_gmt_from_date( $result_date ), self::REASON, $round );
	}

	/**
	 * Delete the rows an earlier run wrote. Overridable seam; rehearsals only.
	 */
	protected function clearImported(): void {
		Api::clearHistoricalPromotions( self::REASON );
		delete_option( self::OPTION_DONE );
	}
}
