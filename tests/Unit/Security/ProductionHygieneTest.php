<?php
/**
 * Unit tests for the production-hygiene audit (Story 18.6, NFR-7).
 *
 * Target: {@see \Ink\Security\ProductionHygiene} — the pure forbiddenActive()
 * intersection + the forbidden-set single source + environment gating via the
 * overridable seam. Brain-Monkey, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Security;

use Ink\Security\ProductionHygiene;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure intersection ---

test( 'forbiddenActive returns exactly the forbidden plugins that are active', function (): void {
	$active = array(
		'ink-core/ink-core.php',
		'loco-translate/loco-translate.php',
		'woocommerce/woocommerce.php',
		'code-snippets/code-snippets.php',
	);

	$found = ProductionHygiene::forbiddenActive( $active, ProductionHygiene::FORBIDDEN_PLUGINS );

	expect( $found )->toContain( 'loco-translate/loco-translate.php' );
	expect( $found )->toContain( 'code-snippets/code-snippets.php' );
	// legitimate plugins are NOT flagged (non-vacuous).
	expect( $found )->not->toContain( 'woocommerce/woocommerce.php' );
	expect( $found )->not->toContain( 'ink-core/ink-core.php' );
} );

test( 'forbiddenActive returns empty when production is clean', function (): void {
	$active = array( 'ink-core/ink-core.php', 'woocommerce/woocommerce.php' );

	expect( ProductionHygiene::forbiddenActive( $active, ProductionHygiene::FORBIDDEN_PLUGINS ) )->toBe( array() );
} );

// --- the forbidden set ---

test( 'the forbidden set names the staging/authoring-only tools', function (): void {
	$set = ProductionHygiene::FORBIDDEN_PLUGINS;

	expect( $set )->toContain( 'loco-translate/loco-translate.php' );
	expect( $set )->toContain( 'code-snippets/code-snippets.php' );
	expect( $set )->toContain( 'wp-migrate-db/wp-migrate-db.php' );
	expect( $set )->toContain( 'string-locator/string-locator.php' );
} );

test( 'forbiddenSet honours the extension filter', function (): void {
	Functions\when( 'apply_filters' )->alias(
		static function ( string $hook, $value ) {
			if ( 'ink_security_forbidden_plugins' === $hook ) {
				return array_merge( (array) $value, array( 'extra-tool/extra-tool.php' ) );
			}
			return $value;
		}
	);

	$set = ( new ProductionHygiene() )->forbiddenSet();

	expect( $set )->toContain( 'extra-tool/extra-tool.php' );
	expect( $set )->toContain( 'loco-translate/loco-translate.php' );
} );

// --- environment gating (admin notice) ---

test( 'renderAdminNotice prints a warning when a forbidden plugin is active', function (): void {
	Functions\when( 'apply_filters' )->alias( static fn ( string $hook, $value ) => $value );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );

	$hygiene = new class() extends ProductionHygiene {
		protected function isProduction(): bool {
			return true;
		}
		protected function activePlugins(): array {
			return array( 'loco-translate/loco-translate.php' );
		}
	};

	ob_start();
	$hygiene->renderAdminNotice();
	$out = (string) ob_get_clean();

	expect( $out )->toContain( 'notice-error' );
	expect( $out )->toContain( 'loco-translate/loco-translate.php' );
} );

test( 'renderAdminNotice prints nothing when production is clean', function (): void {
	Functions\when( 'apply_filters' )->alias( static fn ( string $hook, $value ) => $value );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );

	$hygiene = new class() extends ProductionHygiene {
		protected function isProduction(): bool {
			return true;
		}
		protected function activePlugins(): array {
			return array( 'ink-core/ink-core.php' );
		}
	};

	ob_start();
	$hygiene->renderAdminNotice();
	$out = (string) ob_get_clean();

	expect( $out )->toBe( '' );
} );
