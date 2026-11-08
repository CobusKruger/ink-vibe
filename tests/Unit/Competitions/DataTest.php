<?php
/**
 * Unit tests for what `/kompetisies/` shows, and the two types' fields and save paths.
 *
 * Targets: {@see \Ink\Competitions\Data}, {@see \Ink\Competitions\Edition},
 * {@see \Ink\Competitions\Listing}, {@see \Ink\Content\FieldSets}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Competitions;

use Ink\Competitions\Data;
use Ink\Competitions\Edition;
use Ink\Competitions\Listing;
use Ink\Competitions\Module;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	ink_reset_guard_spies();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
	Functions\when( 'esc_url_raw' )->returnArg( 1 );
	Functions\when( 'absint' )->alias( fn ( $v ): int => abs( (int) $v ) );
	\WP_Query::$ink_test_posts = array();
} );

afterEach( function (): void {
	\WP_Query::$ink_test_posts = array();
	unset( $_POST );
	$_POST = array();
	Monkey\tearDown();
} );

function ink_edition( int $id, array $values ): Edition {
	return new Edition( $id, 'Kompetisie ' . $id, $values );
}

test( 'current ignores an imported post: no description, however high its year', function (): void {
	$imported = ink_edition( 1, array( 'jaar' => '2030', 'gala_hoogtepunt' => 'Hoog' ) );
	$old      = ink_edition( 2, array( 'jaar' => '2025', 'beskrywing' => 'Vorige' ) );
	$current  = ink_edition( 3, array( 'jaar' => '2026', 'beskrywing' => 'Hierdie jaar' ) );

	expect( Data::pickCurrent( array( $imported, $old, $current ) ) )->toBe( $current );
	expect( Data::pickCurrent( array( $imported ) ) )->toBeNull();
	expect( Data::pickCurrent( array() ) )->toBeNull();
} );

test( 'the default announcement is never the current edition, and is shown only when there is none', function (): void {
	$default = ink_edition( 1, array( 'standaard' => 'ja', 'beskrywing' => 'Binnekort', 'jaar' => '2030' ) );
	$real    = ink_edition( 2, array( 'jaar' => '2026', 'beskrywing' => 'Hierdie jaar' ) );

	expect( $default->isDefault() )->toBeTrue();
	expect( $default->acceptsEntries() )->toBeFalse();
	expect( $real->acceptsEntries() )->toBeTrue();
	expect( Data::pickCurrent( array( $default ) ) )->toBeNull();
	expect( Data::pickCurrent( array( $default, $real ) ) )->toBe( $real );

	expect( Data::pickShown( array( $default, $real ) ) )->toBe( $real ); // A real competition replaces it.
	expect( Data::pickShown( array( $real->isDefault() ? $real : $default ) ) )->toBe( $default );
	expect( Data::pickShown( array() )->isDefault() )->toBeTrue(); // Never no hero: the built-in text.
	expect( Data::pickShown( array() )->title )->not->toBe( '' );
} );

test( 'photos uploaded to a gala post become its gallery; a gallery list wins', function (): void {
	$new    = ink_edition( 1, array( 'jaar' => '2026', 'gala_fotos' => '' ) );
	$listed = ink_edition( 2, array( 'jaar' => '2025', 'gala_fotos' => '7,8' ) );
	$bare   = ink_edition( 3, array( 'jaar' => '2024' ) );

	$out = Data::withAttached( array( $new, $listed, $bare ), array( 1 => array( 30, 10, 20 ), 2 => array( 99 ) ) );

	expect( $out[0]->photoIds() )->toBe( array( 30, 10, 20 ) ); // Query order kept: by filename.
	expect( $out[0]->year() )->toBe( 2026 );
	expect( $out[1]->photoIds() )->toBe( array( 7, 8 ) );
	expect( $out[2] )->toBe( $bare );

	$galas = Data::pickPastGalas( $out );
	expect( $galas[0]->year )->toBe( 2026 );
	expect( $galas[0]->featured )->toHaveCount( 3 ); // No picks yet: the first photos stand in.
} );

test( 'the default announcement is never a past gala', function (): void {
	expect( Data::pickPastGalas( array( ink_edition( 1, array( 'standaard' => 'ja', 'gala_hoogtepunt' => 'X' ) ) ) ) )->toBe( array() );
} );

test( 'feature photos: the editor\'s five in slot order with their captions; else the first five of the gallery', function (): void {
	$picked = ink_edition(
		1,
		array(
			'gala_foto1'            => '10',
			'gala_foto1_onderskrif' => 'Breed',
			'gala_foto2'            => '',
			'gala_foto3'            => '30',
			'gala_foto5'            => '50',
			'gala_fotos'            => '1,2,3,4,5,6,7',
		)
	);
	$none   = ink_edition( 2, array( 'gala_fotos' => '1,2,3,4,5,6,7' ) );

	expect( $picked->featuredPhotos() )->toBe(
		array(
			array( 'id' => 10, 'caption' => 'Breed' ),
			array( 'id' => 30, 'caption' => '' ),
			array( 'id' => 50, 'caption' => '' ),
		)
	);
	expect( array_column( $none->featuredPhotos(), 'id' ) )->toBe( array( 1, 2, 3, 4, 5 ) );
	expect( ink_edition( 3, array() )->featuredPhotos() )->toBe( array() );
	expect( ink_edition( 4, array( 'gala_foto2' => '9' ) )->hasPastGala() )->toBeTrue(); // Picks alone make a gala.
} );

test( 'pastGalas: posts with a highlight or photos, highest year first', function (): void {
	$galas = Data::pickPastGalas(
		array(
			ink_edition( 1, array( 'jaar' => '2023', 'gala_hoogtepunt' => 'A' ) ),
			ink_edition( 2, array( 'jaar' => '2025', 'gala_fotos' => '11,12' ) ),
			ink_edition( 3, array( 'jaar' => '2024', 'gala_hoogtepunt' => 'B', 'gala_fotos' => '13' ) ),
			ink_edition( 4, array( 'jaar' => '2026', 'beskrywing' => 'Nog geen gala' ) ),
		)
	);

	expect( array_column( array_map( fn ( $g ): array => array( 'y' => $g->year ), $galas ), 'y' ) )->toBe( array( 2025, 2024, 2023 ) );
	expect( $galas[0]->photoIds )->toBe( array( 11, 12 ) );
	expect( $galas[0]->highlight )->toBe( '' );
	expect( $galas[1]->highlight )->toBe( 'B' );
} );

test( 'adding photos to this year makes its chip appear; nothing else is needed', function (): void {
	expect( Data::pickPastGalas( array( ink_edition( 1, array( 'jaar' => '2026', 'beskrywing' => 'X' ) ) ) ) )->toBe( array() );
	expect( Data::pickPastGalas( array( ink_edition( 1, array( 'jaar' => '2026', 'beskrywing' => 'X', 'gala_fotos' => '9' ) ) ) ) )->toHaveCount( 1 );
} );

test( 'a blank category slot does not render, and the others keep their order', function (): void {
	$edition = ink_edition(
		1,
		array(
			'kat1_naam'        => 'Gedigte',
			'kat1_ikoon'       => 'book',
			'kat1_riglyne'     => "Een\n\n Twee ",
			'kat2_naam'        => '  ',
			'kat2_tema'        => 'Sonder naam',
			'kat3_naam'        => 'Artikels',
			'kat3_ikoon'       => 'iets-vreemds',
			'kat3_woordlimiet' => '500',
		)
	);

	$categories = $edition->categories();

	expect( array_column( $categories, 'naam' ) )->toBe( array( 'Gedigte', 'Artikels' ) );
	expect( $categories[0]['riglyne'] )->toBe( array( 'Een', 'Twee' ) );
	expect( $categories[0]['ikoon'] )->toBe( 'book' );
	expect( $categories[1]['ikoon'] )->toBe( 'feather' ); // Junk folds to the first.
	expect( $categories[1]['riglyne'] )->toBe( array() );
	expect( $categories[1]['woordlimiet'] )->toBe( '500' );
} );

test( 'a blank ticket slot or programme line does not render; line order is the order', function (): void {
	$edition = ink_edition(
		1,
		array(
			'kaartjie1_etiket' => 'Algemeen',
			'kaartjie1_prys'   => 'R100',
			'kaartjie2_prys'   => 'R200',
			'kaartjie3_etiket' => 'VIP',
			'gala_program'     => "Ontvangs\r\n\r\nToesprake\nDans",
		)
	);

	expect( $edition->tickets() )->toBe(
		array(
			array( 'label' => 'Algemeen', 'price' => 'R100' ),
			array( 'label' => 'VIP', 'price' => '' ),
		)
	);
	expect( $edition->programme() )->toBe( array( 'Ontvangs', 'Toesprake', 'Dans' ) );
} );

test( 'a blank fee means free, and the genre is from the closed set', function (): void {
	$free = new Listing( 1, 'A', array( 'fooi' => '  ', 'genre' => 'fiksie' ) );
	$paid = new Listing( 2, 'B', array( 'fooi' => 'R50', 'genre' => 'iets' ) );

	expect( $free->isFree() )->toBeTrue();
	expect( $free->genre() )->toBe( 'fiksie' );
	expect( $paid->isFree() )->toBeFalse();
	expect( $paid->genre() )->toBe( 'gedigte' );
} );

test( 'valuesOf strips the prefix and ignores other meta', function (): void {
	Functions\when( 'get_post_meta' )->justReturn(
		array(
			'ink_kompetisie_jaar' => array( '2026' ),
			'ink_kompetisie_gala_fotos' => array( '1,2' ),
			'_edit_lock'          => array( 'x' ),
			'ink_eksterne_fooi'   => array( 'R5' ),
		)
	);

	expect( Data::valuesOf( 3, FieldSets::KOMPETISIE_PREFIX ) )->toBe( array( 'jaar' => '2026', 'gala_fotos' => '1,2' ) );
} );

test( 'the reads go through published posts of the right type, capped', function (): void {
	expect( Data::listings() )->toBe( array() );
	expect( \WP_Query::$ink_test_last_args['post_type'] )->toBe( PostTypes::EKSTERNE_KOMPETISIE );
	expect( \WP_Query::$ink_test_last_args['post_status'] )->toBe( 'publish' );
	expect( \WP_Query::$ink_test_last_args['posts_per_page'] )->toBe( 200 );

	expect( Data::current() )->toBeNull();
	expect( \WP_Query::$ink_test_last_args['post_type'] )->toBe( PostTypes::KOMPETISIE );
} );

test( 'the gallery sanitiser keeps the modal order and drops zeros, junk and repeats', function (): void {
	expect( FieldSets::sanitizeIdList( '12, 7,abc,0,33,7' ) )->toBe( '12,7,33' );
	expect( FieldSets::sanitizeIdList( '' ) )->toBe( '' );
	expect( FieldSets::sanitizeIdList( array( 'x' ) ) )->toBe( '' );
} );

test( 'the selects fold anything else to their first option', function (): void {
	expect( FieldSets::sanitizeIcon( 'pen' ) )->toBe( 'pen' );
	expect( FieldSets::sanitizeIcon( '<b>' ) )->toBe( 'feather' );
	expect( FieldSets::sanitizeGenre( 'gemeng' ) )->toBe( 'gemeng' );
	expect( FieldSets::sanitizeGenre( null ) )->toBe( 'gedigte' );
} );

test( 'only the post types with media inputs load the media script', function (): void {
	expect( FieldSets::usesMediaInputs( PostTypes::KOMPETISIE ) )->toBeTrue();
	expect( FieldSets::usesMediaInputs( PostTypes::INKPOLS_UITGAWE ) )->toBeTrue();
	expect( FieldSets::usesMediaInputs( PostTypes::UITDAGING ) )->toBeFalse();
	expect( FieldSets::usesMediaInputs( 'gedig' ) )->toBeFalse();
} );

test( 'the media script ships with the plugin', function (): void {
	expect( is_file( dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/assets/js/admin-media-field.js' ) )->toBeTrue();
} );

test( 'the field labels are the approved strings, and the price label is D4 key', function (): void {
	expect( FieldSets::labelFor( 'ink_kompetisie_jaar' ) )->toBe( 'jaar' );
	expect( FieldSets::labelFor( 'ink_kompetisie_kaartjie2_prys' ) )->toContain( 'Kaartjieprys' );
	expect( FieldSets::labelFor( 'ink_eksterne_url' ) )->toBe( 'Skakel' );
	expect( FieldSets::labelFor( 'ink_uitdaging_brief' ) )->toBe( 'Skei paragrawe met \'n blanko reël.' );
	expect( FieldSets::labelFor( 'nie-n-veld' ) )->toBe( 'nie-n-veld' );
} );

/**
 * Run the real save path for a post type with one posted form.
 *
 * @param string               $cpt  The post type.
 * @param array<string, mixed> $form The posted fields.
 * @return array<string, mixed> What was written.
 */
function ink_save_through_fieldsets( string $cpt, array $form ): array {
	$written = array();

	$post            = new \WP_Post();
	$post->ID        = 5;
	$post->post_type = $cpt;

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	Functions\when( 'current_user_can' )->justReturn( true );
	Functions\when( 'update_post_meta' )->alias(
		function ( int $id, string $key, $value ) use ( &$written ): void {
			$written[ $key ] = $value;
		}
	);

	$_POST = array( 'ink_content_fieldsets_nonce' => 'n' ) + $form;

	( new FieldSets() )->save( 5, $post );

	return $written;
}

test( 'the feature photos and the default flag save through FieldSets', function (): void {
	$written = ink_save_through_fieldsets(
		PostTypes::KOMPETISIE,
		array(
			'ink_kompetisie_standaard'            => 'ja',
			'ink_kompetisie_gala_foto1'           => '12',
			'ink_kompetisie_gala_foto1_onderskrif' => 'Wenner',
			'ink_kompetisie_gala_foto2'           => 'x',
			'ink_kompetisie_kat_titel'            => 'Drie kategorieë',
		)
	);

	expect( $written )->toBe(
		array(
			'ink_kompetisie_standaard'             => 'ja',
			'ink_kompetisie_kat_titel'             => 'Drie kategorieë',
			'ink_kompetisie_gala_foto1'            => 12,
			'ink_kompetisie_gala_foto1_onderskrif' => 'Wenner',
			'ink_kompetisie_gala_foto2'            => 0,
		)
	);
	expect( FieldSets::sanitizeDefaultFlag( 'iets' ) )->toBe( 'nee' );
	expect( FieldSets::sanitizeDefaultFlag( 'ja' ) )->toBe( 'ja' );
} );

test( 'ensureDefault creates the announcement once, from the built-in text', function (): void {
	$created = array();

	Functions\when( 'get_option' )->justReturn( false );
	Functions\when( 'get_posts' )->justReturn( array() );
	Functions\when( 'wp_slash' )->returnArg( 1 );
	Functions\when( 'is_wp_error' )->justReturn( false );
	Functions\when( 'wp_insert_post' )->alias(
		function ( array $post ) use ( &$created ): int {
			$created['post'] = $post;
			return 77;
		}
	);
	Functions\when( 'update_post_meta' )->alias(
		function ( int $id, string $key, $value ) use ( &$created ): void {
			$created['meta'][ $key ] = $value;
		}
	);
	Functions\expect( 'update_option' )->once()->with( Module::OPTION_DEFAULT_MADE, true, false );

	Module::ensureDefault();

	expect( $created['post']['post_type'] )->toBe( PostTypes::KOMPETISIE );
	expect( $created['post']['post_status'] )->toBe( 'publish' );
	expect( $created['meta']['ink_kompetisie_standaard'] )->toBe( 'ja' );
	expect( $created['meta'] )->toHaveKey( 'ink_kompetisie_beskrywing' );
} );

test( 'ensureDefault does nothing once made, and does not duplicate an existing one', function (): void {
	Functions\when( 'get_option' )->justReturn( true );
	Functions\expect( 'wp_insert_post' )->never();
	Module::ensureDefault();

	Functions\when( 'get_option' )->justReturn( false );
	Functions\when( 'get_posts' )->justReturn( array( 5 ) );
	Functions\expect( 'update_option' )->once();
	Module::ensureDefault();
} );

test( 'a kompetisie saves through FieldSets: number, date, select, gallery', function (): void {
	$written = ink_save_through_fieldsets(
		PostTypes::KOMPETISIE,
		array(
			'ink_kompetisie_jaar'           => '2026',
			'ink_kompetisie_gala_datum'     => '2026-11-14T18:00',
			'ink_kompetisie_kat1_ikoon'     => 'book',
			'ink_kompetisie_kat2_ikoon'     => 'hack',
			'ink_kompetisie_gala_fotos'     => '4,4,9,x',
			'ink_kompetisie_gala_program'   => "Een\nTwee",
			'ink_eksterne_fooi'             => 'nie van hierdie tipe nie',
		)
	);

	expect( $written )->toBe(
		array(
			'ink_kompetisie_jaar'         => 2026,
			'ink_kompetisie_kat1_ikoon'   => 'book',
			'ink_kompetisie_kat2_ikoon'   => 'feather',
			'ink_kompetisie_gala_datum'   => '2026-11-14',
			'ink_kompetisie_gala_program' => "Een\nTwee",
			'ink_kompetisie_gala_fotos'   => '4,9',
		)
	);
} );

test( 'an eksterne_kompetisie saves through FieldSets: text deadline, select, url', function (): void {
	$written = ink_save_through_fieldsets(
		PostTypes::EKSTERNE_KOMPETISIE,
		array(
			'ink_eksterne_sluitingsdatum' => '1 November',
			'ink_eksterne_fooi'           => '',
			'ink_eksterne_genre'          => 'niefiksie',
			'ink_eksterne_url'            => 'https://example.org/',
		)
	);

	expect( $written )->toBe(
		array(
			'ink_eksterne_sluitingsdatum' => '1 November',
			'ink_eksterne_fooi'           => '',
			'ink_eksterne_genre'          => 'niefiksie',
			'ink_eksterne_url'            => 'https://example.org/',
		)
	);
} );

test( 'without the editorial capability neither type saves', function (): void {
	$post            = new \WP_Post();
	$post->ID        = 5;
	$post->post_type = PostTypes::KOMPETISIE;

	Functions\when( 'wp_unslash' )->returnArg( 1 );
	Functions\when( 'wp_verify_nonce' )->justReturn( 1 );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	Functions\when( 'current_user_can' )->alias( fn ( string $cap ): bool => 'edit_post' === $cap );
	Functions\expect( 'update_post_meta' )->never();

	$_POST = array( 'ink_content_fieldsets_nonce' => 'n', 'ink_kompetisie_jaar' => '2026' );

	( new FieldSets() )->save( 5, $post );
} );

test( 'a save purges the Page, by its slug, through the LiteSpeed action', function (): void {
	$page     = new \WP_Post();
	$page->ID = 59192;

	Functions\when( 'get_page_by_path' )->justReturn( $page );
	Actions\expectDone( 'litespeed_purge_post' )->once()->with( 59192 );

	Module::purgePage();
} );

test( 'the module listens on both post types', function (): void {
	Functions\when( 'add_rewrite_rule' )->justReturn( null );
	( new Module() )->register();

	expect( has_filter( 'page_template_hierarchy', array( \Ink\Competitions\GalaPage::class, 'templateHierarchy' ) ) )->not->toBeFalse();

	expect( has_action( 'save_post_kompetisie', array( Module::class, 'purgePage' ) ) )->not->toBeFalse();
	expect( has_action( 'save_post_eksterne_kompetisie', array( Module::class, 'purgePage' ) ) )->not->toBeFalse();
} );
