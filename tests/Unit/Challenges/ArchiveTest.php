<?php
/**
 * Unit tests for the uitdagings list page (Story 12.2, FR-46).
 *
 * Target: {@see \Ink\Challenges\Archive} — the `ink/uitdaging-argief` server block
 * (paginated card grid with a per-card countdown). Pure layers only (queryArgs /
 * countdownLabel / cardHtml / toHtml); the thin impure render() is integration-tested.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\Archive;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( '_n' )->alias( static fn ( string $one, string $many, int $n ): string => 1 === $n ? $one : $many );
	Functions\when( 'number_format_i18n' )->alias( static fn ( $n ): string => (string) $n );
	// ArchiveRender::pagination uses these; stub to inert passthroughs.
	Functions\when( 'add_query_arg' )->justReturn( '#' );
	Functions\when( 'remove_query_arg' )->justReturn( '#' );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'queryArgs lists published uitdagings newest-first, paged and bounded', function (): void {
	$args = Archive::queryArgs( 2, 12 );

	expect( $args['post_type'] )->toBe( PostTypes::UITDAGING );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['posts_per_page'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 2 );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );
} );

test( 'queryArgs floors a non-positive page to 1', function (): void {
	expect( Archive::queryArgs( 0, 12 )['paged'] )->toBe( 1 );
	expect( Archive::queryArgs( -5, 12 )['paged'] )->toBe( 1 );
} );

test( 'countdownLabel counts whole SAST days remaining while open', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-26 09:00:00', $sast ); // 5 calendar days before

	expect( Archive::countdownLabel( $deadline, $now ) )->toContain( '5' );
	expect( Archive::countdownLabel( $deadline, $now ) )->toContain( 'dae' );
} );

test( 'countdownLabel uses the singular dag for exactly one day out', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-30 09:00:00', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Nog 1 dag' );
} );

test( 'countdownLabel says Sluit vandag on the deadline SAST day', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 23:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-10-31 08:00:00', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Sluit vandag' );
} );

test( 'countdownLabel says Gesluit once the inclusive deadline has passed', function (): void {
	$sast     = new \DateTimeZone( 'Africa/Johannesburg' );
	$deadline = new \DateTimeImmutable( '2026-10-31 12:00:00', $sast );
	$now      = new \DateTimeImmutable( '2026-11-01 00:00:01', $sast );

	expect( Archive::countdownLabel( $deadline, $now ) )->toBe( 'Gesluit' );
} );

test( 'countdownLabel renders nothing without a deadline', function (): void {
	expect( Archive::countdownLabel( null, new \DateTimeImmutable( 'now' ) ) )->toBe( '' );
} );

test( 'cardHtml renders the title→permalink, tema, deadline and countdown with an open marker', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Oktober-uitdaging',
			'permalink' => 'https://ink.test/uitdaging/oktober',
			'tema'      => 'Herfs',
			'deadline'  => '31 Oktober 2026',
			'countdown' => 'Nog 5 dae',
			'is_open'   => true,
		)
	);

	expect( $html )->toContain( 'Oktober-uitdaging' );
	expect( $html )->toContain( 'https://ink.test/uitdaging/oktober' );
	expect( $html )->toContain( 'Herfs' );
	expect( $html )->toContain( '31 Oktober 2026' );
	expect( $html )->toContain( 'Nog 5 dae' );
	expect( $html )->toContain( 'is-oop' );
} );

test( 'cardHtml reuses the single-page status pill and icon-led date-row classes (Post-Epic-19 fidelity pass, workstream 7)', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Oktober-uitdaging',
			'permalink' => 'https://ink.test/uitdaging/oktober',
			'tema'      => 'Herfs',
			'deadline'  => '31 Oktober 2026',
			'countdown' => 'Nog 5 dae',
			'is_open'   => true,
		)
	);

	// Reuses SinglePage's exact status-pill/date-row classes — same colours/shape/
	// icon treatment as the single-challenge page, not a reinvented visual language.
	expect( $html )->toContain( 'ink-uitdaging__toestand-pil' );
	expect( $html )->toContain( 'ink-uitdaging__sluitingsdatum-ry' );
	expect( $html )->toContain( '<svg' );
	expect( $html )->toContain( 'ink-uitdagings__tema-pil' );
	expect( $html )->toContain( 'ink-uitdagings__lees' );
} );

test( 'cardHtml renders a Gesluit-coloured pill once closed', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Junie-uitdaging',
			'permalink' => 'https://ink.test/uitdaging/junie',
			'tema'      => 'Winter',
			'deadline'  => '1 Junie 2026',
			'countdown' => 'Gesluit',
			'is_open'   => false,
		)
	);

	expect( $html )->toContain( 'is-gesluit' );
	expect( $html )->toContain( 'Gesluit' );
} );

test( 'cardHtml omits the meta row and footer when tema/deadline/countdown are absent', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Kaal kaart',
			'permalink' => '#kaal',
			'tema'      => '',
			'deadline'  => '',
			'countdown' => '',
			'is_open'   => false,
		)
	);

	expect( $html )->not->toContain( 'ink-uitdagings__item-meta' );
	expect( $html )->not->toContain( 'ink-uitdagings__item-voet' );
} );

test( 'toHtml renders the heading and a card per challenge', function (): void {
	$cards = array(
		Archive::cardHtml(
			array(
				'title'     => 'A',
				'permalink' => '#a',
				'tema'      => '',
				'deadline'  => '',
				'countdown' => '',
				'is_open'   => false,
			)
		),
		Archive::cardHtml(
			array(
				'title'     => 'B',
				'permalink' => '#b',
				'tema'      => '',
				'deadline'  => '',
				'countdown' => '',
				'is_open'   => false,
			)
		),
	);

	$html = Archive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 1 ) );

	expect( $html )->toContain( 'ink-uitdagings' );
	expect( substr_count( $html, '<li class="ink-uitdagings__item ' ) )->toBe( 2 );
} );

test( 'toHtml renders a graceful empty state with no challenges', function (): void {
	$html = Archive::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0 ) );

	expect( $html )->toContain( 'ink-uitdagings' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->not->toContain( 'ink-uitdagings__item' );
} );

test( 'cardHtml gives the round\'s month top left, where other cards show their type, and the entry count top right', function (): void {
	$html = Archive::cardHtml(
		array(
			'title'     => 'Oktober 2026 - Getal 10 projek',
			'permalink' => '#okt',
			'month'     => 'Oktober 2026',
			'excerpt'   => 'Beste INKers, hierdie maand ...',
			'entries'   => 14,
		)
	);

	expect( $html )->toContain( 'ink-uitdagings__maand ink-pill' )
		->and( $html )->toContain( 'Oktober 2026' )
		->and( $html )->toContain( '14 inskrywings' )
		->and( $html )->toContain( 'ink-uitdagings__item-uittreksel' )
		->and( $html )->toContain( 'Beste INKers' )
		->and( strpos( $html, 'Oktober 2026</span>' ) )->toBeLessThan( strpos( $html, '14 inskrywings' ) );
} );

test( 'cardHtml says "1 inskrywing" in the singular and hides a zero count', function (): void {
	$one  = Archive::cardHtml( array( 'title' => 'X', 'permalink' => '#', 'entries' => 1 ) );
	$none = Archive::cardHtml( array( 'title' => 'X', 'permalink' => '#', 'entries' => 0 ) );

	expect( $one )->toContain( '1 inskrywing<' )->and( $none )->not->toContain( 'inskrywing' );
} );

test( 'queryArgs leaves the current round out on every page', function (): void {
	foreach ( array( 1, 2, 7 ) as $page ) {
		expect( Archive::queryArgs( $page, 12, 42 )['post__not_in'] )->toBe( array( 42 ) );
	}

	expect( Archive::queryArgs( 1, 12 ) )->not->toHaveKey( 'post__not_in' );
} );

function ink_archive_round( array $over = array() ): array {
	return array_merge(
		array(
			'id'          => 42,
			'theme'       => 'Nuwe begin',
			'url'         => 'https://ink.test/uitdaging/nov',
			'excerpt'     => 'Skryf oor nuwe begin.',
			'is_open'     => true,
			'month'       => 'November 2026',
			'close'       => new \DateTimeImmutable( '2026-11-30 00:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) ),
			'type_counts' => array( 'gedig' => 2 ),
		),
		$over
	);
}

test( 'the open current card shows Oop, the countdown in the date slot, and Lees meer', function (): void {
	$html = Archive::currentHtml( ink_archive_round(), 'Nog 5 dae' );

	expect( $html )->toContain( 'Oop · November 2026' )
		->and( $html )->toContain( '"Nuwe begin"' )
		->and( $html )->toContain( 'Skryf oor nuwe begin.' )
		->and( $html )->toContain( 'ink-card--soft ink-card--soft-lg' )
		->and( $html )->toContain( 'ink-pill--solid' )
		->and( $html )->toContain( 'ink-meta-row--wide' )
		->and( $html )->toMatch( '/data-audit-id="uitdagings-current-meta-close">.*Nog 5 dae/' )
		->and( $html )->toContain( 'Lees meer' )
		->and( $html )->not->toContain( 'Skryf in' )
		->and( $html )->toContain( 'https://ink.test/uitdaging/nov' );
} );

test( 'the closed current card says Gesluit and Lees meer, with no date slot', function (): void {
	$round = ink_archive_round( array( 'is_open' => false, 'type_counts' => array() ) );
	$html  = Archive::currentHtml( $round, Archive::currentCountdown( $round, new \DateTimeImmutable( '2026-12-05' ) ) );

	expect( $html )->toContain( 'Gesluit · November 2026' )
		->and( $html )->toContain( 'Lees meer' )
		->and( $html )->not->toContain( 'Skryf in' )
		->and( $html )->not->toContain( 'uitdagings-current-meta' );
} );

test( 'currentCountdown uses countdownLabel for an open round', function (): void {
	$now = new \DateTimeImmutable( '2026-11-25 09:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	expect( Archive::currentCountdown( ink_archive_round(), $now ) )->toBe( 'Nog 5 dae' );
} );

test( 'the current card drops an empty description', function (): void {
	expect( Archive::currentHtml( ink_archive_round( array( 'excerpt' => '' ) ), '' ) )->not->toContain( 'uitdagings-current-description' );
} );

test( 'the page has the hero, the current band only when given, and no scope ids', function (): void {
	$cards = array( Archive::cardHtml( array( 'title' => 'A', 'permalink' => '#a' ) ) );
	$one   = Archive::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 2 ), '<section data-audit-id="uitdagings-current"></section>' );
	$two   = Archive::toHtml( $cards, array( 'paged' => 2, 'max_pages' => 2 ) );

	expect( $one )->toContain( 'data-audit-id="uitdagings-hero"' )
		->and( strpos( $one, 'uitdagings-hero' ) )->toBeLessThan( strpos( $one, 'uitdagings-current' ) )
		->and( strpos( $one, 'uitdagings-current' ) )->toBeLessThan( strpos( $one, 'uitdagings-past' ) )
		->and( $one )->toContain( 'ink-pager' )
		->and( $one )->not->toContain( 'uitdagings-pagination' )
		->and( $one )->not->toContain( 'uitdagings-past-list' )
		->and( $two )->toContain( 'data-audit-id="uitdagings-hero"' )
		->and( $two )->not->toContain( 'uitdagings-current' )
		->and( $two )->toContain( 'data-audit-id="uitdagings-past"' );
} );

test( 'a current round with nothing else shows no past band and no empty state', function (): void {
	$html = Archive::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0 ), '<section data-audit-id="uitdagings-current"></section>' );

	expect( $html )->not->toContain( 'uitdagings-past' )->and( $html )->not->toContain( 'Geen' );
} );
