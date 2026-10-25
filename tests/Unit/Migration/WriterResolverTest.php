<?php
/**
 * Unit tests for the by-line to account resolver.
 *
 * Target: {@see \Ink\Migration\WriterResolver}. Pure; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Migration;

use Ink\Migration\LegacyResultsParser as P;
use Ink\Migration\WriterResolver;

function resolver(): WriterResolver {
	$users = array(
		P::nameKey( 'Amanda Meyer De Meillon' ) => 7,
		P::nameKey( 'Carma Shaw' )              => 3,
		P::nameKey( 'Viooltjie' )               => 3, // a pen name for the same account
		P::nameKey( 'Annamarie van der Merwe' ) => 9,
		P::nameKey( 'Annemarie van der Merwe' ) => 10, // a different account with a near name
	);
	$aliases = array(
		P::nameKey( 'Amanda de Meillon' ) => P::nameKey( 'Amanda Meyer De Meillon' ),
	);

	return new WriterResolver( $users, $aliases );
}

test( 'an exact name and a pen name both resolve to the account', function (): void {
	expect( resolver()->resolve( 'Carma Shaw' ) )->toBe( array( 'user' => 3, 'how' => 'exact', 'matched' => P::nameKey( 'Carma Shaw' ) ) );
	expect( resolver()->resolve( 'Viooltjie' )['user'] )->toBe( 3 );
} );

test( 'a known variant resolves through the alias table', function (): void {
	$hit = resolver()->resolve( 'Amanda de Meillon' );

	expect( $hit['user'] )->toBe( 7 );
	expect( $hit['how'] )->toBe( 'alias' );
} );

test( 'layout noise around the by-line is stripped', function (): void {
	expect( resolver()->resolve( '(60%) - Carma Shaw' )['user'] )->toBe( 3 );
	expect( resolver()->resolve( 'Gesamentlik Carma Shaw' )['user'] )->toBe( 3 );
	expect( resolver()->resolve( 'No. 22 Carma Shaw' )['user'] )->toBe( 3 );
} );

test( 'a unique near match resolves as fuzzy, an ambiguous or short one does not', function (): void {
	$hit = resolver()->resolve( 'Amanda Meyer De Mellion' );

	expect( $hit['user'] )->toBe( 7 );
	expect( $hit['how'] )->toBe( 'fuzzy' );

	// Two accounts are one edit away from this name: never guess between them.
	expect( resolver()->resolve( 'Annamerie van der Merwe' ) )->toBeNull();
	// Too short to trust a near match.
	expect( resolver()->resolve( 'Carma Shaa' ) )->toBeNull();
} );

test( 'an unknown name is null', function (): void {
	expect( resolver()->resolve( 'Niemand Bekend' ) )->toBeNull();
	expect( resolver()->resolve( '' ) )->toBeNull();
} );

test( 'numeric account keys (a login like 1981) do not break the near-match search', function (): void {
	$resolver = new WriterResolver( array( 1981 => 4, P::nameKey( 'Carma Shaw' ) => 3 ), array() );

	expect( $resolver->resolve( 'Karoo in daardie konteks' ) )->toBeNull();
} );
