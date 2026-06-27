<?php
/**
 * Entry-time Gradering snapshot — Story 12.4 (FR-48, UJ-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * Snapshots the writer's entry-time Gradering onto a challenge entry.
 *
 * When {@see \Ink\Submission\ChallengeLinking} links a bydrae to an open round it
 * fires `ink/uitdaging_entry_linked`; this listener records the entry author's
 * CURRENT Gradering ({@see TiersApi::forUser()}) onto the entry as
 * {@see self::GRADERING_META_KEY}. That snapshot is the **pool that governs judging**
 * (Brons-vs-Brons, Silwer-vs-Silwer, Goud-vs-Goud — Story 12.5), captured at entry
 * time so a later promotion can't retroactively move an entry between pools. The entry
 * record is authoritative (AD-5).
 *
 * Listening here (not in Submission) keeps THE conflation rule intact: Submission
 * never references `Ink\Tiers`; Gradering never gates submission — it is only read,
 * after the fact, to record the judging pool. The snapshot is idempotent (first link
 * wins) so multi-round / re-saved entries keep their original entry-time pool.
 *
 * Conflation-clean: reads only `Ink\Tiers` (the Gradering Api facade, the module
 * charter's documented dependency) + WP core — zero `Ink\Entitlement`.
 *
 * Not `final`: {@see self::gradingValueFor()} is an overridable seam (the
 * {@see \Ink\Submission\ChallengeLinking} / {@see \Ink\Entitlement\SubmissionGate}
 * precedent) so the snapshot logic is unit-testable without the Tiers Api.
 *
 * @package Ink\Core
 */
class Entry {

	/**
	 * The runtime hook the snapshot subscribes (fired by Submission\ChallengeLinking).
	 *
	 * @var string
	 */
	public const HOOK = 'ink/uitdaging_entry_linked';

	/**
	 * The entry meta key holding the entry-time Gradering pool (a {@see \Ink\Kernel\Tier}
	 * backing value: brons/silwer/goud/meester).
	 *
	 * @var string
	 */
	public const GRADERING_META_KEY = 'ink_entry_gradering';

	/**
	 * Subscribe the snapshot to the entry-linked hook on registration.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'onEntryLinked' ), 10, 2 );
	}

	/**
	 * Record the entry-time Gradering pool on the entry (idempotent — first link wins).
	 *
	 * @param int       $post_id       The entry (bydrae) id.
	 * @param list<int> $uitdaging_ids The rounds it was just linked to (unused — the
	 *                                 pool is the writer's tier, the same for every
	 *                                 round in one submission; present for the hook
	 *                                 contract + future per-round needs).
	 */
	public function onEntryLinked( int $post_id, array $uitdaging_ids = array() ): void {
		unset( $uitdaging_ids );

		if ( $post_id <= 0 ) {
			return;
		}

		// Idempotent: keep the original entry-time pool if already snapshotted.
		if ( '' !== (string) get_post_meta( $post_id, self::GRADERING_META_KEY, true ) ) {
			return;
		}

		$author = (int) get_post_field( 'post_author', $post_id );

		if ( $author <= 0 ) {
			return;
		}

		update_post_meta( $post_id, self::GRADERING_META_KEY, $this->gradingValueFor( $author ) );
	}

	/**
	 * The writer's current Gradering backing value. Overridable seam for tests.
	 *
	 * @param int $user_id The entry author.
	 * @return string A {@see \Ink\Kernel\Tier} backing value.
	 */
	protected function gradingValueFor( int $user_id ): string {
		return TiersApi::forUser( $user_id )->value;
	}
}
