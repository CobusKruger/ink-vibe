<?php
/**
 * Notifications module bootstrap.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications module bootstrap.
 *
 * Live at 1.12 (AD-9): the lightweight form-letter / notification capability —
 * an options-backed {@see TemplateStore} (per-event body + send toggle +
 * randomized message list), the single greeting-line {@see MergeResolver}, and
 * the {@see Notifier} that composes + dispatches transactional email via
 * `wp_mail` (toggle-gated). The capability is exposed on {@see Api}.
 *
 * No concrete templates or event subscriptions are wired here: downstream
 * consumers depend BACKWARDS on this foundation — R2 (12A.4 winners post), R3
 * (5.10 promotion email), R5 (4.8 lifecycle emails), R7 (9.11 receipt
 * notification + randomized list) register their Afrikaans-source templates via
 * `Api::registerTemplate()` and subscribe their `ink/{module}/{event}` events
 * (AD-6) to `Api::send()`.
 *
 * RESERVED: BuddyPress notification types (Epic 9.9); concrete Action Scheduler
 * fan-out jobs ride with their consumers.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Wire the form-letter / notification capability.
	 *
	 * Dispatched once by the Kernel on `init` (via `Plugin::registerModules()`).
	 * Builds the collaborators and hands them to the {@see Api} facade so other
	 * modules reach Notifications only through that single public surface (AD-1).
	 */
	public function register(): void {
		$store    = new TemplateStore();
		$notifier = new Notifier( $store );

		Api::bootstrap( $store, $notifier );

		// Story 9.9: in-app kennisgewings (BP notifications store). The source
		// subscriptions route reaksie/@mention, followed-writer new work and the
		// 4.8-anchored lidmaatskap-expiry reminder through Kennisgewings::add()
		// (guarded — a clean no-op without BuddyPress).
		( new Events() )->register();

		// Story 9.11 (R7): the receipt trigger — an encouraging kennisgewing when
		// a work crosses a read-count milestone. Inert until the 18.9 analytics +
		// 9.12 fire `ink/ontvangs` AND the R7 form-letter list is authored.
		( new ReceiptNotification() )->register();
	}
}
