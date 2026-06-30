<?php
/**
 * Unit tests for the custom Kontak contact form (Story 15.4, FR-61).
 *
 * Target: {@see \Ink\Forms\ContactForm} — the `ink/kontak-vorm` server block + the
 * `admin-post` handler. We test INK-owned OUTCOMES: the pure {@see ContactForm::validate()}
 * rules and the pure {@see ContactForm::toHtml()} markup (nonce field, the four field
 * names, the honeypot, the admin-post action). Brain-Monkey-mocked — no WordPress/DB.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Forms;

use Ink\Forms\ContactForm;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	// is_email: a thin valid-shape stand-in for the test (real validation is WP's).
	Functions\when( 'is_email' )->alias( static fn ( string $e ) => false !== strpos( $e, '@' ) ? $e : false );
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

test( 'the block name is the single-source constant', function (): void {
	expect( ContactForm::BLOCK )->toBe( 'ink/kontak-vorm' );
} );

// --- validate(): accepts good input ---

test( 'validate accepts a complete, well-formed submission', function (): void {
	$result = ( new ContactForm() )->validate( 'Mar=na', 'marna@ink.test', 'Hallo', 'Ek wil graag betrokke raak.' );

	expect( $result )->toBeTrue();
} );

// --- validate(): rejects each failure mode with a WP_Error ---

test( 'validate rejects an empty name', function (): void {
	$result = ( new ContactForm() )->validate( '   ', 'marna@ink.test', '', 'Boodskap hier.' );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'ink_kontak_missing_name' );
} );

test( 'validate rejects an invalid email', function (): void {
	$result = ( new ContactForm() )->validate( 'Marna', 'nie-n-eposadres', '', 'Boodskap hier.' );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'ink_kontak_invalid_email' );
} );

test( 'validate rejects an empty message', function (): void {
	$result = ( new ContactForm() )->validate( 'Marna', 'marna@ink.test', 'Onderwerp', '   ' );

	expect( $result )->toBeInstanceOf( \WP_Error::class );
	expect( $result->get_error_code() )->toBe( 'ink_kontak_missing_message' );
} );

// --- toHtml(): the rendered form carries the INK-owned contract ---

test( 'toHtml renders the form posting to the admin-post action with the nonce and all fields', function (): void {
	$html = ContactForm::toHtml( '<!--nonce-->', 'https://ink.test/wp-admin/admin-post.php' );

	// Non-vacuous: it is a real form posting to admin-post with our action + nonce.
	expect( $html )->toContain( '<form' );
	expect( $html )->toContain( 'action="https://ink.test/wp-admin/admin-post.php"' );
	expect( $html )->toContain( '<!--nonce-->' );
	expect( $html )->toContain( 'value="' . ContactForm::POST_ACTION . '"' );

	// The four fields + the honeypot are present by their single-source names.
	expect( $html )->toContain( ContactForm::FIELD_NAME );
	expect( $html )->toContain( ContactForm::FIELD_EMAIL );
	expect( $html )->toContain( ContactForm::FIELD_SUBJECT );
	expect( $html )->toContain( ContactForm::FIELD_MESSAGE );
	expect( $html )->toContain( ContactForm::FIELD_HONEYPOT );
} );

test( 'toHtml shows the success notice only for the sent slug', function (): void {
	$sent = ContactForm::toHtml( '', '', ContactForm::NOTICE_SENT );
	$none = ContactForm::toHtml( '', '' );

	expect( $sent )->toContain( 'ink-kontak-vorm__notice--ok' );
	expect( $none )->not->toContain( 'ink-kontak-vorm__notice--ok' );
} );

test( 'toHtml shows the send-failure notice (not success) for the send-fail slug', function (): void {
	$fail = ContactForm::toHtml( '', '', ContactForm::NOTICE_SEND_FAIL );

	// An honest failure notice — never the "gestuur" success styling (R15 patch:
	// the handler routes here when send() could not deliver, so the visitor is not
	// told a lost message was sent).
	expect( $fail )->toContain( 'ink-kontak-vorm__notice--fout' );
	expect( $fail )->not->toContain( 'ink-kontak-vorm__notice--ok' );
} );

// --- invalidNotice(): each validation code surfaces its own per-field notice ---

test( 'invalidNotice maps each validation error code to its per-field notice slug', function (): void {
	expect( ContactForm::invalidNotice( new \WP_Error( 'ink_kontak_missing_name', 'x' ) ) )->toBe( ContactForm::NOTICE_INVALID_NAME );
	expect( ContactForm::invalidNotice( new \WP_Error( 'ink_kontak_invalid_email', 'x' ) ) )->toBe( ContactForm::NOTICE_INVALID_EMAIL );
	expect( ContactForm::invalidNotice( new \WP_Error( 'ink_kontak_missing_message', 'x' ) ) )->toBe( ContactForm::NOTICE_INVALID_MESSAGE );
} );

test( 'invalidNotice falls back to the collapsed notice for an unknown code', function (): void {
	expect( ContactForm::invalidNotice( new \WP_Error( 'iets_anders', 'x' ) ) )->toBe( ContactForm::NOTICE_INVALID );
} );

// --- toHtml(): the authored microcopy renders (field hints, privacy note, per-field errors) ---

test( 'toHtml renders the authored field hints and the privacy note', function (): void {
	$html = ContactForm::toHtml( '', '' );

	expect( $html )->toContain( 'Onderwerp (opsioneel)' );
	expect( $html )->toContain( 'Hoe ons kan help?' );
	expect( $html )->toContain( 'Ons gebruik jou besonderhede net om op hierdie boodskap te antwoord.' );
	// The message hint is wired to the textarea for assistive tech.
	expect( $html )->toContain( 'aria-describedby="' . ContactForm::FIELD_MESSAGE . '-wenk"' );
} );

test( 'toHtml surfaces the specific per-field validation message for each field notice', function (): void {
	expect( ContactForm::toHtml( '', '', ContactForm::NOTICE_INVALID_NAME ) )->toContain( 'Vul asseblief jou naam in.' );
	expect( ContactForm::toHtml( '', '', ContactForm::NOTICE_INVALID_EMAIL ) )->toContain( "Vul asseblief 'n geldige e-posadres in." );
	expect( ContactForm::toHtml( '', '', ContactForm::NOTICE_INVALID_MESSAGE ) )->toContain( "Vul asseblief 'n boodskap in." );

	// Each is an alert-role error notice, never the success styling.
	expect( ContactForm::toHtml( '', '', ContactForm::NOTICE_INVALID_NAME ) )->toContain( 'ink-kontak-vorm__notice--fout' );
	expect( ContactForm::toHtml( '', '', ContactForm::NOTICE_INVALID_NAME ) )->not->toContain( 'ink-kontak-vorm__notice--ok' );
} );
