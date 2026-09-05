<?php
/**
 * Unit tests for the uitdaging single-page surface (Story 12.1, FR-45).
 *
 * Target: {@see \Ink\Challenges\SinglePage} — the `ink/uitdaging-besonderhede`
 * server block (deadline/status line + entries list) rendered on a single uitdaging.
 *
 * Pure layers only (queryArgs / isOpen / statusHtml / entriesHtml / toHtml); the
 * thin impure render() touches WP and is exercised by the integration suite. Brain
 * Monkey stubs the i18n + escaping passthroughs (tests/bootstrap.php precedent).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\SinglePage;
use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( '_n' )->alias(
		static fn ( string $single, string $plural, int $number ): string => 1 === $number ? $single : $plural
	);
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'entriesQueryArgs targets published readable bydraes filtered by the round term, newest-first', function (): void {
	$args = SinglePage::entriesQueryArgs( 7 );

	expect( $args['post_type'] )->toBe( PostTypes::readableTypes() );
	expect( $args['post_status'] )->toBe( 'publish' );
	expect( $args['orderby'] )->toBe( 'date' );
	expect( $args['order'] )->toBe( 'DESC' );

	expect( $args['tax_query'][0]['taxonomy'] )->toBe( Taxonomies::UITDAGINGSRONDTE );
	expect( $args['tax_query'][0]['field'] )->toBe( 'slug' );
	expect( $args['tax_query'][0]['terms'] )->toBe( ChallengeRound::slugFor( 7 ) );
} );

test( 'entriesQueryArgs with a non-positive id matches nothing (post__in [0])', function (): void {
	$args = SinglePage::entriesQueryArgs( 0 );

	expect( $args['post__in'] )->toBe( array( 0 ) );
	expect( $args )->not->toHaveKey( 'tax_query' );
} );

test( 'isOpen is true through the inclusive end-of-day-SAST deadline and false after', function (): void {
	$deadline = new \DateTimeImmutable( '2026-10-31 00:00:00', new \DateTimeZone( 'Africa/Johannesburg' ) );

	// 23:59:59 SAST on the deadline day is still open (inclusive boundary).
	$open = new \DateTimeImmutable( '2026-10-31 21:59:59', new \DateTimeZone( 'UTC' ) );
	expect( SinglePage::isOpen( $deadline, $open ) )->toBeTrue();

	// One second later (next SAST day) is closed.
	$closed = new \DateTimeImmutable( '2026-10-31 22:00:00', new \DateTimeZone( 'UTC' ) );
	expect( SinglePage::isOpen( $deadline, $closed ) )->toBeFalse();
} );

test( 'statusHtml renders the sluitingsdatum with an Oop marker while open', function (): void {
	$html = SinglePage::statusHtml( '31 Oktober 2026', true );

	expect( $html )->toContain( 'Sluitingsdatum' );
	expect( $html )->toContain( '31 Oktober 2026' );
	expect( $html )->toContain( 'Oop' );
	expect( $html )->toContain( 'is-oop' );
	// Post-Epic-19 fidelity pass (workstream 6): an icon-led meta row + a coloured
	// status pill, not a plain inline text line — the "date readability" risk
	// flagged for this page in page-map.csv.
	expect( $html )->toContain( 'ink-uitdaging__sluitingsdatum-ry' );
	expect( $html )->toContain( 'ink-uitdaging__toestand-pil' );
	expect( $html )->toContain( '<svg' );
} );

test( 'statusHtml renders the participants meta item (Post-Epic-19 third pass) with a default of 0', function (): void {
	$html = SinglePage::statusHtml( '31 Oktober 2026', true );

	expect( $html )->toContain( 'ink-uitdaging__deelnemers-ry' );
	expect( $html )->toContain( '0 skrywers het ingeskryf' );
} );

test( 'statusHtml renders the given participant count, singular and plural', function (): void {
	expect( SinglePage::statusHtml( '31 Oktober 2026', true, 1 ) )->toContain( '1 skrywer het ingeskryf' );
	expect( SinglePage::statusHtml( '31 Oktober 2026', true, 12 ) )->toContain( '12 skrywers het ingeskryf' );
} );

test( 'statusHtml renders a Gesluit marker once closed', function (): void {
	$html = SinglePage::statusHtml( '31 Oktober 2026', false );

	expect( $html )->toContain( 'Gesluit' );
	expect( $html )->toContain( 'is-gesluit' );
} );

test( 'statusHtml renders nothing when there is no deadline', function (): void {
	expect( SinglePage::statusHtml( '', true ) )->toBe( '' );
} );

test( 'entriesHtml lists each entry as a title→permalink, newest-first', function (): void {
	$entries = array(
		array(
			'title'     => 'My gedig',
			'permalink' => 'https://ink.test/gedig/my-gedig',
		),
		array(
			'title'     => 'Sy storie',
			'permalink' => 'https://ink.test/storie/sy-storie',
		),
	);

	$html = SinglePage::entriesHtml( $entries );

	expect( $html )->toContain( 'Inskrywings' );
	expect( $html )->toContain( 'My gedig' );
	expect( $html )->toContain( 'https://ink.test/gedig/my-gedig' );
	expect( $html )->toContain( 'Sy storie' );
	expect( substr_count( $html, '<li' ) )->toBe( 2 );
} );

test( 'entriesHtml renders a graceful empty state with no entries (no empty list shell)', function (): void {
	$html = SinglePage::entriesHtml( array() );

	expect( $html )->toContain( 'Geen' );
	expect( $html )->not->toContain( '<li' );
	expect( $html )->not->toContain( '<ul' );
} );

test( 'entriesHtml renders the type-label pill, excerpt and author on a card when supplied', function (): void {
	$html = SinglePage::entriesHtml(
		array(
			array(
				'title'      => 'My gedig',
				'permalink'  => 'https://ink.test/gedig/my-gedig',
				'type_label' => 'Gedig',
				'excerpt'    => 'n Kort greep uit die gedig.',
				'author'     => 'Anna Botha',
			),
		)
	);

	expect( $html )->toContain( 'ink-uitdaging__inskrywing-tipe' );
	expect( $html )->toContain( 'Gedig' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywing-uittreksel' );
	expect( $html )->toContain( 'n Kort greep uit die gedig.' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywing-outeur' );
	expect( $html )->toContain( 'Anna Botha' );
} );

test( 'entriesHtml omits the pill/excerpt/author elements when a card has no such data', function (): void {
	$html = SinglePage::entriesHtml(
		array(
			array(
				'title'     => 'Sy storie',
				'permalink' => 'https://ink.test/storie/sy-storie',
			),
		)
	);

	expect( $html )->not->toContain( 'ink-uitdaging__inskrywing-tipe' );
	expect( $html )->not->toContain( 'ink-uitdaging__inskrywing-uittreksel' );
	expect( $html )->not->toContain( 'ink-uitdaging__inskrywing-outeur' );
} );

test( 'entriesHtml renders the author avatar (Post-Epic-19 third pass, workstream 6b) when supplied', function (): void {
	$html = SinglePage::entriesHtml(
		array(
			array(
				'title'      => 'My gedig',
				'permalink'  => 'https://ink.test/gedig/my-gedig',
				'author'     => 'Anna Botha',
				'avatar_url' => 'https://ink.test/avatar/anna.jpg',
			),
		)
	);

	expect( $html )->toContain( 'ink-uitdaging__inskrywing-foto' );
	expect( $html )->toContain( 'https://ink.test/avatar/anna.jpg' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywing-outeur-naam' );
	expect( $html )->toContain( 'Anna Botha' );
} );

test( 'entriesHtml omits the avatar image when no avatar_url is supplied', function (): void {
	$html = SinglePage::entriesHtml(
		array(
			array(
				'title'     => 'My gedig',
				'permalink' => 'https://ink.test/gedig/my-gedig',
				'author'    => 'Anna Botha',
			),
		)
	);

	expect( $html )->not->toContain( 'ink-uitdaging__inskrywing-foto' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywing-outeur-naam' );
} );

test( 'toHtml composes the status line and entries list inside the section shell', function (): void {
	$html = SinglePage::toHtml( '<p class="ink-uitdaging__status">x</p>', '<ul class="ink-uitdaging__inskrywings"></ul>' );

	expect( $html )->toContain( 'ink-uitdaging' );
	expect( $html )->toContain( 'ink-uitdaging__status' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywings' );
	// The anchor target for the pattern-level "Lees inskrywings" CTA button
	// (reading-uitdaging.php) — mirrors Lovable's `href="#submissions"` jump-link.
	expect( $html )->toContain( 'id="inskrywings"' );
} );

test( 'inskrywingsSectionHtml (Post-Epic-19 third pass, workstream 6b) wraps the entries list with the id="inskrywings" anchor', function (): void {
	$html = SinglePage::inskrywingsSectionHtml( '<ul class="ink-uitdaging__inskrywings"></ul>' );

	expect( $html )->toContain( 'id="inskrywings"' );
	expect( $html )->toContain( 'ink-uitdaging__inskrywings' );
	// The status meta row does NOT live in this section any more — it moved to the
	// hero (VARIANT_KOP), matching Lovable's `Challenge.tsx` meta-row position.
	expect( $html )->not->toContain( 'ink-uitdaging__status' );
} );

test( 'the variant constants are the single-source values (Post-Epic-19 third pass, workstream 6b)', function (): void {
	expect( SinglePage::VARIANT_KOP )->toBe( 'kop' );
	expect( SinglePage::VARIANT_INSKRYWINGS )->toBe( 'inskrywings' );
} );
