<?php
/**
 * Unit tests for the read-only media verification (Story 16.10).
 *
 * Target: {@see \Ink\Migration\MediaVerifier} — classifies migrated attachments
 * and flags those whose backing file is missing. Pure class/summarise logic
 * (the "test the OUTCOME" rule), never WP attachment internals.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\MediaVerifier;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure class + summarise ---

test( 'mediaClassFor maps image/audio/video/pdf and falls through to other', function (): void {
	expect( MediaVerifier::mediaClassFor( 'image/jpeg' ) )->toBe( 'image' );
	expect( MediaVerifier::mediaClassFor( 'audio/mpeg' ) )->toBe( 'audio' );
	expect( MediaVerifier::mediaClassFor( 'video/mp4' ) )->toBe( 'video' );
	expect( MediaVerifier::mediaClassFor( 'application/pdf' ) )->toBe( 'pdf' );
	expect( MediaVerifier::mediaClassFor( 'application/zip' ) )->toBe( 'other' );
	expect( MediaVerifier::mediaClassFor( '' ) )->toBe( 'other' );
} );

test( 'summarise counts per class and flags ONLY the missing-file records', function (): void {
	$report = MediaVerifier::summarise(
		array(
			array( 'id' => 1, 'mime' => 'image/jpeg', 'exists' => true ),
			array( 'id' => 2, 'mime' => 'audio/mpeg', 'exists' => true ),
			array( 'id' => 3, 'mime' => 'application/pdf', 'exists' => false ), // missing
			array( 'id' => 4, 'mime' => 'image/png', 'exists' => false ),       // missing
		)
	);

	expect( $report['total'] )->toBe( 4 );
	expect( $report['by_class'] )->toBe( array( 'audio' => 1, 'image' => 2, 'pdf' => 1 ) );

	// Only the two absent files are flagged; the present ones are not.
	expect( $report['missing'] )->toHaveCount( 2 );
	expect( array_column( $report['missing'], 'id' ) )->toBe( array( 3, 4 ) );
	expect( $report['missing'][0]['class'] )->toBe( 'pdf' );
} );

test( 'summarise flags nothing when every file is present', function (): void {
	$report = MediaVerifier::summarise(
		array(
			array( 'id' => 1, 'mime' => 'video/mp4', 'exists' => true ),
		)
	);

	expect( $report['missing'] )->toBe( array() );
} );

// --- verify() over the seam ---

test( 'verify summarises the attachment records from the seam', function (): void {
	$verifier = new class() extends MediaVerifier {
		protected function attachmentRecords(): array {
			return array(
				array( 'id' => 1, 'mime' => 'image/jpeg', 'exists' => true ),
				array( 'id' => 2, 'mime' => 'application/pdf', 'exists' => false ),
			);
		}
	};

	$report = $verifier->verify();

	expect( $report['total'] )->toBe( 2 );
	expect( $report['missing'] )->toHaveCount( 1 );
} );
