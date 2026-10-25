<?php
// Scores LegacyResultsParser against the hand-verified July 2026 winner list (originals/winner-list.csv).
// Read-only. Run from the WordPress root of a migrated copy:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/validate-parser.php
// SHOW=n worst posts to detail, XSHOW=n posts with extra parsed rows.
// Read-only: score LegacyResultsParser against the July 2026 hand-verified winner-list.csv.
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Migration/LegacyResultsParser.php';
use Ink\Migration\LegacyResultsParser;

function norm( string $s ): string {
	$s = mb_strtolower( $s );
	$lead = ( 1 === preg_match( "/^[\x{2019}'ŉ]n\b|^ŉ/u", $s ) );
	$s = preg_replace( '/[^\p{L}\p{N}]+/u', '', $s );
	if ( $lead && str_starts_with( $s, 'n' ) ) { $s = substr( $s, 1 ); }
	return $s;
}
function writer_key( string $s ): string { return norm( preg_replace( '/\(.*?\)/u', '', $s ) ); }

$csv = fopen( dirname( __DIR__ ) . '/data-cleanup/winners/originals/winner-list.csv', 'r' );
$head = fgetcsv( $csv );
$exp = array(); $heading = array();
while ( ( $r = fgetcsv( $csv ) ) !== false ) {
	$row = array_combine( $head, $r );
	$link = $row['Resolved Link'] ?: $row['Link'];
	$slug = basename( rtrim( (string) parse_url( $link, PHP_URL_PATH ), '/' ) );
	if ( '' === $slug || 1 === preg_match( '/^\d+$/', $slug ) ) { $slug = 'q:' . $link; }
	$exp[ $slug ][] = $row; $heading[ $slug ] = $row['Heading'];
}
global $wpdb;
$known = array();
foreach ( $wpdb->get_results( "SELECT display_name, user_nicename, user_login FROM {$wpdb->users}" ) as $u ) { $known[] = $u->display_name; $known[] = $u->user_nicename; $known[] = $u->user_login; }
foreach ( $exp as $rows0 ) { foreach ( $rows0 as $r0 ) { $known[] = $r0['Writer']; $known[] = $r0['Writer (canonical)']; } }
$tot = array( 'exp' => 0, 'parsed' => 0, 'exact' => 0, 'title' => 0, 'posts' => 0, 'nopost' => 0, 'unparsed' => 0 );
$worst = array(); $extras = array(); $byYear = array();
foreach ( $exp as $slug => $rows ) {
	$post = $wpdb->get_row( $wpdb->prepare( "SELECT ID, post_content, YEAR(post_date) y FROM {$wpdb->posts} WHERE post_name=%s AND post_status='publish' AND post_type IN ('post','skryfwerk','artikel') LIMIT 1", $slug ) );
	if ( ! $post ) { $tot['nopost']++; continue; }
	$tot['posts']++;
	$res = LegacyResultsParser::parse( $post->post_content, $known );
	$tot['unparsed'] += count( $res['unparsed'] );
	$got = array();
	foreach ( $res['rows'] as $g ) { $got[ norm( $g['title'] ) ][] = $g; }
	$exact = 0; $title = 0; $miss = array(); $diff = array();
	foreach ( $rows as $e ) {
		$k = norm( $e['Title'] );
		$cands = $got[ $k ] ?? array();
		if ( ! $cands ) { $miss[] = "{$e['Category']}/{$e['Grading']}/{$e['Position']} {$e['Writer']} - {$e['Title']}"; continue; }
		$title++;
		$ok = false;
		foreach ( $cands as $c ) {
			if ( writer_key( $c['writer'] ) === writer_key( $e['Writer'] ) && $c['category'] === $e['Category'] && $c['grading'] === $e['Grading'] && (string) $c['position'] === (string) $e['Position'] ) { $ok = true; break; }
		}
		if ( $ok ) { $exact++; } else { $c = $cands[0]; $diff[] = "exp {$e['Category']}/{$e['Grading']}/{$e['Position']} {$e['Writer']}  |  got {$c['category']}/{$c['grading']}/{$c['position']} {$c['writer']}  ({$e['Title']})"; }
	}
	$expKeys = array(); foreach ( $rows as $e ) { $expKeys[ norm( $e['Title'] ) ] = true; }
	foreach ( $res['rows'] as $g ) { if ( ! isset( $expKeys[ norm( $g['title'] ) ] ) ) { $tot['extra'] = ( $tot['extra'] ?? 0 ) + 1; $extras[ $slug ][] = "{$g['category']}/{$g['grading']}/{$g['position']} {$g['writer']} - {$g['title']}"; } }
	$n = count( $rows ); $tot['exp'] += $n; $tot['parsed'] += count( $res['rows'] ); $tot['exact'] += $exact; $tot['title'] += $title;
	$by = &$byYear[ $post->y ]; $by['exp'] = ( $by['exp'] ?? 0 ) + $n; $by['exact'] = ( $by['exact'] ?? 0 ) + $exact; unset( $by );
	if ( $exact < $n || $res['unparsed'] ) { $worst[ $slug ] = array( $n - $exact, $n, $exact, count( $res['rows'] ), $miss, $diff, $res['unparsed'] ); }
}
uasort( $worst, fn( $a, $b ) => $b[0] <=> $a[0] );
printf( "posts scored %d (CSV posts with no DB post: %d)\nexpected rows %d | parsed rows %d | title matched %d | fully exact %d (%.1f%%) | unparsed candidate lines %d\n", $tot['posts'], $tot['nopost'], $tot['exp'], $tot['parsed'], $tot['title'], $tot['exact'], 100 * $tot['exact'] / max( 1, $tot['exp'] ), $tot['unparsed'] );
ksort( $byYear ); foreach ( $byYear as $y => $v ) printf( "  %d: %d/%d exact (%.0f%%)\n", $y, $v['exact'], $v['exp'], 100 * $v['exact'] / max( 1, $v['exp'] ) );
$limit = (int) ( getenv( 'SHOW' ) ?: 8 ); $i = 0;
foreach ( $worst as $slug => $w ) { if ( $i++ >= $limit ) break;
	echo "\n--- $slug  exact {$w[2]}/{$w[1]} parsedRows {$w[3]}\n";
	foreach ( array_slice( $w[4], 0, 4 ) as $m ) echo "  MISSING $m\n";
	foreach ( array_slice( $w[5], 0, 4 ) as $m ) echo "  DIFF $m\n";
	foreach ( array_slice( $w[6], 0, 3 ) as $m ) echo "  UNPARSED $m\n";
}

echo "\nEXTRA parsed rows not in the July list: " . ( $tot['extra'] ?? 0 ) . " across " . count( $extras ) . " posts\n";
$i = 0; foreach ( $extras as $slug => $list ) { if ( $i++ >= (int) ( getenv( 'XSHOW' ) ?: 12 ) ) break; echo "  $slug (" . count( $list ) . "): " . implode( ' | ', array_slice( $list, 0, 2 ) ) . "\n"; }
