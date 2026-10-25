<?php
/**
 * Unit tests for the migrate-all orchestrator.
 *
 * Target: {@see \Ink\Migration\MigrateAll}. Process execution and state stubbed; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\MigrateAll;

/**
 * A MigrateAll whose commands are scripted.
 *
 * @param array<string, array{code:int, stdout:string, stderr:string}> $script  Per-command-prefix results.
 * @param array<string, array<string,string>>                          $state   Pre-existing state.
 */
function orchestrator( array $script, array $state = array() ): object {
	return new class( $script, $state ) extends MigrateAll {
		/** @var list<string> */
		public array $ran = array();
		/** @var array<string, array<string,string>> */
		public array $saved = array();

		public function __construct( private array $script, private array $stored ) {}

		protected function execute( string $command ): array {
			$this->ran[] = $command;
			foreach ( $this->script as $prefix => $result ) {
				if ( str_starts_with( $command, $prefix ) ) {
					return $result;
				}
			}
			return array( 'code' => 0, 'stdout' => "Success: 5 done.\n", 'stderr' => '' );
		}
		protected function fileExists( string $path ): bool {
			return true;
		}
		protected function loadState(): array {
			return $this->stored;
		}
		protected function saveState( array $state ): void {
			$this->saved = $state;
			$this->stored = $state;
		}
	};
}

test( 'the pipeline order puts posts before challenges and the redirects last-but-media', function (): void {
	$ids = array_map( static fn ( array $s ): string => $s[0], MigrateAll::steps() );

	expect( array_search( 'library-training', $ids, true ) )->toBeLessThan( array_search( 'posts', $ids, true ) );
	expect( array_search( 'posts', $ids, true ) )->toBeLessThan( array_search( 'challenges', $ids, true ) );
	expect( array_search( 'challenges', $ids, true ) )->toBeLessThan( array_search( 'challenge-entries', $ids, true ) );
	expect( array_search( 'recount', $ids, true ) )->toBeLessThan( array_search( 'generate-redirects', $ids, true ) );
	expect( end( $ids ) )->toBe( 'verify-media' );
} );

test( 'verdict flags silent and all-zero steps but accepts real work and already-done', function (): void {
	expect( MigrateAll::verdict( 'posts', '' ) )->toBe( MigrateAll::VERDICT_SILENT );
	expect( MigrateAll::verdict( 'challenges', 'Success: 0 geskep, 0 gekoppel.' ) )->toBe( MigrateAll::VERDICT_ALL_ZERO );
	expect( MigrateAll::verdict( 'posts', 'Success: 7 herklassifiseer, 0 oorgeslaan.' ) )->toBe( MigrateAll::VERDICT_OK );
	expect( MigrateAll::verdict( 'posts', 'Success: 0 herklassifiseer (oorgeslaan — reeds gedoen).' ) )->toBe( MigrateAll::VERDICT_OK );
	// Verification steps may legitimately print nothing / zeros.
	expect( MigrateAll::verdict( 'verify-media', '' ) )->toBe( MigrateAll::VERDICT_OK );
} );

test( 'pending skips succeeded steps on resume, and --from restarts at a step', function (): void {
	$state = array( 'sanitise' => array( 'status' => 'ok' ), 'users' => array( 'status' => 'ok' ), 'tiers' => array( 'status' => 'failed' ) );

	expect( MigrateAll::pending( $state )[0] )->toBe( 'tiers' );
	expect( MigrateAll::pending( $state, 'users' )[0] )->toBe( 'users' );
	expect( MigrateAll::pending( array() )[0] )->toBe( 'sanitise' );
} );

test( 'preflight needs no tier CSV, but stops when a given one is missing', function (): void {
	expect( MigrateAll::preflight( '', false ) )->toBe( array() );
	expect( MigrateAll::preflight( 'x.csv', false ) )->not->toBe( array() );
	expect( MigrateAll::preflight( 'x.csv', true ) )->toBe( array() );
} );

test( 'gradering is derived from the result posts unless a CSV path is given', function (): void {
	$by_id = static function ( string $tiers ): array {
		return array_column( array_map( static fn ( array $s ): array => array( 'id' => $s[0], 'cmd' => $s[1] ), MigrateAll::steps( $tiers ) ), 'cmd', 'id' );
	};

	expect( $by_id( '' )['tiers'] )->toBe( 'ink derive-tiers' );
	expect( $by_id( 'x.csv' )['tiers'] )->toBe( 'ink migrate-tiers {tiers}' );
} );

test( 'a clean run executes every step in order and records them', function (): void {
	$o      = orchestrator( array() );
	$report = $o->run( 'tier.csv' );

	expect( $report['failed'] )->toBeFalse();
	expect( count( $o->ran ) )->toBe( count( MigrateAll::steps() ) );
	expect( $o->ran[2] )->toBe( "ink migrate-tiers 'tier.csv'" );

	$derived = orchestrator( array() );
	$derived->run( '' );
	expect( $derived->ran[2] )->toBe( 'ink derive-tiers' );
	expect( $o->saved['verify-media']['status'] )->toBe( 'ok' );
} );

test( 'it stops at the first failing step and a re-run resumes there', function (): void {
	$o = orchestrator( array( 'ink migrate-posts' => array( 'code' => 1, 'stdout' => '', 'stderr' => 'boom' ) ) );

	$first = $o->run( 'tier.csv' );
	expect( $first['failed'] )->toBeTrue();
	expect( end( $o->ran ) )->toBe( 'ink migrate-posts' );
	expect( $o->saved['posts']['status'] )->toBe( 'failed' );

	$o->ran = array();
	$o2     = orchestrator( array(), $o->saved );
	$o2->run( 'tier.csv' );

	expect( $o2->ran[0] )->toBe( 'ink migrate-posts' ); // earlier steps skipped
} );

test( 'a silent step is flagged but does not stop the run', function (): void {
	$o      = orchestrator( array( 'ink migrate-challenges' => array( 'code' => 0, 'stdout' => '', 'stderr' => '' ) ) );
	$report = $o->run( 'tier.csv' );

	expect( $report['failed'] )->toBeFalse();
	expect( $report['flagged'] )->toBe( 1 );
	expect( $o->saved['challenges']['verdict'] )->toBe( MigrateAll::VERDICT_SILENT );
} );
