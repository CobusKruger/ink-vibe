<?php
/**
 * Unit tests for the winners-announcement post generation (Story 12A.4, FR-50-R2).
 *
 * Target: {@see \Ink\Challenges\WinnersPost} — composes + publishes the wenneraankondiging
 * (idempotent), and feeds the home featured slot. Pure composers + seam-isolated writes.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Challenges;

use Ink\Challenges\WinnersPost;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'composeTitle prefixes the uitdaging title, with a fallback', function (): void {
	expect( WinnersPost::composeTitle( 'Mei-uitdaging' ) )->toBe( 'Wenners: Mei-uitdaging' );
	expect( WinnersPost::composeTitle( '   ' ) )->toBe( 'Wenneraankondiging' );
} );

test( 'composeTitle carries the cadence period when supplied (monthly vs annual)', function (): void {
	// Story 12B.1: the period makes the round cadence observable in the announcement.
	expect( WinnersPost::composeTitle( 'Herfs', 'Desember 2026' ) )->toBe( 'Wenners: Desember 2026 — Herfs' ); // monthly
	expect( WinnersPost::composeTitle( 'Herfs', '2026' ) )->toBe( 'Wenners: 2026 — Herfs' );                   // annual
	// Empty period preserves the prior form (a round with no deadline set).
	expect( WinnersPost::composeTitle( 'Herfs', '' ) )->toBe( 'Wenners: Herfs' );
} );

test( 'composeBody renders the frame then the ordered winner links', function (): void {
	$entries = array(
		array( 'label' => 'algehele wenner', 'title' => 'Maanlig', 'url' => 'https://ink.test/maanlig' ),
		array( 'label' => 'wenner', 'title' => 'Nag', 'url' => '' ),
	);

	$body = WinnersPost::composeBody( 'Baie geluk!', $entries );

	expect( $body )->toContain( '<p>Baie geluk!</p>' );
	expect( $body )->toContain( 'algehele wenner: ' );
	expect( $body )->toContain( '<a href="https://ink.test/maanlig">Maanlig</a>' );
	// No url → plain title, no anchor.
	expect( $body )->toContain( '<li>wenner: Nag</li>' );
} );

test( 'composeBody with no entries renders only the frame', function (): void {
	expect( WinnersPost::composeBody( 'Net die raam.', array() ) )->toBe( '<p>Net die raam.</p>' );
} );

test( 'featuredPayload assembles the slot payload', function (): void {
	$payload = WinnersPost::featuredPayload( 'Wenners: Mei', 'https://ink.test/w', array( array( 'id' => 1, 'rank' => 1, 'title' => 'A', 'url' => 'u' ) ) );

	expect( $payload['title'] )->toBe( 'Wenners: Mei' );
	expect( $payload['winners'][0]['rank'] )->toBe( 1 );
} );

test( 'generate composes + inserts an announcement and links it to the round', function (): void {
	$captured = array();
	Functions\expect( 'update_post_meta' )->once()->with( 900, WinnersPost::UITDAGING_META, 7 );

	$wp = new class( $captured ) extends WinnersPost {
		public array $cap;
		public function __construct( array &$cap ) {
			$this->cap = &$cap;
		}
		protected function existingPostFor( int $uitdaging_id ): int {
			return 0; // none yet
		}
		protected function bodyFrame(): string {
			return 'Baie geluk!';
		}
		protected function uitdagingTitle( int $uitdaging_id ): string {
			return 'Mei-uitdaging';
		}
		protected function roundPeriod( int $uitdaging_id ): string {
			return ''; // no deadline set → period-less title (isolates the meta reads)
		}
		protected function entryView( int $post_id ): array {
			return array( 'title' => 'Werk ' . $post_id, 'url' => 'https://ink.test/' . $post_id );
		}
		protected function insertPost( string $title, string $body ): int {
			$this->cap = array( 'title' => $title, 'body' => $body );
			return 900;
		}
	};

	$winners = array(
		array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ),
		array( 'post_id' => 11, 'rank' => 2, 'author_id' => 200 ),
	);

	$id = $wp->generate( 7, $winners );

	expect( $id )->toBe( 900 );
	expect( $captured['title'] )->toBe( 'Wenners: Mei-uitdaging' );
	expect( $captured['body'] )->toContain( 'Baie geluk!' );
	expect( $captured['body'] )->toContain( 'Werk 10' );
} );

test( 'generate carries the round cadence period into the announcement title (annual reuse, production path)', function (): void {
	// Story 12B.1: the REAL proof the annual cadence is reused by the winners machinery —
	// generate() resolves the round period via roundPeriod() (→ Cadence::periodLabelFor)
	// and folds it into the published title. An annual round reads "Wenners: 2026 — …".
	Functions\when( 'update_post_meta' )->justReturn( true );

	$make = function ( string $period ): array {
		$captured = array();
		$wp       = new class( $captured, $period ) extends WinnersPost {
			public array $cap;
			public string $period;
			public function __construct( array &$cap, string $period ) {
				$this->cap    = &$cap;
				$this->period = $period;
			}
			protected function existingPostFor( int $uitdaging_id ): int {
				return 0;
			}
			protected function bodyFrame(): string {
				return 'Baie geluk!';
			}
			protected function uitdagingTitle( int $uitdaging_id ): string {
				return 'Jaarkompetisie';
			}
			protected function roundPeriod( int $uitdaging_id ): string {
				return $this->period;
			}
			protected function entryView( int $post_id ): array {
				return array( 'title' => 'Werk ' . $post_id, 'url' => 'u' );
			}
			protected function insertPost( string $title, string $body ): int {
				$this->cap = array( 'title' => $title );
				return 900;
			}
		};
		$wp->generate( 7, array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) );
		return $captured;
	};

	// Annual round → year period; monthly round → month-year period. Same machinery, cadence-driven.
	expect( $make( '2026' )['title'] )->toBe( 'Wenners: 2026 — Jaarkompetisie' );
	expect( $make( 'Desember 2026' )['title'] )->toBe( 'Wenners: Desember 2026 — Jaarkompetisie' );
} );

test( 'generate is idempotent — an existing announcement is returned, not re-posted (non-vacuous)', function (): void {
	// Non-vacuous: the test above proved generate WOULD insert when none exists.
	Functions\expect( 'update_post_meta' )->never();

	$wp = new class() extends WinnersPost {
		protected function existingPostFor( int $uitdaging_id ): int {
			return 555; // already announced
		}
		protected function insertPost( string $title, string $body ): int {
			throw new \RuntimeException( 'must not insert' );
		}
	};

	expect( $wp->generate( 7, array( array( 'post_id' => 10, 'rank' => 1, 'author_id' => 100 ) ) ) )->toBe( 555 );
} );

test( 'featured passes through a payload already set by a higher-priority filter', function (): void {
	$existing = array( 'title' => 'Reeds gestel', 'url' => 'x', 'winners' => array() );

	expect( ( new WinnersPost() )->featured( $existing ) )->toBe( $existing );
} );

test( 'featured builds the slot payload from the latest announcement + its placed entries', function (): void {
	Functions\when( 'get_post_meta' )->justReturn( 7 );
	Functions\when( 'get_the_title' )->justReturn( 'Wenners: Mei' );
	Functions\when( 'get_permalink' )->justReturn( 'https://ink.test/wenners-mei' );

	$wp = new class() extends WinnersPost {
		protected function latestAnnouncement(): int {
			return 900;
		}
		protected function placedEntries( int $uitdaging_id ): array {
			return array( array( 'id' => 10, 'rank' => 1 ), array( 'id' => 11, 'rank' => 2 ) );
		}
		protected function entryView( int $post_id ): array {
			return array( 'title' => 'Werk ' . $post_id, 'url' => 'https://ink.test/' . $post_id );
		}
	};

	$payload = $wp->featured( null );

	expect( $payload['title'] )->toBe( 'Wenners: Mei' );
	expect( $payload['winners'] )->toHaveCount( 2 );
	expect( $payload['winners'][0] )->toBe( array( 'id' => 10, 'rank' => 1, 'title' => 'Werk 10', 'url' => 'https://ink.test/10' ) );
} );

test( 'featured collapses (returns the incoming value) when there is no announcement', function (): void {
	$wp = new class() extends WinnersPost {
		protected function latestAnnouncement(): int {
			return 0;
		}
	};

	expect( $wp->featured( null ) )->toBeNull();
} );
