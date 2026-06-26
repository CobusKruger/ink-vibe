<?php
/**
 * Unit tests for the custom Skryf submission-form handler (Story 6.1, FR-16).
 *
 * Target: {@see \Ink\Submission\SubmissionForm} — the logged-in `admin-post` write
 * path that validates a chosen bydrae type + title + body and creates a draft
 * (konsep) bydrae. These pin the type allowlist (gedig/storie/artikel ONLY —
 * `skryfwerk`, the migration bucket, and any tampered value are rejected), the
 * required-field validation, and the fail-safe handler (a logged-out request or a
 * bad nonce performs NO `wp_insert_post`).
 *
 * Brain Monkey, no WordPress/DB. The pure validation core ({@see buildDraft()}) is
 * tested directly; the effectful {@see handlePost()} mocks the WP globals and uses
 * a test subclass that neuters the redirect's `exit` while still letting flow
 * return — the SubmissionGate precedent.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\SubmissionForm;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	$_POST = array();
	Monkey\tearDown();
} );

/**
 * A SubmissionForm whose redirect does not terminate the test process.
 */
function ink_skryf_form(): SubmissionForm {
	return new class() extends SubmissionForm {
		/** @var list<string> */
		public array $redirected = array();

		public bool $canPublishReturn = true;

		protected function canPublish( int $user_id ): bool {
			return $this->canPublishReturn;
		}

		protected function formUrl( string $notice = '' ): string {
			return '' === $notice ? '/skryf/' : '/skryf/?ink_skryf=' . $notice;
		}

		protected function successUrl( int $post_id ): string {
			return '/skryf/?ink_skryf=geplaas&id=' . $post_id;
		}

		protected function redirect( string $url ): void {
			$this->redirected[] = $url;
			// No wp_safe_redirect / exit — record the target and let flow return.
		}
	};
}

/**
 * The submittable types are exactly the three user-facing bydrae CPTs — the
 * `skryfwerk` migration bucket is NOT submittable.
 */
test( 'submittableTypes is gedig/storie/artikel and excludes skryfwerk', function (): void {
	expect( SubmissionForm::submittableTypes() )->toBe( array( 'gedig', 'storie', 'artikel' ) );
	expect( SubmissionForm::submittableTypes() )->not->toContain( 'skryfwerk' );
} );

/**
 * The template/test accessors expose the single-source constants.
 */
test( 'accessors expose the nonce + post-action single source', function (): void {
	expect( SubmissionForm::nonceAction() )->toBe( SubmissionForm::NONCE_ACTION );
	expect( SubmissionForm::nonceName() )->toBe( SubmissionForm::NONCE_NAME );
	expect( SubmissionForm::postAction() )->toBe( SubmissionForm::POST_ACTION );
} );

/**
 * A valid submission of each type builds a draft bydrae of the chosen CPT.
 */
test( 'buildPost builds a draft post array for each valid type', function (): void {
	$form = new SubmissionForm();

	foreach ( array( 'gedig', 'storie', 'artikel' ) as $type ) {
		$arr = $form->buildPost( $type, 'My titel', 'Reël een', 7 );

		expect( $arr )->toBeArray();
		expect( $arr['post_type'] )->toBe( $type );
		expect( $arr['post_title'] )->toBe( 'My titel' );
		expect( $arr['post_content'] )->toBe( 'Reël een' );
		expect( $arr['post_status'] )->toBe( 'draft' );
		expect( $arr['post_author'] )->toBe( 7 );
	}
} );

/**
 * buildPost honours an explicit status (Story 6.7 publish path).
 */
test( 'buildPost honours an explicit publish status', function (): void {
	$arr = ( new SubmissionForm() )->buildPost( 'gedig', 'T', 'B', 7, 'publish' );

	expect( $arr )->toBeArray();
	expect( $arr['post_status'] )->toBe( 'publish' );
} );

/**
 * statusForIntent maps plaas to publish; anything else is the ungated draft.
 */
test( 'statusForIntent maps plaas to publish and else to draft', function (): void {
	expect( SubmissionForm::statusForIntent( 'plaas' ) )->toBe( 'publish' );
	expect( SubmissionForm::statusForIntent( 'konsep' ) )->toBe( 'draft' );
	expect( SubmissionForm::statusForIntent( '' ) )->toBe( 'draft' );
	expect( SubmissionForm::statusForIntent( 'tamper' ) )->toBe( 'draft' );
} );

/**
 * The migration bucket and any unknown type are rejected — no post array.
 */
test( 'buildPost rejects skryfwerk and unknown types', function (): void {
	$form = new SubmissionForm();

	expect( $form->buildPost( 'skryfwerk', 'T', 'B', 7 ) )->toBeInstanceOf( \WP_Error::class );
	expect( $form->buildPost( 'gibberish', 'T', 'B', 7 ) )->toBeInstanceOf( \WP_Error::class );
	expect( $form->buildPost( '', 'T', 'B', 7 ) )->toBeInstanceOf( \WP_Error::class );
} );

/**
 * Title and body are required — whitespace-only counts as empty.
 */
test( 'buildPost requires a non-empty title and body', function (): void {
	$form = new SubmissionForm();

	expect( $form->buildPost( 'gedig', '', 'B', 7 ) )->toBeInstanceOf( \WP_Error::class );
	expect( $form->buildPost( 'gedig', '   ', 'B', 7 ) )->toBeInstanceOf( \WP_Error::class );
	expect( $form->buildPost( 'gedig', 'T', '', 7 ) )->toBeInstanceOf( \WP_Error::class );
	expect( $form->buildPost( 'gedig', 'T', "  \n ", 7 ) )->toBeInstanceOf( \WP_Error::class );
} );

/**
 * Happy path: a logged-in, nonce-valid submission inserts the draft bydrae.
 */
test( 'handlePost inserts a draft bydrae on a valid submission', function (): void {
	$_POST = array(
		SubmissionForm::NONCE_NAME  => 'nonce123',
		SubmissionForm::FIELD_TYPE  => 'gedig',
		SubmissionForm::FIELD_TITLE => 'My Gedig',
		SubmissionForm::FIELD_BODY  => 'Reël een',
	);

	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_key' )->returnArg( 1 );
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );

	Functions\expect( 'wp_insert_post' )
		->once()
		->with(
			\Mockery::on(
				static fn( $arr ): bool => is_array( $arr )
					&& 'gedig' === $arr['post_type']
					&& 'draft' === $arr['post_status']
					&& 7 === $arr['post_author']
					&& 'My Gedig' === $arr['post_title']
			),
			true
		)
		->andReturn( 123 );

	$form = ink_skryf_form();
	$form->handlePost();

	expect( $form->redirected )->toBe( array( '/skryf/?ink_skryf=konsep-gestoor' ) );
} );

/**
 * On a `plaas` intent the bydrae is published and the success screen is shown.
 */
test( 'handlePost publishes and redirects to the success screen on plaas', function (): void {
	$_POST = array(
		SubmissionForm::NONCE_NAME    => 'nonce123',
		SubmissionForm::FIELD_TYPE    => 'gedig',
		SubmissionForm::FIELD_TITLE   => 'My Gedig',
		SubmissionForm::FIELD_BODY    => 'Reël een',
		SubmissionForm::INTENT_FIELD  => 'plaas',
	);

	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_key' )->returnArg( 1 );
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );

	Functions\expect( 'wp_insert_post' )
		->once()
		->with(
			\Mockery::on(
				static fn( $arr ): bool => is_array( $arr ) && 'publish' === $arr['post_status'] && 'gedig' === $arr['post_type']
			),
			true
		)
		->andReturn( 123 );

	$form = ink_skryf_form();
	$form->handlePost();

	expect( $form->redirected )->toBe( array( '/skryf/?ink_skryf=geplaas&id=123' ) );
} );

/**
 * THE conflation rule (Story 6.8): a non-entitled plaas is DENIED — the bydrae is
 * preserved as a konsep (draft, NOT published) and the writer is sent to the
 * denial state. The gate keys on entitlement only; the writer Gradering is never
 * consulted, so a lapsed Goud writer (canPublish=false) is denied just the same.
 */
test( 'handlePost denies a non-entitled plaas, preserving the bydrae as a draft', function (): void {
	$_POST = array(
		SubmissionForm::NONCE_NAME              => 'nonce123',
		SubmissionForm::FIELD_TYPE              => 'gedig',
		SubmissionForm::FIELD_TITLE             => 'My Gedig',
		SubmissionForm::FIELD_BODY              => 'Reël een',
		SubmissionForm::INTENT_FIELD            => 'plaas',
		// A ticked challenge: the denied path must NOT link it (if linkChallenges
		// ran it would hit unmocked get_post_type and fail — so a clean pass proves
		// the round entry is skipped on denial, the review-fix behaviour).
		\Ink\Submission\ChallengeLinking::FIELD => array( 5 ),
	);

	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_key' )->returnArg( 1 );
	Functions\when( 'wp_kses' )->returnArg( 1 );
	Functions\when( 'absint' )->alias( static fn( $value ): int => (int) $value );
	Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
	Functions\when( 'is_wp_error' )->alias( static fn( $thing ): bool => $thing instanceof \WP_Error );

	// Despite the plaas intent, the insert must be a DRAFT (text preserved).
	Functions\expect( 'wp_insert_post' )
		->once()
		->with(
			\Mockery::on(
				static fn( $arr ): bool => is_array( $arr ) && 'draft' === $arr['post_status']
			),
			true
		)
		->andReturn( 123 );

	$form                   = ink_skryf_form();
	$form->canPublishReturn = false; // no active lidmaatskap (e.g. lapsed Goud)
	$form->handlePost();

	expect( $form->redirected )->toBe( array( '/skryf/?ink_skryf=geen-toegang' ) );
} );

/**
 * Fail-safe: a logged-out request never inserts a post.
 */
test( 'handlePost does not insert when logged out', function (): void {
	$_POST = array( SubmissionForm::NONCE_NAME => 'nonce123' );

	Functions\when( 'get_current_user_id' )->justReturn( 0 );
	Functions\expect( 'wp_insert_post' )->never();

	$form = ink_skryf_form();
	$form->handlePost();

	expect( $form->redirected )->toBe( array( '/skryf/' ) );
} );

/**
 * Fail-safe: a bad nonce never inserts a post.
 */
test( 'handlePost does not insert on a bad nonce', function (): void {
	$_POST = array(
		SubmissionForm::NONCE_NAME  => 'bad',
		SubmissionForm::FIELD_TYPE  => 'gedig',
		SubmissionForm::FIELD_TITLE => 'T',
		SubmissionForm::FIELD_BODY  => 'B',
	);

	Functions\when( 'get_current_user_id' )->justReturn( 7 );
	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( false );
	Functions\expect( 'wp_insert_post' )->never();

	$form = ink_skryf_form();
	$form->handlePost();

	expect( $form->redirected )->toBe( array( '/skryf/' ) );
} );
