<?php
/**
 * Structured placement records — Story 12.6 (FR-50).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\I18n\Terms;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The authoritative, queryable placement store for challenge results.
 *
 * Records the placement **rank** (1st / 2nd / 3rd) on the authoritative entry (AD-5)
 * as {@see self::PLACEMENT_META_KEY} — so all of 1st, 2nd AND 3rd are stored per pool,
 * not only the single winner (FR-50). The pool is the entry's entry-time Gradering
 * snapshot ({@see Entry::GRADERING_META_KEY}, Story 12.4/12.5), so placements are
 * per-Gradering-per-round automatically.
 *
 * Rank semantics (glossary): rank 1 = **algehele wenner** ("[Maand] algehele wenner");
 * ranks 2–3 = **wenner** ("[Maand] wenner"). {@see self::record()}/{@see self::clear()}
 * are the write API the R2 ingestion (12A.3) calls; {@see self::forRound()} is the
 * queryable read that R3 auto-promotion (5.8 — a "win" = any top-3 placement at the
 * writer's current grade) and the SM-8 metric consume.
 *
 * Conflation-clean: placements hang off the entry + its Gradering pool — zero
 * `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Placements {

	/**
	 * The entry meta key holding the placement rank (1/2/3; absent/0 = not placed).
	 *
	 * @var string
	 */
	public const PLACEMENT_META_KEY = 'ink_entry_placement';

	/**
	 * Placement ranks.
	 */
	public const RANK_FIRST  = 1;
	public const RANK_SECOND = 2;
	public const RANK_THIRD  = 3;

	/**
	 * The lowest placing rank stored (top-3).
	 */
	public const MAX_RANK = 3;

	/**
	 * Whether a rank is a stored placement (1–3). Pure.
	 *
	 * @param int $rank The placement rank.
	 * @return bool
	 */
	public static function isValidRank( int $rank ): bool {
		return $rank >= self::RANK_FIRST && $rank <= self::MAX_RANK;
	}

	/**
	 * Whether a rank is the algehele wenner (1st). Pure.
	 *
	 * @param int $rank The placement rank.
	 * @return bool
	 */
	public static function isAlgeheleWenner( int $rank ): bool {
		return self::RANK_FIRST === $rank;
	}

	/**
	 * The placement label: "algehele wenner" (1st) vs "wenner" (2nd/3rd); '' if not
	 * a placement. Pure — Terms only.
	 *
	 * @param int $rank The placement rank.
	 * @return string
	 */
	public static function placementLabel( int $rank ): string {
		if ( ! self::isValidRank( $rank ) ) {
			return '';
		}

		return self::isAlgeheleWenner( $rank )
			? Terms::label( 'algehele_wenner' )
			: Terms::label( 'wenner' );
	}

	/**
	 * Record a placement rank on an entry (1–3). The write API for 12A.3 ingestion.
	 *
	 * @param int $entry_id The placed entry id.
	 * @param int $rank     The placement rank (1/2/3).
	 * @return bool True when a valid placement was written.
	 */
	public static function record( int $entry_id, int $rank ): bool {
		if ( $entry_id <= 0 || ! self::isValidRank( $rank ) ) {
			return false;
		}

		update_post_meta( $entry_id, self::PLACEMENT_META_KEY, $rank );

		return true;
	}

	/**
	 * Clear an entry's placement (for re-ingestion / correction).
	 *
	 * @param int $entry_id The entry id.
	 */
	public static function clear( int $entry_id ): void {
		if ( $entry_id <= 0 ) {
			return;
		}

		delete_post_meta( $entry_id, self::PLACEMENT_META_KEY );
	}

	/**
	 * The stored placement rank for an entry (0 = not placed).
	 *
	 * @param int $entry_id The entry id.
	 * @return int
	 */
	public static function placementFor( int $entry_id ): int {
		if ( $entry_id <= 0 ) {
			return 0;
		}

		return (int) get_post_meta( $entry_id, self::PLACEMENT_META_KEY, true );
	}

	/**
	 * Group placed entries per pool, sorted by rank. Pure.
	 *
	 * Entries with a non-placement rank (0 / out of 1–3) are excluded. Within a pool
	 * the placements are ordered by rank (1st, 2nd, 3rd) and each carries its
	 * algehele-wenner flag + label.
	 *
	 * @param list<array{id:int, gradering:string, rank:int}> $placed The candidate entries.
	 * @return array<string, list<array{id:int, rank:int, is_algehele_wenner:bool, label:string}>>
	 */
	public static function arrange( array $placed ): array {
		$by_pool = array();

		foreach ( $placed as $entry ) {
			$rank = (int) ( $entry['rank'] ?? 0 );
			$id   = (int) ( $entry['id'] ?? 0 );
			$pool = Scalar::asString( $entry['gradering'] ?? '' );

			if ( $id <= 0 || '' === $pool || ! self::isValidRank( $rank ) ) {
				continue;
			}

			$by_pool[ $pool ][] = array(
				'id'                 => $id,
				'rank'               => $rank,
				'is_algehele_wenner' => self::isAlgeheleWenner( $rank ),
				'label'              => self::placementLabel( $rank ),
			);
		}

		foreach ( $by_pool as $pool => $rows ) {
			// Deterministic order: by rank, then by entry id (so ties never depend on
			// incidental query order). Then collapse to one entry per rank per pool —
			// a defensive guard so dirty ingestion (two rank-1s in a pool) can never
			// surface two "algehele wenners" downstream; the lowest-id placement wins
			// the slot (R12 review — the canonical one-per-rank invariant lives here,
			// the authoritative dedup is the 12A.3 ingestion's responsibility).
			usort(
				$rows,
				static function ( array $a, array $b ): int {
					$by_rank = $a['rank'] <=> $b['rank'];

					return 0 !== $by_rank ? $by_rank : ( $a['id'] <=> $b['id'] );
				}
			);

			$seen   = array();
			$unique = array();

			foreach ( $rows as $row ) {
				if ( isset( $seen[ $row['rank'] ] ) ) {
					continue;
				}

				$seen[ $row['rank'] ] = true;
				$unique[]             = $row;
			}

			$by_pool[ $pool ] = $unique;
		}

		return $by_pool;
	}

	/**
	 * The placement records for a round, grouped per pool and sorted by rank. Thin
	 * impure shell over {@see self::arrange()} reusing {@see Pools::forRound()}.
	 *
	 * @param int $uitdaging_id The producing uitdaging id.
	 * @return array<string, list<array{id:int, rank:int, is_algehele_wenner:bool, label:string}>>
	 */
	public static function forRound( int $uitdaging_id ): array {
		$placed = array();

		foreach ( Pools::forRound( $uitdaging_id ) as $pool => $entry_ids ) {
			foreach ( $entry_ids as $entry_id ) {
				$placed[] = array(
					'id'        => (int) $entry_id,
					'gradering' => (string) $pool,
					'rank'      => self::placementFor( (int) $entry_id ),
				);
			}
		}

		return self::arrange( $placed );
	}
}
