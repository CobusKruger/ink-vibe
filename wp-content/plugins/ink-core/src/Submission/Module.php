<?php
/**
 * Submission module bootstrap (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Submission module — the custom Skryf front-end submission workflow (Epic 6).
 *
 * Owns the Skryf form, draft/publish (konsep/plaas) states, and the
 * publish-moment flow that (from Story 6.8) calls `Entitlement\Api::can_submit()`
 * (AD-2) and (from Story 6.6) links challenges — always through facades, never
 * owning entry rules itself. THE conflation rule: never references `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * Register this module's hooks (dispatched by the Kernel on `init`).
	 *
	 * Thin delegation to per-concern collaborators, mirroring the house style of
	 * {@see \Ink\Content\Module} / {@see \Ink\Entitlement\Module}.
	 */
	public function register(): void {
		( new SubmissionForm() )->register();
		( new MediaAttachment() )->register();
	}
}
