<?php
/**
 * Unit tests for the committed-translation presence audit (Story 18.7, NFR-7/NFR-1).
 *
 * Target: {@see \Ink\I18n\TranslationAudit} — the pure missingTranslations() set
 * difference + the required-set single source + the filter seam. Brain-Monkey, no
 * WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\I18n;

use Ink\I18n\TranslationAudit;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

// --- pure set difference ---

test( 'missingTranslations returns the required files absent from the present set', function (): void {
	$present  = array( 'woocommerce-memberships-af.mo' );
	$required = TranslationAudit::REQUIRED_TRANSLATIONS;

	$missing = TranslationAudit::missingTranslations( $present, $required );

	expect( $missing )->not->toContain( 'woocommerce-memberships-af.mo' );
	expect( $missing )->toContain( 'woocommerce-gateway-payfast-af.mo' );
	expect( $missing )->toContain( 'real3d-flipbook-af.json' );
} );

test( 'missingTranslations is empty when every required file is present', function (): void {
	$present = TranslationAudit::REQUIRED_TRANSLATIONS;

	expect( TranslationAudit::missingTranslations( $present, TranslationAudit::REQUIRED_TRANSLATIONS ) )->toBe( array() );
} );

test( 'missingTranslations ignores extra present files', function (): void {
	$present = array_merge( TranslationAudit::REQUIRED_TRANSLATIONS, array( 'buddypress-af.mo', 'woocommerce-af.mo' ) );

	expect( TranslationAudit::missingTranslations( $present, TranslationAudit::REQUIRED_TRANSLATIONS ) )->toBe( array() );
} );

// --- the required set ---

test( 'the required set names the premium plugins (no complete community pack)', function (): void {
	$set = TranslationAudit::REQUIRED_TRANSLATIONS;

	expect( $set )->toContain( 'woocommerce-memberships-af.mo' );
	expect( $set )->toContain( 'woocommerce-gateway-payfast-af.mo' );
	expect( $set )->toContain( 'real3d-flipbook-af.json' );
} );

test( 'requiredSet honours the extension filter', function (): void {
	Functions\when( 'apply_filters' )->alias(
		static function ( string $hook, $value ) {
			if ( 'ink_i18n_required_translations' === $hook ) {
				return array_merge( (array) $value, array( 'report-content-af.mo' ) );
			}
			return $value;
		}
	);

	$set = ( new TranslationAudit() )->requiredSet();

	expect( $set )->toContain( 'report-content-af.mo' );
	expect( $set )->toContain( 'woocommerce-memberships-af.mo' );
} );
