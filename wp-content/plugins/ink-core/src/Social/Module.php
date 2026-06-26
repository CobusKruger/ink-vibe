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
		}

		// Story 9.2: the asymmetric follow graph — REST write path + toggle block.
		// The store + counts are stateless statics reached through the Api facade;
		// the table DDL is registered with the Kernel Schema in the bootstrap.
		( new FollowController() )->register();
		( new FollowToggle() )->register();

		// Story 9.3: the following-feed (the profile "Aktiwiteit" tab).
		( new FollowingFeed() )->register();

		// Story 9.4: the public Skrywerprofiel block (resolves the queried author).
		( new SkrywerProfiel() )->register();

		// Story 9.5: pinned / selected works — REST write path + curation block.
		( new PinnedWorksController() )->register();
		( new PinnedWorksManager() )->register();
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
