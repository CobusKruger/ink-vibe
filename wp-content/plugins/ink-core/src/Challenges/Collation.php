<?php
/**
 * Judge-email collation logic — Story 12A.2 (FR-50-R1, R1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\Scalar;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * The pure collation logic behind the judge-email tool (Story 12A.2, R1).
 *
 * Turns a round's entries into the anonymized judge email: it orders entries by
 * **entry type → Gradering → EntryID**, assigns the per-type EntryID (12A.1) at
 * collation time, strips the writer's identity, and composes the editable preview body.
 * The impure admin shell ({@see CollationPage}) gathers the entry rows and persists +
 * sends; every decision lives here as a pure static so it is unit-testable without WP.
 *
 * Ordering: type order is {@see PostTypes::readableTypes()} (gedig, storie, artikel);
 * Gradering order is {@see Pools::competingTiers()} (brons, silwer, goud). The EntryID
 * is assigned IN that order, so "→ EntryID" is the within-(type, grade) id tiebreak.
 *
 * Meester is an **elevated Goud member** ({@see Tier::competitionTier()}): it forms no
 * pool of its own but is judged WITHIN the Goud pool, so a Meester entry is ordered
 * among the Goud entries rather than dropped. Only an empty/unknown-snapshot entry
 * (no valid {@see Tier}) forms no judge pool and is excluded.
 *
 * Conflation-clean: reads the Gradering snapshot value only (never `ink_writer_tier`,
 * never `Ink\Entitlement`).
 *
 * @package Ink\Core
 */
final class Collation {

	/**
	 * The entry-type assignment order (gedig, storie, artikel). Pure.
	 *
	 * @return list<string>
	 */
	public static function typeOrder(): array {
		return PostTypes::readableTypes();
	}

	/**
	 * The Gradering pool order (brons, silwer, goud) — the competing tiers only. Pure.
	 *
	 * @return list<string>
	 */
	public static function gradingOrder(): array {
		return array_map(
			static fn ( $tier ): string => $tier->value,
			Pools::competingTiers()
		);
	}

	/**
	 * The competition pool a Gradering snapshot is judged in — the entry's own grade,
	 * except Meester which folds into Goud ({@see Tier::competitionTier()}). An
	 * empty/unknown snapshot has no valid {@see Tier} and returns '' (no pool). Pure.
	 *
	 * @param string $gradering The entry-time Gradering snapshot value.
	 * @return string The pool grade's backing value, or '' when there is no valid grade.
	 */
	private static function poolOf( string $gradering ): string {
		$tier = Tier::tryFrom( $gradering );

		return null !== $tier ? $tier->competitionTier()->value : '';
	}

	/**
	 * Order a round's entries for EntryID assignment: type → Gradering → id. Pure.
	 *
	 * Entries with an empty/unknown snapshot (no valid grade), an unknown type, or a
	 * non-positive id are EXCLUDED — they form no judge pool. A Meester entry is NOT
	 * excluded: it is judged in the Goud pool ({@see self::poolOf()}), so it sorts among
	 * the Goud entries. The within-(type, grade) tiebreak is ascending id, so order
	 * never depends on incidental query order and the assigned EntryID is deterministic.
	 *
	 * @param list<array{id:int, type:string, gradering:string}> $entries The round entries.
	 * @return list<array{id:int, type:string, gradering:string}>
	 */
	public static function sortForAssignment( array $entries ): array {
		$type_order  = array_flip( self::typeOrder() );
		$grade_order = array_flip( self::gradingOrder() );

		$kept = array();

		foreach ( $entries as $entry ) {
			$id        = (int) ( $entry['id'] ?? 0 );
			$type      = Scalar::asString( $entry['type'] ?? '' );
			$gradering = Scalar::asString( $entry['gradering'] ?? '' );

			if ( $id <= 0 || ! isset( $type_order[ $type ] ) || ! isset( $grade_order[ self::poolOf( $gradering ) ] ) ) {
				continue;
			}

			$kept[] = array(
				'id'        => $id,
				'type'      => $type,
				'gradering' => $gradering,
			);
		}

		usort(
			$kept,
			static function ( array $a, array $b ) use ( $type_order, $grade_order ): int {
				$by_type = $type_order[ $a['type'] ] <=> $type_order[ $b['type'] ];

				if ( 0 !== $by_type ) {
					return $by_type;
				}

				// Sort by the pool the entry competes in (Meester → Goud), so a Meester
				// entry is ordered among the Goud entries, then id-tiebroken.
				$by_grade = $grade_order[ self::poolOf( $a['gradering'] ) ] <=> $grade_order[ self::poolOf( $b['gradering'] ) ];

				return 0 !== $by_grade ? $by_grade : ( $a['id'] <=> $b['id'] );
			}
		);

		return $kept;
	}

	/**
	 * Compute the per-type EntryID number for each sorted entry. Pure.
	 *
	 * Each type is numbered from 1 in sort order. **Idempotent re-collation:** an entry
	 * that already carries a number (in `$existing`) keeps it; new entries continue the
	 * type's sequence past the highest existing number — so a late entry added before
	 * the deadline gets the next number and no EntryID is ever renumbered or burned.
	 *
	 * @param list<array{id:int, type:string, gradering:string}> $sorted   Sorted entries.
	 * @param array<int, int>                                    $existing id → existing number (0/absent = unassigned).
	 * @return array<int, int> id → assigned EntryID number.
	 */
	public static function computeAssignments( array $sorted, array $existing = array() ): array {
		// The next free number per type = (max existing number of that type) + 1.
		$next = array();

		foreach ( $sorted as $entry ) {
			$type    = $entry['type'];
			$has     = (int) ( $existing[ $entry['id'] ] ?? 0 );
			$current = (int) ( $next[ $type ] ?? 1 );

			if ( $has > 0 && $has >= $current ) {
				$next[ $type ] = $has + 1;
			}
		}

		$assignments = array();

		foreach ( $sorted as $entry ) {
			$id   = $entry['id'];
			$type = $entry['type'];
			$has  = (int) ( $existing[ $id ] ?? 0 );

			if ( $has > 0 ) {
				$assignments[ $id ] = $has;
				continue;
			}

			$number             = (int) ( $next[ $type ] ?? 1 );
			$assignments[ $id ] = $number;
			$next[ $type ]      = $number + 1;
		}

		return $assignments;
	}

	/**
	 * Strip the writer's identity from an entry's text for the anonymized judge email.
	 * Pure.
	 *
	 * Removes (a) every occurrence of the author's display name and (b) any copyright-
	 * notice line (©, "(c)", "copyright", "kopiereg" — case-insensitive), in both the
	 * heading and body positions, then collapses the blank lines the removals leave.
	 * Conservative: it deletes known identity tokens, it never rewrites the work.
	 *
	 * @param string $content     The entry body.
	 * @param string $author_name The author's display name (may be '').
	 * @return string
	 */
	public static function stripIdentity( string $content, string $author_name ): string {
		$name = trim( $author_name );

		if ( '' !== $name ) {
			// Word-boundaried removal (R12A review): a blunt str_ireplace would scrub the
			// name as a SUBSTRING — a short display name ("An", "Roos", "Son") would be
			// excised from inside the work's own words, corrupting the judged text. Match
			// whole-word/phrase occurrences only.
			$replaced = preg_replace( '/\b' . preg_quote( $name, '/' ) . '\b/iu', '', $content );

			if ( null !== $replaced ) {
				$content = $replaced;
			}
		}

		$lines = preg_split( '/\R/', $content );

		if ( false === $lines ) {
			$lines = array( $content );
		}

		$kept = array();

		foreach ( $lines as $line ) {
			if ( 1 === preg_match( '/(?:©|\(c\)|copyright|kopiereg)/i', $line ) ) {
				continue;
			}

			$kept[] = $line;
		}

		// Collapse 3+ consecutive newlines (left by removals) to a single blank line.
		$out = (string) preg_replace( "/\n{3,}/", "\n\n", implode( "\n", $kept ) );

		return trim( $out );
	}

	/**
	 * Compose the editable judge-email preview body. Pure.
	 *
	 * The full challenge body first, then each entry as a heading line "{EntryID}: {title}",
	 * a blank line, then the (already identity-stripped) full text. Entries are separated
	 * by a blank line. The caller passes entries already ordered (type → Gradering →
	 * EntryID) and already stripped.
	 *
	 * @param string                                                  $challenge_body The uitdaging body.
	 * @param list<array{entry_id:string, title:string, text:string}> $entries     Ordered, stripped entries.
	 * @return string
	 */
	public static function buildPreviewBody( string $challenge_body, array $entries ): string {
		$blocks = array();

		$body = trim( $challenge_body );

		if ( '' !== $body ) {
			$blocks[] = $body;
		}

		foreach ( $entries as $entry ) {
			$entry_id = trim( Scalar::asString( $entry['entry_id'] ?? '' ) );
			$title    = trim( Scalar::asString( $entry['title'] ?? '' ) );
			$text     = trim( Scalar::asString( $entry['text'] ?? '' ) );

			$heading = '' !== $entry_id ? $entry_id . ': ' . $title : $title;

			$blocks[] = $heading . "\n\n" . $text;
		}

		return implode( "\n\n", $blocks );
	}

	/**
	 * Parse, validate and dedupe a raw recipient-address string. Pure-ish (WP fns).
	 *
	 * Splits on commas / newlines, sanitises each, keeps only valid addresses
	 * ({@see is_email()}), and dedupes preserving first-seen order.
	 *
	 * @param string $raw The recipient field value.
	 * @return list<string> Valid, unique recipient emails.
	 */
	public static function parseRecipients( string $raw ): array {
		$parts = preg_split( '/[,\r\n]+/', $raw );

		if ( false === $parts ) {
			return array();
		}

		$valid = array();

		foreach ( $parts as $part ) {
			$email = sanitize_email( trim( $part ) );

			if ( '' === $email || false === is_email( $email ) ) {
				continue;
			}

			$valid[ $email ] = true;
		}

		return array_keys( $valid );
	}

	/**
	 * Collate a round's entries into the judge-email preview. Pure aggregator.
	 *
	 * Orders the entries ({@see sortForAssignment}), numbers them per type continuing the
	 * existing sequence ({@see computeAssignments}), composes each entry's canonical
	 * EntryID string ({@see EntryId::format}), strips identity ({@see stripIdentity}) and
	 * builds the editable preview ({@see buildPreviewBody}). No side effects — the impure
	 * shell ({@see CollationPage}) gathers the inputs, then persists the new numbers via
	 * {@see assignRound} and sends.
	 *
	 * @param list<array{id:int, type:string, gradering:string, title:string, content:string, author_name:string}> $entries        Round entries.
	 * @param array<int, int>                                                                                      $existing       id → existing EntryID number.
	 * @param string                                                                                               $challenge_body The uitdaging body.
	 * @return array{empty:bool, preview:string, ordered:list<array{id:int, type:string, number:int, entry_id:string, title:string}>, assignments:array<int,int>}
	 */
	public static function collate( array $entries, array $existing, string $challenge_body ): array {
		$by_id = array();

		foreach ( $entries as $entry ) {
			$by_id[ (int) ( $entry['id'] ?? 0 ) ] = $entry;
		}

		$sorted      = self::sortForAssignment( $entries );
		$assignments = self::computeAssignments( $sorted, $existing );

		$ordered      = array();
		$preview_rows = array();

		foreach ( $sorted as $entry ) {
			$id       = $entry['id'];
			$type     = $entry['type'];
			$number   = (int) ( $assignments[ $id ] ?? 0 );
			$full     = $by_id[ $id ] ?? array();
			$title    = Scalar::asString( $full['title'] ?? '' );
			$content  = Scalar::asString( $full['content'] ?? '' );
			$author   = Scalar::asString( $full['author_name'] ?? '' );
			$entry_id = EntryId::format( Terms::label( $type ), $number );

			$ordered[] = array(
				'id'       => $id,
				'type'     => $type,
				'number'   => $number,
				'entry_id' => $entry_id,
				'title'    => $title,
			);

			$preview_rows[] = array(
				'entry_id' => $entry_id,
				'title'    => $title,
				'text'     => self::stripIdentity( $content, $author ),
			);
		}

		return array(
			'empty'       => array() === $ordered,
			'preview'     => self::buildPreviewBody( $challenge_body, $preview_rows ),
			'ordered'     => $ordered,
			'assignments' => $assignments,
		);
	}

	/**
	 * Persist the computed EntryIDs onto the entries. Impure (the only side effect here).
	 *
	 * Calls {@see EntryId::assign()} per row; since assign is first-wins, re-collation
	 * writes nothing for already-numbered entries. Returns the count NEWLY assigned.
	 *
	 * @param list<array{id:int, type:string, number:int}> $rows The entries + their numbers.
	 * @return int Number of entries newly assigned an EntryID.
	 */
	public static function assignRound( array $rows ): int {
		$assigned = 0;

		foreach ( $rows as $row ) {
			$id     = (int) ( $row['id'] ?? 0 );
			$type   = Scalar::asString( $row['type'] ?? '' );
			$number = (int) ( $row['number'] ?? 0 );

			if ( EntryId::assign( $id, $type, $number ) ) {
				++$assigned;
			}
		}

		return $assigned;
	}
}
