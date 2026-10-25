<?php
/**
 * Unit tests for turning a stored profile-field bio into plain text.
 *
 * Target: {@see \Ink\Social\SkrywerProfiel::cleanBio()}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Social;

use Ink\Social\SkrywerProfiel;

test( 'a profile-field bio loses markup, decodes entities and collapses runs of spaces', function (): void {
	expect( SkrywerProfiel::cleanBio( "<p>Met sy   vingers &amp; sleutelbord</p>\t" ) )->toBe( 'Met sy vingers & sleutelbord' );
} );

test( 'an empty profile field stays empty', function (): void {
	expect( SkrywerProfiel::cleanBio( '' ) )->toBe( '' );
} );
