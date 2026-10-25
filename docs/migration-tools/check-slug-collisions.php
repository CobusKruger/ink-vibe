<?php
// Slug collisions that matter AFTER migration (read-only). Run with:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/check-slug-collisions.php
// Run it BEFORE migrate-all (legacy bibliotekon/opleiding still present): it predicts the result.
// Read-only: slug collisions that matter AFTER migration (flat slugs, post_parent cleared).
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Content/PostTypes.php';
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Content/Taxonomies.php';
require_once dirname( __DIR__, 2 ) . '/wp-content/plugins/ink-core/src/Migration/LegacyContentMap.php';
use Ink\Migration\LegacyContentMap;
global $wpdb;
$all = $wpdb->get_results( "SELECT ID, post_name, post_parent, post_type, post_status, post_title FROM {$wpdb->posts} WHERE post_status IN ('publish','draft','private') AND post_name<>'' AND post_type NOT IN ('nav_menu_item','revision','attachment')" );
$legacy = array();
foreach ( $all as $r ) { if ( in_array( $r->post_type, array( 'bibliotekon', 'opleiding' ), true ) ) $legacy[ $r->ID ] = $r; }
$final = array(); // ID => final post type
foreach ( $all as $r ) {
	if ( isset( $legacy[ $r->ID ] ) ) {
		$chain = array(); $c = $r; $g = 0;
		while ( $c && $g++ < 20 ) { array_unshift( $chain, $c->post_name ); $c = $legacy[ $c->post_parent ] ?? null; }
		$d = LegacyContentMap::decide( $r->post_type, $chain );
		if ( 'migrate' === $d['action'] ) $final[ $r->ID ] = $d['cpt'];
	} else { $final[ $r->ID ] = $r->post_type; }
}
$bySlug = array();
foreach ( $all as $r ) { if ( isset( $final[ $r->ID ] ) ) $bySlug[ $r->post_name ][] = $r; }
$n = 0;
foreach ( $bySlug as $slug => $list ) {
	if ( count( $list ) < 2 ) continue;
	// only collisions involving at least one migrated legacy item
	if ( ! array_filter( $list, fn( $r ) => isset( $legacy[ $r->ID ] ) ) ) continue;
	// WP allows same slug across DIFFERENT post types (separate url spaces); report same-final-type and page clashes.
	$byType = array();
	foreach ( $list as $r ) { $byType[ $final[ $r->ID ] ][] = $r; }
	foreach ( $byType as $type => $rows ) {
		if ( count( $rows ) > 1 ) { $n++; echo "SAME-TYPE [$type] /$slug : " . implode( ', ', array_map( fn( $r ) => $r->ID . '(' . $r->post_type . ')', $rows ) ) . "\n"; }
	}
	if ( isset( $byType['page'] ) ) { $n++; echo "PAGE CLASH /$slug : " . implode( ', ', array_map( fn( $r ) => $r->ID . ' ' . $final[ $r->ID ], $list ) ) . "\n"; }
}
echo "total: $n\n";
