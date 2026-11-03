<?php
/**
 * Unit tests for a winner list's category label to its results tab (`01-uitslae` §5).
 *
 * Target: {@see \Ink\Migration\WinCategory}. Pure.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\PostReclassifier;
use Ink\Migration\WinCategory;

test( 'all seven labels reach the ruled tab', function ( string $label, string $type ): void {
	expect( WinCategory::postTypeFor( $label ) )->toBe( $type );
} )->with(
	array(
		array( 'Gedigte', 'gedig' ),
		array( 'Verhale', 'storie' ),
		array( 'Artikels', 'artikel' ),
		array( 'Rubrieke', 'artikel' ),
		array( 'Prosa', 'artikel' ),
		array( 'Meningstukke', 'artikel' ),
		array( 'Algemeen', 'artikel' ),
	)
);

test( 'the trap: the label Algemeen sent straight into the map would give Stories', function (): void {
	expect( PostReclassifier::cptForCategorySlugs( array( 'algemeen' ) ) )->toBe( 'storie' );
	expect( WinCategory::postTypeFor( 'Algemeen' ) )->toBe( 'artikel' );
} );

test( 'an unknown label is null for the importer to report, not the map default', function (): void {
	expect( WinCategory::postTypeFor( 'Onbekend' ) )->toBeNull();
	expect( PostReclassifier::cptForCategorySlugs( array( 'onbekend' ) ) )->toBe( 'skryfwerk' );
} );

test( 'every slug it sends is in the existing map: there is no second map', function (): void {
	foreach ( WinCategory::LABEL_SLUGS as $slug ) {
		expect( PostReclassifier::CATEGORY_CPT_MAP )->toHaveKey( $slug );
	}
} );
