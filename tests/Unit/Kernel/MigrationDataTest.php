<?php
/**
 * Unit tests for where the migration's input files are read from.
 *
 * Target: {@see \Ink\Kernel\MigrationData::resolve()} — a file in the configured
 * `INK_MIGRATION_DATA_DIR` folder wins; anything else falls back to the plugin's own
 * `migration-data/` folder. Pure: real files in a temp folder, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\MigrationData;

beforeEach( function (): void {
	$this->dir = sys_get_temp_dir() . '/ink-migration-data-' . uniqid();
	mkdir( $this->dir );
	file_put_contents( $this->dir . '/writer-accounts.csv', "name,user_login\n" );
} );

afterEach( function (): void {
	@unlink( $this->dir . '/writer-accounts.csv' );
	@rmdir( $this->dir );
} );

it( 'reads a file from the configured folder when it is there', function (): void {
	expect( MigrationData::resolve( 'writer-accounts.csv', $this->dir, '/plugin/migration-data' ) )
		->toBe( $this->dir . '/writer-accounts.csv' );
} );

it( 'tolerates a trailing separator on the configured folder', function (): void {
	expect( MigrationData::resolve( 'writer-accounts.csv', $this->dir . '/', '/plugin/migration-data' ) )
		->toBe( $this->dir . '/writer-accounts.csv' );
} );

it( 'falls back to the plugin folder for a file the configured folder lacks', function (): void {
	expect( MigrationData::resolve( 'inkpols-uitgawes.csv', $this->dir, '/plugin/migration-data' ) )
		->toBe( '/plugin/migration-data/inkpols-uitgawes.csv' );
} );

it( 'uses the plugin folder when no folder is configured', function (): void {
	expect( MigrationData::resolve( 'writer-accounts.csv', '', '/plugin/migration-data' ) )
		->toBe( '/plugin/migration-data/writer-accounts.csv' );
} );
