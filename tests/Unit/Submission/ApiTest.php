<?php
/**
 * Unit tests for the Submission facade view-model (Story 6.1).
 *
 * Target: {@see \Ink\Submission\Api::formModel()} — the flat, escapable model the
 * theme's `ink_foundation_skryf_*` bridge hands the Skryf pattern. Pins that the
 * model carries the three submittable types (slug + Afrikaans noun) and the form
 * wiring (action, nonce, field names) sourced from the handler's single source.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\Api;
use Ink\Submission\SubmissionForm;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	// formModel() lists open challenges (Story 6.6) — no published uitdagings here.
	Functions\when( 'get_posts' )->justReturn( array() );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * The form model carries the wiring from the handler's single source.
 */
test( 'formModel exposes the handler wiring', function (): void {
	$model = Api::formModel();

	expect( $model['post_action'] )->toBe( SubmissionForm::POST_ACTION );
	expect( $model['nonce_action'] )->toBe( SubmissionForm::NONCE_ACTION );
	expect( $model['nonce_name'] )->toBe( SubmissionForm::NONCE_NAME );
	expect( $model['field_type'] )->toBe( SubmissionForm::FIELD_TYPE );
	expect( $model['field_title'] )->toBe( SubmissionForm::FIELD_TITLE );
	expect( $model['field_body'] )->toBe( SubmissionForm::FIELD_BODY );
} );

/**
 * The model lists exactly the three submittable types, each with a slug + label.
 */
test( 'formModel lists the three submittable types with labels', function (): void {
	$model = Api::formModel();

	expect( $model['types'] )->toHaveCount( 3 );

	$slugs = array_map( static fn( array $t ): string => $t['slug'], $model['types'] );
	expect( $slugs )->toBe( array( 'gedig', 'storie', 'artikel' ) );

	foreach ( $model['types'] as $type ) {
		expect( $type['label'] )->toBeString()->not->toBe( '' );
	}
} );

/**
 * Each type carries its counter mode (Story 6.2): gedig = lines+words, prose = words.
 */
test( 'formModel carries the per-type counter mode', function (): void {
	$modes = array();
	foreach ( Api::formModel()['types'] as $type ) {
		$modes[ $type['slug'] ] = $type['counter_mode'];
	}

	expect( $modes['gedig'] )->toBe( 'lines_words' );
	expect( $modes['storie'] )->toBe( 'words' );
	expect( $modes['artikel'] )->toBe( 'words' );
} );
