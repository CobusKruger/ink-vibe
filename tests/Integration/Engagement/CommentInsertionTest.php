<?php
/**
 * Integration test (real WP) for the comment-disable layer's design guarantee.
 *
 * SCAFFOLDED IN STORY 1.11, ASSERTED IN STORY 18.8. CommentsTest (unit) verifies
 * the `comments_open`/`pings_open` callbacks resolve closed; this integration
 * test is its real-WP counterpart: programmatic `wp_insert_comment` for the
 * sanctioned custom comment types (`ink_reaksie`, `ink_moderator_terugvoer`)
 * BYPASSES `comments_open` by design, so structured engagement still writes even
 * with site-wide commenting disabled. That assertion needs the wp-env WP test
 * library (Story 18.8 pyramid buildout); this file gives it a home against the
 * harness Story 1.11 stood up.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Integration\Engagement;

test( 'wp_insert_comment for sanctioned custom comment types bypasses comments_open', function (): void {
	expect( true )->toBeTrue();
} )->skip( 'Real-WP assertion: lands with the wp-env WP test library in Story 18.8. Home scaffolded in Story 1.11.' );
