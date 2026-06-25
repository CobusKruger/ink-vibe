<?php
/**
 * A single graderingsgeskiedenis (tier-history) audit record.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * One immutable row of the append-only Gradering audit log (Story 5.3, FR-12).
 *
 * A typed view over a `{$wpdb->prefix}ink_tier_history` row: who changed,
 * from→to grade, the actor (0 = the automatic engine), the reason, an optional
 * linked challenge, and the GMT timestamp. Stored grade strings are coerced
 * through the Kernel {@see Tier} enum so a stale/garbage value can never throw.
 *
 * THE conflation rule (AD-1): this is competition-history; it references only
 * the Kernel `Tier`, never `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class PromotionLogEntry {

	/**
	 * Build an immutable audit record.
	 *
	 * @param int    $id          The audit-row id.
	 * @param int    $userId      The writer whose grade changed.
	 * @param Tier   $from        The grade before the change.
	 * @param Tier   $to          The grade after the change.
	 * @param int    $actorId     The staff user id, or 0 for the automatic engine.
	 * @param string $reason      The recorded reason.
	 * @param int    $challengeId The optional linked challenge id (0 = none).
	 * @param string $createdAt   The GMT datetime the change was recorded.
	 */
	public function __construct(
		public readonly int $id,
		public readonly int $userId,
		public readonly Tier $from,
		public readonly Tier $to,
		public readonly int $actorId,
		public readonly string $reason,
		public readonly int $challengeId,
		public readonly string $createdAt,
	) {}

	/**
	 * Whether this change was made by the automatic promotion engine (Story 5.8)
	 * rather than a staff member — recorded as `actor_id = 0`.
	 */
	public function isSystem(): bool {
		return 0 === $this->actorId;
	}

	/**
	 * Whether this change is linked to a challenge result.
	 */
	public function isChallengeLinked(): bool {
		return $this->challengeId > 0;
	}

	/**
	 * Map a raw DB row to a typed entry, coercing the stored grade strings
	 * through the Kernel {@see Tier} enum (a stale/garbage grade falls back to
	 * {@see Tier::default()} rather than throwing).
	 *
	 * @param object $row A `$wpdb->get_results()` row object.
	 */
	public static function fromRow( object $row ): self {
		return new self(
			(int) ( $row->id ?? 0 ),
			(int) ( $row->user_id ?? 0 ),
			Tier::tryFrom( (string) ( $row->from_tier ?? '' ) ) ?? Tier::default(),
			Tier::tryFrom( (string) ( $row->to_tier ?? '' ) ) ?? Tier::default(),
			(int) ( $row->actor_id ?? 0 ),
			(string) ( $row->reason ?? '' ),
			(int) ( $row->challenge_id ?? 0 ),
			(string) ( $row->created_at ?? '' ),
		);
	}
}
