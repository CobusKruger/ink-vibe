<?php
/**
 * Unit tests for the custom content-report form (Story 18.4, §8).
 *
 * Target: {@see \Ink\Forms\ReportForm} — INK-owned OUTCOMES: the pure validate()
 * rules and the pure toHtml() markup (nonce, hidden target fields, the reason
 * <select> with every option, the honeypot, the admin-post action). Brain-Monkey,
 * no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Forms;

use Ink\Forms\ReportForm;
use Ink\Forms\ReportReason;
use Ink\Forms\ReportTarget;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the block name is the single-source constant', function (): void {
	expect( ReportForm::BLOCK )->toBe( 'ink/rapporteer-vorm' );
} );

// --- validate(): accepts a good report ---

test( 'validate accepts a well-formed report from a logged-in lid', function (): void {
	$result = ( new ReportForm() )->validate( 42, ReportTarget::Werk->value, 99, ReportReason::Spam->value );

	expect( $result )->toBeTrue();
} );

// --- validate(): rejects each failure mode ---

test( 'validate rejects an anonymous reporter', function (): void {
	$result = ( new ReportForm() )->validate( 0, ReportTarget::Werk->value, 99, ReportReason::Spam->value );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'ink_rapporteer_anoniem' );
} );

test( 'validate rejects an unknown target kind', function (): void {
	$result = ( new ReportForm() )->validate( 42, 'onbekend', 99, ReportReason::Spam->value );

	expect( $result->get_error_code() )->toBe( 'ink_rapporteer_ongeldige_tipe' );
} );

test( 'validate rejects a non-positive object id', function (): void {
	$result = ( new ReportForm() )->validate( 42, ReportTarget::Resensie->value, 0, ReportReason::Spam->value );

	expect( $result->get_error_code() )->toBe( 'ink_rapporteer_ongeldige_objek' );
} );

test( 'validate rejects an unknown reason', function (): void {
	$result = ( new ReportForm() )->validate( 42, ReportTarget::Werk->value, 99, 'nonsens' );

	expect( $result->get_error_code() )->toBe( 'ink_rapporteer_ongeldige_rede' );
} );

// --- toHtml(): renders the report form ---

test( 'toHtml renders the nonce, hidden target fields, honeypot and admin-post action', function (): void {
	$html = ReportForm::toHtml( '<nonce/>', 'https://ink.test/wp-admin/admin-post.php', ReportTarget::Werk->value, 99 );

	expect( $html )->toContain( '<nonce/>' );
	expect( $html )->toContain( 'name="action" value="' . ReportForm::POST_ACTION . '"' );
	expect( $html )->toContain( 'name="' . ReportForm::FIELD_TARGET . '" value="werk"' );
	expect( $html )->toContain( 'name="' . ReportForm::FIELD_OBJECT . '" value="99"' );
	expect( $html )->toContain( ReportForm::FIELD_HONEYPOT );
} );

test( 'toHtml renders a <select> option for every ReportReason (non-vacuous)', function (): void {
	$html = ReportForm::toHtml( '', '', ReportTarget::Werk->value, 1 );

	foreach ( ReportReason::values() as $value ) {
		expect( $html )->toContain( 'value="' . $value . '"' );
	}
} );

test( 'toHtml shows the done notice after a successful report', function (): void {
	$html = ReportForm::toHtml( '', '', ReportTarget::Werk->value, 1, ReportForm::NOTICE_DONE );

	expect( $html )->toContain( 'role="status"' );
} );
