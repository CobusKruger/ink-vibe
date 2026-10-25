<?php
/**
 * Unit tests for deriving gradering from the old result posts.
 *
 * Target: {@see \Ink\Migration\TierDeriver} and the engine's cumulative thresholds
 * {@see \Ink\Tiers\PromotionEngine::standingForTotalWins()}. Seams stubbed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Kernel\Tier;
use Ink\Migration\LegacyResultsParser as P;
use Ink\Migration\TierDeriver;
use Ink\Tiers\PromotionEngine;

test( 'standing for total wins follows the live thresholds: 5 to leave brons, 15 more to leave silwer', function (): void {
	$cases = array(
		0  => array( Tier::Brons, 0 ),
		4  => array( Tier::Brons, 4 ),
		5  => array( Tier::Silwer, 0 ),
		19 => array( Tier::Silwer, 14 ),
		20 => array( Tier::Goud, 0 ),
		40 => array( Tier::Goud, 0 ),
	);

	foreach ( $cases as $total => $expected ) {
		$standing = PromotionEngine::standingForTotalWins( $total );

		expect( $standing['tier'] )->toBe( $expected[0] );
		expect( $standing['count'] )->toBe( $expected[1] );
	}
} );

/**
 * A deriver over one canned result post, recording what it would write.
 */
function deriverFor( string $html ): object {
	return new class( $html ) extends TierDeriver {
		/** @var array<int, array{0:string,1:int}> */
		public array $applied = array();

		public function __construct( private string $html ) {}

		protected function resultPosts(): array {
			return array( (object) array( 'id' => 1, 'html' => $this->html ) );
		}
		protected function directory(): array {
			return array(
				'users' => array(
					P::nameKey( 'Carma Shaw' )   => 3,
					P::nameKey( 'Anzé Bezuidenhout' ) => 5,
				),
				'names' => array( 'Carma Shaw', 'Anzé Bezuidenhout' ),
			);
		}
		protected function aliasMap(): array {
			return array( P::nameKey( 'Anze Bezuidenhout' ) => P::nameKey( 'Anzé Bezuidenhout' ) );
		}
		protected function applyStanding( int $user_id, Tier $tier, int $count ): void {
			$this->applied[ $user_id ] = array( $tier->value, $count );
		}
	};
}

test( 'derive counts Gedigte wins only, through aliases, and reports what it could not resolve', function (): void {
	$html = "GEDIGTE\nBrons\n(1) No 1 Carma Shaw met Een\n(2) No 2 Anze Bezuidenhout met Twee\n(3) No 3 Onbekende Skrywer met Drie\nPROSA:\n(1) No 4 Carma Shaw met Prosa\n";

	$report = deriverFor( $html )->derive();

	expect( $report['wins'] )->toBe( array( 3 => 1, 5 => 1 ) ); // the Prosa win does not count.
	expect( $report['standings'][3]['tier'] )->toBe( Tier::Brons );
	expect( $report['unresolved'] )->toBe( array( 'Onbekende Skrywer' => 1 ) );
	expect( $report['by_tier'] )->toBe( array( 'brons' => 2, 'silwer' => 0, 'goud' => 0 ) );
} );

test( 'derive grades a writer by lifetime wins across posts', function (): void {
	$post = "GEDIGTE\nGoud\n(1) No 1 Carma Shaw met A\n(2) No 2 Carma Shaw met B\n(3) No 3 Carma Shaw met C\n";
	$deriver = new class( $post ) extends TierDeriver {
		public function __construct( private string $post ) {}

		protected function resultPosts(): array {
			return array_fill( 0, 2, (object) array( 'id' => 1, 'html' => $this->post ) ); // 2 posts x 3 wins = 6.
		}
		protected function directory(): array {
			return array( 'users' => array( P::nameKey( 'Carma Shaw' ) => 3 ), 'names' => array( 'Carma Shaw' ) );
		}
		protected function aliasMap(): array {
			return array();
		}
	};

	$report = $deriver->derive();

	expect( $report['wins'][3] )->toBe( 6 );
	expect( $report['standings'][3]['tier'] )->toBe( Tier::Silwer );
	expect( $report['standings'][3]['count'] )->toBe( 1 ); // 6 total = 5 to leave brons + 1 toward goud.
} );
