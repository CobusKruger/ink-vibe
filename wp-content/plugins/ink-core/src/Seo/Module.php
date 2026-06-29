<?php
/**
 * SEO module bootstrap — Story 18.1 (NFR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Seo;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * SEO module — the thin Rank Math refinement seam (Story 18.1).
 *
 * Rank Math is a configured platform plugin (project-context: "do not
 * reimplement"): it owns sitemaps, meta, breadcrumbs and the importer, all of
 * which are admin configuration on a running site (see
 * docs/seo-rank-math-runbook.md). The ONE thing Rank Math cannot infer is the
 * correct schema `@type` for a custom post type — it defaults every singular to
 * `Article`. This module supplies that per-CPT refinement ({@see SchemaTypes})
 * via Rank Math's documented `rank_math/json_ld` filter, and is inert when Rank
 * Math is absent.
 *
 * THE conflation rule (AD-1): references neither Tiers nor Entitlement — schema
 * is never gated on membership or Gradering.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 */
	public function register(): void {
		( new SchemaTypes() )->register();
	}
}
