<?php
/**
 * Per-type EntryID data model — Story 12A.1 (FR-50-R1, R1 linchpin).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\I18n\Terms;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The per-type EntryID assigned to a challenge entry at collation (Story 12A.1, R1).
 *
 * The judge email (12A.2) numbers each round's entries **per type** (Gedigte, Stories,
 * Artikels — each from 1) and the judges paste their results back **by EntryID**; this
 * helper is the data model that makes those numbers persist on the authoritative entry
 * and recompose into the canonical "{type} {number}" string both sides reference.
 *
 * Storage (AD-5): the authoritative entry record is the **bydrae post + per-entry
 * meta** — there is no separate `ink_entries` custom table. The per-type number lives in
 * {@see self::NUMBER_META_KEY} and a type snapshot in {@see self::TYPE_META_KEY},
 * mirroring exactly the {@see Entry::GRADERING_META_KEY} (12.4) and
 * {@see Placements::PLACEMENT_META_KEY} (12.6) meta-on-post pattern (internal meta, no
 * `register_post_meta` — not REST-exposed, not user-editable).
 *
 * Assignment is **at collation time, not entry time** (the epics.md 12A.1 AC), and
 * {@see self::assign()} is **idempotent / first-assignment-wins**: an already-numbered
 * entry is never renumbered and its number is never burned. That puts the 12A.2
 * "re-collation must not renumber" invariant in the write where it is unit-testable
 * (R12's idempotency-in-the-write lesson); the round-wide sort + sequence-per-type is
 * 12A.2's job, layered over this single-entry primitive.
 *
 * The EntryID **string** is "{type label} {number}" (e.g. "Gedig 1"): the type label is
 * the existing {@see Terms} singular noun (the CPT slug doubles as the Terms key), so no
 * new contested copy is introduced. 12A.3's parser matches these back.
 *
 * Conflation-clean: the number hangs off the entry post — zero `Ink\Tiers` /
 * `Ink\Entitlement`. Static helper (no hooks), so the bootstrap is untouched — the
 * {@see Placements} house style.
 *
 * @package Ink\Core
 */
final class EntryId {

	/**
	 * The entry meta key holding the entry's type snapshot (a bydrae CPT slug:
	 * gedig/storie/artikel). Absent until collation assigns it.
	 *
	 * @var string
	 */
	public const TYPE_META_KEY = 'ink_entry_type';

	/**
	 * The entry meta key holding the per-type EntryID number (≥ 1; absent/0 = unassigned).
	 *
	 * @var string
	 */
	public const NUMBER_META_KEY = 'ink_entry_number';

	/**
	 * Compose the canonical EntryID string: "{type label} {number}". Pure.
	 *
	 * Returns '' for a non-positive number or an empty label, so an unassigned entry
	 * never renders a malformed "  0" token.
	 *
	 * @param string $type_label The display type label (e.g. "Gedig").
	 * @param int    $number     The per-type EntryID number.
	 * @return string
	 */
	public static function format( string $type_label, int $number ): string {
		if ( $number <= 0 || '' === trim( $type_label ) ) {
			return '';
		}

		return $type_label . ' ' . $number;
	}

	/**
	 * The stored per-type EntryID number for an entry (0 = unassigned).
	 *
	 * @param int $entry_id The entry (bydrae) id.
	 * @return int
	 */
	public static function numberFor( int $entry_id ): int {
		if ( $entry_id <= 0 ) {
			return 0;
		}

		return Scalar::asNonNegativeInt( get_post_meta( $entry_id, self::NUMBER_META_KEY, true ) );
	}

	/**
	 * The stored type snapshot for an entry (a bydrae CPT slug; '' = unassigned).
	 *
	 * @param int $entry_id The entry id.
	 * @return string
	 */
	public static function typeFor( int $entry_id ): string {
		if ( $entry_id <= 0 ) {
			return '';
		}

		return Scalar::asString( get_post_meta( $entry_id, self::TYPE_META_KEY, true ) );
	}

	/**
	 * Whether an entry already carries a per-type EntryID (number ≥ 1).
	 *
	 * @param int $entry_id The entry id.
	 * @return bool
	 */
	public static function isAssigned( int $entry_id ): bool {
		return self::numberFor( $entry_id ) > 0;
	}

	/**
	 * Assign a per-type EntryID to an entry. The write primitive 12A.2 collation calls.
	 *
	 * Idempotent / first-assignment-wins: a non-positive id, empty type, or non-positive
	 * number is rejected without writing; an entry that already has a number is left
	 * untouched (never renumbered, never burned — the 12A.2 AC-6 invariant). The caller
	 * (12A.2) owns the round-wide sort + the per-type sequence; this writes one entry.
	 *
	 * @param int    $entry_id The entry id.
	 * @param string $type     The entry's type snapshot (a bydrae CPT slug).
	 * @param int    $number   The per-type EntryID number (≥ 1).
	 * @return bool True when a new EntryID was written; false when rejected or already assigned.
	 */
	public static function assign( int $entry_id, string $type, int $number ): bool {
		if ( $entry_id <= 0 || '' === trim( $type ) || $number <= 0 ) {
			return false;
		}

		// First-assignment-wins: never renumber / burn an already-assigned EntryID.
		if ( self::isAssigned( $entry_id ) ) {
			return false;
		}

		update_post_meta( $entry_id, self::TYPE_META_KEY, $type );
		update_post_meta( $entry_id, self::NUMBER_META_KEY, $number );

		return true;
	}

	/**
	 * The canonical EntryID string for a stored entry (e.g. "Gedig 1"); '' if unassigned.
	 *
	 * Thin impure shell: resolves the type label from {@see Terms} (the CPT slug is the
	 * Terms key) and composes via {@see self::format()}.
	 *
	 * @param int $entry_id The entry id.
	 * @return string
	 */
	public static function entryIdFor( int $entry_id ): string {
		$number = self::numberFor( $entry_id );
		$type   = self::typeFor( $entry_id );

		if ( $number <= 0 || '' === $type ) {
			return '';
		}

		return self::format( Terms::label( $type ), $number );
	}
}
