<?php
/**
 * Unit tests for the optional featured-image upload (Story 6.4, FR-20).
 *
 * Targets: {@see \Ink\Submission\FeaturedImage} (the pure present/image predicates)
 * and {@see \Ink\Submission\SubmissionForm::attachFeaturedImage()} (the handler
 * integration, via a subclass that stubs the WP media seams). The story's promise
 * is "optional + non-fatal": these pin that an image attaches on success AND that
 * no file / a non-image / an upload error leaves the bydrae untouched (no thumbnail
 * set), so the writer's text is never lost to an image problem.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\FeaturedImage;
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
 * A SubmissionForm exposing attachFeaturedImage with stubbed media seams.
 */
function ink_image_form( int|\WP_Error $uploadReturn ): SubmissionForm {
	return new class( $uploadReturn ) extends SubmissionForm {
		public bool $mediaStackLoaded = false;
		/** @var list<array{0:string,1:int}> */
		public array $uploadCalls = array();
		/** @var int|\WP_Error */
		public $uploadReturn;

		public function __construct( int|\WP_Error $uploadReturn ) {
			$this->uploadReturn = $uploadReturn;
		}

		public function attach( int $post_id ): void {
			$this->attachFeaturedImage( $post_id );
		}

		protected function ensureMediaStack(): void {
			$this->mediaStackLoaded = true;
		}

		protected function mediaHandleUpload( string $field, int $post_id ) {
			$this->uploadCalls[] = array( $field, $post_id );
			return $this->uploadReturn;
		}
	};
}

test( 'isPresent is true only for an error-free non-empty upload', function (): void {
	expect( FeaturedImage::isPresent( array( 'error' => UPLOAD_ERR_OK, 'size' => 1234, 'name' => 'a.jpg' ) ) )->toBeTrue();
	expect( FeaturedImage::isPresent( array( 'error' => UPLOAD_ERR_NO_FILE, 'size' => 0, 'name' => '' ) ) )->toBeFalse();
	expect( FeaturedImage::isPresent( array( 'error' => UPLOAD_ERR_OK, 'size' => 0, 'name' => 'a.jpg' ) ) )->toBeFalse();
	expect( FeaturedImage::isPresent( array() ) )->toBeFalse();
} );

test( 'isImage gates on the client MIME prefix', function (): void {
	expect( FeaturedImage::isImage( array( 'type' => 'image/jpeg' ) ) )->toBeTrue();
	expect( FeaturedImage::isImage( array( 'type' => 'image/png' ) ) )->toBeTrue();
	expect( FeaturedImage::isImage( array( 'type' => 'text/plain' ) ) )->toBeFalse();
	expect( FeaturedImage::isImage( array() ) )->toBeFalse();
} );

test( 'attachFeaturedImage sets the thumbnail on a successful image upload', function (): void {
	$_FILES = array(
		FeaturedImage::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 2048, 'name' => 'p.jpg', 'type' => 'image/jpeg' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $t ): bool => $t instanceof \WP_Error );
	Functions\expect( 'set_post_thumbnail' )->once()->with( 99, 55 )->andReturn( true );

	$form = ink_image_form( 55 );
	$form->attach( 99 );

	expect( $form->mediaStackLoaded )->toBeTrue();
	expect( $form->uploadCalls )->toBe( array( array( FeaturedImage::FIELD, 99 ) ) );
} );

test( 'attachFeaturedImage does nothing when no file was uploaded', function (): void {
	$_FILES = array();

	Functions\expect( 'set_post_thumbnail' )->never();

	$form = ink_image_form( 55 );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array() );
	expect( $form->mediaStackLoaded )->toBeFalse();
} );

test( 'attachFeaturedImage ignores a non-image upload', function (): void {
	$_FILES = array(
		FeaturedImage::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 2048, 'name' => 'doc.txt', 'type' => 'text/plain' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\expect( 'set_post_thumbnail' )->never();

	$form = ink_image_form( 55 );
	$form->attach( 99 );

	expect( $form->uploadCalls )->toBe( array() );
} );

test( 'attachFeaturedImage is non-fatal when the upload errors', function (): void {
	$_FILES = array(
		FeaturedImage::FIELD => array( 'error' => UPLOAD_ERR_OK, 'size' => 2048, 'name' => 'p.jpg', 'type' => 'image/jpeg' ),
	);

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $t ): bool => $t instanceof \WP_Error );
	Functions\expect( 'set_post_thumbnail' )->never();

	$form = ink_image_form( new \WP_Error( 'upload_failed', 'nee' ) );
	$form->attach( 99 );

	// The media stack was reached, the upload was attempted, but no thumbnail set.
	expect( $form->uploadCalls )->toBe( array( array( FeaturedImage::FIELD, 99 ) ) );
} );
