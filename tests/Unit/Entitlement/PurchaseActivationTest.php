<?php
/**
 * Unit tests for the front-end PayFast purchase / self-activation seam (Story 4.2, FR-5/UJ-2).
 *
 * Target: {@see \Ink\Entitlement\PurchaseActivation} — the `ink-core` business
 * SEAM around the OFF-SITE PayFast flow. It (a) initiates a purchase of a Story-4.1
 * plan by handing off to the WooCommerce checkout / WC PayFast gateway, and (b)
 * REACTS to the WooCommerce Memberships activation transition
 * (`wc_memberships_user_membership_status_changed`, gated on `active`) to confirm
 * the betaalde lid and fire the thank-you/activation email trigger via the
 * Notifications API (placeholder Afrikaans-source template, send toggle OFF — Story
 * 4.8 owns the real copy).
 *
 * Brain Monkey, no WordPress/DB, no WooCommerce/WC-Memberships/WC-PayFast loaded and
 * NO network — every platform function + the activation hook + `wp_mail` are mocked.
 * Tests use the PayFast SANDBOX semantics only (no live gateway, no real endpoint).
 * The real `Ink\Entitlement\*` registry + the real `Ink\Notifications\*` store are
 * autoloaded so the asserted reuse / toggle behaviour is genuine.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Entitlement;

use Ink\Entitlement\LidmaatskapTerm;
use Ink\Entitlement\PurchaseActivation;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Notifier;
use Ink\Notifications\TemplateStore;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();

	// `__()` is identity in unit context (no .mo) so Afrikaans source renders.
	Functions\when( '__' )->returnArg( 1 );

	// Reset + wire a known Notifications store/notifier into the facade so each
	// test reads back registration/toggle state directly (the ApprovalTest precedent).
	$facade = new \ReflectionClass( Notifications::class );
	foreach ( array( 'store', 'notifier' ) as $prop ) {
		$facade->getProperty( $prop )->setValue( null, null );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Wire a fresh known store + notifier into the Notifications facade and return the
 * store, so a test can register templates through PurchaseActivation and inspect them.
 */
function ink_wire_notifications(): TemplateStore {
	$store    = new TemplateStore();
	$notifier = new Notifier( $store );
	Notifications::bootstrap( $store, $notifier );

	return $store;
}

/**
 * Drive the activation template's send TOGGLE on via a stored override READ (never a
 * write): `get_option` returns the templates-option row with the activation key
 * enabled, so {@see TemplateStore::isEnabled()} resolves true without `update_option`.
 */
function ink_enable_activation_toggle(): void {
	Functions\when( 'get_option' )->alias(
		function ( string $name, $default = false ) {
			if ( TemplateStore::OPTION === $name ) {
				return array(
					PurchaseActivation::ACTIVATED_TEMPLATE_KEY => array( 'enabled' => true ),
				);
			}
			return $default;
		}
	);
}

/**
 * A minimal WC Memberships user-membership double: exposes `get_user_id()` only,
 * the single thing the activation handler reads off it (the membership owner).
 */
function ink_membership_for( int $user_id ): object {
	return new class( $user_id ) {
		public function __construct( private int $user_id ) {}
		public function get_user_id(): int {
			return $this->user_id;
		}
	};
}

/**
 * A `\WP_User` user-data double (what `get_userdata()` returns in production): the
 * recipient + greeting source the activation email resolves. The handler now guards
 * with `instanceof \WP_User` (the Approval::sendDecisionEmail() house pattern), so the
 * double MUST be a genuine WP_User (the repo's tests/stubs/class-wp-user.php stub) for
 * the send path to be exercised.
 */
function ink_userdata( string $email, string $display = '', string $login = 'lid' ): \WP_User {
	return new \WP_User( 7, $email, $display, $login );
}

/**
 * A realistic `add_query_arg` stub (FIX 3): mimics WordPress's real `add_query_arg`
 * separator behaviour — it appends with `?` only when the URL has no query string,
 * and with `&` when one already exists. The naive `$url.'?'.$key.'='.$value` stub
 * masked the "checkout URL already has a query string" branch.
 *
 * @param string $key   The query arg key.
 * @param mixed  $value The query arg value.
 * @param string $url   The base URL.
 * @return string The URL with the arg appended (correct separator).
 */
function ink_add_query_arg( string $key, $value, string $url ): string {
	$separator = ( false === strpos( $url, '?' ) ) ? '?' : '&';

	return $url . $separator . $key . '=' . $value;
}

/**
 * AC-6: the hook / status / template keys are the exact `ink_`-prefixed (or WC-own)
 * single-source constants — never scattered literals.
 */
test( 'the hook, status and template keys are the exact single-source constants', function (): void {
	expect( PurchaseActivation::HOOK_STATUS_CHANGED )->toBe( 'wc_memberships_user_membership_status_changed' );
	expect( PurchaseActivation::STATUS_ACTIVE )->toBe( 'active' );
	expect( PurchaseActivation::ACTIVATED_TEMPLATE_KEY )->toBe( 'ink_membership_activated_email' );
} );

/**
 * AC-6: register() wires the WC Memberships activation listener and registers the
 * activation email template (toggle OFF). Mirrors Approval::register() wiring.
 */
test( 'register() wires the activation listener and registers the email template', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	$store = ink_wire_notifications();

	( new PurchaseActivation() )->register();

	expect(
		has_action(
			PurchaseActivation::HOOK_STATUS_CHANGED,
			'Ink\Entitlement\PurchaseActivation->onMembershipStatusChanged()'
		)
	)->not->toBeFalse();

	// The activation template is registered (and OFF).
	expect( $store->isRegistered( PurchaseActivation::ACTIVATED_TEMPLATE_KEY ) )->toBeTrue();
	expect( $store->isEnabled( PurchaseActivation::ACTIVATED_TEMPLATE_KEY ) )->toBeFalse();
} );

/**
 * AC-1/AC-2 (the core guarantee): a transition INTO `active` self-activates and
 * fires the email trigger EXACTLY ONCE — one activation, one send attempt.
 */
test( 'a transition into active fires the activation email trigger exactly once', function (): void {
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );

	ink_wire_notifications();

	// Enable the toggle so a send WOULD dispatch (driven by a get_option READ), then
	// register the template and assert wp_mail is called exactly once (one trigger).
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle();

	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * AC-1/AC-2 (idempotency): a NON-active transition (or a no-op save) does NOTHING —
 * no email trigger, no double-fire. The handler reacts only to `→ active`.
 */
test( 'a non-active transition fires nothing (idempotent, no double-send)', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );

	ink_wire_notifications();
	( new PurchaseActivation() )->registerEmailTemplate();

	// No recipient resolution, no dispatch, for any non-active transition.
	Functions\expect( 'get_userdata' )->never();
	Functions\expect( 'wp_mail' )->never();

	$activation = new PurchaseActivation();
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'active', 'expired' );   // deactivation.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', 'cancelled' ); // never active.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'active', 'active' );      // no-op save.
} );

/**
 * AC-2: the activation template registers Afrikaans-source with the send toggle OFF
 * by default — so NO wp_mail fires until Story 4.8 lands the curated copy and turns
 * it on. The body carries the {skrywer} greeting. (Reads back via the known store —
 * an unregistered template is ALSO fail-safe OFF, so a send()-only assertion would
 * pass green even if registration broke; the ApprovalTest precedent.)
 */
test( 'the activation email registers Afrikaans-source, DISABLED, and does not send by default', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );
	Functions\expect( 'wp_mail' )->never(); // toggle OFF ⇒ no dispatch.

	$store = ink_wire_notifications();

	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();

	$key = PurchaseActivation::ACTIVATED_TEMPLATE_KEY;
	expect( $store->isRegistered( $key ) )->toBeTrue();
	expect( $store->isEnabled( $key ) )->toBeFalse();
	expect( $store->body( $key ) )->toContain( '{skrywer}' );
	expect( $store->body( $key ) )->toContain( '[WAG OP MENSLIKE KOPIE]' );

	// Afrikaans-source, zero English leakage in the subject.
	$subject = strtolower( $store->subject( $key ) );
	expect( $subject )->not->toContain( 'membership' );
	expect( $subject )->not->toContain( 'activated' );
	expect( $subject )->not->toContain( 'thank' );

	// With the toggle OFF, an activation fires the trigger but NOTHING dispatches.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * AC-2: a missing user / empty email is a graceful no-op — never a fatal, never a
 * dispatch with an empty recipient.
 */
test( 'an activation for a missing user is a graceful no-op', function (): void {
	Functions\when( 'get_userdata' )->justReturn( false ); // no such user.
	Functions\expect( 'wp_mail' )->never();

	ink_wire_notifications();

	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle(); // even ON, no recipient ⇒ no send.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * FIX 1 (review): a non-`WP_User` truthy object returned from `get_userdata()` (e.g.
 * a filtered / unexpected return) is REJECTED by the `instanceof \WP_User` guard — it
 * must NOT trigger `wp_mail`, even with the toggle ON and a non-empty `user_email`.
 */
test( 'a non-WP_User get_userdata return does not trigger wp_mail', function (): void {
	// A truthy object that LOOKS like user-data (has user_email) but is NOT a WP_User.
	Functions\when( 'get_userdata' )->justReturn(
		new class() {
			public string $user_email   = 'spoof@ink.test';
			public string $display_name = 'Spoof';
			public string $user_login   = 'spoof';
		}
	);
	Functions\expect( 'wp_mail' )->never(); // non-WP_User ⇒ guarded out, no dispatch.

	ink_wire_notifications();

	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle(); // even ON, a non-WP_User recipient ⇒ no send.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * FIX 2 (review — by design, per Story 4.8 AC "a thank-you email is sent on EVERY
 * activation"): the trigger fires on a RENEWAL (`expired → active`) and a
 * REACTIVATION (`cancelled → active`), exactly as on a first activation — one send
 * per genuine transition INTO active. This is INTENDED and STATELESS (no persisted
 * "already-thanked" marker); the only no-op is the no-op `active → active` save.
 */
test( 'the activation email fires on renewal (expired→active) and reactivation (cancelled→active)', function (): void {
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );

	ink_wire_notifications();
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle();

	// Renewal: expired → active is a genuine transition INTO active ⇒ one send.
	Functions\expect( 'wp_mail' )->once()->andReturn( true );
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'expired', PurchaseActivation::STATUS_ACTIVE );
} );

test( 'the activation email fires on reactivation (cancelled→active)', function (): void {
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );

	ink_wire_notifications();
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle();

	// Reactivation: cancelled → active is a genuine transition INTO active ⇒ one send.
	Functions\expect( 'wp_mail' )->once()->andReturn( true );
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'cancelled', PurchaseActivation::STATUS_ACTIVE );
} );

test( 'the activation email does NOT fire on a no-op active→active save', function (): void {
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );
	Functions\expect( 'wp_mail' )->never(); // active → active is not a genuine transition.

	ink_wire_notifications();
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle();

	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'active', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * A WC membership double that ALSO exposes `get_plan()->get_product_ids()`, so the
 * Story-4.8 per-term activation toggle can resolve the term (4.1 mapping inverse).
 *
 * @param list<int> $product_ids The plan's product ids.
 */
function ink_membership_with_plan( int $user_id, array $product_ids ): object {
	$plan = new class( $product_ids ) {
		/** @param list<int> $ids */
		public function __construct( private array $ids ) {}
		/** @return list<int> */
		public function get_product_ids(): array {
			return $this->ids;
		}
	};

	return new class( $user_id, $plan ) {
		public function __construct( private int $user_id, private object $plan ) {}
		public function get_user_id(): int {
			return $this->user_id;
		}
		public function get_plan(): object {
			return $this->plan;
		}
	};
}

/**
 * Story 4.8 (AC-2/AC-4): when the activated membership's term resolves, the activation
 * thank-you is gated on the PER-TERM toggle. With the 4.1 map present (6mo→106) and the
 * (thank-you, 6mo) per-term toggle OFF (fail-safe), NO wp_mail fires even though the base
 * activation template toggle is ON.
 */
test( 'the activation thank-you does NOT send when the per-term toggle is OFF (term resolves)', function (): void {
	Functions\when( 'get_option' )->alias(
		function ( string $name, $default = false ) {
			if ( 'ink_membership_plan_products' === $name ) {
				return array( 1 => 101, 6 => 106, 12 => 112 );
			}
			if ( TemplateStore::OPTION === $name ) {
				// Base activation toggle ON, but NO per-term row ⇒ per-term fail-safe OFF.
				return array( PurchaseActivation::ACTIVATED_TEMPLATE_KEY => array( 'enabled' => true ) );
			}
			return $default;
		}
	);
	Functions\when( 'apply_filters' )->returnArg( 2 );
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );
	Functions\expect( 'wp_mail' )->never(); // per-term (thank-you,6mo) OFF ⇒ no dispatch.

	ink_wire_notifications();
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();

	$activation->onMembershipStatusChanged( ink_membership_with_plan( 7, array( 106 ) ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * Story 4.8 (AC-2/AC-4): with the 4.1 map present, the base activation toggle ON, AND the
 * (thank-you, 6mo) per-term toggle ON, the activation thank-you dispatches exactly once.
 */
test( 'the activation thank-you sends once when BOTH the base and the per-term toggle are ON', function (): void {
	$term_key = \Ink\Entitlement\LifecycleEmails::toggleKeyFor( PurchaseActivation::ACTIVATED_TEMPLATE_KEY, LidmaatskapTerm::SixMonths );

	Functions\when( 'get_option' )->alias(
		function ( string $name, $default = false ) use ( $term_key ) {
			if ( 'ink_membership_plan_products' === $name ) {
				return array( 1 => 101, 6 => 106, 12 => 112 );
			}
			if ( TemplateStore::OPTION === $name ) {
				return array(
					PurchaseActivation::ACTIVATED_TEMPLATE_KEY => array( 'enabled' => true ),
					$term_key => array( 'enabled' => true ),
				);
			}
			return $default;
		}
	);
	Functions\when( 'apply_filters' )->returnArg( 2 );
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );
	Functions\expect( 'wp_mail' )->once()->andReturn( true );

	ink_wire_notifications();
	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();

	$activation->onMembershipStatusChanged( ink_membership_with_plan( 7, array( 106 ) ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * AC-1/AC-3: a malformed membership object (no `get_user_id()`) is a graceful no-op
 * — the handler never fatals and never reads a card / gateway field.
 */
test( 'a malformed membership object is a graceful no-op', function (): void {
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'get_userdata' )->never();
	Functions\expect( 'wp_mail' )->never();

	ink_wire_notifications();
	( new PurchaseActivation() )->registerEmailTemplate();

	$activation = new PurchaseActivation();
	// An object without get_user_id() — must not fatal.
	$activation->onMembershipStatusChanged( new \stdClass(), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * AC-1/AC-3: the activation handler writes NO card-shaped data anywhere — it never
 * calls update_user_meta / update_option during activation (the betaalde lid is WC
 * Memberships' record; ink-core only reacts + emails). PCI scope stays low.
 */
test( 'the activation handler stores nothing (no card data, no meta/option write)', function (): void {
	Functions\when( 'get_userdata' )->justReturn( ink_userdata( 'lid@ink.test', 'Jan', 'jan' ) );
	Functions\when( 'wp_mail' )->justReturn( true );

	// The activation reaction must persist NOTHING (no card data, no membership copy).
	Functions\expect( 'update_user_meta' )->never();
	Functions\expect( 'update_option' )->never();

	ink_wire_notifications();

	$activation = new PurchaseActivation();
	$activation->registerEmailTemplate();
	ink_enable_activation_toggle(); // toggle ON via a READ — no update_option write.
	$activation->onMembershipStatusChanged( ink_membership_for( 7 ), 'pending', PurchaseActivation::STATUS_ACTIVE );
} );

/**
 * AC-1/AC-4: the purchase initiation HANDS OFF to the WooCommerce checkout for a
 * Story-4.1 plan (reusing the plan registry) — it builds no card form and resolves
 * the WC product via the 4.1 mapping, returning the checkout URL.
 */
test( 'purchaseUrl hands off to the WooCommerce checkout for a valid 4.1 plan', function (): void {
	// 4.1 product map: 6-month term → product 106.
	Functions\when( 'get_option' )->justReturn( array( 6 => 106 ) );
	Functions\when( 'apply_filters' )->returnArg( 2 );
	Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://ink.test/betaal/' );
	Functions\when( 'add_query_arg' )->alias( __NAMESPACE__ . '\ink_add_query_arg' );

	// A published, valid-priced product so the plan is sellable (Api::isAvailable()).
	Functions\when( 'wc_get_product' )->alias(
		function ( int $id ) {
			return new class() {
				public function get_status(): string {
					return 'publish';
				}
				public function get_price(): string {
					return '300';
				}
			};
		}
	);

	$url = ( new PurchaseActivation() )->purchaseUrl( LidmaatskapTerm::SixMonths );

	expect( $url )->toBeString();
	expect( $url )->toContain( 'https://ink.test/betaal/' );
	expect( $url )->toContain( '106' ); // the mapped 4.1 WC product id, reused — not re-defined.
} );

/**
 * FIX 3 (review): when `wc_get_checkout_url()` already carries a query string, the
 * `add-to-cart` arg is appended with `&` (not a second `?`), so the resulting URL is
 * well-formed. Production calls the REAL `add_query_arg`; this closes the test blind
 * spot that the naive `?`-only stub masked.
 */
test( 'purchaseUrl appends add-to-cart with & when the checkout URL already has a query string', function (): void {
	Functions\when( 'get_option' )->justReturn( array( 6 => 106 ) );
	Functions\when( 'apply_filters' )->returnArg( 2 );
	// A checkout URL that ALREADY has a query string (e.g. a page-id permalink).
	Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://ink.test/?page_id=42' );
	Functions\when( 'add_query_arg' )->alias( __NAMESPACE__ . '\ink_add_query_arg' );

	Functions\when( 'wc_get_product' )->alias(
		function ( int $id ) {
			return new class() {
				public function get_status(): string {
					return 'publish';
				}
				public function get_price(): string {
					return '300';
				}
			};
		}
	);

	$url = ( new PurchaseActivation() )->purchaseUrl( LidmaatskapTerm::SixMonths );

	expect( $url )->toBe( 'https://ink.test/?page_id=42&add-to-cart=106' );
	expect( $url )->not->toContain( '?add-to-cart' ); // no malformed second `?`.
} );

/**
 * AC-4/AC-6: when WooCommerce is absent OR the plan is unavailable, purchaseUrl
 * degrades gracefully to null — no fatal, no invented endpoint, no live-gateway URL.
 */
test( 'purchaseUrl returns null when WooCommerce is absent or the plan is unavailable', function (): void {
	Functions\when( 'get_option' )->justReturn( array() ); // no plan mapping ⇒ unavailable.
	Functions\when( 'apply_filters' )->returnArg( 2 );

	// WooCommerce absent: the WC-availability seam is forced off via a subclass.
	$wc_absent = new class() extends PurchaseActivation {
		protected function isWooCommerceAvailable(): bool {
			return false;
		}
	};
	expect( $wc_absent->purchaseUrl( LidmaatskapTerm::OneMonth ) )->toBeNull();

	// WooCommerce present but no mapped/sellable product ⇒ still null.
	Functions\when( 'wc_get_product' )->justReturn( false );
	Functions\when( 'wc_get_checkout_url' )->justReturn( 'https://ink.test/betaal/' );
	expect( ( new PurchaseActivation() )->purchaseUrl( LidmaatskapTerm::OneMonth ) )->toBeNull();
} );

/**
 * Strip PHP comments + docblocks, leaving only executable CODE — so the static
 * scans below assert against logic, not explanatory prose (the 4.1/3.6 precedent).
 *
 * @param string $file Absolute path to a PHP source file.
 * @return string The concatenated code tokens (no comments).
 */
function ink_pa_code_only( string $file ): string {
	$code = '';

	foreach ( token_get_all( (string) file_get_contents( $file ) ) as $token ) {
		if ( is_array( $token ) ) {
			if ( in_array( $token[0], array( T_COMMENT, T_DOC_COMMENT ), true ) ) {
				continue;
			}
			$code .= $token[1];
			continue;
		}

		$code .= $token;
	}

	return $code;
}

/**
 * AC-3 (PCI / no secret): the purchase/activation CODE captures or references NO
 * card / cardholder field and NO PayFast credential as a literal — PayFast is
 * off-site, the card capture + ITN belong to the WC PayFast gateway. Static scan
 * over the comment-stripped source (logic only — a docblock may mention "card data"
 * while stating the rule).
 */
test( 'the purchase/activation code references no card field or PayFast secret literal', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/PurchaseActivation.php';
	expect( is_file( $file ) )->toBeTrue();

	$code = strtolower( ink_pa_code_only( $file ) );

	foreach ( array( 'cardnumber', 'card_number', "'pan'", 'cvv', 'cvc', 'cardholder', 'merchant_key', 'merchant_id', 'passphrase' ) as $forbidden ) {
		expect( $code )->not->toContain( $forbidden );
	}
} );

/**
 * AC-4 (sandbox only): the code targets NO live PayFast gateway endpoint as a
 * literal — initiation hands off to the WC checkout (WC owns the gateway endpoint +
 * the sandbox/live switch via .env). No `www.payfast.co.za` live host in the code.
 */
test( 'the code targets no live PayFast gateway endpoint literal', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/PurchaseActivation.php';
	$code = strtolower( ink_pa_code_only( $file ) );

	expect( $code )->not->toContain( 'payfast.co.za' ); // neither live nor sandbox host hardcoded.
} );

/**
 * AC-5 (THE conflation rule): the purchase/activation CODE carries ZERO reference to
 * `Ink\Tiers` — buying/activating a lidmaatskap never touches writer Gradering.
 * Scans the comment-stripped source so the conflation-rule DOC prose is not a false
 * positive.
 */
test( 'the purchase/activation code has no Ink\\Tiers coupling (conflation rule)', function (): void {
	$file = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/Entitlement/PurchaseActivation.php';
	$code = ink_pa_code_only( $file );

	expect( $code )->not->toContain( 'use Ink\Tiers' );
	expect( $code )->not->toContain( 'Ink\Tiers\\' );
	// And no writer-tier meta write from the membership path.
	expect( $code )->not->toContain( 'ink_writer_tier' );
} );
