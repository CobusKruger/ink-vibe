<?php
/**
 * Social module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Social module — the community layer (Epic 9).
 *
 * Owns the custom asymmetric follow graph (one-way; BuddyPress Friend
 * Connections are OFF), the following-feed, pinned/selected works, reader
 * ratings & reviews, and the BuddyPress glue (scoped profiles/directory/
 * notifications). Live at 9.1: the {@see BuddyPress} scoped-component config.
 * The follow graph, feed, profile templates, pinned works and ratings extend
 * this module in Stories 9.2+.
 *
 * @package Ink\Core
 */
class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init`. Only wires the BuddyPress scope
	 * when BuddyPress is present — with the platform plugin absent, `ink-core`
	 * is a clean no-op (the scope filter has nothing to filter). The scoping
	 * logic itself lives in the pure {@see BuddyPress::scopeComponents()} so it
	 * unit-tests without BuddyPress loaded.
	 */
	public function register(): void {
		if ( $this->buddyPressActive() ) {
			add_filter( 'bp_active_components', array( BuddyPress::class, 'scopeComponents' ) );

			// Epic 19 auth fidelity pass: stop BuddyPress's own Register/Activate
			// directory-page mapping from hijacking /registreer/ — see
			// BuddyPress::excludeAuthPages() for the full rationale.
			add_filter( 'bp_core_get_directory_page_ids', array( BuddyPress::class, 'excludeAuthPages' ) );

			// Epic 19 auth fidelity pass: the content-level half of the same
			// fix — frees the `registreer` slug from BuddyPress's own Register
			// CPT post and ensures a real `page` object exists there instead.
			( new AuthPageRelease() )->register();
		}

		// Story 9.2: the asymmetric follow graph — REST write path + toggle block.
		// The store + counts are stateless statics reached through the Api facade;
		// the table DDL is registered with the Kernel Schema in the bootstrap.
		( new FollowController() )->register();
		( new FollowToggle() )->register();

		// Story 9.3: the following-feed (the profile "Aktiwiteit" tab).
		( new FollowingFeed() )->register();

		// My Profiel rebuild §5.6: the "Wie ek volg" writer-card list (distinct
		// from the works-feed above — this lists the followed writers
		// themselves).
		( new FollowingList() )->register();

		// Story 9.4: the public Skrywerprofiel block (resolves the queried author),
		// plus its optional cover-image field (Phase-2 fidelity pass).
		( new CoverImage() )->register();
		( new SkrywerProfiel() )->register();

		// Story 9.5: pinned / selected works — REST write path + curation block.
		( new PinnedWorksController() )->register();
		( new PinnedWorksManager() )->register();

		// Story 9.6: reader ratings & reviews — REST write path + form block
		// (the public Lesergradering display lives on the Skrywerprofiel block;
		// the ink_ratings table is registered with the Kernel Schema in the
		// bootstrap). Reviews are held for moderation (18.4).
		( new RatingController() )->register();
		( new RatingForm() )->register();

		// Epic 19 lees-gedig fidelity pass: the reading-page author card (reuses
		// FollowToggle's own markup for its Follow toggle — no new follow logic).
		( new ReadingAuthorCard() )->register();
	}

	/**
	 * Whether BuddyPress is loaded.
	 *
	 * A `protected` seam (not an inline `function_exists()`) so unit tests can
	 * drive both presence and absence without leaking a process-wide `buddypress`
	 * stub between cases — the same testability pattern as
	 * {@see \Ink\Entitlement\LifecycleEmails::isActionSchedulerAvailable()}.
	 */
	protected function buddyPressActive(): bool {
		return function_exists( 'buddypress' );
	}
}
