<?php
/**
 * Test data for /kompetisies/ on dev (`05-kompetisies` §9). Re-runnable, and a refresh wipes it:
 *
 *   node tools/local.mjs wp eval-file tools/fixtures/kompetisies-toets.php
 *
 * Run after `migrate-all`. Never on the live site. Values use a `Toets` prefix and no copy
 * from the design. It creates one current edition and nine listings, marked with
 * `ink_toets_fixture`, and on the imported 2025, 2024 and 2023 posts sets a test highlight
 * and test captions on the photos `/kompetisies/` shows (four, three and two, as Lovable has).
 * The galleries keep every photo: `/kompetisies/gala/<jaar>/` shows them all (owner, 2026-11-02).
 *
 * @package Ink\Tools
 */

// phpcs:ignoreFile

use Ink\Competitions\Migration;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;

$mark = 'ink_toets_fixture';

$upsert = static function ( string $type, string $key, string $title, array $meta ) use ( $mark ): int {
	$found = get_posts(
		array(
			'post_type'   => $type,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'   => $mark,
					'value' => $key,
				),
			),
		)
	);

	$arr = array(
		'post_type'   => $type,
		'post_status' => 'publish',
		'post_title'  => $title,
	);

	$id = $found ? (int) $found[0] : 0;
	$id = $id ? (int) wp_update_post( array( 'ID' => $id ) + $arr ) : (int) wp_insert_post( $arr );

	update_post_meta( $id, $mark, $key );

	foreach ( $meta as $meta_key => $value ) {
		update_post_meta( $id, $meta_key, $value );
	}

	return $id;
};

$k    = static fn ( string $suffix ): string => FieldSets::kompetisieKey( $suffix );
$meta = array(
	$k( 'jaar' )            => 2026,
	$k( 'uitgawe' )         => 'Toets-uitgawe 11',
	$k( 'beskrywing' )      => 'Toets beskrywing van die jaarlikse kompetisie, lank genoeg om oor meer as een reël te loop op die meeste skerms.',
	$k( 'sluitingsdatum' )  => '2026-08-31',
	$k( 'inskrywing_nota' ) => 'Toets inskrywing',
	$k( 'naaswenner_nota' ) => 'Toets naaswennernota.',
	$k( 'gala_naam' )       => 'Toets Gala-aand 2026',
	$k( 'gala_beskrywing' ) => 'Toets beskrywing van die gala, lank genoeg om oor meer as een reël te loop.',
	$k( 'gala_datum' )      => '2026-11-14',
	$k( 'gala_deure' )      => '18:00',
	$k( 'gala_plek' )       => 'Toets Saal, Toetsdorp',
	$k( 'gala_drag' )       => 'Toets drag',
	$k( 'gala_program' )    => "Toets program een\nToets program twee\nToets program drie\nToets program vier\nToets program vyf",
);

foreach ( array( 1 => 'feather', 2 => 'book', 3 => 'pen' ) as $n => $icon ) {
	$meta[ $k( "kat{$n}_naam" ) ]        = "Toets kategorie {$n}";
	$meta[ $k( "kat{$n}_ikoon" ) ]       = $icon;
	$meta[ $k( "kat{$n}_tema" ) ]        = "Toets tema {$n}";
	$meta[ $k( "kat{$n}_beskrywing" ) ]  = "Toets beskrywing van kategorie {$n}, lank genoeg om oor meer as een reël te loop.";
	$meta[ $k( "kat{$n}_woordlimiet" ) ] = "Toets limiet {$n}";
	$meta[ $k( "kat{$n}_riglyne" ) ]     = "Toets riglyn een\nToets riglyn twee\nToets riglyn drie";
	$meta[ $k( "kaartjie{$n}_etiket" ) ] = "Toets kaartjie {$n}";
	$meta[ $k( "kaartjie{$n}_prys" ) ]   = 'R' . ( 100 * $n );
}

$created = array( 'kompetisie ' . $upsert( PostTypes::KOMPETISIE, 'huidig', 'Toets Jaarlikse Kompetisie', $meta ) );

// Lovable's genre and fee pattern; ids 6 and 8 have no note (05-kompetisies §9).
$rows = array(
	array( 'fiksie', '', true ),
	array( 'gemeng', '', true ),
	array( 'fiksie', '', true ),
	array( 'gemeng', 'R50', true ),
	array( 'gedigte', 'R30', true ),
	array( 'gedigte', 'R40', true ),
	array( 'fiksie', 'R60', false ),
	array( 'gemeng', '', true ),
	array( 'fiksie', 'R80', false ),
);
$e    = static fn ( string $suffix ): string => FieldSets::EKSTERNE_PREFIX . $suffix;
$base = time();

foreach ( $rows as $i => $row ) {
	$no = $i + 1;
	$id = $upsert(
		PostTypes::EKSTERNE_KOMPETISIE,
		"eksterne-{$no}",
		"Toets Eksterne Kompetisie {$no}",
		array(
			$e( 'organiseerder' )  => "Toets Organiseerder {$no}",
			$e( 'streek' )         => 'Toetsstreek',
			$e( 'sluitingsdatum' ) => "{$no} November",
			$e( 'prys' )           => "Toets prys {$no}",
			$e( 'fooi' )           => $row[1],
			$e( 'genre' )          => $row[0],
			$e( 'url' )            => 'https://example.org/toets-' . $no,
			$e( 'nota' )           => $row[2] ? "Toets nota {$no}, lank genoeg om oor meer as een reël te loop." : '',
		)
	);
	// Newest first means the first row needs the newest date.
	wp_update_post(
		array(
			'ID'            => $id,
			'post_date'     => wp_date( 'Y-m-d H:i:s', $base - 60 * ( $i + 1 ) ),
			'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $base - 60 * ( $i + 1 ) ),
		)
	);
	$created[] = 'eksterne_kompetisie ' . $id;
}

foreach ( array( 'gala-2025' => 4, 'gala-2024' => 3, 'gala-2023' => 2 ) as $key => $keep ) {
	$ids = get_posts(
		array(
			'post_type'   => PostTypes::KOMPETISIE,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'   => Migration::SOURCE_KEY_META,
					'value' => $key,
				),
			),
		)
	);

	if ( ! $ids ) {
		WP_CLI::warning( "{$key}: not imported; run migrate-kompetisies first." );
		continue;
	}

	$all    = array_values( array_filter( explode( ',', (string) get_post_meta( $ids[0], $k( 'gala_fotos' ), true ) ) ) );
	$photos = array_slice( $all, 0, $keep );

	foreach ( $photos as $n => $attachment ) {
		wp_update_post(
			array(
				'ID'           => (int) $attachment,
				'post_excerpt' => 'Toets onderskrif ' . ( $n + 1 ),
			)
		);
	}

	update_post_meta( $ids[0], $k( 'gala_hoogtepunt' ), "Toets hoogtepunt {$key}" );
	$created[] = "{$key} (post {$ids[0]}): " . count( $photos ) . ' captioned of ' . count( $all ) . ' photos';
}

foreach ( $created as $line ) {
	WP_CLI::log( $line );
}

WP_CLI::success( count( $created ) . ' fixtures set.' );
