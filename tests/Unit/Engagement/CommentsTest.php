<?php
/**
 * Unit tests for the site-wide comment-disable layer.
 *
 * Target: {@see \Ink\Engagement\Comments} (feature 1.8).
 *
 * WIRED IN STORY 1.11. Authored ready-to-run by Story 1.8; Story 1.11 stood up
 * the foundational harness (Pest function API + Brain Monkey/WP_Mock, the
 * `tests/bootstrap.php` Brain Monkey lifecycle, `phpunit.xml` Unit testsuite)
 * and relocated this file to the repo-root `tests/` tree (architecture.md
 * lines 851, 963-966 — tests live at the repo root, not plugin-local; this
 * supersedes the placeholder plugin-local `tests/` location Story 1.8 used
 * while the harness did not yet exist). Uses the project's chosen unit stack
 * (project-context.md: "Many unit tests — ink-core rules with WP mocked
 * (Brain Monkey / WP_Mock, via Pest)").
 *
 * Harness assumptions (provided by tests/bootstrap.php):
 *  - Brain\Monkey is set up/torn down per test (beforeEach -> Monkey\setUp()).
 *  - `apply_filters` is stubbed by Brain Monkey to return its second argument by
 *    default (so an un-filtered `ink_comment_open_exception` returns its default
 *    `false`), and can be overridden per test with `Filters\expectApplied()`.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\Comments;
use Brain\Monkey;
use Brain\Monkey\Filters;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * AC-1: `comments_open` resolves to false for every post type / post id, no
 * matter the incoming stored value.
 */
test( 'closeComments returns false for comments_open across post types', function ( int $post_id, bool $incoming ): void {
	// Default behaviour: the exception filter is un-filtered, so it returns its
	// default (false) — commenting closed.
	Filters\expectApplied( Comments::FILTER_OPEN_EXCEPTION )
		->andReturnFirstArg();

	$comments = new Comments();

	expect( $comments->closeComments( $incoming, $post_id ) )->toBeFalse();
} )->with( [
	'post id 1, stored open'   => [ 1, true ],
	'post id 42, stored open'  => [ 42, true ],
	'post id 0, stored closed' => [ 0, false ],
	'page id 7, stored open'   => [ 7, true ],
] );

/**
 * AC-1: `pings_open` uses the same callback, so it likewise resolves to false.
 */
test( 'closeComments returns false for pings_open', function (): void {
	Filters\expectApplied( Comments::FILTER_OPEN_EXCEPTION )
		->andReturnFirstArg();

	$comments = new Comments();

	expect( $comments->closeComments( true, 99 ) )->toBeFalse();
} );

/**
 * AC-5: the sanctioned exception seam works — when a context filters
 * `ink_comment_open_exception` to true, the callback returns true for THAT
 * context (proving a later story can re-open a narrow path without re-enabling
 * site-wide commenting).
 */
test( 'closeComments honours the ink_comment_open_exception seam when filtered true', function (): void {
	Filters\expectApplied( Comments::FILTER_OPEN_EXCEPTION )
		->once()
		->andReturn( true );

	$comments = new Comments();

	expect( $comments->closeComments( false, 5 ) )->toBeTrue();
} );

/**
 * AC-5: with the default (unfiltered) exception, the seam stays closed.
 */
test( 'closeComments stays false when the exception seam returns its default', function (): void {
	Filters\expectApplied( Comments::FILTER_OPEN_EXCEPTION )
		->andReturnFirstArg();

	$comments = new Comments();

	expect( $comments->closeComments( true, 5 ) )->toBeFalse();
} );

/**
 * AC-5 (integration): programmatic `wp_insert_comment` is unaffected by this
 * layer — it bypasses `comments_open`/`pings_open` by design, so the sanctioned
 * custom comment types (`ink_reaksie`, `ink_moderator_terugvoer`) still write.
 * This real-WP assertion now has a home at
 * `tests/Integration/Engagement/CommentInsertionTest.php` (wp-env harness wired
 * in Story 1.11); the full integration assertion lands with its feature / the
 * Story 18.8 pyramid buildout, so it is out of scope for this mocked unit test.
 */
test( 'wp_insert_comment for sanctioned custom types is unaffected (see Integration suite)', function (): void {
	expect( true )->toBeTrue();
} )->skip( 'Asserted in tests/Integration/Engagement (wp-env); full integration buildout is Story 18.8.' );
