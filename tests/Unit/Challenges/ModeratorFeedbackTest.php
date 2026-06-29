<?php
/**
 * Unit tests for moderator feedback + the writer display toggle (Story 12A.5, C5).
 *
 * Target: {@see \Ink\Challenges\ModeratorFeedback} — writes the custom comment type on
 * commit, and gates display on the work author's opt-in (the privacy control).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\ModeratorFeedback;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'recordForRound writes one comment per entry, skipping empty + already-fed', function (): void {
	$inserted = array();

	$mf = new class( $inserted ) extends ModeratorFeedback {
		public array $ins;
		/** @var array<int,bool> */
		public array $has = array( 11 => true ); // entry 11 already has feedback
		public function __construct( array &$ins ) {
			$this->ins = &$ins;
		}
		protected function hasFeedback( int $post_id ): bool {
			return ! empty( $this->has[ $post_id ] );
		}
		protected function insertComment( int $post_id, string $text ): int {
			$this->ins[] = array( $post_id, $text );
			return 5000 + $post_id;
		}
	};

	$commentary = array(
		array( 'post_id' => 10, 'title' => 'A', 'text' => 'Goeie beeldspraak.' ),
		array( 'post_id' => 11, 'title' => 'B', 'text' => 'Word oorgeslaan.' ), // already fed
		array( 'post_id' => 12, 'title' => 'C', 'text' => '   ' ),             // empty
		array( 'post_id' => 0, 'title' => 'D', 'text' => 'Geen pos.' ),         // no post
	);

	$written = $mf->recordForRound( 7, $commentary );

	expect( $written )->toBe( 1 );
	expect( $mf->ins )->toBe( array( array( 10, 'Goeie beeldspraak.' ) ) );
} );

test( 'feedbackFor returns the texts when the work author has the toggle ON', function (): void {
	$mf = new class() extends ModeratorFeedback {
		protected function authorOf( int $post_id ): int {
			return 100;
		}
		public function isDisplayEnabled( int $user_id ): bool {
			return 100 === $user_id; // author opted in
		}
		protected function commentsFor( int $post_id ): array {
			return array( 'Sterk slot.', 'Mooi ritme.' );
		}
	};

	expect( $mf->feedbackFor( 10 ) )->toBe( array( 'Sterk slot.', 'Mooi ritme.' ) );
} );

test( 'feedbackFor returns NOTHING when the author has the toggle OFF (non-vacuous privacy gate)', function (): void {
	// Non-vacuous: the SAME stored feedback exists; only the toggle differs.
	$mf = new class() extends ModeratorFeedback {
		protected function authorOf( int $post_id ): int {
			return 100;
		}
		public function isDisplayEnabled( int $user_id ): bool {
			return false; // author has NOT opted in
		}
		protected function commentsFor( int $post_id ): array {
			return array( 'Sterk slot.', 'Mooi ritme.' ); // feedback IS present
		}
	};

	expect( $mf->feedbackFor( 10 ) )->toBe( array() );
} );

test( 'isDisplayEnabled reads the user meta (fail-safe OFF)', function (): void {
	Functions\when( 'rest_sanitize_boolean' )->alias( fn ( $v ) => '1' === (string) $v || true === $v );

	Functions\when( 'get_user_meta' )->justReturn( '1' );
	expect( ( new ModeratorFeedback() )->isDisplayEnabled( 100 ) )->toBeTrue();

	expect( ( new ModeratorFeedback() )->isDisplayEnabled( 0 ) )->toBeFalse(); // non-positive id
} );

test( 'isDisplayEnabled is OFF when the meta is unset', function (): void {
	Functions\when( 'rest_sanitize_boolean' )->alias( fn ( $v ) => '1' === (string) $v || true === $v );
	Functions\when( 'get_user_meta' )->justReturn( '' );

	expect( ( new ModeratorFeedback() )->isDisplayEnabled( 100 ) )->toBeFalse();
} );

test( 'the custom comment type is the sanctioned exception (distinct from ink_reaksie)', function (): void {
	expect( ModeratorFeedback::COMMENT_TYPE )->toBe( 'ink_moderator_terugvoer' );
} );
