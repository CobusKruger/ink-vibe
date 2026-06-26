<?php
/**
 * Auto-promotion congratulation email (Notifications consumer).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\Kernel\Tier;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Sends the Afrikaans congratulation email on an AUTOMATIC Gradering promotion
 * (Story 5.10, FR-12a / R3).
 *
 * A Notifications consumer (reusing the Story-1.12 form-letter store — NO new
 * email engine): it subscribes the `ink/tier_promoted` event (fired by the sole
 * {@see Api::promote()} write path) and, only for an automatic promotion
 * (`actor_id === 0`, the 5.8 engine), dispatches the grade-specific template via
 * {@see Notifications::send()}. A manual staff change (`actor_id != 0`) sends
 * nothing.
 *
 * Auto-promotion only ever targets Silwer or Goud (Brons→Silwer, Silwer→Goud;
 * Goud is terminal-for-auto, Meester is manual-only), so exactly two templates
 * cover it — the grade name is baked into each body because the 1.12 store merges
 * only the `{skrywer}` greeting. Both register Afrikaans-source with the send
 * toggle fail-safe OFF (the 4.2/4.8 convention — staff enable in production).
 *
 * THE conflation rule (AD-1): a Gradering recognition; references only the Kernel
 * `Tier`, this module's `Api`, and the Notifications facade — zero
 * `Ink\Entitlement`. The `Tiers → Notifications` edge is the allowed Api-facade
 * dependency (deptrac), mirroring 4.2's `Entitlement → Notifications`.
 *
 * @package Ink\Core
 */
final class PromotionEmails {

	/**
	 * The auto-promotion event (fired by {@see Api::promote()}).
	 */
	public const HOOK = 'ink/tier_promoted';

	/**
	 * Template keys — one per auto-promotion target grade.
	 */
	public const SILWER_TEMPLATE_KEY = 'ink_tier_promoted_silwer_email';
	public const GOUD_TEMPLATE_KEY   = 'ink_tier_promoted_goud_email';

	/**
	 * Bind the event subscription + register the templates. Invoked from
	 * {@see Module::register()}.
	 */
	public function register(): void {
		// Request 4 of the event's 5 args — the challenge id is not needed here.
		add_action( self::HOOK, array( $this, 'onTierPromoted' ), 10, 4 );

		$this->registerTemplates();
	}

	/**
	 * Register the two Afrikaans-source congratulation templates (toggle OFF).
	 *
	 * Copy is the glossary-approved phrasing (afrikaans-terms.md line 213),
	 * wrapped in literal `__()` as the gettext source (no English `.mo` ships).
	 */
	public function registerTemplates(): void {
		Notifications::registerTemplate(
			new Template(
				self::SILWER_TEMPLATE_KEY,
				__( 'Baie geluk! Jy is na Silwer bevorder.', 'ink-core' ),
				__( 'Hallo {skrywer}! Baie geluk! Jy is na Silwer bevorder.', 'ink-core' ),
				false
			)
		);

		Notifications::registerTemplate(
			new Template(
				self::GOUD_TEMPLATE_KEY,
				__( 'Baie geluk! Jy is na Goud bevorder.', 'ink-core' ),
				__( 'Hallo {skrywer}! Baie geluk! Jy is na Goud bevorder.', 'ink-core' ),
				false
			)
		);
	}

	/**
	 * Send the congratulation email on an automatic promotion.
	 *
	 * @param int  $user_id  The promoted writer.
	 * @param Tier $from     The previous grade (part of the event payload; not used here).
	 * @param Tier $to       The new grade.
	 * @param int  $actor_id 0 = the automatic engine; non-zero = a manual staff change.
	 */
	public function onTierPromoted( int $user_id, Tier $from, Tier $to, int $actor_id ): void {
		// Auto-promotions only — a manual staff set/correction never congratulates.
		if ( 0 !== $actor_id ) {
			return;
		}

		$key = self::templateKeyFor( $to );

		if ( null === $key ) {
			return;
		}

		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof WP_User ) || '' === (string) $user->user_email ) {
			return;
		}

		$skrywer = (string) $user->display_name;
		if ( '' === $skrywer ) {
			$skrywer = (string) $user->user_login;
		}

		Notifications::send(
			$key,
			(string) $user->user_email,
			array( 'skrywer' => $skrywer )
		);
	}

	/**
	 * The template key for an auto-promotion target grade, or null when the grade
	 * is not an auto-promotion target (Brons / Meester).
	 */
	private static function templateKeyFor( Tier $to ): ?string {
		return match ( $to ) {
			Tier::Silwer => self::SILWER_TEMPLATE_KEY,
			Tier::Goud   => self::GOUD_TEMPLATE_KEY,
			default      => null,
		};
	}
}
