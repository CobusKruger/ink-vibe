<?php
/**
 * Challenges module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Challenges module — RESERVED extension point for Epic 12 / 12A.
 *
 * Will own challenge entry (open-window + per-type cap + tier snapshot),
 * per-tier pools and placement records, EntryID collation, the R1 judge-email
 * composer, the R2 paste-text results parser → winners post, and the
 * winner→promotion write (via the Tiers `Api` facade only — never writing
 * `ink_writer_tier` directly). It READS Gradering for pools via the Tiers Api
 * facade and reads the `Tier` value type from the shared Kernel
 * (`Ink\Kernel\Tier`). NOTHING is implemented at 1.7.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks. No-op until Epic 12.
	 */
	public function register(): void {
		// Reserved: entry, pools, placements, collation, winners land in Epic 12.
	}
}
