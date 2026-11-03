<?php
/**
 * Unit tests for which round the hero shows (owner, 2026-11-01).
 *
 * Target: {@see \Ink\Challenges\CurrentChallenge::current()}: the open round, else the
 * newest, and a closed round's payload carries no "Sluit" date.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\CurrentChallenge;
use Ink\Kernel\CadenceType;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	ink_reset_guard_spies();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'wp_date' )->alias( fn ( string $f, int $ts ): string => 'F' === $f ? ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( new \DateTimeZone( 'Africa/Johannesburg' ) )->format( 'F' ) : ( new \DateTimeImmutable( '@' . $ts ) )->setTimezone( new \DateTimeZone( 'Africa/Johannesburg' ) )->format( 'Y-m-d' ) );

	// Reset the per-request memo.
	$memo = new \ReflectionProperty( CurrentChallenge::class, 'memo_set' );
	$memo->setValue( null, false );
	\WP_Query::$ink_test_posts = array();
} );

afterEach( function (): void {
	\WP_Query::$ink_test_posts = array();
	Monkey\tearDown();
} );

/**
 * Stage rounds, newest first, and pin "now".
 *
 * @param array<int, array{0:string, 1:string}> $rounds Id → [title, post date].
 * @param string                                $now    "Now", SAST.
 */
function ink_current_rounds( array $rounds, string $now ): void {
	$posts = array();

	foreach ( array_keys( $rounds ) as $id ) {
		$post               = new \WP_Post();
		$post->ID           = $id;
		$post->post_type    = 'uitdaging';
		$post->post_content = 'Opdrag';
		$posts[]            = $post;
	}

	\WP_Query::$ink_test_posts = $posts;

	Functions\when( 'current_datetime' )->justReturn( new \DateTimeImmutable( $now, new \DateTimeZone( 'Africa/Johannesburg' ) ) );
	Functions\when( 'get_the_title' )->alias( fn ( $post ): string => $rounds[ is_object( $post ) ? $post->ID : $post ][0] );
	Functions\when( 'get_post_field' )->alias( fn ( string $field, int $id ): string => 'post_title' === $field ? $rounds[ $id ][0] : $rounds[ $id ][1] );
	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\when( 'get_permalink' )->alias( fn ( $post ): string => '/u/' . $post->ID . '/' );
	Functions\when( 'has_excerpt' )->justReturn( false );
	Functions\when( 'wp_strip_all_tags' )->returnArg( 1 );
	Functions\when( 'wp_trim_words' )->returnArg( 1 );
	Functions\when( 'add_filter' )->justReturn( true );
	Functions\when( 'remove_filter' )->justReturn( true );
}

test( 'the open round is returned, with its close in the payload', function (): void {
	ink_current_rounds(
		array(
			2 => array( 'November 2026 - Nuut', '2026-11-01 08:00:00' ),
			1 => array( 'Oktober 2026 - Oud', '2026-10-01 08:00:00' ),
		),
		'2026-11-15 12:00:00'
	);

	$payload = CurrentChallenge::current();

	expect( $payload['title'] )->toBe( 'November 2026 - Nuut' );
	expect( $payload['is_open'] )->toBeTrue();
	expect( $payload['deadline'] )->toBe( '2026-11-30' );
	expect( $payload['eyebrow'] )->toBe( 'November-uitdaging' );
} );

test( 'no round for this month: the newest round shows, with no Sluit date', function (): void {
	ink_current_rounds(
		array(
			2 => array( 'Oktober 2026 - Oud', '2026-10-01 08:00:00' ),
			1 => array( 'September 2026 - Ouer', '2026-09-01 08:00:00' ),
		),
		'2026-11-01 09:00:00'
	);

	$payload = CurrentChallenge::current();

	expect( $payload['title'] )->toBe( 'Oktober 2026 - Oud' );
	expect( $payload['is_open'] )->toBeFalse();
	expect( $payload['deadline'] )->toBe( '' );
	expect( $payload['eyebrow'] )->toBe( 'Oktober-uitdaging' );

	$html = CurrentChallenge::toHtml( $payload );

	expect( $html )->toContain( 'Oktober 2026 - Oud' );
	expect( $html )->not->toContain( 'Sluit' );
} );

test( 'the last day of the month is still open, inclusive', function (): void {
	ink_current_rounds( array( 1 => array( 'Oktober 2026 - Oud', '2026-10-01' ) ), '2026-10-31 23:59:00' );

	expect( CurrentChallenge::current()['is_open'] )->toBeTrue();
} );

test( 'a round posted before its month does not push out the one still running', function (): void {
	ink_current_rounds(
		array(
			2 => array( 'November 2026 - Nuut', '2026-10-30 08:00:00' ),
			1 => array( 'Oktober 2026 - Oud', '2026-10-01 08:00:00' ),
		),
		'2026-10-31 12:00:00'
	);

	expect( CurrentChallenge::current()['title'] )->toBe( 'Oktober 2026 - Oud' );
} );

test( 'a QA fixture is never the current round, even as the newest', function (): void {
	ink_current_rounds(
		array(
			2 => array( 'QA FIXTURE — November 2026', '2026-11-01' ),
			1 => array( 'Oktober 2026 - Oud', '2026-10-01' ),
		),
		'2026-11-15 12:00:00'
	);

	expect( CurrentChallenge::current()['title'] )->toBe( 'Oktober 2026 - Oud' );
} );

test( 'no round at all gives null, and the card collapses', function (): void {
	ink_current_rounds( array(), '2026-11-15 12:00:00' );

	expect( CurrentChallenge::current() )->toBeNull();
	expect( CurrentChallenge::toHtml( array() ) )->toBe( '' );
} );

test( 'isOpen is false with no close', function (): void {
	expect( CurrentChallenge::isOpen( null, CadenceType::Jaarliks, new \DateTimeImmutable() ) )->toBeFalse();
} );
