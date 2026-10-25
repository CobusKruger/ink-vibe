<?php
// Derives gradering from the result posts with LegacyResultsParser and diffs it against tier-import.csv.
// Read-only. Run from the WordPress root of a migrated copy:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/validate-gradering.php
// Read-only: derive gradering from the result posts with LegacyResultsParser and compare with tier-import.csv.
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Migration/LegacyResultsParser.php';
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Content/ChallengeResultTitle.php';
use Ink\Migration\LegacyResultsParser as P;
use Ink\Content\ChallengeResultTitle;

$dir = dirname( __DIR__ ) . '/data-cleanup/winners/';
function csv_rows( string $f ): array { $h = fopen( $f, 'r' ); $head = fgetcsv( $h ); $o = array(); while ( ( $r = fgetcsv( $h ) ) !== false ) { if ( count( $r ) === count( $head ) ) $o[] = array_combine( $head, $r ); } return $o; }

$name2email = array(); // nameKey => email
foreach ( csv_rows( $dir . 'writer-accounts.csv' ) as $r ) { if ( '' !== trim( $r['email'] ) ) $name2email[ P::nameKey( $r['Name'] ) ] = strtolower( trim( $r['email'] ) ); }
global $wpdb;
$known = array();
foreach ( $wpdb->get_results( "SELECT display_name, user_nicename, user_login, user_email FROM {$wpdb->users}" ) as $u ) {
	foreach ( array( $u->display_name, $u->user_nicename, $u->user_login ) as $n ) { $known[] = $n; $k = P::nameKey( $n ); if ( '' !== $k && ! isset( $name2email[ $k ] ) ) $name2email[ $k ] = strtolower( $u->user_email ); }
}
$known = array_merge( $known, array_keys( array() ) );
foreach ( csv_rows( $dir . 'originals/winner-list.csv' ) as $r ) { $known[] = $r['Writer']; $known[] = $r['Writer (canonical)']; }

$alias = array(); foreach ( csv_rows( $dir . 'writer-aliases.csv' ) as $r ) { $alias[ P::nameKey( $r['variant'] ) ] = P::nameKey( $r['canonical'] ); }
function resolve_email( string $writer, array $name2email, array $alias ): ?string {
	$writer = preg_replace( '/^(\(\s*(?:no\.?\s*)?\d+\s*%?\s*\)\s*[-–]?\s*)/iu', '', $writer );
	$writer = preg_replace( '/^gesamentlik\s+/iu', '', $writer );
	$k = P::nameKey( $writer );
	$k = $alias[ $k ] ?? $k;
	if ( isset( $name2email[ $k ] ) ) return $name2email[ $k ];
	if ( strlen( $k ) >= 8 ) { $hit = null; foreach ( $name2email as $nk => $em ) { if ( abs( strlen( $nk ) - strlen( $k ) ) <= 2 && levenshtein( $k, $nk ) <= 2 ) { if ( null !== $hit && $hit !== $em ) return null; $hit = $em; } } return $hit; }
	return null;
}
$tiers = array(); foreach ( csv_rows( $dir . 'tier-import.csv' ) as $r ) { $tiers[ strtolower( trim( $r['email'] ) ) ] = strtolower( $r['gradering'] ); }

$posts = $wpdb->get_results( "SELECT ID, post_title, post_content, post_date FROM {$wpdb->posts} WHERE post_type IN ('post','skryfwerk','artikel') AND post_status='publish' ORDER BY post_date" );
$wins = array(); $unresolved = array(); $n = 0; $last = '';
foreach ( $posts as $p ) {
	if ( ! ChallengeResultTitle::isResultTitle( $p->post_title ) ) continue;
	$n++; $last = $p->post_date;
	foreach ( P::parse( $p->post_content, $known )['rows'] as $row ) {
		if ( 'Gedigte' !== $row['category'] ) continue;
		$email = resolve_email( $row['writer'], $name2email, $alias );
		if ( null === $email ) { $unresolved[ $row['writer'] ] = ( $unresolved[ $row['writer'] ] ?? 0 ) + 1; continue; }
		$wins[ $email ] = ( $wins[ $email ] ?? 0 ) + 1;
	}
}
function tier( int $w ): string { return $w >= 15 ? 'goud' : ( $w >= 5 ? 'silwer' : ( $w >= 1 ? 'brons' : '' ) ); }
$derived = array(); foreach ( $wins as $e => $w ) $derived[ $e ] = tier( $w );

echo "result posts parsed: $n (latest $last)\n";
echo "people with Gedigte wins (derived): " . count( $derived ) . " | in tier-import.csv: " . count( $tiers ) . "\n";
$same = 0; $diff = array(); $onlyDerived = array(); $onlyCsv = array();
foreach ( $derived as $e => $t ) { if ( ! isset( $tiers[ $e ] ) ) $onlyDerived[] = "$e ($t, {$wins[$e]} wins)"; elseif ( $tiers[ $e ] === $t ) $same++; else $diff[] = "$e: csv {$tiers[$e]} vs derived $t ({$wins[$e]} wins)"; }
foreach ( $tiers as $e => $t ) { if ( ! isset( $derived[ $e ] ) ) $onlyCsv[] = "$e ($t)"; }
echo "same tier: $same | different: " . count( $diff ) . " | only derived: " . count( $onlyDerived ) . " | only in CSV: " . count( $onlyCsv ) . "\n";
echo "\nDIFFERENT:\n  " . implode( "\n  ", $diff ) . "\n";
echo "\nONLY DERIVED (no row in tier-import.csv):\n  " . implode( "\n  ", array_slice( $onlyDerived, 0, 25 ) ) . "\n";
echo "\nONLY IN CSV (derived none):\n  " . implode( "\n  ", array_slice( $onlyCsv, 0, 25 ) ) . "\n";
arsort( $unresolved );
echo "\nUNRESOLVED writer names (Gedigte wins, no account match): " . count( $unresolved ) . "\n";
foreach ( array_slice( $unresolved, 0, 25, true ) as $k => $v ) echo "  $k = $v\n";
