<?php
/**
 * Unit tests for the Gemeenskapsreaksie list/form renderer (Story 7.4, FR-27).
 *
 * Target: {@see \Ink\Engagement\ResponsesList::toHtml()} — pure (Terms + escaping
 * only). `__`/`esc_*` are mocked as identity so the assertions are about OUR
 * structure: the count heading, the typed badges, escaped content, and the typed
 * form (the three enum radios + a submit).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Engagement;

use Ink\Engagement\ResponsesList;
use Ink\Kernel\ResponseType;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n, $d = 0 ): string => number_format( (float) $n, (int) $d ) );
	Functions\when( '_n' )->alias( static fn ( string $s, string $p, int $n ): string => 1 === $n ? $s : $p );

	if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
		define( 'MINUTE_IN_SECONDS', 60 );
	}
	if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
		define( 'HOUR_IN_SECONDS', 3600 );
	}
	if ( ! defined( 'DAY_IN_SECONDS' ) ) {
		define( 'DAY_IN_SECONDS', 86400 );
	}
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'toHtml renders a plain (non count-prefixed) heading, typed badges and escaped content', function (): void {
	$responses = array(
		array(
			'id'      => 1,
			'type'    => ResponseType::Lof,
			'content' => 'Pragtige beeldspraak.',
			'author'  => 'Lid Een',
			'date'    => '2026-06-26 10:00:00',
		),
	);

	$html = ResponsesList::toHtml( 42, $responses, 1 );

	// Fourth pass (docs/theme-fidelity-audit-handoff.md, 2026-09-05): Lovable's
	// own heading is a plain static string, never count-prefixed — "1
	// Gemeenskapsreaksie(s)" must NOT appear anywhere in the markup.
	expect( $html )->toContain( 'Gemeenskapsreaksies' );
	expect( $html )->not->toContain( '1 Gemeenskapsreaksie' );
	expect( $html )->toContain( 'ink-reaksie--lof' );               // typed badge class
	expect( $html )->toContain( 'Lof' );                            // badge label (from Terms)
	expect( $html )->toContain( 'Pragtige beeldspraak.' );          // escaped content
	expect( $html )->toContain( 'Lid Een' );
} );

test( 'toHtml never count-prefixes the heading, regardless of response count', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( 'Gemeenskapsreaksies' );
	expect( $html )->not->toContain( '0 Gemeenskapsreaksies' );
} );

test( 'toHtml renders the typed form with all three response-type radios and a submit', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( 'data-ink-post="42"' );
	foreach ( ResponseType::values() as $value ) {
		expect( $html )->toContain( 'value="' . $value . '"' );
	}
	expect( $html )->toContain( 'name="ink_reaksie_content"' );
	expect( $html )->toContain( 'Plaas' ); // submit label (from Terms)
} );

test( 'toHtml renders the authored instruction line before the type radios', function (): void {
	$html = ResponsesList::toHtml( 42, array(), 0 );

	expect( $html )->toContain( 'ink-reaksies__intro' );
	expect( $html )->toContain( "Deel 'n deurdagte reaksie" );
	expect( strpos( $html, 'ink-reaksies__intro' ) )->toBeLessThan( strpos( $html, 'ink-reaksies__types' ) );
} );

test( 'toHtml renders the compose form BEFORE the existing-response list', function (): void {
	// Fourth pass: `ReadStory.tsx`'s literal JSX order is compose-card, THEN
	// `critiques.map(...)` — this class used to render the inverse.
	$responses = array(
		array(
			'id'      => 1,
			'type'    => ResponseType::Insig,
			'content' => 'n Insiggewende reaksie.',
			'author'  => 'Lid Twee',
			'date'    => '2026-06-26 10:00:00',
		),
	);

	$html = ResponsesList::toHtml( 42, $responses, 1 );

	expect( strpos( $html, 'ink-reaksies__form' ) )->toBeLessThan( strpos( $html, 'ink-reaksies__list' ) );
} );

test( 'toHtml renders no Reply action at all', function (): void {
	// A prior pass first wired Reply to focus the compose textarea (flagged by
	// the product owner as misleading), then left it rendered but inert to
	// match Lovable's own dead `<button>Reply</button>` — but a control with no
	// effect when activated is a defect regardless of the reference, so it was
	// removed outright, not just de-wired.
	$responses = array(
		array(
			'id'      => 1,
			'type'    => ResponseType::Voorstel,
			'content' => "'n Voorstel.",
			'author'  => 'Lid Drie',
			'date'    => '2026-06-26 10:00:00',
		),
	);

	$html = ResponsesList::toHtml( 42, $responses, 1 );

	expect( $html )->not->toContain( 'ink-reaksies__reply' );
	expect( $html )->not->toContain( 'data-ink-reply-target' );
} );

/**
 * The response upvote (owner-approved 2026-11-02): a working toggle for a signed-in member who
 * did not write the response; a disabled, count-only button for a visitor and for the author.
 *
 * @param int $author The response author's user id.
 * @return list<array<string, mixed>>
 */
function ink_upvote_response( int $author ): array {
	return array(
		array(
			'id'      => 9,
			'type'    => ResponseType::Lof,
			'content' => 'Mooi.',
			'author'  => 'Lid Een',
			'date'    => '2026-06-26 10:00:00',
			'user_id' => $author,
		),
	);
}

test( 'a signed-in member gets a pressable upvote showing the count and their vote', function (): void {
	$votes = array(
		9 => array(
			'count' => 3,
			'voted' => true,
		),
	);
	$html  = ResponsesList::toHtml( 42, ink_upvote_response( 5 ), 1, $votes, 7 );

	expect( $html )->toContain( '<button type="button" class="ink-reaksies__upvote' );
	expect( $html )->toContain( 'data-ink-opstem="9"' );
	expect( $html )->toContain( 'aria-pressed="true"' );
	expect( $html )->toContain( 'aria-label="Like"' );
	expect( $html )->toContain( '<span data-ink-opstem-telling aria-live="polite">3</span>' );
	expect( $html )->not->toContain( ' disabled ' );
} );

test( 'a member who has not voted sees aria-pressed false', function (): void {
	$votes = array(
		9 => array(
			'count' => 2,
			'voted' => false,
		),
	);
	$html  = ResponsesList::toHtml( 42, ink_upvote_response( 5 ), 1, $votes, 7 );

	expect( $html )->toContain( 'aria-pressed="false"' );
	expect( $html )->toContain( '>2</span>' );
} );

test( 'a logged-out visitor sees the count on a disabled, non-toggling button', function (): void {
	$votes = array(
		9 => array(
			'count' => 4,
			'voted' => false,
		),
	);
	$html  = ResponsesList::toHtml( 42, ink_upvote_response( 5 ), 1, $votes, 0 );

	expect( $html )->toContain( '<button type="button" class="ink-reaksies__upvote' );
	expect( $html )->toContain( ' disabled ' );
	expect( $html )->not->toContain( 'data-ink-opstem="' );
	expect( $html )->not->toContain( 'aria-pressed' );
	expect( $html )->toContain( '>4</span>' );
} );

test( 'the author cannot upvote their own response', function (): void {
	$html = ResponsesList::toHtml( 42, ink_upvote_response( 7 ), 1, array(), 7 );

	expect( $html )->toContain( ' disabled ' );
	expect( $html )->not->toContain( 'data-ink-opstem="' );
} );

test( 'a response with no upvotes yet reads 0, with no migration', function (): void {
	$html = ResponsesList::toHtml( 42, ink_upvote_response( 5 ), 1 );

	expect( $html )->toContain( '>0</span>' );
} );
