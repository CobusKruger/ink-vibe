<?php
/**
 * Unit tests for the kennisgewing type enum (Story 9.9, FR-44).
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\NotificationType;
use Brain\Monkey;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the kennisgewing categories carry their lowercase action values', function (): void {
	expect( NotificationType::Reaksie->value )->toBe( 'reaksie' );
	expect( NotificationType::Mention->value )->toBe( 'mention' );
	expect( NotificationType::VolgWerk->value )->toBe( 'volg_werk' );
	expect( NotificationType::Uitdaging->value )->toBe( 'uitdaging' );
	expect( NotificationType::LidmaatskapVerval->value )->toBe( 'lidmaatskap_verval' );
	expect( NotificationType::Ontvangs->value )->toBe( 'ontvangs' );
} );

test( 'all six FR-44 categories are present and the BP component is ink', function (): void {
	expect( NotificationType::cases() )->toHaveCount( 6 );
	expect( NotificationType::COMPONENT )->toBe( 'ink' );
} );
