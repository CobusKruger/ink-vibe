<?php
/**
 * Challenge (uitdaging) linking at submission — Story 6.6.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Content\ChallengeRound;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Links a bydrae to the open uitdaging(s) a skrywer ticks at submission (FR-22, UJ-4).
 *
 * A skrywer may tick the currently-open uitdagings; each open tick writes the
 * bydrae's `uitdagingsrondte` term for that round. "Open" is the AD-3 boundary —
 * `now <= end-of-day SAST of the uitdaging's deadline` (inclusive 23:59:59 SAST)
 * via the single {@see Sast} helper, NOT a status flag. A passed-deadline,
 * unpublished, missing-deadline, or non-uitdaging id is fail-safe NOT linkable (a
 * tampered or closed tick is silently ignored; the bydrae still saves).
 *
 * The authoritative entry record (`ink_entries`) and the definitive round model
 * are Epic 12/12A; here {@see resolveRoundTerm()} is a documented seam that
 * get-or-creates a round term keyed to the uitdaging, which Epic 12 may refine.
 * THE conflation rule: no `Ink\Tiers` — competition pools never gate submission.
 *
 * Not `final`: the `publishedChallenges` / `resolveRoundTerm` / `assign` methods
 * are overridable seams (the {@see \Ink\Entitlement\SubmissionGate} precedent) so
 * the link orchestration is unit-testable without the WordPress term API.
 *
 * @package Ink\Core
 */
class ChallengeLinking {

	/**
	 * The Skryf checkbox-array field name for ticked uitdaging ids.
	 */
	public const FIELD = 'ink_submission_uitdagings';

	/**
	 * The maximum entries of a given content type, per author, per uitdaging (FR-48).
	 */
	public const MAX_ENTRIES_PER_TYPE = 3;

	/**
	 * Whether another entry of a type is allowed given the author's existing count
	 * of that type in the round (Story 12.4, FR-48). Pure.
	 *
	 * @param int $existing The author's existing entries of this type in this round.
	 * @return bool
	 */
	public static function withinCap( int $existing ): bool {
		return $existing < self::MAX_ENTRIES_PER_TYPE;
	}

	/**
	 * The open published uitdagings, for the Skryf tick boxes.
	 *
	 * @param \DateTimeInterface|null $now Pinned "now" (tests); defaults to SAST now.
	 * @return list<array{id:int, title:string}> Open uitdagings (id + title).
	 */
	public function openChallenges( ?\DateTimeInterface $now = null ): array {
		$open = array();

		foreach ( $this->publishedChallenges() as $post ) {
			$id = isset( $post->ID ) ? (int) $post->ID : 0;

			if ( $id > 0 && $this->isOpen( $id, $now ) ) {
				$open[] = array(
					'id'    => $id,
					'title' => (string) get_the_title( $id ),
				);
			}
		}

		return $open;
	}

	/**
	 * Whether a uitdaging is still open for entry (AD-3, inclusive 23:59:59 SAST).
	 *
	 * @param int                     $uitdaging_id The uitdaging post id.
	 * @param \DateTimeInterface|null $now          Pinned "now" (tests).
	 * @return bool True only for a published uitdaging whose SAST deadline has not passed.
	 */
	public function isOpen( int $uitdaging_id, ?\DateTimeInterface $now = null ): bool {
		if ( PostTypes::UITDAGING !== get_post_type( $uitdaging_id ) ) {
			return false;
		}

		if ( 'publish' !== get_post_status( $uitdaging_id ) ) {
			return false;
		}

		$deadline = $this->parseDeadline( $this->deadlineRaw( $uitdaging_id ) );

		if ( null === $deadline ) {
			return false; // No / unparseable deadline → fail-safe closed.
		}

		return Sast::isThroughEndOfDay( $deadline, $now );
	}

	/**
	 * Link a bydrae to each OPEN ticked uitdaging by writing the round term.
	 *
	 * @param int                     $post_id       The bydrae id.
	 * @param list<int>               $uitdaging_ids The ticked uitdaging ids.
	 * @param \DateTimeInterface|null $now           Pinned "now" (tests).
	 * @return list<int> The uitdaging ids actually linked.
	 */
	public function link( int $post_id, array $uitdaging_ids, ?\DateTimeInterface $now = null ): array {
		$linked = array();

		foreach ( $uitdaging_ids as $raw_id ) {
			$uid = (int) $raw_id;

			if ( $uid <= 0 || in_array( $uid, $linked, true ) ) {
				continue;
			}

			if ( ! $this->isOpen( $uid, $now ) ) {
				continue;
			}

			// Per-type cap (Story 12.4, FR-48): at most 3 entries of this content type,
			// per author, per uitdaging. A 4th of the type is not linked (the bydrae
			// still saves; only the round link is refused).
			if ( ! self::withinCap( $this->entryCountFor( $post_id, $uid ) ) ) {
				continue;
			}

			$term_id = $this->resolveRoundTerm( $uid );

			if ( $term_id > 0 ) {
				$this->assign( $post_id, $term_id );
				$linked[] = $uid;
			}
		}

		// Entry-time Gradering snapshot seam (Story 12.4): a runtime hook, so the
		// snapshot (Ink\Challenges\Entry) can read the writer's Gradering for the
		// judging pool WITHOUT Submission ever referencing Ink\Tiers (THE conflation
		// rule). The firer uses the literal; the listener owns the HOOK constant
		// (mirrors Tiers\Api → Tiers\PromotionEmails::HOOK).
		if ( array() !== $linked ) {
			do_action( 'ink/uitdaging_entry_linked', $post_id, $linked ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD).
		}

		return $linked;
	}

	/**
	 * The author's EXISTING entries of the same content type already linked to this
	 * round (excludes the post being linked now). Overridable seam for tests.
	 *
	 * @param int $post_id      The bydrae being linked.
	 * @param int $uitdaging_id The round's uitdaging id.
	 * @return int
	 */
	protected function entryCountFor( int $post_id, int $uitdaging_id ): int {
		$type   = (string) get_post_type( $post_id );
		$author = (int) get_post_field( 'post_author', $post_id );

		if ( '' === $type || $author <= 0 ) {
			return 0;
		}

		$existing = get_posts(
			array(
				'post_type'        => $type,
				'author'           => $author,
				'post_status'      => array( 'publish', 'pending', 'draft', 'future' ),
				'fields'           => 'ids',
				'numberposts'      => self::MAX_ENTRIES_PER_TYPE + 1,
				'post__not_in'     => array( $post_id ),
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded round-slug facet to enforce the per-type entry cap; not a listing query.
				'tax_query'        => array(
					array(
						'taxonomy' => Taxonomies::UITDAGINGSRONDTE,
						'field'    => 'slug',
						'terms'    => ChallengeRound::slugFor( $uitdaging_id ),
					),
				),
			)
		);

		return is_array( $existing ) ? count( $existing ) : 0;
	}

	/**
	 * The raw stored deadline string for a uitdaging.
	 *
	 * @param int $uitdaging_id The uitdaging id.
	 * @return string The stored deadline, or ''.
	 */
	protected function deadlineRaw( int $uitdaging_id ): string {
		return Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_DEADLINE, true ) );
	}

	/**
	 * Parse a stored deadline string into a SAST instant.
	 *
	 * @param string $raw The `Y-m-d[ T]H:i(:s)` deadline string.
	 * @return \DateTimeImmutable|null The parsed instant, or null when empty/invalid.
	 */
	protected function parseDeadline( string $raw ): ?\DateTimeImmutable {
		if ( '' === $raw ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( $raw, new \DateTimeZone( Sast::TIMEZONE ) );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * The published uitdaging posts. Overridable seam for tests.
	 *
	 * @return array<int, object> The uitdaging posts.
	 */
	protected function publishedChallenges(): array {
		return get_posts(
			array(
				'post_type'   => PostTypes::UITDAGING,
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
	}

	/**
	 * Get-or-create the `uitdagingsrondte` round term for a uitdaging.
	 *
	 * A documented seam: the round term is keyed to the uitdaging by a stable slug.
	 * Epic 12/12A owns the authoritative round model and may refine this mapping.
	 *
	 * @param int $uitdaging_id The uitdaging id.
	 * @return int The round term id, or 0 on failure.
	 */
	protected function resolveRoundTerm( int $uitdaging_id ): int {
		$slug     = ChallengeRound::slugFor( $uitdaging_id );
		$existing = get_term_by( 'slug', $slug, Taxonomies::UITDAGINGSRONDTE );

		if ( $existing instanceof \WP_Term ) {
			return (int) $existing->term_id;
		}

		$created = wp_insert_term(
			(string) get_the_title( $uitdaging_id ),
			Taxonomies::UITDAGINGSRONDTE,
			array( 'slug' => $slug )
		);

		if ( is_wp_error( $created ) || ! isset( $created['term_id'] ) ) {
			return 0;
		}

		return (int) $created['term_id'];
	}

	/**
	 * Assign a round term to the bydrae (append — never clobbers existing terms).
	 *
	 * @param int $post_id The bydrae id.
	 * @param int $term_id The round term id.
	 */
	protected function assign( int $post_id, int $term_id ): void {
		wp_set_object_terms( $post_id, array( $term_id ), Taxonomies::UITDAGINGSRONDTE, true );
	}
}
