<?php
/**
 * Results coverage report (dekkingsverslag) — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Reconciles the parsed judges' results against ALL stored EntryIDs and produces the
 * **dekkingsverslag** (coverage report) that gates the irreversible commit (Story 12A.3,
 * R2). Pure: no WP, no side effects.
 *
 * It answers, for the editor's confirm decision: were all winner references matched
 * against real entries? was commentary resolved for every entry? are there EntryIDs in
 * the document that match nothing (and entries with no commentary)? and — the
 * authoritative rank-uniqueness invariant (Epic-12 retro readiness flag #1) — is any
 * pool slot (Gradering × category × rank) claimed by more than one entry, or any entry
 * placed at more than one rank? {@see blocksCommit()} hard-blocks the commit on an
 * unknown EntryID or a duplicate — so two "algehele wenners" can never be committed; the
 * softer gaps (missing commentary) are surfaced for the editor's explicit confirm.
 *
 * @package Ink\Core
 */
final class Coverage {

	/**
	 * Build the dekkingsverslag. Pure.
	 *
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners    Parsed winners.
	 * @param list<array{entry_id:string, title:string, text:string}>           $commentary Parsed commentary.
	 * @param list<string>                                                      $stored     All stored EntryID strings for the round.
	 * @return array{
	 *   matched_winners:list<string>, unknown_winners:list<string>,
	 *   matched_commentary:list<string>, unknown_commentary:list<string>,
	 *   entries_without_commentary:list<string>, duplicates:list<string>,
	 *   all_winners_identified:bool, all_commentary_resolved:bool
	 * }
	 */
	public static function report( array $winners, array $commentary, array $stored ): array {
		$stored_set = array_fill_keys( $stored, true );

		$matched_winners = array();
		$unknown_winners = array();

		foreach ( $winners as $winner ) {
			$id = (string) ( $winner['entry_id'] ?? '' );

			if ( isset( $stored_set[ $id ] ) ) {
				$matched_winners[ $id ] = true;
			} else {
				$unknown_winners[ $id ] = true;
			}
		}

		$commentary_ids     = array();
		$matched_commentary = array();
		$unknown_commentary = array();

		foreach ( $commentary as $block ) {
			$id                    = (string) ( $block['entry_id'] ?? '' );
			$commentary_ids[ $id ] = true;

			if ( isset( $stored_set[ $id ] ) ) {
				$matched_commentary[ $id ] = true;
			} else {
				$unknown_commentary[ $id ] = true;
			}
		}

		$without_commentary = array();

		foreach ( $stored as $id ) {
			if ( ! isset( $commentary_ids[ $id ] ) ) {
				$without_commentary[] = $id;
			}
		}

		$duplicates = self::duplicates( $winners );

		return array(
			'matched_winners'            => array_keys( $matched_winners ),
			'unknown_winners'            => array_keys( $unknown_winners ),
			'matched_commentary'         => array_keys( $matched_commentary ),
			'unknown_commentary'         => array_keys( $unknown_commentary ),
			'entries_without_commentary' => $without_commentary,
			'duplicates'                 => $duplicates,
			'all_winners_identified'     => array() === $unknown_winners,
			'all_commentary_resolved'    => array() === $without_commentary && array() === $unknown_commentary,
		);
	}

	/**
	 * Whether the report hard-blocks the commit. Pure.
	 *
	 * True on any unknown winner EntryID (a placement that matches no real entry) or any
	 * duplicate (a pool slot claimed twice / an entry placed at two ranks) — these would
	 * corrupt the placement + promotion writes. Missing commentary does NOT hard-block
	 * (it is surfaced for the editor's explicit confirm).
	 *
	 * @param array{unknown_winners:list<string>, duplicates:list<string>} $report The report.
	 * @return bool
	 */
	public static function blocksCommit( array $report ): bool {
		return array() !== ( $report['unknown_winners'] ?? array() )
			|| array() !== ( $report['duplicates'] ?? array() );
	}

	/**
	 * The duplicate descriptors: a pool slot (grade|type|rank) claimed by more than one
	 * entry, or an EntryID placed at more than one rank. Pure.
	 *
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners Parsed winners.
	 * @return list<string> Human-readable duplicate descriptors.
	 */
	private static function duplicates( array $winners ): array {
		$slot_counts  = array();
		$entry_counts = array();

		foreach ( $winners as $winner ) {
			$slot = ( $winner['grade'] ?? '' ) . '|' . ( $winner['type'] ?? '' ) . '|' . ( $winner['rank'] ?? 0 );
			$id   = (string) ( $winner['entry_id'] ?? '' );

			$slot_counts[ $slot ] = ( $slot_counts[ $slot ] ?? 0 ) + 1;
			$entry_counts[ $id ]  = ( $entry_counts[ $id ] ?? 0 ) + 1;
		}

		$duplicates = array();

		foreach ( $slot_counts as $slot => $count ) {
			if ( $count > 1 ) {
				$duplicates[] = $slot;
			}
		}

		foreach ( $entry_counts as $id => $count ) {
			if ( $count > 1 ) {
				$duplicates[] = (string) $id;
			}
		}

		return $duplicates;
	}
}
