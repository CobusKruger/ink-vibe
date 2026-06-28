<?php
/**
 * Sponsor campaign scheduling + rotation — Story 14.2 (FR-58).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Sponsors;

use Ink\Content\PostTypes;
use Ink\Kernel\Sast;
use WP_Post;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * The sponsor (borg) campaign-window + rotation logic — Story 14.2 (FR-58).
 *
 * A sponsor shows only within its campaign window: inclusive of the start and end
 * dates, evaluated as SAST calendar days via the project's single timezone authority
 * {@see Sast} (no hand-rolled date maths). When several sponsors are active at once
 * the featured one rotates day by day so each gets exposure without per-page churn.
 *
 * The class is split the house-style way: pure layers ({@see isActive()},
 * {@see activeFrom()}, {@see rotate()}, {@see dayIndex()}, {@see parseDate()},
 * {@see queryArgs()}) carry the assertions; the thin WP-touching wrappers
 * ({@see activeSponsors()}, {@see featured()}) only run the query and delegate.
 * `now` is injectable end-to-end so the window/rotation maths is deterministic in
 * the unit suite. Conflation-clean: reads only `Ink\Content` + `Ink\Kernel` + WP
 * core — the scheduling decision is editorial (campaign dates), never gated.
 *
 * @package Ink\Core
 */
final class Campaign {

	/**
	 * Whole seconds in a calendar day — the {@see dayIndex()} divisor.
	 */
	private const SECONDS_PER_DAY = 86400;

	/**
	 * A sane upper bound on sponsors fetched at once (sponsors are few; this just
	 * avoids an unbounded query).
	 */
	private const MAX_SPONSORS = 100;

	/**
	 * Parse a stored `Y-m-d` borg campaign date into a SAST instant. Pure.
	 *
	 * The borg start/end dates are stored as `Y-m-d` (no time component;
	 * {@see \Ink\Content\FieldSets::sanitizeDate}). Sponsors-owned rather than reusing
	 * `Challenges\Deadline::parse` so no `Sponsors -> Challenges` edge is introduced.
	 *
	 * @param string $ymd The stored `Y-m-d` date string.
	 * @return \DateTimeImmutable|null The SAST instant, or null when empty/invalid.
	 */
	public static function parseDate( string $ymd ): ?\DateTimeImmutable {
		if ( '' === $ymd ) {
			return null;
		}

		try {
			return new \DateTimeImmutable( $ymd, new \DateTimeZone( Sast::TIMEZONE ) );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Whether a sponsor is within its campaign window right now. Pure given `$now`.
	 *
	 * Delegates the inclusive two-sided SAST window to {@see Sast::isWithinDayRange()}.
	 * Empty-bound policy: an empty start ⇒ no lower bound; an empty end ⇒ no upper
	 * bound; BOTH empty ⇒ an evergreen sponsor (always active). A single-day campaign
	 * (start == end) is active for that whole SAST day.
	 *
	 * An INVERTED window (start after end, e.g. a transposed-date typo) is never
	 * active — the dates are evaluated literally, fail-closed, NOT silently swapped
	 * (guessing the editor's intent would be worse than the sponsor visibly not
	 * showing). R14 code-review: this behaviour is intentional and pinned by a test.
	 *
	 * @param Sponsor                 $sponsor The sponsor read-model.
	 * @param \DateTimeImmutable|null $now     The instant to test; defaults to SAST now.
	 * @return bool
	 */
	public static function isActive( Sponsor $sponsor, ?\DateTimeImmutable $now = null ): bool {
		return Sast::isWithinDayRange(
			self::parseDate( $sponsor->startDate ),
			self::parseDate( $sponsor->endDate ),
			$now
		);
	}

	/**
	 * Filter a list of sponsors to those active now, re-indexed. Pure given `$now`.
	 *
	 * @param list<Sponsor>           $sponsors The candidate sponsors.
	 * @param \DateTimeImmutable|null $now      The instant to test; defaults to SAST now.
	 * @return list<Sponsor>
	 */
	public static function activeFrom( array $sponsors, ?\DateTimeImmutable $now = null ): array {
		return array_values(
			array_filter(
				$sponsors,
				static fn ( Sponsor $sponsor ): bool => self::isActive( $sponsor, $now )
			)
		);
	}

	/**
	 * The rotation cursor — advances once per SAST calendar day. Pure.
	 *
	 * Anchored on the instant's SAST start-of-day ({@see Sast::startOfDay()}) rather
	 * than the raw UTC timestamp, so every instant within the same SAST day yields the
	 * same index (stable rotation within a day) and consecutive SAST days differ by
	 * exactly one (SAST has no DST, so days are exactly 86400s apart). Using the raw
	 * UTC timestamp would split a SAST day across two indices at the +2 offset.
	 *
	 * @param \DateTimeInterface $now The reference instant.
	 * @return int
	 */
	public static function dayIndex( \DateTimeInterface $now ): int {
		return (int) floor( Sast::startOfDay( $now )->getTimestamp() / self::SECONDS_PER_DAY );
	}

	/**
	 * Pick the featured sponsor from an active set by the daily rotation cursor. Pure.
	 *
	 * `$active[ dayIndex mod count ]` — deterministic and, for a CONSTANT active set,
	 * stable within a SAST day while cycling through the set day by day. (When the
	 * active-set membership changes mid-day — a campaign opening/closing — the
	 * positional slots remap, so the featured pick may change; acceptable for the
	 * few-sponsors reality. R14 review note.) Negative-safe modulo (defensive;
	 * `dayIndex` is non-negative for any post-1970 instant). Returns null for an empty set.
	 *
	 * @param list<Sponsor> $active   The active sponsors (already filtered).
	 * @param int           $dayIndex The rotation cursor from {@see dayIndex()}.
	 * @return Sponsor|null
	 */
	public static function rotate( array $active, int $dayIndex ): ?Sponsor {
		$count = count( $active );

		if ( 0 === $count ) {
			return null;
		}

		$slot = ( ( $dayIndex % $count ) + $count ) % $count;

		return $active[ $slot ];
	}

	/**
	 * The `WP_Query` args for all published sponsors, newest-first. Pure.
	 *
	 * Reads the CPT slug from the migration-load-bearing single source
	 * {@see PostTypes::BORG}. Bounded to {@see MAX_SPONSORS} (sponsors are few).
	 *
	 * @return array<string, mixed>
	 */
	public static function queryArgs(): array {
		return array(
			'post_type'           => PostTypes::BORG,
			'post_status'         => 'publish',
			'posts_per_page'      => self::MAX_SPONSORS,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}

	/**
	 * All sponsors currently within their campaign window. Thin WP wrapper.
	 *
	 * Runs {@see queryArgs()}, maps each post to a {@see Sponsor} read-model, and
	 * returns those {@see activeFrom()} — the window filter runs in the pure layer.
	 *
	 * @param \DateTimeImmutable|null $now The instant to test; defaults to SAST now.
	 * @return list<Sponsor>
	 */
	public static function activeSponsors( ?\DateTimeImmutable $now = null ): array {
		$query    = new WP_Query( self::queryArgs() );
		$sponsors = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof WP_Post ) {
				$sponsors[] = Sponsor::forPost( $post );
			}
		}

		return self::activeFrom( $sponsors, $now );
	}

	/**
	 * The single sponsor to feature right now, or null when none is active. Thin WP wrapper.
	 *
	 * The "which sponsor shows now" entry point the homepage strip (14.3) consumes:
	 * the daily-rotated pick from the active set.
	 *
	 * @param \DateTimeImmutable|null $now The instant; defaults to SAST now.
	 * @return Sponsor|null
	 */
	public static function featured( ?\DateTimeImmutable $now = null ): ?Sponsor {
		$now ??= Sast::now();

		return self::rotate( self::activeSponsors( $now ), self::dayIndex( $now ) );
	}
}
