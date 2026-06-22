<?php
/**
 * Social-login availability seam (R6, Story 3.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

defined( 'ABSPATH' ) || exit;

/**
 * The INK-side seam for R6 social login (Epic 3, Story 3.5).
 *
 * R6 social login is a VETTED-PLUGIN seam integrated via hooks — NOT `ink-core`
 * business logic (architecture.md line 650-652). This class therefore implements
 * NO OAuth, stores NO provider tokens, and reimplements NO commodity capability.
 * It exposes a single read-only contract the theme bridge consumes to decide
 * whether to render the social-login section:
 *
 *   {@see SocialLogin::isAvailable()} === (bool) apply_filters( 'ink_social_login_available', false )
 *
 * Default is FALSE ⇒ graceful degradation: with no vetted social-login plugin
 * present, the auth surface renders no social section and never errors; the
 * e-mail auth path stays fully usable. A vetted plugin (or a thin deploy-time
 * glue mu-plugin) flips the `ink_social_login_available` filter true and paints
 * its buttons through the `ink_social_login_buttons` render action the theme
 * bridge fires. The concrete plugin + OAuth-app credentials are a documented
 * deploy-time integration step (third-party plugins are Composer-assembled and
 * git-ignored), per the Story-3.4 decision.
 *
 * THE conflation rule (AD-1): a socially-registered account lands at Brons /
 * gratis lid through the SAME `user_register` path as e-mail signup
 * ({@see Registration::applyDefaults()} already stamps it) — this seam adds no
 * parallel default-setter, no lidmaatskap, no entitlement, no `ink_writer_tier`
 * re-write, and no reader/writer intent flag. `src/Accounts/` carries ZERO
 * reference to `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class SocialLogin {

	/**
	 * The filter a vetted social-login plugin (or deploy-time glue) flips true to
	 * announce that social sign-in is available. Default-off ⇒ graceful degradation.
	 */
	public const AVAILABLE_FILTER = 'ink_social_login_available';

	/**
	 * The render action the active plugin hooks to paint its provider buttons.
	 * Fired by the theme bridge inside the (Afrikaans, escaped) social section.
	 */
	public const BUTTONS_ACTION = 'ink_social_login_buttons';

	/**
	 * Whether a vetted social-login plugin is available to render buttons.
	 *
	 * The graceful-degradation seam the theme bridge reads: default FALSE (no
	 * plugin ⇒ no social section, never an error). A vetted plugin flips
	 * {@see AVAILABLE_FILTER} true. This is a pure read — it writes no state and
	 * references no entitlement.
	 *
	 * @return bool True when social login should be offered on the auth surface.
	 */
	public static function isAvailable(): bool {
		return (bool) apply_filters( self::AVAILABLE_FILTER, false );
	}
}
