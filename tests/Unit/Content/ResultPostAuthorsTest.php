<?php
/**
 * Unit tests for who counts as a result-post author.
 *
 * Target: {@see \Ink\Content\ResultPostAuthors}. Pure; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Content;

use Ink\Content\ResultPostAuthors;

it( 'reads the authors of result-titled posts from the posts, once each', function (): void {
	$rows = array(
		array( 'login' => 'Inkmoderator', 'title' => 'Augustus 2026 – Aanhalingsprojek – Kommentaar en uitslae' ),
		array( 'login' => 'Inkmoderator', 'title' => 'Wenners van die Amakeia projek' ),
		array( 'login' => 'Anze', 'title' => 'Carpe diem projek wenners' ),
		array( 'login' => 'LidIemand', 'title' => 'My gedig oor die see' ),
	);

	expect( ResultPostAuthors::loginsFromPosts( $rows ) )->toBe( array( 'Inkmoderator', 'Anze' ) );
	expect( ResultPostAuthors::loginsFromPosts( array() ) )->toBe( array() );
} );

it( 'counts an editor, or a login that authored a result post, in any letter case', function (): void {
	expect( ResultPostAuthors::matches( 'Redakteur', true, array() ) )->toBeTrue();
	expect( ResultPostAuthors::matches( 'inkmoderator', false, array( 'Inkmoderator' ) ) )->toBeTrue();
	expect( ResultPostAuthors::matches( 'Lid', false, array( 'Inkmoderator' ) ) )->toBeFalse();
} );
