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
 * AC-1: the ONLY production writer of `ink_writer_tier` lives under src/Tiers
 * (the `Tiers::promote()` sole write path). No other module writes the grade;
 * Content/UserMeta only REGISTERS it (via register_meta, the Kernel-owned key).
 */
test( 'the only ink_writer_tier writer is in Ink\\Tiers', function (): void {
	$modules = glob( ABSPATH . 'wp-content/plugins/ink-core/src/*', GLOB_ONLYDIR );

	foreach ( $modules as $dir ) {
		foreach ( (array) glob( $dir . '/*.php' ) as $file ) {
			$src = (string) file_get_contents( $file );

			// A write is update_user_meta( …, <tier key> ). Only Tiers may do it.
			if ( false !== strpos( $src, 'update_user_meta' ) && false !== strpos( $src, 'WIN_COUNT_META_KEY' ) ) {
				expect( basename( $dir ) )->toBe( 'Tiers' );
			}
		}
	}
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
	Functions\when( 'get_option' )->justReturn( array() );
	Functions\when( 'get_userdata' )->justReturn( new \WP_User( 7, 'lid@ink.test', 'Jan', 'jan' ) );

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

	expect( $written_keys )->not->toContain( 'ink_writer_tier' );
	expect( $written_keys )->not->toContain( 'ink_tier_promoted_at' );
	expect( $written_keys )->not->toContain( 'ink_tier_win_count' );
} );
