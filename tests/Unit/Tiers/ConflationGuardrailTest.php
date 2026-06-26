<?php
/**
 * Conflation-rule guardrail tests (Story 5.6, FR-13 / AD-1 / AD-8).
 *
 * THE conflation rule: `Ink\Tiers` (Gradering → competition pools) and
 * `Ink\Entitlement` (lidmaatskap → submission) must never reference each other,
 * and there is no write path between `ink_writer_tier` and membership state.
 *
 * Deptrac enforces the typed dependency at CI; these tests ALSO catch a
 * stringly-typed leak (a hardcoded tier meta key written from the entitlement
 * layer) and assert behaviourally that a membership-state transition leaves the
 * writer's Gradering untouched.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Tiers;

use Ink\Entitlement\PurchaseActivation;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The absolute path to an ink-core src subdirectory (ABSPATH is the repo root,
 * defined by tests/bootstrap.php).
 */
function ink_src_dir( string $module ): string {
	return ABSPATH . 'wp-content/plugins/ink-core/src/' . $module;
}

/**
 * The file's PHP code with all comments / docblocks stripped, so the conflation
 * scan inspects real code references — NOT the docblocks that legitimately
 * describe the rule ("Ink\Tiers ⟂ Ink\Entitlement").
 */
function ink_code_only( string $file ): string {
	$code = '';

	foreach ( token_get_all( (string) file_get_contents( $file ) ) as $token ) {
		if ( is_array( $token ) ) {
			if ( T_COMMENT === $token[0] || T_DOC_COMMENT === $token[0] ) {
				continue;
			}
			$code .= $token[1];
		} else {
			$code .= $token;
		}
	}

	return $code;
}

/**
 * AC-1/AC-2: no `Ink\Tiers` CODE references `Ink\Entitlement` (comments excluded).
 */
test( 'Ink\\Tiers references no Ink\\Entitlement symbol', function (): void {
	$files = glob( ink_src_dir( 'Tiers' ) . '/*.php' );

	expect( $files )->not->toBeEmpty();

	foreach ( $files as $file ) {
		$code = ink_code_only( $file );
		expect( $code )->not->toContain( 'Entitlement' );
	}
} );

/**
 * AC-1/AC-2: no `Ink\Entitlement` CODE references `Ink\Tiers` or the tier meta
 * keys (comments excluded).
 */
test( 'Ink\\Entitlement references neither Ink\\Tiers nor the tier meta keys', function (): void {
	$files = glob( ink_src_dir( 'Entitlement' ) . '/*.php' );

	expect( $files )->not->toBeEmpty();

	foreach ( $files as $file ) {
		$code = ink_code_only( $file );
		expect( $code )->not->toContain( 'Tiers' );
		expect( $code )->not->toContain( 'ink_writer_tier' );
		expect( $code )->not->toContain( 'ink_tier_' );
	}
} );

/**
 * AC-1: only sanctioned writers persist a tier meta key.
 *
 * The sole writer of tier CHANGES is `Ink\Tiers\Api::promote()`. The ONE other
 * sanctioned writer is `Ink\Accounts\Registration` — it materialises the `brons`
 * DEFAULT at `user_register` (Story 3.3); it cannot route through `promote()`,
 * which no-ops a `brons → brons` set. Every other module is forbidden from
 * writing a tier meta key.
 *
 * Scans CODE ONLY (comments stripped) for `update_user_meta` co-occurring with a
 * tier grade/promoted-at/win-count key (literal OR a single-source constant), so
 * a stringly-typed leak (`'ink_writer_tier'`) is caught alongside the typed one.
 */
test( 'only Tiers and the Accounts registration default-setter write a tier meta key', function (): void {
	// Tier meta-key markers: the literals + the Kernel/UserMeta constants the
	// production writers actually use (Tiers\Api::promote() uses Tier::META_KEY /
	// PROMOTED_AT_META_KEY / WIN_COUNT_META_KEY; Accounts\Registration uses
	// UserMeta::WRITER_TIER).
	$tier_markers = array(
		'ink_writer_tier',
		'ink_tier_promoted_at',
		'ink_tier_win_count',
		'WRITER_TIER',
		'TIER_PROMOTED_AT',
		'PROMOTED_AT_META_KEY',
		'WIN_COUNT_META_KEY',
		'Tier::META_KEY',
	);

	// Module-relative paths permitted to write a tier meta key.
	$allowed = array( 'Tiers', 'Accounts/Registration.php' );

	// All src PHP: top-level, module, and one nested level (so a future subdir
	// cannot silently escape the scan).
	$base  = ABSPATH . 'wp-content/plugins/ink-core/src';
	$files = array_merge(
		(array) glob( $base . '/*.php' ),
		(array) glob( $base . '/*/*.php' ),
		(array) glob( $base . '/*/*/*.php' )
	);

	expect( $files )->not->toBeEmpty();

	$writers = array();

	foreach ( $files as $file ) {
		$code = ink_code_only( $file );

		if ( false === strpos( $code, 'update_user_meta' ) ) {
			continue;
		}

		$writes_tier = false;
		foreach ( $tier_markers as $marker ) {
			if ( false !== strpos( $code, $marker ) ) {
				$writes_tier = true;
				break;
			}
		}

		if ( ! $writes_tier ) {
			continue;
		}

		$rel = substr( $file, strpos( $file, '/src/' ) + 5 );

		$sanctioned = false;
		foreach ( $allowed as $allow ) {
			if ( $rel === $allow || str_starts_with( $rel, $allow . '/' ) ) {
				$sanctioned = true;
				break;
			}
		}

		$writers[] = $rel;

		// An unsanctioned module writing a tier meta key fails here (with $rel).
		expect( $sanctioned )->toBeTrue();
	}

	// Non-vacuous: the scan must actually have found the known writers
	// (Tiers\Api + Accounts\Registration), else a path/marker regression has
	// silently disabled the guardrail.
	expect( $writers )->toContain( 'Tiers/Api.php' );
	expect( $writers )->toContain( 'Accounts/Registration.php' );
} );

/**
 * A minimal WC user-membership double (only `get_user_id()` is read).
 */
function ink_guardrail_membership( int $user_id ): object {
	return new class( $user_id ) {
		public function __construct( private int $user_id ) {}
		public function get_user_id(): int {
			return $this->user_id;
		}
	};
}

/**
 * AC-1/AC-2: no membership-state transition writes `ink_writer_tier` — the
 * writer's Gradering is untouched by activation/expiry/cancellation/pause.
 */
test( 'a membership-state transition never writes the writer tier', function (): void {
	// Capture every meta write the entitlement transition handler makes.
	$written_keys = array();
	Functions\when( 'update_user_meta' )->alias(
		function ( int $id, string $key, $value ) use ( &$written_keys ): bool {
			$written_keys[] = $key;
			return true;
		}
	);

	// Activation path needs these; the toggle stays OFF so no mail is sent.
	// get_userdata is asserted (not merely stubbed) so the test PROVES the
	// active-transition handler body actually executed past its early return into
	// sendActivationEmail() — i.e. the negative tier-write assertions below are a
	// real regression guard, not a vacuous pass on an empty array.
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\expect( 'get_userdata' )
		->atLeast()->once()
		->andReturn( new \WP_User( 7, 'lid@ink.test', 'Jan', 'jan' ) );

	$activation = new PurchaseActivation();
	$membership = ink_guardrail_membership( 7 );

	foreach ( array(
		array( '', 'active' ),
		array( 'expired', 'active' ),
		array( 'active', 'expired' ),
		array( 'active', 'cancelled' ),
		array( 'active', 'paused' ),
	) as $transition ) {
		$activation->onMembershipStatusChanged( $membership, $transition[0], $transition[1] );
	}

	// The handler writes no tier meta on any membership-state transition (it only
	// sends the activation email). A future regression adding a tier write here
	// would surface in $written_keys.
	expect( $written_keys )->not->toContain( 'ink_writer_tier' );
	expect( $written_keys )->not->toContain( 'ink_tier_promoted_at' );
	expect( $written_keys )->not->toContain( 'ink_tier_win_count' );
} );
