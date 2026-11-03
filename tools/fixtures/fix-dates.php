<?php
// Test data: oldest response is the insight, as Lovable's first critique.
// DETERMINISM: the response time is a relative label ("6 ure gelede", ResponsesList::relativeDateLabel(), time()).
// Its width feeds the flex-shrunk author span, so a label that ticks over between a capture and its verify moves
// the author width by 0.015 to 0.4px (measured on dev, Nov 2026). Ages are therefore set to N hours
// + 30 minutes, which keeps every label fixed for +/- 30 minutes. Re-run this (tools/fidelity/accept/fixtures.sh recentre)
// at the start of every capture / verify / determinism session; the label never changes within one.
global $wpdb;
$order = array( 'insig' => 6, 'lof' => 4, 'voorstel' => 2 );
foreach ( array( 68211, 68070, 68214 ) as $pid ) {
	$cs = get_comments( array( 'post_id' => $pid, 'type' => 'ink_reaksie', 'orderby' => 'comment_ID', 'order' => 'ASC' ) );
	foreach ( $cs as $c ) {
		$t = get_comment_meta( $c->comment_ID, 'ink_response_type', true );
		$d = gmdate( 'Y-m-d H:i:s', time() - 3600 * ( $order[ $t ] ?? 2 ) - 1800 );
		$wpdb->update( $wpdb->comments, array( 'comment_date' => $d, 'comment_date_gmt' => $d ), array( 'comment_ID' => $c->comment_ID ) );
	}
}
echo "ok\n";
