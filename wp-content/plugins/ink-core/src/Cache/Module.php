<?php
/**
 * Cache module bootstrap — Story 18.5 (NFR-3, §14.9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Cache;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Cache module — cache CORRECTNESS for INK's dynamic surfaces (Story 18.5).
 *
 * LiteSpeed Cache + Cloudflare edge caching are configured platform layers
 * (project-context: do not reimplement) — see docs/caching-runbook.md. This module
 * owns only the part that must live in code: making sure INK's private/transactional
 * surfaces ({@see CacheControl}) opt OUT of the page cache so the cache can be
 * aggressive on everything else without leaking a member's personalised content or
 * serving a stale form result.
 *
 * THE conflation rule (AD-1): references neither Tiers nor Entitlement — caching is
 * never gated on membership or Gradering.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 */
	public function register(): void {
		( new CacheControl() )->register();
	}
}
