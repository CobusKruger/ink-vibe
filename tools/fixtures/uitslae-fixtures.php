<?php
// Dev-only fixtures for specs 01 §11, 02 §9, 03 §9. Run with wp eval-file. A refresh wipes them.

// Round ids change with every refresh (68679 became 68680): find the rounds by slug.
$r_aug = (int) get_page_by_path( 'augustus-2026-aanhalingsprojek', OBJECT, 'uitdaging' )->ID;
$r_sep = (int) get_page_by_path( 'september-2026-my-opinie-projek', OBJECT, 'uitdaging' )->ID;

$placed = array(
	// Spec 01 §11: round 68679.
	68070 => array( 1, 'goud' ), 68144 => array( 2, 'goud' ), 68201 => array( 3, 'goud' ),
	68211 => array( 1, 'goud' ), 68151 => array( 2, 'goud' ), 68214 => array( 1, 'goud' ),
	// Spec 03 §9 F2: round 68791.
	68301 => array( 1, 'goud' ), 68398 => array( 1, 'goud' ), 68446 => array( 1, 'silwer' ), 68420 => array( 1, 'goud' ),
);

foreach ( $placed as $id => $row ) {
	update_post_meta( $id, 'ink_entry_placement', $row[0] );
	update_post_meta( $id, 'ink_entry_gradering', $row[1] );
}

// Citations from the real results post, per piece where a paragraph names it.
$source = wp_strip_all_tags( get_post_field( 'post_content', 68266 ) );
$paras  = array_values( array_filter( array_map( 'trim', preg_split( '/\R+/u', $source ) ), static fn ( $p ) => mb_strlen( $p ) > 80 ) );

$lens = array( 435, 381, 254, 547, 405, 402 );

foreach ( array( 68070, 68144, 68201, 68211, 68151, 68214 ) as $index => $id ) {
	if ( get_comments( array( 'post_id' => $id, 'type' => \Ink\Challenges\ModeratorFeedback::COMMENT_TYPE, 'count' => true ) ) ) {
		continue;
	}

	wp_insert_comment(
		array(
			'comment_post_ID'  => $id,
			'comment_type'     => \Ink\Challenges\ModeratorFeedback::COMMENT_TYPE,
			'comment_content'  => rtrim( mb_substr( implode( ' ', array_slice( $paras, $index ) ), 0, $lens[ $index ] ) ), // same length as Lovable's citation in that slot (awards.ts), so the box heights compare
			'comment_approved' => 1,
			'comment_author'   => 'Toets',
		)
	);
	update_user_meta( (int) get_post_field( 'post_author', $id ), 'ink_wys_moderator_terugvoer', 1 );
}

// Letter and mentions.
update_post_meta( $r_aug, 'ink_uitdaging_brief', "Toetsdata: die eerste paragraaf van die beoordelaars se brief, net om die bladsy te meet.\n\nToetsdata: die tweede paragraaf, langer as die eerste, sodat die bladsy se ritme gemeet kan word teen die ontwerp se twee paragrawe." );
update_post_meta( $r_aug, 'ink_uitdaging_vermeldings', "Toetstitel een | Toetsskrywer\nToetstitel twee | Toetsskrywer\nToetstitel drie | Toetsskrywer" );

// F1: an open round for this month.
$open = get_page_by_path( 'november-2026-toetsronde', OBJECT, 'uitdaging' );

if ( ! $open ) {
	wp_insert_post(
		array(
			'post_type'    => 'uitdaging',
			'post_status'  => 'publish',
			'post_title'   => 'November 2026 Toetsronde',
			'post_name'    => 'november-2026-toetsronde',
			'post_date'    => '2026-11-01 00:05:00',
			'post_content' => '<p>Toetsdata: skryf \'n stuk oor enige onderwerp. Hierdie ronde bestaan net om die oop toestand van die tuisblad se kaart te meet, en \'n verversing vee dit uit.</p>',
		)
	);
}

// F3: six sponsors.
$tiers = array( 'goud', 'goud', 'silwer', 'silwer', 'silwer', 'brons' );

foreach ( $tiers as $index => $tier ) {
	$title = 'Toetsborg ' . ( $index + 1 );

	if ( get_page_by_path( sanitize_title( $title ), OBJECT, 'borg' ) ) {
		continue;
	}

	$id = wp_insert_post( array( 'post_type' => 'borg', 'post_status' => 'publish', 'post_title' => $title ) );
	update_post_meta( $id, 'ink_borg_tier', $tier );
	update_post_meta( $id, 'ink_borg_start_date', '2026-01-01' );
	update_post_meta( $id, 'ink_borg_end_date', '2027-12-31' );
}

// The rows without a post, through a dev-only mu-plugin.
$mu = WP_CONTENT_DIR . '/mu-plugins/ink-toets-uitslae.php';
wp_mkdir_p( dirname( $mu ) );
file_put_contents( $mu, <<<'MU'
<?php
// DEV FIXTURE (spec 01 §11): win rows with no post, until the importer's store exists. A refresh wipes it.
add_filter( 'ink_uitslae_rows', static function ( array $rows, int $round ): array {
	if ( __ROUND__ !== $round ) {
		return $rows;
	}
	$extra = array(
		array( 'gedig', 'silwer', 1, '… Waar storms gaan lê', 'Pierce van Tonder' ),
		array( 'gedig', 'silwer', 2, 'Waar niks ontbreek nie', 'Nicola Brown' ),
		array( 'gedig', 'silwer', 3, 'Que vadis', 'Hennie du Toit' ),
		array( 'gedig', 'brons', 1, 'Geloof, Hoop en Liefde', 'Audrey Bierman' ),
		array( 'gedig', 'brons', 2, 'As verbeelding belangriker as kennis is', 'Anja Nel' ),
		array( 'gedig', 'brons', 3, 'Toetstitel brons 3', 'Toetsskrywer' ),
		array( 'storie', 'goud', 3, 'Een dollar per jaar', 'Rika Gerryts' ),
		array( 'artikel', 'goud', 2, 'Waar siel en liggaam ontmoet', 'Pieter Mostert' ),
		array( 'artikel', 'goud', 3, 'Wees die verandering', 'Hester Steenkamp' ),
	);
	foreach ( $extra as $e ) {
		$rows[] = array( 'category' => $e[0], 'tier' => $e[1], 'position' => $e[2], 'post_id' => 0, 'title' => $e[3], 'author' => $e[4], 'user_id' => 0, 'excerpt' => '', 'citation' => 'Toetsdata: die moderator se terugvoer op hierdie stuk, net om die blok te meet.' );
	}
	return $rows;
}, 10, 2 );
MU
);
$mu_src = str_replace( '__ROUND__', (string) $r_aug, (string) file_get_contents( $mu ) );
file_put_contents( $mu, $mu_src );

echo "placed=" . count( $placed ) . " paras=" . count( $paras ) . "\n";
foreach ( array( 'uitdaging' => 'november-2026-toetsronde' ) as $type => $slug ) {
	$p = get_page_by_path( $slug, OBJECT, $type );
	echo $slug . '=' . ( $p ? $p->ID : 'none' ) . "\n";
}
echo 'borg=' . wp_count_posts( 'borg' )->publish . "\n";
