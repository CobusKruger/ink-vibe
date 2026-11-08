<?php
/**
 * `wp ink import-entry-gradering`: the Gradering each migrated entry was submitted under.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Challenges\Entry;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\Tier;
use Ink\Tiers\Api as TiersApi;
use Ink\Tiers\PromotionLogEntry;

defined( 'ABSPATH' ) || exit;

/**
 * Writes the entry-time Gradering snapshot ({@see Entry::GRADERING_META_KEY}) on every migrated
 * round entry that has none.
 *
 * Live entries get the snapshot from the entry form. Migrated ones never went through it, so
 * without this step they would sit in no judging pool. The snapshot is the grade the writer held
 * **when the entry was submitted**, never the grade they hold now: it is replayed from the writer's
 * promotion history (the rows `import-tier-history` wrote, plus any live ones), against the entry's
 * post date. The grade at a moment is the `from` of the first promotion after it; if none came
 * after, it is the writer's current grade.
 *
 * Runs after `import-tier-history`. First-wins and idempotent: an entry that already has a snapshot
 * (the winners, written by `import-winners` from the grade heading they won under) is never
 * touched. **After go-live this step is finished**: the entry form writes the snapshot.
 *
 * Not recoverable from the old posts, so not replayed: manual staff promotions (Meester). A Meester
 * with no history rows gets Meester on every old entry, which still judges in the Goud pool.
 *
 * @package Ink\Core
 */
class EntryGraderingImporter {

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink import-entry-gradering';

	/**
	 * Register the WP-CLI trigger, only under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function (): void {
				\WP_CLI::success( self::summaryLine( $this->run() ) );
			}
		);
	}

	/**
	 * The one-line result `migrate-all` shows and judges. Pure.
	 *
	 * @param array{entries:int, written:int, kept:int, no_author:int} $summary A {@see run()} summary.
	 */
	public static function summaryLine( array $summary ): string {
		return sprintf(
			'Inskrywing-gradering ingevoer: %d inskrywings, %d geskryf, %d reeds gestel, %d sonder skrywer.',
			$summary['entries'],
			$summary['written'],
			$summary['kept'],
			$summary['no_author']
		);
	}

	/**
	 * The grade a writer held at a moment. Pure.
	 *
	 * @param list<array{at:string, from:Tier, to:Tier}> $history The writer's promotions, oldest first; `at` is a GMT 'Y-m-d H:i:s'.
	 * @param Tier                                       $current The writer's grade now.
	 * @param string                                     $at      The moment, GMT 'Y-m-d H:i:s'.
	 */
	public static function gradeAt( array $history, Tier $current, string $at ): Tier {
		foreach ( $history as $promotion ) {
			if ( $promotion['at'] > $at ) {
				return $promotion['from'];
			}
		}

		return $current;
	}

	/**
	 * Write the snapshots.
	 *
	 * @return array{entries:int, written:int, kept:int, no_author:int}
	 */
	public function run(): array {
		$summary = array(
			'entries'   => 0,
			'written'   => 0,
			'kept'      => 0,
			'no_author' => 0,
		);

		$histories = array();
		$current   = array();

		foreach ( $this->entries() as $entry ) {
			++$summary['entries'];

			if ( '' !== $this->snapshotOf( $entry['id'] ) ) {
				++$summary['kept'];
				continue;
			}

			$user = $entry['author'];

			if ( $user <= 0 ) {
				++$summary['no_author'];
				continue;
			}

			if ( ! isset( $histories[ $user ] ) ) {
				$histories[ $user ] = $this->history( $user );
				$current[ $user ]   = $this->currentGrade( $user );
			}

			$grade = self::gradeAt( $histories[ $user ], $current[ $user ], $entry['at'] );

			$this->write( $entry['id'], $grade );
			++$summary['written'];
		}

		return $summary;
	}

	// --- Overridable seams (I/O) -------------------------------------------------------

	/**
	 * Every published readable post linked to a round. Overridable seam.
	 *
	 * @return list<array{id:int, author:int, at:string}> `at` is the post's GMT date.
	 */
	protected function entries(): array {
		$query = new \WP_Query(
			array(
				'post_type'           => PostTypes::readableTypes(),
				'post_status'         => 'publish',
				'posts_per_page'      => -1,
				'orderby'             => 'ID',
				'order'               => 'ASC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- one-off migration step over every round entry.
				'tax_query'           => array(
					array(
						'taxonomy' => Taxonomies::UITDAGINGSRONDTE,
						'operator' => 'EXISTS',
					),
				),
			)
		);

		$rows = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$gmt = (string) $post->post_date_gmt;

			if ( '' === $gmt || str_starts_with( $gmt, '0000' ) ) {
				$gmt = get_gmt_from_date( (string) $post->post_date );
			}

			$rows[] = array(
				'id'     => (int) $post->ID,
				'author' => (int) $post->post_author,
				'at'     => $gmt,
			);
		}

		return $rows;
	}

	/**
	 * An entry's existing snapshot, or ''. Overridable seam.
	 *
	 * @param int $post_id The entry.
	 */
	protected function snapshotOf( int $post_id ): string {
		return (string) get_post_meta( $post_id, Entry::GRADERING_META_KEY, true );
	}

	/**
	 * A writer's promotions, oldest first. Overridable seam.
	 *
	 * @param int $user_id The writer.
	 * @return list<array{at:string, from:Tier, to:Tier}>
	 */
	protected function history( int $user_id ): array {
		$rows = array();

		foreach ( array_reverse( TiersApi::historyFor( $user_id ) ) as $entry ) {
			if ( $entry instanceof PromotionLogEntry ) {
				$rows[] = array(
					'at'   => $entry->createdAt,
					'from' => $entry->from,
					'to'   => $entry->to,
				);
			}
		}

		return $rows;
	}

	/**
	 * A writer's grade now. Overridable seam.
	 *
	 * @param int $user_id The writer.
	 */
	protected function currentGrade( int $user_id ): Tier {
		return TiersApi::forUser( $user_id );
	}

	/**
	 * Write one snapshot. Overridable seam.
	 *
	 * @param int  $post_id The entry.
	 * @param Tier $grade   The grade it was submitted under.
	 */
	protected function write( int $post_id, Tier $grade ): void {
		update_post_meta( $post_id, Entry::GRADERING_META_KEY, $grade->value );
	}
}
