<?php
/**
 * Live English-leak scan (NFR-1 Layer 2) — Story 18.8.
 *
 * The runtime counterpart to `scan-placeholders.php` (the static ratchet). Crawls a
 * list of key front-end pages against a RUNNING site and scans each rendered page's
 * visible text for suspected-English leakage via {@see \Ink\Tests\Support\RenderedLeakScanner}
 * (whose detection logic is unit-tested). Pair it with `wp i18n` untranslated counts
 * for full Layer-2 coverage (see docs/test-pyramid-plan.md).
 *
 * Usage (CI/cron, needs a running site — e.g. wp-env or staging):
 *   php tools/leak-scan/scan-rendered.php https://staging.ink.example
 *
 * Exits non-zero if any crawled page leaks English — a standing gate.
 *
 * @package Ink\Tools
 */

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';

use Ink\Tests\Support\RenderedLeakScanner;

/**
 * The key front-end pages to crawl (paths; the §12 leak-vector surfaces). Mirrors
 * docs/i18n-leak-vectors.md — extend there + here together.
 *
 * @var list<string>
 */
$ink_pages = array(
	'/',
	'/ontdek',
	'/biblioteek',
	'/opleiding',
	'/uitdagings',
	'/inkpols',
	'/borge',
	'/oor-ink',
	'/kontak',
	'/lidmaatskap',
);

$base = isset( $argv[1] ) ? rtrim( (string) $argv[1], '/' ) : '';

if ( '' === $base ) {
	fwrite( STDERR, "Usage: php tools/leak-scan/scan-rendered.php <base-url>\n" );
	exit( 2 );
}

echo "Live English-leak scan (NFR-1 Layer 2)\n";
echo "Base: {$base}\n";
echo str_repeat( '-', 56 ) . "\n";

$leaks = 0;

foreach ( $ink_pages as $path ) {
	$url  = $base . $path;
	$html = @file_get_contents( $url ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- standalone CI/cron crawl tool, not WP runtime.

	if ( false === $html ) {
		fwrite( STDERR, "  ?  {$path} — kon nie laai nie (oorgeslaan)\n" );
		continue;
	}

	$found = RenderedLeakScanner::candidates( (string) $html );

	if ( array() === $found ) {
		echo "  ok {$path}\n";
		continue;
	}

	++$leaks;
	echo "  ✗  {$path} — moontlike Engels: " . implode( ', ', $found ) . "\n";
}

echo str_repeat( '-', 56 ) . "\n";

if ( $leaks > 0 ) {
	echo "FAIL: {$leaks} bladsy(e) met moontlike Engelse lekkasie.\n";
	echo "Trieer met die hand (geen AI-Afrikaans), outeur op staging, herontplooi.\n";
	exit( 1 );
}

echo "OK: geen front-end Engelse lekkasie op die gekruipte bladsye nie.\n";
exit( 0 );
