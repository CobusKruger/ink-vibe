<?php
/**
 * Optional, off-by-default manual-approval backstop (R6, Story 3.6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Accounts;

use Ink\I18n\Terms;
use Ink\Kernel\Capabilities;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * The R6 manual-approval BACKSTOP — an OFF-by-default registration escalation lever
 * (Epic 3, Story 3.6; FR-3a / C8; the Story-3.4 build decision's layer 5).
 *
 * This is the legitimate `ink-core` Accounts-module surface for R6: a single
 * fail-safe-OFF toggle, a WP-native "wag vir goedkeuring" pending account state
 * stamped on `user_register` when ON, a login gate (a `wp_authenticate_user`
 * filter returning an Afrikaans `WP_Error`), and the plugin's first admin
 * approval-queue screen (WP-admin chrome, redakteur, `ink_moderate`) to
 * goedkeur / verwerp pending accounts. WordPress owns the auth MECHANISM
 * (credentials, sessions, hashing) — this hooks it, never reimplements it.
 *
 * THE hard constraint (C8 / UJ-1): manual approval is **never** the launch
 * default. The toggle reads fail-safe OFF — an unset OR garbled option value is
 * treated as OFF ({@see isEnabled()}). When OFF, `user_register` stamps NOTHING
 * and the login gate is a no-op, so signup is exactly as frictionless as today;
 * flipping the toggle OFF again instantly restores the frictionless path and
 * un-gates any still-pending login.
 *
 * THE conflation rule (AD-1): the pending state is a registration-lifecycle
 * concept, strictly separate from lidmaatskap-status (Entitlement) and Gradering
 * (Tiers). This collaborator writes ONLY the pending / rejected flags and carries
 * ZERO reference to `Ink\Entitlement` / `Ink\Tiers`.
 *
 * Scope (Story 3.6 out-of-scope list): NOT social login (Story 3.5); NOT the
 * always-on anti-spam baseline — Cloudflare Turnstile, email double-opt-in,
 * honeypot/timing (the Story-3.4 spike's layers 1–4); NOT edge rate-limiting / IP
 * reputation / Patchstack / Turnstile-tuning / blocked-attempt analytics (Story
 * 18.10, which hardens AROUND this pending state); NOT any change to the OFF
 * default; NOT any front-end / design-system / `theme.json` work (the queue is
 * WP-admin chrome); NOT reimplemented WordPress auth.
 *
 * Curated-copy gate (Gate D): the controlled-vocabulary LABELS (keur goed /
 * verwerp / "wag vir goedkeuring" / the queue name) come from the AD-10
 * terminology registry ({@see Terms} / `ink_term()`), projected from
 * `docs/afrikaans-terms.md` where they are flagged pending redakteur
 * ratification. The full member-facing PROSE (the login-blocked sentence, the
 * approve/reject result notices, the approval/rejection email bodies) is the
 * human-authored Afrikaans in `ui-copy-translations.md` (never AI-translated);
 * the email send toggles default OFF until staff deliberately enable them.
 *
 * @package Ink\Core
 */
final class Approval {

	/**
	 * The single `ink_`-prefixed master toggle (a bool option). Fail-safe OFF.
	 *
	 * Unset OR garbled ⇒ OFF (manual approval is never the launch default).
	 */
	public const OPTION_ENABLED = 'ink_account_approval_enabled';

	/**
	 * The `ink_`-prefixed pending-approval user-meta flag (single source).
	 *
	 * Set on `user_register` only when the backstop is ON; its presence blocks
	 * login (via {@see blockPendingLogin()}) and surfaces the account in the
	 * approval queue. Cleared by {@see approve()}.
	 */
	public const META_PENDING = 'ink_account_pending';

	/**
	 * The `ink_`-prefixed rejected user-meta flag (single source).
	 *
	 * Set by {@see reject()} — the non-destructive "mark blocked" reject behaviour
	 * (NOT account deletion; see {@see reject()}). A rejected account stays
	 * login-blocked while the backstop is ON, and drops off the pending queue.
	 */
	public const META_REJECTED = 'ink_account_rejected';

	/**
	 * The `WP_Error` codes returned by the login gate.
	 *
	 * Distinct from the {@see META_PENDING} / {@see META_REJECTED} meta keys (no
	 * aliasing): a blocked login surfaces a pending-specific OR rejected-specific
	 * code so the member-facing message can differ (a rejected account is not
	 * "waiting for approval").
	 */
	public const ERROR_CODE          = 'ink_account_pending_login';
	public const ERROR_CODE_REJECTED = 'ink_account_rejected_login';

	/**
	 * Notifications template/event keys for the approval / rejection emails.
	 *
	 * The `_email` suffix keeps these template keys distinct from the
	 * {@see META_PENDING} / {@see META_REJECTED} user-meta keys — different
	 * subsystems (Notifications keyspace vs user-meta), deliberately non-aliasing
	 * strings so neither can ever be read as the other.
	 */
	public const APPROVED_TEMPLATE_KEY = 'ink_account_approved_email';
	public const REJECTED_TEMPLATE_KEY = 'ink_account_rejected_email';

	/**
	 * The admin-screen slug (the approval queue, under Users).
	 */
	public const SCREEN_SLUG = 'ink-account-approval';

	/**
	 * The `admin_post` action names + nonce actions for the approve / reject writes.
	 */
	public const ACTION_APPROVE = 'ink_account_approve';
	public const ACTION_REJECT  = 'ink_account_reject';
	private const NONCE_APPROVE = 'ink_account_approve_nonce';
	private const NONCE_REJECT  = 'ink_account_reject_nonce';

	/**
	 * Register the backstop's hooks. Invoked once from {@see Module::register()}
	 * (dispatched by the Kernel on `init`).
	 *
	 * Wires: the `user_register` pending-stamp (priority 20 — after
	 * {@see Registration::applyDefaults()} at 10, so the Brons/gratis-lid default
	 * is materialised first; the two are independent, but keeping the tier default
	 * authoritative-first is the documented composition order); the WP-native
	 * login gate; the pending/rejected meta registration; the approval-queue admin
	 * screen; and the nonce-protected approve/reject `admin_post` handlers.
	 *
	 * Every hook here is gated AT RUNTIME by {@see isEnabled()} (the stamp + login
	 * gate) or by `current_user_can( MODERATE )` (the queue + writes) — wiring the
	 * hooks unconditionally keeps the toggle the single master switch (flipping it
	 * ON needs no re-bootstrap) while OFF stays fully frictionless.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerMeta' ) );

		// Task 3: stamp the pending state on new accounts (only when ON).
		add_action( 'user_register', array( $this, 'maybeMarkPending' ), 20, 1 );

		// Task 4: gate login while pending — WP-native filter, never reimplemented.
		add_filter( 'wp_authenticate_user', array( $this, 'blockPendingLogin' ), 10, 1 );

		// Task 6: the Afrikaans-source approval/rejection email templates (toggle OFF).
		$this->registerEmailTemplates();

		// Task 5: the approval-queue admin screen (redakteur, ink_moderate).
		add_action( 'admin_menu', array( $this, 'registerQueueScreen' ) );

		// Task 6: the nonce-protected approve/reject write handlers (logged-in).
		add_action( 'admin_post_' . self::ACTION_APPROVE, array( $this, 'approveViaPost' ) );
		add_action( 'admin_post_' . self::ACTION_REJECT, array( $this, 'rejectViaPost' ) );
	}

	/**
	 * Whether the manual-approval backstop is enabled (the single master switch).
	 *
	 * Fail-safe OFF (C8 / UJ-1): manual approval is never the launch default, so
	 * this accepts ON ONLY for an explicit truthy boolean/int/string value. An
	 * unset option (default `false`), a non-scalar (array/object from a corrupted
	 * row), or any other garbled value reads OFF — never accidentally ON.
	 *
	 * @return bool True only when a redakteur has explicitly enabled the backstop.
	 */
	public static function isEnabled(): bool {
		$raw = get_option( self::OPTION_ENABLED, false );

		if ( is_bool( $raw ) ) {
			return $raw;
		}

		if ( is_int( $raw ) ) {
			return 1 === $raw;
		}

		if ( is_string( $raw ) ) {
			return in_array( strtolower( $raw ), array( '1', 'true', 'on', 'yes' ), true );
		}

		// Non-scalar / garbled ⇒ OFF.
		return false;
	}

	/**
	 * Register the pending + rejected user-meta on the Story-2.3 substrate.
	 *
	 * Mirrors {@see Onboarding::registerMeta()} house style: `single`, boolean,
	 * a `false` registered default, an `is_scalar`-guarded sanitiser (the Epic-2
	 * recurring bug class — a non-scalar payload must never reach a scalar
	 * coercion), and an `auth_callback`. Unlike the self-set onboarding flag, the
	 * pending/rejected state is STAFF-managed (the system stamps it; only a
	 * redakteur clears it), so the `auth_callback` authorises the `ink_moderate`
	 * cap only — a member can never clear their own pending flag via REST.
	 */
	public function registerMeta(): void {
		$args = array(
			'single'            => true,
			'type'              => 'boolean',
			'show_in_rest'      => true,
			'default'           => false,
			'sanitize_callback' => array( self::class, 'sanitizeFlag' ),
			'auth_callback'     => array( self::class, 'authModerate' ),
		);

		register_meta( 'user', self::META_PENDING, $args );
		register_meta( 'user', self::META_REJECTED, $args );
	}

	/**
	 * Coerce any incoming value to a boolean flag (shared `is_scalar` guard).
	 *
	 * A non-scalar payload (array/object from a malformed REST/POST body) falls
	 * back to the `false` default rather than reaching a scalar coercion.
	 *
	 * @param mixed $value Incoming meta value.
	 * @return bool The normalised flag.
	 */
	public static function sanitizeFlag( $value ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return (bool) rest_sanitize_boolean( $value );
	}

	/**
	 * Authorise a write to the pending/rejected flag for moderators only.
	 *
	 * The pending state is staff-managed (NOT self-set, unlike onboarding): only a
	 * redakteur holding {@see Capabilities::MODERATE} may write it. This is the
	 * live, granted cap (Story 3.3 grants it to editor + admin at activation) — not
	 * a new ungranted "deny-everyone" stub.
	 *
	 * @return bool Whether the write is authorised.
	 */
	public static function authModerate(): bool {
		return current_user_can( Capabilities::MODERATE );
	}

	/**
	 * Stamp the pending-approval flag on a freshly-created account — ONLY when the
	 * backstop is ON (Task 3 / AC 1, 2).
	 *
	 * The frictionless guarantee (UJ-1): when OFF this writes NOTHING — no pending
	 * flag, no state — so a new account is immediately usable exactly as today.
	 *
	 * Trusted-bypass edge (documented decision): an account created in wp-admin by
	 * a redakteur (an actor holding {@see Capabilities::MODERATE}) is NOT marked
	 * pending — the approval queue exists to vet UNTRUSTED self-registration, and a
	 * redakteur creating an account has already exercised that judgement. Only
	 * self-service registrations (no moderating actor in context) enter the queue.
	 *
	 * THE conflation rule: writes ONLY the pending flag — no entitlement,
	 * membership, tier, promotion, or intent stamp.
	 *
	 * @param int $user_id The freshly-created user ID.
	 */
	public function maybeMarkPending( int $user_id ): void {
		if ( ! self::isEnabled() ) {
			return; // OFF ⇒ frictionless ⇒ stamp nothing.
		}

		if ( $user_id <= 0 ) {
			return;
		}

		// Trusted bypass: a moderator creating the account is not queued.
		if ( current_user_can( Capabilities::MODERATE ) ) {
			return;
		}

		update_user_meta( $user_id, self::META_PENDING, true );
	}

	/**
	 * Block login while an account is pending (or rejected) — WP-native (Task 4).
	 *
	 * Filters `wp_authenticate_user`: WordPress has already validated the
	 * credentials and passes the resolved `WP_User`; we only GATE that result. When
	 * the backstop is ON and the user is pending/rejected, we return a `WP_Error`
	 * carrying an Afrikaans-source message (zero English leakage). We never touch
	 * credentials, sessions, or hashing — the mechanism stays WordPress's.
	 *
	 * No-op contract: when the backstop is OFF, or the incoming value is already a
	 * `WP_Error` (WP rejected the credentials upstream), or the user is not
	 * pending/rejected, the value is returned unchanged. OFF therefore instantly
	 * un-gates any still-pending login (the frictionless-restore guarantee).
	 *
	 * @param mixed $user The WP_User (validated) or WP_Error from upstream filters.
	 * @return mixed The user unchanged, or a WP_Error blocking the pending login.
	 */
	public function blockPendingLogin( $user ) {
		if ( ! self::isEnabled() ) {
			return $user; // OFF ⇒ no-op ⇒ frictionless login.
		}

		// Only gate a successfully-resolved user; never override an upstream error.
		if ( ! ( $user instanceof \WP_User ) ) {
			return $user;
		}

		$user_id = (int) $user->ID;

		// Afrikaans-source messages (Gate D), human-authored in ui-copy-translations.md
		// (never AI-translated). A REJECTED account gets a DISTINCT code + message:
		// it is not "waiting for approval".
		if ( self::isRejected( $user_id ) ) {
			return new \WP_Error(
				self::ERROR_CODE_REJECTED,
				__( 'Jou rekening is ongelukkig afgekeur.', 'ink-core' )
			);
		}

		if ( self::isPending( $user_id ) ) {
			return new \WP_Error(
				self::ERROR_CODE,
				__( 'Jou rekening wag vir goedkeuring. Ons kyk binnekort daarna.', 'ink-core' )
			);
		}

		return $user;
	}

	/**
	 * Whether an account is currently login-blocked by the backstop.
	 *
	 * Blocked when its pending flag OR its rejected flag is set. (The toggle gate
	 * is applied by the callers — this is the pure meta read.)
	 *
	 * @param int $user_id The account.
	 * @return bool True when pending or rejected.
	 */
	public static function isBlocked( int $user_id ): bool {
		return self::isPending( $user_id ) || self::isRejected( $user_id );
	}

	/**
	 * Whether an account is awaiting approval (the pending flag is set).
	 *
	 * @param int $user_id The account.
	 */
	public static function isPending( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		return self::sanitizeFlag( get_user_meta( $user_id, self::META_PENDING, true ) );
	}

	/**
	 * Whether an account has been rejected (the rejected flag is set).
	 *
	 * @param int $user_id The account.
	 */
	public static function isRejected( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		return self::sanitizeFlag( get_user_meta( $user_id, self::META_REJECTED, true ) );
	}

	/**
	 * Approve a pending account — the single testable write path (Task 6 / AC 3).
	 *
	 * Clears the pending (and any rejected) flag so the lid can log in, then
	 * optionally dispatches the Afrikaans approval email (gated OFF until human
	 * copy lands — {@see registerEmailTemplates()}). THE conflation rule: clears
	 * ONLY the approval flags — no entitlement / tier / membership write.
	 *
	 * @param int $user_id The account being approved.
	 */
	public static function approve( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		delete_user_meta( $user_id, self::META_PENDING );
		delete_user_meta( $user_id, self::META_REJECTED );

		self::sendDecisionEmail( self::APPROVED_TEMPLATE_KEY, $user_id );
	}

	/**
	 * Reject a pending account — the single testable write path (Task 6 / AC 3).
	 *
	 * Reject behaviour (owner decision, 2026-06-22): mark the account BLOCKED
	 * rather than delete it. Deleting an account on a single click is irreversible
	 * and would destroy a legitimate mis-click's account (and any work) — so reject
	 * is the non-destructive "mark blocked" path: it clears the pending flag and
	 * sets the rejected flag, dropping the account off the queue while keeping it
	 * login-blocked WHILE THE BACKSTOP IS ON. There is deliberately NO in-product
	 * un-reject affordance (owner: none needed). Recovery for a mistaken rejection
	 * is the standard wp-admin path — delete the user (they re-register), or flip
	 * the backstop OFF, which un-gates EVERY pending/rejected account
	 * ({@see blockPendingLogin()} short-circuits when OFF). Optionally dispatches
	 * the Afrikaans rejection email (gated OFF until human copy).
	 *
	 * THE conflation rule: writes ONLY the approval flags.
	 *
	 * @param int $user_id The account being rejected.
	 */
	public static function reject( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		delete_user_meta( $user_id, self::META_PENDING );
		update_user_meta( $user_id, self::META_REJECTED, true );

		self::sendDecisionEmail( self::REJECTED_TEMPLATE_KEY, $user_id );
	}

	/**
	 * Dispatch a decision email through the Story-1.12 Notifications capability.
	 *
	 * Gated by the template's own send toggle (OFF by default until human copy
	 * lands), so no `wp_mail` fires today. Resolves the recipient from the account;
	 * a missing user / empty email is a graceful no-op (never fatals the write).
	 *
	 * @param string $template_key The Notifications template/event key.
	 * @param int    $user_id      The recipient account.
	 */
	private static function sendDecisionEmail( string $template_key, int $user_id ): void {
		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof \WP_User ) || '' === (string) $user->user_email ) {
			return;
		}

		// Fall back to the login name when display_name is empty, so the {skrywer}
		// greeting never renders as "Hallo ,". MergeResolver substitutes any provided
		// token — including an empty value — and only an absent key stays literal.
		$skrywer = (string) $user->display_name;
		if ( '' === $skrywer ) {
			$skrywer = (string) $user->user_login;
		}

		Notifications::send(
			$template_key,
			(string) $user->user_email,
			array( 'skrywer' => $skrywer )
		);
	}

	/**
	 * Register the Afrikaans-source approval + rejection email templates (Task 6).
	 *
	 * Mirrors {@see Registration::registerWelcomeTemplate()}: Afrikaans-source
	 * subject/body as LITERAL `__( …, 'ink-core' )` strings (extractable by
	 * `wp i18n make-pot`; no English `.mo` ships), the `{skrywer}` greeting token,
	 * and the per-event send toggle OFF by default. The bodies are the human-authored
	 * Afrikaans curated in `ui-copy-translations.md` (never AI-translated); staff
	 * enable the toggle deliberately to start sending.
	 */
	public function registerEmailTemplates(): void {
		Notifications::registerTemplate(
			new Template(
				self::APPROVED_TEMPLATE_KEY,
				__( 'Jou INK rekening is goedgekeur', 'ink-core' ),
				__( 'Hallo {skrywer}. Jou rekening is goedgekeur. Jy kan nou inteken en begin skryf.', 'ink-core' ),
				false
			)
		);

		Notifications::registerTemplate(
			new Template(
				self::REJECTED_TEMPLATE_KEY,
				__( 'Omtrent jou INK rekeningaansoek', 'ink-core' ),
				__( 'Hallo {skrywer}. Jou rekeningaansoek is ongelukkig afgekeur.', 'ink-core' ),
				false
			)
		);
	}

	/**
	 * Register the approval-queue admin screen under Users (Task 5).
	 *
	 * The plugin's first admin UI: a submenu under Users, its callback gated on the
	 * live {@see Capabilities::MODERATE} cap (granted to editor + admin at
	 * activation). WP-admin chrome only — no `theme.json`, no design-system, no
	 * Lovable work. `add_users_page` itself takes the capability as its gate, and
	 * {@see renderQueue()} re-checks it (defence in depth).
	 */
	public function registerQueueScreen(): void {
		add_users_page(
			Terms::label( 'account_approval_queue' ),
			Terms::label( 'account_approval_queue' ),
			Capabilities::MODERATE,
			self::SCREEN_SLUG,
			array( $this, 'renderQueue' )
		);
	}

	/**
	 * Render the approval-queue screen (Task 5).
	 *
	 * Capability-gated (defence in depth over the menu registration). Lists the
	 * accounts holding the pending flag with per-row goedkeur / verwerp controls,
	 * each posting to a nonce-protected `admin_post` handler. When the backstop is
	 * OFF the screen is still reachable by a redakteur but shows an "af" notice (no
	 * pending accounts exist) — the capability-gated chrome is never hidden behind
	 * the toggle (documented behaviour). All labels come from the terminology
	 * registry; all output is escaped.
	 *
	 * Rendering ends in markup output (no `exit`); the full rendered screen is
	 * verified by E2E (Story 18.8). The unit-testable LOGIC lives in
	 * {@see approve()} / {@see reject()} / {@see isEnabled()} / {@see isPending()}.
	 */
	public function renderQueue(): void {
		if ( ! current_user_can( Capabilities::MODERATE ) ) {
			wp_die( esc_html__( 'Jy het nie toestemming om hierdie bladsy te sien nie.', 'ink-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html( Terms::label( 'account_approval_queue' ) ) . '</h1>';

		$this->renderNotice();

		if ( ! self::isEnabled() ) {
			// Documented OFF-state: chrome stays reachable; no pending accounts exist.
			echo '<p>' . esc_html__( 'Die goedkeuring-backstop is af. Registrasie is vryevloei en geen rekeninge wag vir goedkeuring nie.', 'ink-core' ) . '</p>';
			echo '</div>';
			return;
		}

		$pending = self::pendingUsers();

		if ( array() === $pending ) {
			echo '<p>' . esc_html__( 'Geen rekeninge wag tans op goedkeuring nie.', 'ink-core' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<table class="wp-list-table widefat fixed striped">';
		echo '<thead><tr>';
		echo '<th>' . esc_html( Terms::label( 'lid' ) ) . '</th>';
		echo '<th>' . esc_html__( 'E-pos', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Aksies', 'ink-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $pending as $user ) {
			echo '<tr>';
			echo '<td>' . esc_html( (string) $user->display_name ) . '</td>';
			echo '<td>' . esc_html( (string) $user->user_email ) . '</td>';
			echo '<td>';
			$this->renderActionForm( self::ACTION_APPROVE, self::NONCE_APPROVE, (int) $user->ID, Terms::label( 'account_approve' ) );
			echo ' ';
			$this->renderActionForm( self::ACTION_REJECT, self::NONCE_REJECT, (int) $user->ID, Terms::label( 'account_reject' ) );
			echo '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';
	}

	/**
	 * Render the post-action status notice (Task 6 / AC 3, 4) — the Afrikaans
	 * "result notice" a redakteur sees after an approve/reject redirect.
	 *
	 * Display-only: reads our own `ink_notice` redirect marker (allowlisted, never
	 * used for any state change), so no nonce is required here. The notice copy is
	 * the human-authored Afrikaans curated in ui-copy-translations.md (not invented here).
	 */
	private function renderNotice(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only status from our own wp_safe_redirect; value is allowlisted, drives no write.
		if ( ! isset( $_GET['ink_notice'] ) || ! is_scalar( $_GET['ink_notice'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- see above.
		$notice = sanitize_key( wp_unslash( $_GET['ink_notice'] ) );

		$messages = array(
			'approved' => __( 'Jou rekening is goedgekeur.', 'ink-core' ),
			'rejected' => __( 'Jou rekening is afgekeur.', 'ink-core' ),
			'error'    => __( 'Iets het foutgegaan.', 'ink-core' ),
		);

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s</p></div>',
			esc_attr( 'error' === $notice ? 'error' : 'success' ),
			esc_html( $messages[ $notice ] )
		);
	}

	/**
	 * Render one nonce-protected approve/reject mini-form (escaped throughout).
	 *
	 * @param string $action       The `admin_post` action name.
	 * @param string $nonce_action The nonce action.
	 * @param int    $user_id      The target account.
	 * @param string $label        The (registry-sourced, Afrikaans) button label.
	 */
	private function renderActionForm( string $action, string $nonce_action, int $user_id, string $label ): void {
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
		echo '<input type="hidden" name="action" value="' . esc_attr( $action ) . '" />';
		echo '<input type="hidden" name="user_id" value="' . esc_attr( (string) $user_id ) . '" />';
		wp_nonce_field( $nonce_action, $nonce_action );
		echo '<button type="submit" class="button">' . esc_html( $label ) . '</button>';
		echo '</form>';
	}

	/**
	 * The accounts currently holding the pending flag (queue source).
	 *
	 * @return list<\WP_User>
	 */
	private static function pendingUsers(): array {
		$users = get_users(
			array(
				'meta_key'   => self::META_PENDING, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- low-volume admin queue; a pending account is short-lived.
				'meta_value' => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			)
		);

		return is_array( $users ) ? array_values( $users ) : array();
	}

	/**
	 * Handle the nonce-protected approve POST (Task 6 / AC 3, 5).
	 *
	 * The sanctioned (never raw) `$_POST` path, mirroring
	 * {@see Onboarding::completeViaPost()}: capability check → nonce verify →
	 * `is_scalar`-guarded sanitised user id → {@see approve()} → redirect back. No
	 * raw superglobal reaches a sanitiser without the `is_scalar` guard.
	 */
	public function approveViaPost(): void {
		$user_id = $this->guardWrite( self::NONCE_APPROVE );

		if ( $user_id > 0 ) {
			self::approve( $user_id );
			$this->redirectToQueue( 'approved' );
		}

		$this->redirectToQueue( 'error' );
	}

	/**
	 * Handle the nonce-protected reject POST (Task 6 / AC 3, 5).
	 */
	public function rejectViaPost(): void {
		$user_id = $this->guardWrite( self::NONCE_REJECT );

		if ( $user_id > 0 ) {
			self::reject( $user_id );
			$this->redirectToQueue( 'rejected' );
		}

		$this->redirectToQueue( 'error' );
	}

	/**
	 * Shared write-guard for the approve/reject handlers (AD-6 write contract).
	 *
	 * Capability (`ink_moderate`) + nonce + `is_scalar`-guarded sanitised user id,
	 * with NO raw `$_POST`/`$_GET`/`$_REQUEST` reaching a sanitiser. Returns the
	 * validated user id, or 0 when any gate fails (the caller then no-ops the write
	 * and simply redirects back).
	 *
	 * @param string $nonce_action The nonce action to verify.
	 * @return int The validated target user id, or 0 on any failure.
	 */
	private function guardWrite( string $nonce_action ): int {
		if ( ! current_user_can( Capabilities::MODERATE ) ) {
			return 0;
		}

		if ( ! isset( $_POST[ $nonce_action ] ) || ! is_scalar( $_POST[ $nonce_action ] ) ) {
			return 0;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_action ] ) );

		if ( ! wp_verify_nonce( $nonce, $nonce_action ) ) {
			return 0;
		}

		if ( ! isset( $_POST['user_id'] ) || ! is_scalar( $_POST['user_id'] ) ) {
			return 0;
		}

		return absint( wp_unslash( $_POST['user_id'] ) );
	}

	/**
	 * Redirect back to the approval-queue screen (ends the request).
	 *
	 * Carries an optional `ink_notice` marker so {@see renderNotice()} can show the
	 * Afrikaans result notice (approved / rejected / error). The marker is
	 * display-only and drives no state.
	 *
	 * @param string $notice One of 'approved' | 'rejected' | 'error' | '' (none).
	 */
	private function redirectToQueue( string $notice = '' ): void {
		$url = admin_url( 'users.php?page=' . self::SCREEN_SLUG );

		if ( '' !== $notice ) {
			$url = add_query_arg( 'ink_notice', $notice, $url );
		}

		wp_safe_redirect( $url );
		exit;
	}
}
