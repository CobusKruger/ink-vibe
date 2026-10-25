<?php
// Post-migration counts (read-only). Run AFTER migrate-all with:
//   wp --skip-plugins --skip-themes eval-file <repo>/docs/migration-tools/post-counts.php
global $wpdb;

function show( string $title, array $rows ): void {
	echo "\n== $title ==\n";
	foreach ( $rows as $r ) {
		echo '  ' . implode( "\t", array_map( 'strval', (array) $r ) ) . "\n";
	}
}

show( 'published posts by type', $wpdb->get_results( "SELECT post_type, COUNT(*) FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('post','gedig','storie','artikel','skryfwerk','biblioteek_item','opleiding_artikel','uitdaging','inkpols_uitgawe','bibliotekon','opleiding') GROUP BY post_type ORDER BY 2 DESC", ARRAY_N ) );
show( 'draft posts by type', $wpdb->get_results( "SELECT post_type, COUNT(*) FROM {$wpdb->posts} WHERE post_status='draft' AND post_type IN ('gedig','storie','artikel','skryfwerk') GROUP BY post_type", ARRAY_N ) );
show( 'legacy types still untouched (expected: projek-wenners children + dropped placeholders only)', $wpdb->get_results( "SELECT post_type, COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('bibliotekon','opleiding') AND post_status='publish' GROUP BY post_type", ARRAY_N ) );
show( 'skryfwerk residue by category (should be small)', $wpdb->get_results( "SELECT t.slug, COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' JOIN {$wpdb->terms} t ON t.term_id=tt.term_id WHERE p.post_type='skryfwerk' AND p.post_status='publish' GROUP BY t.slug ORDER BY 2 DESC LIMIT 15", ARRAY_N ) );
show( 'skryfwerk with no category at all', $wpdb->get_results( "SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='skryfwerk' AND p.post_status='publish' AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='category' WHERE tr.object_id=p.ID)", ARRAY_N ) );
show( 'uitdagings', $wpdb->get_results( "SELECT COUNT(*), SUM(post_status='publish') FROM {$wpdb->posts} WHERE post_type='uitdaging'", ARRAY_N ) );
show( 'uitdagings with the most entries (round term)', $wpdb->get_results( "SELECT p.post_title, tt.count FROM {$wpdb->posts} p JOIN {$wpdb->terms} t ON t.slug=CONCAT('uitdaging-',p.ID) JOIN {$wpdb->term_taxonomy} tt ON tt.term_id=t.term_id AND tt.taxonomy='uitdagingsrondte' WHERE p.post_type='uitdaging' ORDER BY tt.count DESC LIMIT 8", ARRAY_N ) );
show( 'result posts linked to an uitdaging / total result-looking posts', $wpdb->get_results( "SELECT SUM(m.meta_value IS NOT NULL), COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='ink_wenneraankondiging_uitdaging' WHERE p.post_type='post' AND p.post_status='publish' AND p.post_title REGEXP '(uitsla|wenner)' AND p.post_title REGEXP '(projek|kompetisie|kommentaar)'", ARRAY_N ) );
show( 'biblioteek genre terms (collection/shelf) with item counts', $wpdb->get_results( "SELECT t.name, COUNT(*) FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='genre' JOIN {$wpdb->terms} t ON t.term_id=tt.term_id JOIN {$wpdb->posts} p ON p.ID=tr.object_id AND p.post_type='biblioteek_item' GROUP BY t.name ORDER BY 2 DESC", ARRAY_N ) );
show( 'opleiding vaardigheid terms with article counts', $wpdb->get_results( "SELECT t.name, COUNT(*) FROM {$wpdb->term_relationships} tr JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id AND tt.taxonomy='vaardigheid' JOIN {$wpdb->terms} t ON t.term_id=tt.term_id JOIN {$wpdb->posts} p ON p.ID=tr.object_id AND p.post_type='opleiding_artikel' GROUP BY t.name ORDER BY 2 DESC", ARRAY_N ) );
show( 'tiers set (writer_tier usermeta values)', $wpdb->get_results( "SELECT meta_value, COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key='ink_writer_tier' GROUP BY meta_value", ARRAY_N ) );
show( 'migration step outcomes (ink_migrate_all_state)', array( array( get_option( 'ink_migrate_all_state' ) ? wp_json_encode( get_option( 'ink_migrate_all_state' ) ) : '(none)' ) ) );
