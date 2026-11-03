<?php
// wp eval-file tools/fixtures/ui-fixture.php create|remove . Dev-only: one entry on F1 so -meta-entries renders.
use Ink\Content\ChallengeRound;
use Ink\Content\Taxonomies;

$mode = $args[0] ?? 'create';
$ids  = get_posts( array( 'post_type' => 'gedig', 'title' => 'Toets-inskrywing November', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => 5 ) );

if ( 'remove' === $mode ) {
	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
		echo "deleted $id\n";
	}
	return;
}

if ( $ids ) {
	echo 'exists ' . (int) $ids[0] . "\n";
	return;
}

$id = wp_insert_post( array( 'post_type' => 'gedig', 'post_status' => 'publish', 'post_title' => 'Toets-inskrywing November', 'post_content' => 'Toets.', 'post_author' => (int) get_user_by( 'login', 'fidelity-capture' )->ID ) );
$slug = ChallengeRound::slugFor( (int) get_page_by_path( 'november-2026-toetsronde', OBJECT, 'uitdaging' )->ID );
if ( ! term_exists( $slug, Taxonomies::UITDAGINGSRONDTE ) ) {
	wp_insert_term( $slug, Taxonomies::UITDAGINGSRONDTE, array( 'slug' => $slug ) );
}
wp_set_object_terms( $id, $slug, Taxonomies::UITDAGINGSRONDTE );
echo "created $id in $slug\n";
