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
 * Challenges module — the Uitdagings section (Epic 12).
 *
 * Owns the challenge reader surfaces and competition machinery: the single-page
 * deadline/status + entries list ({@see SinglePage}, Story 12.1) and the rest of the
 * epic (list page, entry capture, Gradering pools, placement records). It READS
 * Gradering for pools via the Tiers `Api` facade and reads the `Tier` value type from
 * the shared Kernel (`Ink\Kernel\Tier`) — never writing `ink_writer_tier` directly
 * (THE conflation rule). Viewing published challenges is open (no Entitlement edge).
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks.
	 *
	 * Dispatched once by the Kernel on `init`. Delegates to each collaborator so this
	 * bootstrap stays thin (the Library/Discovery house style).
	 */
	public function register(): void {
		( new SinglePage() )->register();
		( new Archive() )->register();
	}
}
