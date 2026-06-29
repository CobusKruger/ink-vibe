<?php
/**
 * Results commit pipeline — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * The irreversible, **idempotent** results-commit pipeline (Story 12A.3, R2, AC-5/AC-6).
 *
 * On the editor's confirm, this runs the commit steps in the AC-5 order: (1) generate
 * the wenneraankondiging post (12A.4 — reserved seam), (2) write the Terugvoer van die
 * moderator comments (12A.5 — reserved seam), (3) fire the Biblioteek auto-update hook
 * (R4/10.6), (4) record the placements (12.6 {@see Placements}), (5) the featured feed
 * follows from the winners post (12A.7), (6) trigger the Gradering auto-promotion engine
 * ({@see TiersApi::awardWins}, 5.8 — a win = any top-3 placement).
 *
 * Idempotency-in-the-write (R12 lesson): a per-round commit-done marker guards the WHOLE
 * pipeline, so a re-run after a successful commit double-posts nothing, double-comments
 * nothing, and double-promotes nothing (AC-6).
 *
 * The side-effecting steps are protected overridable seams (the Brain-Monkey isolation
 * rule) so the pipeline ORDER + idempotency are unit-testable without WP. The winners-post
 * (12A.4) and moderator-feedback (12A.5) seams are documented reserved no-ops here (the
 * 10.6 reserved-stub precedent) — their stories fill the bodies; the commit order is
 * established now.
 *
 * Conflation-clean: records Gradering placements + awards Gradering wins; carries zero
 * `Ink\Entitlement`. The biblioteek hook is fired by literal name (firer-uses-literal,
 * mirroring `ink/uitdaging_entry_linked`) so Challenges keeps no `Ink\Library` edge.
 *
 * @package Ink\Core
 */
class Ingestion {

	/**
	 * The per-round commit-done marker (post meta on the uitdaging). Its presence makes
	 * the whole commit a no-op (idempotent re-run guard).
	 *
	 * @var string
	 */
	public const COMMIT_DONE_META = 'ink_uitdaging_commit_done';

	/**
	 * Commit confirmed results for a round. Idempotent.
	 *
	 * @param int                                                 $uitdaging_id The round.
	 * @param list<array{post_id:int, rank:int, author_id:int}>   $winners      Resolved, rank-unique winners.
	 * @param list<array{post_id:int, title:string, text:string}> $commentary   Resolved per-entry commentary.
	 * @return array{committed:bool, reason:string, post_id:int, feedback:int, placed:int, promoted:int}
	 */
	public function commit( int $uitdaging_id, array $winners, array $commentary ): array {
		$result = array(
			'committed' => false,
			'reason'    => '',
			'post_id'   => 0,
			'feedback'  => 0,
			'placed'    => 0,
			'promoted'  => 0,
		);

		if ( $uitdaging_id <= 0 ) {
			$result['reason'] = 'ongeldige_uitdaging';
			return $result;
		}

		// Idempotent: a committed round writes nothing on re-run.
		if ( $this->isCommitted( $uitdaging_id ) ) {
			$result['reason'] = 'reeds_gepleeg';
			return $result;
		}

		// AC-5 order. (1) winners post, (2) moderator feedback (reserved seams → 0).
		$result['post_id']  = $this->commitWinnersPost( $uitdaging_id, $winners );
		$result['feedback'] = $this->commitModeratorFeedback( $uitdaging_id, $commentary );

		// (3) Biblioteek auto-update stub (R4/10.6).
		$this->fireBiblioteek( $uitdaging_id, $this->winnerPostIds( $winners ) );

		// (4) Record placements (algehele wenner / wenner).
		foreach ( $winners as $winner ) {
			if ( $this->recordPlacement( (int) ( $winner['post_id'] ?? 0 ), (int) ( $winner['rank'] ?? 0 ) ) ) {
				++$result['placed'];
			}
		}

		// (6) Trigger auto-promotion (a win = any top-3 placement at current grade).
		$result['promoted'] = $this->awardPromotions( $uitdaging_id, $winners );

		$this->markCommitted( $uitdaging_id );

		$result['committed'] = true;

		return $result;
	}

	/**
	 * Whether the round's results have already been committed.
	 *
	 * @param int $uitdaging_id The round.
	 * @return bool
	 */
	public function isCommitted( int $uitdaging_id ): bool {
		return '' !== (string) get_post_meta( $uitdaging_id, self::COMMIT_DONE_META, true );
	}

	/**
	 * The winner post ids carried to the biblioteek hook. Pure.
	 *
	 * @param list<array{post_id:int, rank:int, author_id:int}> $winners The winners.
	 * @return list<int>
	 */
	protected function winnerPostIds( array $winners ): array {
		$ids = array();

		foreach ( $winners as $winner ) {
			$id = (int) ( $winner['post_id'] ?? 0 );

			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return $ids;
	}

	/**
	 * Award auto-promotion wins: one win per top-3 placement, summed per author. A writer
	 * with two top-3 placements this round earns two wins (the engine accumulates). Pure
	 * grouping; the write is the {@see awardWins()} seam.
	 *
	 * @param int                                               $uitdaging_id The round (recorded as the win's challenge id).
	 * @param list<array{post_id:int, rank:int, author_id:int}> $winners      The winners.
	 * @return int The number of authors who were promoted by this commit.
	 */
	protected function awardPromotions( int $uitdaging_id, array $winners ): int {
		$wins_by_author = array();

		foreach ( $winners as $winner ) {
			$author = (int) ( $winner['author_id'] ?? 0 );

			if ( $author > 0 ) {
				$wins_by_author[ $author ] = ( $wins_by_author[ $author ] ?? 0 ) + 1;
			}
		}

		$promoted = 0;

		foreach ( $wins_by_author as $author => $count ) {
			if ( null !== $this->awardWins( (int) $author, (int) $count, $uitdaging_id ) ) {
				++$promoted;
			}
		}

		return $promoted;
	}

	// --- Overridable side-effect seams (the Brain-Monkey isolation rule) ---------------

	/**
	 * Generate the wenneraankondiging post (Story 12A.4). Overridable seam.
	 *
	 * Filled by Story 12A.4: delegates to {@see WinnersPost::generate()} (idempotent —
	 * returns the existing announcement for a round rather than double-posting).
	 *
	 * @param int                                               $uitdaging_id The round.
	 * @param list<array{post_id:int, rank:int, author_id:int}> $winners      The winners.
	 * @return int The created (or existing) announcement post id.
	 */
	protected function commitWinnersPost( int $uitdaging_id, array $winners ): int {
		return ( new WinnersPost() )->generate( $uitdaging_id, $winners );
	}

	/**
	 * Write the Terugvoer van die moderator comments (Story 12A.5). Overridable seam.
	 *
	 * Filled by Story 12A.5: delegates to {@see ModeratorFeedback::recordForRound()}
	 * (one `ink_moderator_terugvoer` comment per entry; idempotent).
	 *
	 * @param int                                                 $uitdaging_id The round.
	 * @param list<array{post_id:int, title:string, text:string}> $commentary   The commentary.
	 * @return int The number of feedback comments written.
	 */
	protected function commitModeratorFeedback( int $uitdaging_id, array $commentary ): int {
		return ( new ModeratorFeedback() )->recordForRound( $uitdaging_id, $commentary );
	}

	/**
	 * Fire the Biblioteek auto-update hook (R4/10.6). Firer-uses-LITERAL so Challenges
	 * keeps no `Ink\Library` edge; `Ink\Library\AutoUpdate` (10.6) owns the `HOOK`
	 * constant and listens — exactly the `ink/uitdaging_entry_linked` convention where
	 * Submission fires the literal and `Challenges\Entry` listens.
	 *
	 * @param int       $uitdaging_id    The round.
	 * @param list<int> $winner_post_ids The winning post ids.
	 */
	protected function fireBiblioteek( int $uitdaging_id, array $winner_post_ids ): void {
		if ( $uitdaging_id <= 0 ) {
			return;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention; the Library\AutoUpdate (10.6) listener owns the HOOK constant. Literal (not the const) keeps Challenges free of a Library edge.
		do_action( 'ink/biblioteek_wen_bywerking', $uitdaging_id, $winner_post_ids );
	}

	/**
	 * Record one placement on an entry (12.6 {@see Placements}). Overridable seam.
	 *
	 * @param int $post_id The placed entry post id.
	 * @param int $rank    The placement rank (1/2/3).
	 * @return bool Whether a valid placement was written.
	 */
	protected function recordPlacement( int $post_id, int $rank ): bool {
		return Placements::record( $post_id, $rank );
	}

	/**
	 * Award a writer's wins to the auto-promotion engine (5.8). Overridable seam.
	 *
	 * @param int $author_id    The writer.
	 * @param int $count        The number of top-3 wins.
	 * @param int $uitdaging_id The linked challenge id.
	 * @return \Ink\Kernel\Tier|null The new grade if promoted; null otherwise.
	 */
	protected function awardWins( int $author_id, int $count, int $uitdaging_id ) {
		return TiersApi::awardWins( $author_id, $count, $uitdaging_id );
	}

	/**
	 * Mark the round committed (the idempotency guard). Overridable seam.
	 *
	 * @param int $uitdaging_id The round.
	 */
	protected function markCommitted( int $uitdaging_id ): void {
		update_post_meta( $uitdaging_id, self::COMMIT_DONE_META, '1' );
	}
}
