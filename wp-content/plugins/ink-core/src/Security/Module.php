<?php
/**
 * Security module bootstrap — Story 18.3 (§14.16).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Security;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Security module — the ORIGIN-SIDE complement to the edge security stack (18.3).
 *
 * Cloudflare (edge + login rule + origin lock), Patchstack (CVE alerts), staff
 * 2FA (a plugin), staging-gated updates and host malware scanning are external —
 * see docs/security-stack-runbook.md. This module owns only what genuinely belongs
 * at the origin: surface reduction ({@see Hardening} — xmlrpc off, username
 * enumeration blocked, version disclosure removed) and the 2FA-coverage audit
 * ({@see TwoFactorAudit}) that verifies "staff 2FA in place" actually holds.
 *
 * THE conflation rule (AD-1): references neither Tiers nor Entitlement — hardening
 * is never gated on membership or Gradering.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 */
	public function register(): void {
		( new Hardening() )->register();
		( new TwoFactorAudit() )->register();
	}
}
