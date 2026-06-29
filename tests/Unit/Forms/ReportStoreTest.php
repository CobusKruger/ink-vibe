<?php
/**
 * Unit tests for the content-report store + enums (Story 18.4, §8).
 *
 * Target: {@see \Ink\Forms\ReportStore} schema/table single source +
 * {@see \Ink\Forms\ReportReason}/{@see \Ink\Forms\ReportTarget} value sets.
 * Brain-Monkey, no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Forms;

use Ink\Forms\ReportStore;
use Ink\Forms\ReportReason;
use Ink\Forms\ReportTarget;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- enums ---

test( 'ReportReason exposes the controlled vocabulary as its single source', function (): void {
	expect( ReportReason::values() )->toBe( array( 'kwetsend', 'spam', 'plagiaat', 'ander' ) );
} );

test( 'ReportTarget exposes the target kinds', function (): void {
	expect( ReportTarget::values() )->toBe( array( 'werk', 'resensie', 'reaksie' ) );
} );

test( 'ReportReason labels are non-empty Afrikaans for every case', function (): void {
	Functions\when( '__' )->returnArg( 1 );

	foreach ( ReportReason::cases() as $reason ) {
		expect( trim( $reason->label() ) )->not->toBe( '' );
	}
} );

// --- store schema (single source) ---

test( 'ReportStore::TABLE is the unprefixed single source', function (): void {
	expect( ReportStore::TABLE )->toBe( 'ink_reports' );
} );

test( 'tableName prefixes the table with the wpdb prefix', function (): void {
	global $wpdb;
	$wpdb = new class() {
		public string $prefix = 'wp_';
	};

	expect( ReportStore::tableName() )->toBe( 'wp_ink_reports' );
} );

test( 'schemaSql declares the table and its report columns', function (): void {
	global $wpdb;
	$wpdb = new class() {
		public string $prefix = 'wp_';
		public function get_charset_collate(): string {
			return 'DEFAULT CHARSET=utf8mb4';
		}
	};

	$sql = ReportStore::schemaSql();

	expect( $sql )->toContain( 'CREATE TABLE wp_ink_reports' );
	foreach ( array( 'object_type', 'object_id', 'reporter_id', 'reason', 'detail', 'status', 'created_at' ) as $col ) {
		expect( $sql )->toContain( $col );
	}
	// dbDelta convention: two spaces after PRIMARY KEY.
	expect( $sql )->toContain( 'PRIMARY KEY  (id)' );
} );

test( 'open is the default status single source', function (): void {
	expect( ReportStore::STATUS_OPEN )->toBe( 'oop' );
} );
