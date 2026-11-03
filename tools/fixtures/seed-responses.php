<?php
// Dev-only test data: 3 Gemeenskapsreaksies on each reading fixture post (68211 storie, 68070 gedig, 68214 artikel;
// stable prod ids), as Lovable's three critiques. Authored by the capture user, found by login (its id changes after
// a refresh). Run: node tools/local.mjs wp eval-file tools/fixtures/seed-responses.php, then fix-dates.php
// (tools/fidelity/accept/fixtures.sh runs both, in order).
$capture_user = get_user_by( 'login', 'fidelity-capture' );
if ( ! $capture_user ) {
	fwrite( STDERR, "user 'fidelity-capture' not found: run tools/fidelity/create-capture-user.sql first\n" );
	exit( 1 );
}
$types = array( 'insig', 'lof', 'voorstel' );
$texts = array(
	'Toetsdata: die herhaling werk pragtig om die gewig van die oomblik te vestig.',
	'Toetsdata: die beelde is ongewoon maar bly geaard.',
	'Toetsdata: ek wil bietjie meer sintuiglike detail in die middel sien.',
);
foreach ( array( 68211, 68070, 68214 ) as $post_id ) {
	if ( \Ink\Engagement\ResponseStore::countForPost( $post_id ) >= 3 ) {
		continue;
	}
	foreach ( $types as $i => $t ) {
		$id = wp_insert_comment( array(
			'comment_post_ID'  => $post_id,
			'comment_type'     => 'ink_reaksie',
			'comment_content'  => $texts[ $i ],
			'user_id'          => (int) $capture_user->ID,
			'comment_author'   => 'Fidelity Capture',
			'comment_approved' => 1,
			'comment_date'     => gmdate( 'Y-m-d H:i:s', time() - 3600 * ( $i + 2 ) - 1800 ),
		) );
		add_comment_meta( $id, 'ink_response_type', $t, true );
	}
	echo "seeded $post_id\n";
}
