<?php
declare(strict_types=1);

use Ink\Kernel\Tier;
use Ink\Migration\EntryGraderingImporter;

$history = array(
	array( 'at' => '2026-03-02 10:00:00', 'from' => Tier::Brons, 'to' => Tier::Silwer ),
	array( 'at' => '2026-08-04 10:00:00', 'from' => Tier::Silwer, 'to' => Tier::Goud ),
);

it( 'gives the grade held at the entry date, not the current one', function () use ( $history ): void {
	expect( EntryGraderingImporter::gradeAt( $history, Tier::Goud, '2026-01-15 08:00:00' ) )->toBe( Tier::Brons );
	expect( EntryGraderingImporter::gradeAt( $history, Tier::Goud, '2026-05-01 08:00:00' ) )->toBe( Tier::Silwer );
	expect( EntryGraderingImporter::gradeAt( $history, Tier::Goud, '2026-10-01 08:00:00' ) )->toBe( Tier::Goud );
} );

it( 'uses the current grade when there is no history', function (): void {
	expect( EntryGraderingImporter::gradeAt( array(), Tier::Brons, '2026-10-01 08:00:00' ) )->toBe( Tier::Brons );
} );
