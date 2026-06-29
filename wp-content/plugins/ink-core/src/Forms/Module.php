<?php
/**
 * Forms module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Forms;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Forms module — the custom front-end forms (Story 15.4 + 18.4).
 *
 * Owns the Kontak contact form ({@see ContactForm}, Story 15.4) — a custom
 * `ink-core` form, never CF7 / Fluent Forms (OQ-8). The content-report path
 * (18.4) lands here later. May fold into an adjacent module if it stays thin
 * (AD-1, ~8–12 modules). THE conflation rule: references neither Tiers nor
 * Entitlement — a contact form is not gated on membership or Gradering.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 */
	public function register(): void {
		( new ContactForm() )->register();
		( new ReportForm() )->register();
	}
}
