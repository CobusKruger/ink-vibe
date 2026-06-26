<?php
/**
 * Unit tests for the optional audio/video attachment (Story 6.5, FR-21).
 *
 * Targets: {@see \Ink\Submission\MediaAttachment::isAudioVideo()} and
 * {@see \Ink\Submission\SubmissionForm::attachMedia()} (via the media-seam
 * subclass). The promise is "optional + non-fatal + stored as meta": pins that a
 * valid audio/video upload writes the `ink_media_attachment` meta on success, and
 * that no file / a non-AV file / an upload error writes NO meta.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\MediaAttachment;
use Ink\Submission\SubmissionForm;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	$_FILES = array();
	Monkey\tearDown();
} );

/**
 * A SubmissionForm exposing attachMedia with stubbed media seams.
 */
function ink_media_form( int|\WP_Error $uploadReturn ): SubmissionForm {
	return new class( $uploadReturn ) extends SubmissionForm {
		/** @var list<array{0:string,1:int}> */
		public array $uploadCalls = array();
		/** @var int|\WP_Error */
		public $uploadReturn;

		public function __construct( int|\WP_Error $uploadReturn ) {
			$this->uploadReturn = $uploadReturn;
		}

		public function attach( int $post_id ): void {
			$this->attachMedia( $post_id );
		}

		protected function ensureMediaStack(): void {}

		protected function mediaHandleUpload( string $field, int $post_id ) {
			$this->uploadCalls[] = array( $field, $post_id );
			return $this->uploadReturn;
		}
	};
}

test( 'isAudioVideo accepts audio and video, rejects others', function (): void {
	expect( MediaAttachment::isAudioVideo( array( 'type' => 'audio/mpeg' ) ) )->toBeTrue();
	expect( MediaAttachment::isAudioVideo( array( 'type' => 'video/mp4' ) ) )->toBeTrue();
	expect( MediaAttachment::isAudioVideo( array( 'type' => 'image/png' ) ) )->toBeFalse();
	expect( MediaAttachment::isAudioVideo( array( 'type' => 'application/pdf' ) ) )->toBeFalse();
} );

test( 'attachMedia stores the attachment id as bydrae meta on success', function (): void {
	$_FILES = array(
		MediaAttachment::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 4096, 'name' => 'v.mp4', 'type' => 'video/mp4' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $t ): bool => $t instanceof \WP_Error );
	Functions\expect( 'update_post_meta' )->once()->with( 99, MediaAttachment::META_KEY, 77 )->andReturn( true );

	$form = ink_media_form( 77 );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array( array( MediaAttachment::FIELD, 99 ) ) );
} );

test( 'attachMedia writes no meta when no file was uploaded', function (): void {
	$_FILES = array();

	Functions\expect( 'update_post_meta' )->never();

	$form = ink_media_form( 77 );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array() );
} );

test( 'attachMedia ignores a non-audio/video upload', function (): void {
	$_FILES = array(
		MediaAttachment::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 4096, 'name' => 'p.png', 'type' => 'image/png' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\expect( 'update_post_meta' )->never();

	$form = ink_media_form( 77 );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array() );
} );

test( 'attachMedia is non-fatal when the upload errors', function (): void {
	$_FILES = array(
		MediaAttachment::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 4096, 'name' => 'v.mp4', 'type' => 'video/mp4' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $t ): bool => $t instanceof \WP_Error );
	Functions\expect( 'update_post_meta' )->never();

	$form = ink_media_form( new \WP_Error( 'upload_failed', 'nee' ) );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array( array( MediaAttachment::FIELD, 99 ) ) );
} );
