<?php
/**
 * Unit tests for the per-type Skryf counter mode (Story 6.2, FR-17).
 *
 * Target: {@see \Ink\Submission\ContentType} — a gedig (verse) counts lines AND
 * words; storie / artikel (prose) count words only. Pure PHP, no Brain Monkey.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Submission;

use Ink\Submission\ContentType;

test( 'gedig counts lines and words; prose counts words only', function (): void {
	expect( ContentType::counterMode( 'gedig' ) )->toBe( ContentType::MODE_LINES_AND_WORDS );
	expect( ContentType::counterMode( 'storie' ) )->toBe( ContentType::MODE_WORDS );
	expect( ContentType::counterMode( 'artikel' ) )->toBe( ContentType::MODE_WORDS );

	expect( ContentType::countsLines( 'gedig' ) )->toBeTrue();
	expect( ContentType::countsLines( 'storie' ) )->toBeFalse();
	expect( ContentType::countsLines( 'artikel' ) )->toBeFalse();
} );
