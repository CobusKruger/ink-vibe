<?php
/**
 * Unit tests for where the migration's input files are read from.
 *
 * Target: {@see \Ink\Kernel\MigrationData} — the one folder named by
 * `INK_MIGRATION_DATA_DIR`, with no fallback. Real files in a temp folder, no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Kernel;

use Ink\Kernel\MigrationData;

beforeEach( function (): void {
	$this->dir = sys_get_temp_dir() . '/ink-migration-data-' . uniqid();
	mkdir( $this->dir );

	foreach ( MigrationData::FILES as $file ) {
		file_put_contents( $this->dir . '/' . $file, "header\n" );
	}
} );

afterEach( function (): void {
	foreach ( MigrationData::FILES as $file ) {
		if ( file_exists( $this->dir . '/' . $file ) ) {
			unlink( $this->dir . '/' . $file );
		}
	}

	@rmdir( $this->dir );
} );

it( 'resolves a file inside the configured folder, with or without a trailing separator', function (): void {
	expect( MigrationData::resolve( 'writer-accounts.csv', '/data' ) )->toBe( '/data/writer-accounts.csv' );
	expect( MigrationData::resolve( 'writer-accounts.csv', '/data/' ) )->toBe( '/data/writer-accounts.csv' );
} );

it( 'resolves to nothing when no folder is configured', function (): void {
	expect( MigrationData::resolve( 'writer-accounts.csv', '' ) )->toBe( '' );
} );

it( 'reports no problems when every file is there', function (): void {
	expect( MigrationData::problems( $this->dir ) )->toBe( array() );
} );

it( 'names each missing file', function (): void {
	unlink( $this->dir . '/writer-aliases.csv' );

	$problems = MigrationData::problems( $this->dir );

	expect( $problems )->toHaveCount( 1 );
	expect( $problems[0] )->toContain( 'writer-aliases.csv' );
} );

it( 'reports an unconfigured folder', function (): void {
	expect( MigrationData::problems( '' )[0] )->toContain( 'INK_MIGRATION_DATA_DIR' );
} );

it( 'needs exactly the three owner-maintained files, and no code reads any other file name', function (): void {
	expect( MigrationData::FILES )->toBe( array( 'writer-accounts.csv', 'writer-aliases.csv', 'inkpols-uitgawes.csv' ) );

	$root    = dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src';
	$strays  = array();
	$files   = new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) );

	foreach ( $files as $file ) {
		if ( 'php' !== $file->getExtension() ) {
			continue;
		}

		foreach ( (array) file( $file->getPathname() ) as $line ) {
			$code = trim( (string) $line );

			if ( '' === $code || str_starts_with( $code, '*' ) || str_starts_with( $code, '//' ) || str_starts_with( $code, '/*' ) ) {
				continue;
			}

			if ( preg_match_all( "/'([A-Za-z0-9_.-]+\.csv)'/", $code, $found ) > 0 ) {
				foreach ( $found[1] as $name ) {
					if ( ! in_array( $name, MigrationData::FILES, true ) ) {
						$strays[] = $file->getFilename() . ': ' . $name;
					}
				}
			}
		}
	}

	expect( $strays )->toBe( array() );
} );
