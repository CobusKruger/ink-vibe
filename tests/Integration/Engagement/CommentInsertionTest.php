<?php
/**
 * Integration test (real WP) for the comment-disable layer's design guarantee.
 *
 * SCAFFOLDED IN STORY 1.11, ASSERTED IN STORY 18.8. CommentsTest (unit) verifies
 * the `comments_open`/`pings_open` callbacks resolve closed; this integration test
 * is its real-WP counterpart: programmatic `wp_insert_comment` for a sanctioned
 * custom comment type BYPASSES `comments_open` by design, so structured engagement
 * still writes even with site-wide commenting disabled.
 *
 * Runs INSIDE wp-env (real WP+DB), NOT a mocked unit test.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Integration\Engagement;

test( 'wp_insert_comment for a sanctioned custom comment type bypasses comments_open', function (): void {
	$post_id = wp_insert_post(
		array(
			'post_title'  => 'Toets-werk vir reaksies',
			'post_status' => 'publish',
			'post_type'   => 'post',
		)
	);

	expect( $post_id )->toBeInt();

	// Site-wide commenting is disabled, yet a sanctioned structured-engagement
	// comment type still writes (the AD-7 "comments are the only feedback path"
	// guarantee — structured engagement is NOT a WP comment_open concern).
	$comment_id = wp_insert_comment(
		array(
			'comment_post_ID' => (int) $post_id,
			'comment_content' => 'n Gestruktureerde reaksie.',
			'comment_type'    => 'ink_reaksie',
			'comment_approved' => 1,
		)
	);

	expect( $comment_id )->not->toBeFalse();
	expect( (int) $comment_id )->toBeGreaterThan( 0 );
} );
