<?php
/**
 * I18n module bootstrap — Story 18.7 (NFR-7 / NFR-1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\I18n;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * I18n module — the i18n-resilience tooling (Story 18.7).
 *
 * The terminology registry ({@see Terms}) and Block Bindings ({@see Bindings}) are
 * wired by the foundation stories; this module bootstrap hosts the standing
 * translation-resilience audit ({@see TranslationAudit}) — the post-update recheck
 * that the committed premium-plugin translations are present (the "committed `.mo`
 * for premium plugins re-checked after their updates" requirement).
 *
 * THE conflation rule (AD-1): references neither Tiers nor Entitlement.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 */
	public function register(): void {
		( new TranslationAudit() )->register();
	}
}
