<?php
/**
 * Gradering-based competition pools — Story 12.5 (FR-49, UJ-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\I18n\Terms;
use Ink\Kernel\Scalar;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * Groups a round's entries into per-Gradering competition pools.
 *
 * Judging is fair because writers compete only within their own Gradering: Brons vs
 * Brons, Silwer vs Silwer, Goud vs Goud (FR-49). The pool is taken from the
 * **entry-time** Gradering snapshot ({@see Entry::GRADERING_META_KEY}, Story 12.4),
 * NOT the writer's live grade — so a promotion after entry never moves an entry
 * between pools. Meester (manual-only, terminal) forms no monthly competition pool.
 *
 * THE conflation rule: pools are governed by Gradering ({@see Tier}) ONLY. There is
 * zero `Ink\Entitlement` here — a paid/free lidmaatskap never affects the pool. The
 * read helper feeds the placement records (Story 12.6) and the R2 ingestion (12A).
 *
 * Within a Gradering the competition is run PER CATEGORY (gedig/storie/artikel — the
 * entry's post type, the same dimension as the judges' EntryID type word and the
 * {@see Coverage} `grade|type|rank` slot). The read pools are therefore keyed on
 * (Gradering × category) via {@see self::poolKey()} so the read-side one-per-rank guard
 * scopes within a category — two algehele wenners in different categories of one
 * Gradering both survive (the D1 read-collapse fix). Category is a read-side grouping
 * key only; it is not an `Ink\Entitlement` dimension, so the conflation rule holds.
 *
 * @package Ink\Core
 */
final class Pools {

	/**
	 * The competition pools, in grade order: Brons, Silwer, Goud. Pure.
	 *
	 * Derived from {@see Tier} — every grade EXCEPT the manual-only/terminal Meester,
	 * which does not form a monthly competition pool.
	 *
	 * @return list<Tier>
	 */
	public static function competingTiers(): array {
		return array_values(
			array_filter(
				Tier::cases(),
				static fn ( Tier $tier ): bool => ! $tier->isManualOnly()
			)
		);
	}

	/**
	 * The Gradering label for a pool (e.g. Goud → "Goud"). Pure.
	 *
	 * @param Tier $tier The pool's grade.
	 * @return string
	 */
	public static function poolLabel( Tier $tier ): string {
		return Terms::label( $tier->value );
	}

	/**
	 * Compose the pool key for a (Gradering × category) bucket. Pure.
	 *
	 * The judging pool is the entry's entry-time Gradering (Brons/Silwer/Goud), but the
	 * competition is run PER CATEGORY within each Gradering (gedig vs gedig, storie vs
	 * storie — the EntryID type word the judges' document carries, {@see Coverage}'s
	 * `grade|type|rank` slot). Keying the read pools on the composite keeps the two
	 * dimensions separate so the per-rank read-side guard ({@see Placements::arrange()})
	 * scopes WITHIN a category — a Goud-Gedig rank-1 and a Goud-Storie rank-1 are two
	 * legitimate algehele wenners, not a collision (the D1 read-collapse fix). A
	 * category-less call ('' category) degrades to a Gradering-only key (the prior 12.5
	 * contract), so callers that do not yet thread category keep their behaviour.
	 *
	 * @param string $gradering The pool's Gradering backing value.
	 * @param string $category  The entry category (e.g. gedig/storie/artikel); '' to omit.
	 * @return string
	 */
	public static function poolKey( string $gradering, string $category = '' ): string {
		return '' === $category ? $gradering : $gradering . '|' . $category;
	}

	/**
	 * Split a composite pool key back into its [gradering, category] parts. Pure.
	 *
	 * The inverse of {@see self::poolKey()}: "goud|gedig" → ['goud', 'gedig']; a
	 * category-less key "goud" → ['goud', '']. Only the FIRST separator splits, so a
	 * category value is taken whole.
	 *
	 * @param string $pool_key The composite (or Gradering-only) pool key.
	 * @return array{0:string, 1:string} [gradering, category].
	 */
	public static function splitPoolKey( string $pool_key ): array {
		$parts = explode( '|', $pool_key, 2 );

		return array( $parts[0], $parts[1] ?? '' );
	}

	/**
	 * Bucket entries into competition pools by entry-time gradering × category. Pure.
	 *
	 * Entries whose snapshot is empty, junk, or a non-competing grade (Meester) are
	 * excluded — they do not enter a competition pool. Pool insertion order follows
	 * {@see self::competingTiers()} (Brons, Silwer, Goud); within a grade the categories
	 * appear in first-seen order. When an entry carries a non-empty `category` the pool
	 * is keyed on {@see self::poolKey()} (Gradering × category); a category-less entry
	 * keys on Gradering alone (the prior contract).
	 *
	 * @param list<array{id:int, gradering:string, category?:string}> $entries The round entries.
	 * @return array<string, list<int>> Pool key → entry ids (pools with entries only).
	 */
	public static function group( array $entries ): array {
		$pools = array();

		foreach ( self::competingTiers() as $tier ) {
			// First-seen category order within this grade (deterministic, query-order driven
			// only across categories — the within-category rank order is settled downstream).
			foreach ( $entries as $entry ) {
				$gradering = Scalar::asString( $entry['gradering'] ?? '' );
				$category  = Scalar::asString( $entry['category'] ?? '' );
				$id        = (int) ( $entry['id'] ?? 0 );

				if ( $id <= 0 || $gradering !== $tier->value ) {
					continue;
				}

				$pools[ self::poolKey( $gradering, $category ) ][] = $id;
			}
		}

		return $pools;
	}

	/**
	 * The per-pool entry-id map for a round. Thin impure shell over {@see self::group()}.
	 *
	 * Reads the round's published entries (the same set the single page lists), each
	 * entry's entry-time Gradering snapshot AND its category (the entry's post type — the
	 * same mechanism the collation EntryID + {@see Coverage} `grade|type|rank` slot use),
	 * then groups per (Gradering × category).
	 *
	 * @param int $uitdaging_id The producing uitdaging id.
	 * @return array<string, list<int>>
	 */
	public static function forRound( int $uitdaging_id ): array {
		if ( $uitdaging_id <= 0 ) {
			return array();
		}

		$query   = new \WP_Query( SinglePage::entriesQueryArgs( $uitdaging_id ) );
		$entries = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$entries[] = array(
				'id'        => (int) $post->ID,
				'gradering' => Scalar::asString( get_post_meta( (int) $post->ID, Entry::GRADERING_META_KEY, true ) ),
				'category'  => (string) $post->post_type,
			);
		}

		return self::group( $entries );
	}
}
