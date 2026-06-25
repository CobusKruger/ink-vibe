<?php
/**
 * Account-creation defaults + Afrikaans transactional auth email.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use Ink\Content\UserMeta;
use Ink\Kernel\Tier;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Stamps the new-account defaults and wires Afrikaans transactional auth email
 * (Story 3.1).
 *
 * Two responsibilities, both `ink-core` business logic hooked onto WordPress's
 * own auth machinery (WordPress owns the mechanism; this never reimplements it):
 *
 *  1. On `user_register` (the canonical new-account hook for BOTH wp-admin and
 *     front-end registration), materialise `ink_writer_tier = brons` so the
 *     default is queryable/visible from the first moment ({@see applyDefaults}).
 *     "Gratis lid" is the absence of an active lidmaatskap — there is nothing to
 *     write, and THE conflation rule is upheld by NOT referencing
 *     `Ink\Entitlement` / `Ink\Tiers` here.
 *
 *  2. Make INK's transactional auth mail Afrikaans: register the
 *     account-welcome template through the Story-1.12 Notifications capability
 *     (toggle OFF until human Afrikaans body copy lands), and Afrikaans the
 *     WP-core password-reset mail we cannot route through Notifications via the
 *     `retrieve_password_title` / `retrieve_password_message` filters.
 *
 * @package Ink\Core
 */
final class Registration {

	/**
	 * Notifications template/event key for the account-welcome email.
	 */
	public const WELCOME_TEMPLATE_KEY = 'ink_account_welcome';

	/**
	 * Register all account hooks.
	 *
	 * Called once by {@see Module::register()} (dispatched by the Kernel on
	 * `init`). Hooks are wired via array callables (the registration runs after
	 * Notifications' own `init` bootstrap, so the facade store is available).
	 */
	public function register(): void {
		// Task 2: stamp the Brons + gratis-lid default on every new account.
		add_action( 'user_register', array( $this, 'applyDefaults' ), 10, 1 );

		// Task 3: Afrikaans transactional auth email.
		$this->registerWelcomeTemplate();
		add_filter( 'retrieve_password_title', array( $this, 'passwordResetTitle' ), 10, 1 );
		add_filter( 'retrieve_password_message', array( $this, 'passwordResetMessage' ), 10, 1 );
	}

	/**
	 * Set the new account's writer-tier default to Brons (gratis lid).
	 *
	 * Sources the key from {@see UserMeta::WRITER_TIER} and the value from
	 * {@see Tier::Brons} — never a `'ink_writer_tier'` / `'brons'` literal. Story
	 * 2.3 already registers a `brons` REGISTERED DEFAULT, so an unset value reads
	 * as `brons`; this materialises it at account creation so it is explicit and
	 * survives a later change to that registered default.
	 *
	 * Idempotency: account creation is the one authoritative stamp, but the write
	 * is guarded so a re-fired `user_register` or an already-set value never
	 * clobbers a deliberately-set tier — if the meta already holds a value
	 * (e.g. a staff promotion, or an import that stamped a grade), it is left
	 * untouched. Only an empty/unset value is materialised to `brons`.
	 *
	 * Gratis lid = the ABSENCE of an active lidmaatskap: this writes ONLY the
	 * writer tier — no `ink_tier_promoted_at` (no promotion happened), no
	 * WooCommerce membership, no entitlement, and no reader/writer intent flag
	 * (Story 3.2 removed that). THE conflation rule holds: zero `Ink\Entitlement`
	 * reference.
	 *
	 * @param int $user_id The freshly-created user ID.
	 */
	public function applyDefaults( int $user_id ): void {
		$existing = get_user_meta( $user_id, UserMeta::WRITER_TIER, true );

		if ( '' !== $existing && false !== $existing && null !== $existing ) {
			return; // Never clobber a deliberately-set tier.
		}

		update_user_meta( $user_id, UserMeta::WRITER_TIER, Tier::Brons->value );
	}

	/**
	 * Register the Afrikaans-source account-welcome email template (Story 1.12).
	 *
	 * Decision 5a: the Afrikaans-source subject/body are passed as LITERAL
	 * `__( '…', 'ink-core' )` strings so `wp i18n make-pot` extracts them; no
	 * English `.mo` ships, so the Afrikaans source is also the rendered output.
	 * The greeting uses the `{skrywer}` merge token.
	 *
	 * The welcome-email body is the human-authored Afrikaans curated in
	 * `ui-copy-translations.md` (never AI-translated). The per-event send toggle is
	 * OFF by default (no `wp_mail` fires) until staff deliberately enable it.
	 */
	public function registerWelcomeTemplate(): void {
		Notifications::registerTemplate(
			new Template(
				self::WELCOME_TEMPLATE_KEY,
				// Subject — glossary-only, sentence case.
				__( 'Welkom by INK', 'ink-core' ),
				// Body — human-authored Afrikaans; send toggle stays OFF until staff enable sending.
				__( 'Hallo {skrywer}, en welkom by INK! Jou rekening is pas geskep.', 'ink-core' ),
				// Send toggle OFF by default — copy is ready; enable deliberately to start sending.
				false
			)
		);
	}

	/**
	 * Afrikaans subject for the WP-core password-reset email.
	 *
	 * Filters `retrieve_password_title` so the WP-core lost-password mail we
	 * cannot route through Notifications never leaks an English subject. Sentence
	 * case, glossary-consistent (`Wagwoord-herstel`).
	 *
	 * @param string $title The incoming (English WP-core) subject (intentionally discarded).
	 * @return string The Afrikaans-source subject.
	 */
	public function passwordResetTitle( string $title ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- the WP-core English subject is intentionally replaced wholesale.
		return __( 'Wagwoord-herstel vir jou INK-rekening', 'ink-core' );
	}

	/**
	 * Afrikaans body for the WP-core password-reset email.
	 *
	 * Filters `retrieve_password_message` so the reset mail body is Afrikaans
	 * ("jy"-voice, sentence case, glossary-only). The reset URL WordPress composed
	 * is preserved verbatim and appended — this filter only Afrikaanses the
	 * surrounding human copy, it does not reimplement the token flow.
	 *
	 * @param string $message The incoming (English WP-core) body, containing the reset URL.
	 * @return string The Afrikaans-source body with the original reset link preserved.
	 */
	public function passwordResetMessage( string $message ): string {
		$reset_url = $this->extractResetUrl( $message );

		$body  = __( 'Iemand het gevra om die wagwoord vir jou INK-rekening te herstel.', 'ink-core' ) . "\r\n\r\n";
		$body .= __( 'As dit nie jy was nie, ignoreer hierdie e-pos.', 'ink-core' ) . "\r\n\r\n";
		$body .= __( 'Om jou wagwoord te herstel, gaan na hierdie skakel:', 'ink-core' ) . "\r\n";
		$body .= $reset_url;

		return $body;
	}

	/**
	 * Pull the first http(s) URL (the password-reset link) out of the WP-core body.
	 *
	 * The reset token URL is WordPress's — it is preserved as-is, never rebuilt.
	 * The character class stops at whitespace AND at the markup delimiters a
	 * wrapped/HTML-ified body uses (`<`, `>`, `"`, `'`), so an angle-bracketed
	 * `<https://…>` link is captured without its delimiters; trailing sentence
	 * punctuation a body may append after the link is then trimmed. (Review P1:
	 * the previous greedy `\S+` swallowed those into the URL, corrupting the link.)
	 *
	 * @param string $message The WP-core message body.
	 * @return string The reset URL, or an empty string if none is found.
	 */
	private function extractResetUrl( string $message ): string {
		if ( 1 !== preg_match( '#https?://[^\s<>"\']+#', $message, $matches ) ) {
			return '';
		}

		return rtrim( $matches[0], '.,;:!?)' );
	}
}
