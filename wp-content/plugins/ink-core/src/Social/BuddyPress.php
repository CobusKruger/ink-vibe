<?php
/**
 * BuddyPress scoped-component configuration (Story 9.1, FR-37).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Code-enforces the INK BuddyPress scope.
 *
 * INK uses only a slice of BuddyPress: extended Profiles, the member Directory
 * and Kennisgewings. Everything else — Friend Connections, the site-wide
 * Activity stream, Groups, Blogs and Private Messaging — is OFF at launch
 * (§14.7, FR-37). Rather than rely on a one-time admin un-check (which the
 * brownfield DB clone could carry forward differently, and which a restored
 * option or re-activated component could silently undo), the active set is made
 * declarative and version-controlled here via BuddyPress's own
 * `bp_active_components` filter — "hook, don't edit" (no BP files touched, no BP
 * internals assumed beyond the public filter + the stable component IDs).
 *
 * Why each scoped-on component:
 * - `xprofile`      — extended Profiles (FR-40 My Profiel / Skrywerprofiel host).
 * - `members`       — BuddyPress's required core component; owns the member
 *                     Directory (the Story 9.7 ledegids surfaces through it).
 * - `notifications` — the BP notifications store INK registers custom `ink`
 *                     types against (AD-5; Stories 9.9 / 9.11).
 * - `settings`      — account settings; hosts the notification preferences.
 *
 * INK's follow graph is the custom asymmetric `ink_follows` table (Story 9.2),
 * NOT BuddyPress `friends` — so Friend Connections stays off here.
 *
 * @package Ink\Core
 */
final class BuddyPress {

	/**
	 * The BuddyPress component IDs INK keeps active.
	 *
	 * These are BuddyPress's public component IDs (the integration contract),
	 * not INK enums, so they live with the integration rather than in `Kernel`.
	 *
	 * @var list<string>
	 */
	public const SCOPED_ON = array( 'xprofile', 'members', 'notifications', 'settings' );

	/**
	 * The BuddyPress component IDs INK forces off at launch.
	 *
	 * Documentary + the non-vacuous test surface: `scopeComponents()` returns
	 * exactly {@see self::SCOPED_ON}, so any ID outside that set (these included)
	 * is dropped regardless of what the cloned DB had active.
	 *
	 * @var list<string>
	 */
	public const FORCED_OFF = array( 'friends', 'activity', 'groups', 'blogs', 'messages' );

	/**
	 * Scope the BuddyPress active-components set to exactly the INK slice.
	 *
	 * Pure and total: the returned set is always exactly {@see self::SCOPED_ON}
	 * (each mapped to `'1'`, BuddyPress's active marker), independent of the
	 * incoming value. That makes it idempotent and order-independent — scoping
	 * an empty array, an already-scoped array, or an array polluted with every
	 * component all yield the same canonical scope.
	 *
	 * @param mixed $active The current `bp_active_components` value (ignored — the
	 *                      scope is fixed). Typed loose to match the filter signature.
	 * @return array<string,string> The scoped active-components map.
	 */
	public static function scopeComponents( $active = array() ): array {
		unset( $active );

		$scoped = array();

		foreach ( self::SCOPED_ON as $component ) {
			$scoped[ $component ] = '1';
		}

		return $scoped;
	}
}
