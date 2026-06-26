<?php
/**
 * Unit tests for the shared upload predicates (Story 6.5, FR-20/FR-21).
 *
 * Target: {@see \Ink\Submission\Upload} — the single source reused by both
 * {@see \Ink\Submission\FeaturedImage} and {@see \Ink\Submission\MediaAttachment}.
 * Pure PHP, no Brain Monkey.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\Upload;

test( 'isPresent is true only for an error-free non-empty upload', function (): void {
	expect( Upload::isPresent( array( 'error' => UPLOAD_ERR_OK, 'size' => 10, 'name' => 'a.mp3' ) ) )->toBeTrue();
	expect( Upload::isPresent( array( 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0, 'name' => '' ) ) )->toBeFalse();
	expect( Upload::isPresent( array( 'error' => UPLOAD_ERR_OK, 'size' => 0, 'name' => 'a.mp3' ) ) )->toBeFalse();
	expect( Upload::isPresent( array( 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 10, 'name' => 'a.mp3' ) ) )->toBeFalse();
	expect( Upload::isPresent( array() ) )->toBeFalse();
} );

test( 'mimeStartsWith matches any of the given prefixes', function (): void {
	expect( Upload::mimeStartsWith( array( 'type' => 'image/png' ), 'image/' ) )->toBeTrue();
	expect( Upload::mimeStartsWith( array( 'type' => 'video/mp4' ), 'audio/', 'video/' ) )->toBeTrue();
	expect( Upload::mimeStartsWith( array( 'type' => 'audio/mpeg' ), 'audio/', 'video/' ) )->toBeTrue();
	expect( Upload::mimeStartsWith( array( 'type' => 'text/plain' ), 'audio/', 'video/' ) )->toBeFalse();
	expect( Upload::mimeStartsWith( array(), 'image/' ) )->toBeFalse();
} );
