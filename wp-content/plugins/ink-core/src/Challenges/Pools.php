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
	 * Bucket entries into competition pools by their entry-time gradering. Pure.
	 *
	 * Entries whose snapshot is empty, junk, or a non-competing grade (Meester) are
	 * excluded — they do not enter a monthly pool. Pool insertion order follows
	 * {@see self::competingTiers()} (Brons, Silwer, Goud).
	 *
	 * @param list<array{id:int, gradering:string}> $entries The round entries.
	 * @return array<string, list<int>> Pool grade value → entry ids (pools with entries only).
	 */
	public static function group( array $entries ): array {
		$pools = array();

		foreach ( self::competingTiers() as $tier ) {
			$bucket = array();

			foreach ( $entries as $entry ) {
				$gradering = Scalar::asString( $entry['gradering'] ?? '' );
				$id        = (int) ( $entry['id'] ?? 0 );

				if ( $id > 0 && $gradering === $tier->value ) {
					$bucket[] = $id;
				}
			}

			if ( array() !== $bucket ) {
				$pools[ $tier->value ] = $bucket;
			}
		}

		return $pools;
	}

	/**
	 * The per-pool entry-id map for a round. Thin impure shell over {@see self::group()}.
	 *
	 * Reads the round's published entries (the same set the single page lists) and each
	 * entry's entry-time Gradering snapshot, then groups.
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
			);
		}

		return self::group( $entries );
	}
}
