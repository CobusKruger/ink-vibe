<?php
/**
 * Unit tests for publishing an issue on the site (`04-inkpols` §4a), and the current-issue read.
 *
 * Targets: {@see \Ink\InkPols\PublishGuard}, {@see \Ink\InkPols\Api}, {@see \Ink\InkPols\Issue}.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\InkPols\Api;
use Ink\InkPols\Issue;
use Ink\InkPols\PublishGuard;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'sanitize_text_field' )->returnArg( 1 );
	Functions\when( 'sanitize_textarea_field' )->returnArg( 1 );
	Functions\when( 'esc_url_raw' )->returnArg( 1 );
	Functions\when( 'absint' )->alias( fn ( $v ): int => abs( (int) $v ) );
	Functions\when( 'wp_is_post_autosave' )->justReturn( false );
	Functions\when( 'wp_is_post_revision' )->justReturn( false );
	\WP_Query::$ink_test_posts = array();
} );

afterEach( function (): void {
	\WP_Query::$ink_test_posts = array();
	Monkey\tearDown();
} );

/**
 * Every required value, filled in.
 *
 * @return array<string, mixed>
 */
function ink_full_issue(): array {
	$values = array();

	foreach ( PublishGuard::REQUIRED as $key ) {
		$values[ $key ] = 'x';
	}

	$values[ FieldSets::INKPOLS_ISSUE_DATE ] = '2026-11-01';
	$values[ FieldSets::INKPOLS_COVER_ID ]   = 12;
	$values[ FieldSets::INKPOLS_PDF_ID ]     = 13;

	return $values;
}

function ink_issue_post( string $status, string $title = 'Julie/Augustus 2026' ): \WP_Post {
	$post              = new \WP_Post();
	$post->ID          = 5;
	$post->post_type   = PostTypes::INKPOLS_UITGAWE;
	$post->post_status = $status;
	$post->post_title  = $title;

	return $post;
}

/**
 * A guard whose demotion is recorded, not performed.
 */
function ink_guard(): PublishGuard {
	return new class() extends PublishGuard {
		public array $demoted = array();

		protected function demote( int $post_id, array $labels ): void {
			$this->demoted = array( $post_id, $labels );
		}
	};
}

test( 'a complete issue has nothing missing; datum is one of the required fields', function (): void {
	expect( PublishGuard::missing( ink_full_issue() ) )->toBe( array() );
	expect( PublishGuard::REQUIRED )->toContain( FieldSets::INKPOLS_ISSUE_DATE );
} );

test( 'blank text and a zero attachment id both count as missing, in order', function (): void {
	$values                                  = ink_full_issue();
	$values[ FieldSets::INKPOLS_PDF_ID ]     = 0;
	$values[ FieldSets::INKPOLS_TEASER ]     = '   ';
	$values[ FieldSets::INKPOLS_ISSUE_DATE ] = '';

	expect( PublishGuard::missing( $values ) )->toBe(
		array( FieldSets::INKPOLS_ISSUE_DATE, FieldSets::INKPOLS_PDF_ID, FieldSets::INKPOLS_TEASER )
	);
} );

test( 'publishing with a blank field goes back to draft, naming the field', function (): void {
	$values                              = ink_full_issue();
	$values[ FieldSets::INKPOLS_TITEL ]  = '';

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $key ) => $values[ $key ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish' ) );

	expect( $guard->demoted )->toBe( array( 5, array( 'Titel' ) ) );
} );

test( 'another post type is ignored by the generic save_post hook', function (): void {
	$post            = ink_issue_post( 'publish' );
	$post->post_type = 'post';
	$guard           = ink_guard();
	$guard->onSave( 5, $post );

	expect( $guard->demoted )->toBe( array() );
} );

test( 'a blank naam (post title) is missing too', function (): void {
	$values = ink_full_issue();

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $key ) => $values[ $key ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish', ' ' ) );

	expect( $guard->demoted )->toBe( array( 5, array( 'Naam' ) ) );
} );

test( 'a complete publish stays published, and a blank draft is left alone', function (): void {
	$values = ink_full_issue();

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $key ) => $values[ $key ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish' ) );
	expect( $guard->demoted )->toBe( array() );

	Functions\when( 'get_post_meta' )->justReturn( '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );
	$guard->onSave( 5, ink_issue_post( 'draft' ) );
	expect( $guard->demoted )->toBe( array() );
} );

test( 'saving sets the featured image from the cover, only when it differs', function (): void {
	$values = ink_full_issue();

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $key ) => $values[ $key ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );
	Functions\expect( 'set_post_thumbnail' )->once()->with( 5, 12 );

	ink_guard()->onSave( 5, ink_issue_post( 'draft' ) );

	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );
	ink_guard()->onSave( 5, ink_issue_post( 'draft' ) );
} );

test( 'autosaves and revisions are ignored', function (): void {
	Functions\when( 'wp_is_post_autosave' )->justReturn( true );
	Functions\expect( 'get_post_meta' )->never();

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish' ) );

	expect( $guard->demoted )->toBe( array() );
} );

test( 'the guard stays out of the way under WP-CLI: the migration inserts first and writes meta after', function (): void {
	// Pest runs without WP_CLI, so the hook is wired here; the same register() returns early under the CLI.
	( new PublishGuard() )->register();

	// Core fires `save_post_{type}` BEFORE `save_post`, and the field box saves on `save_post`:
	// a typed hook would judge the previous save's meta (three saves to publish, found in a browser).
	expect( has_action( 'save_post' ) )->toBeTrue();
	expect( has_action( 'save_post_' . PostTypes::INKPOLS_UITGAWE ) )->toBeFalse();
	expect( file_get_contents( dirname( __DIR__, 3 ) . '/wp-content/plugins/ink-core/src/InkPols/PublishGuard.php' ) )->toContain( "defined( 'WP_CLI' ) && constant( 'WP_CLI' )" );
} );

test( 'the notice shows once, with a copy-debt marker and the labels', function (): void {
	Functions\when( 'get_current_user_id' )->justReturn( 3 );
	Functions\when( 'get_transient' )->justReturn( array( 'Titel', 'PDF (heg-ID)' ) );
	Functions\expect( 'delete_transient' )->once()->with( PublishGuard::NOTICE_TRANSIENT . '3' );

	ob_start();
	( new PublishGuard() )->renderNotice();
	$html = (string) ob_get_clean();

	expect( $html )->toContain( '[NEEDS HUMAN AFRIKAANS] INKPOLS-ADMIN-ONVOLLEDIG' );
	expect( $html )->toContain( 'Titel, PDF (heg-ID)' );
} );

test( 'cover and PDF use the media input, and the box renders it with the real input kept', function (): void {
	Functions\when( 'wp_nonce_field' )->justReturn( '' );
	Functions\when( 'get_post_meta' )->justReturn( '12' );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_textarea' )->returnArg( 1 );

	ob_start();
	( new FieldSets() )->renderBox( ink_issue_post( 'draft' ), array( 'args' => array( 'cpt' => PostTypes::INKPOLS_UITGAWE ) ) );
	$html = (string) ob_get_clean();

	expect( $html )->toContain( '<input type="number" id="field_ink_inkpols_cover_id" name="ink_inkpols_cover_id" value="12" class="regular-text" data-ink-media="media"' );
	expect( $html )->toContain( 'name="ink_inkpols_pdf_id"' );
	expect( $html )->not->toContain( 'ink_inkpols_volume' );
} );

// --- The current issue follows datum, not publish date. ---

test( 'issues sort by datum: an older issue published late never becomes current', function (): void {
	$late_old = new Issue( 1, 'Oud', '2025-11-01', 0, 0, '' );
	$new      = new Issue( 2, 'Nuut', '2026-07-01', 0, 0, '' );
	$middle   = new Issue( 3, 'Middel', '2026-05-01', 0, 0, '' );

	expect( array_column( array_map( fn ( Issue $i ): array => array( 'id' => $i->postId ), Api::newestFirst( array( $late_old, $new, $middle ) ) ), 'id' ) )
		->toBe( array( 2, 3, 1 ) );
} );

test( 'latestPublishedIssue goes through the query, and is null with no issue', function (): void {
	expect( Api::latestPublishedIssue() )->toBeNull();
	expect( \WP_Query::$ink_test_last_args['post_status'] )->toBe( 'publish' );
	expect( \WP_Query::$ink_test_last_args['post_type'] )->toBe( PostTypes::INKPOLS_UITGAWE );

	\WP_Query::$ink_test_posts = array( 7, 8 );
	Functions\when( 'get_the_title' )->justReturn( 'T' );
	Functions\when( 'get_post_meta' )->alias(
		fn ( int $id, string $key ) => FieldSets::INKPOLS_ISSUE_DATE === $key ? ( 7 === $id ? '2025-01-01' : '2026-01-01' ) : ''
	);

	expect( Api::latestPublishedIssue()->postId )->toBe( 8 );
	expect( Api::publishedIssues() )->toHaveCount( 2 );
} );

test( 'items come in glans order, with blanks left out, and carry the tipe the editor typed', function (): void {
	$issue = new Issue(
		1,
		'T',
		'',
		0,
		0,
		'',
		'Kop',
		array(
			3 => array( 'title' => 'Derde', 'writer' => 'C', 'tipe' => 'Onderhoud' ),
			1 => array( 'title' => 'Eerste', 'writer' => 'A', 'tipe' => 'Gedig' ),
			2 => array( 'title' => ' ', 'writer' => 'B', 'tipe' => 'Storie' ),
		)
	);

	expect( $issue->titel )->toBe( 'Kop' );
	expect( $issue->items() )->toBe(
		array(
			array( 'tipe' => 'Gedig', 'title' => 'Eerste', 'writer' => 'A' ),
			array( 'tipe' => 'Onderhoud', 'title' => 'Derde', 'writer' => 'C' ),
		)
	);
} );

test( 'forPost reads the titel and all nine glans cells', function (): void {
	Functions\when( 'get_the_title' )->justReturn( 'Naam' );
	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $key ): string => $key );

	$issue = Issue::forPost( 4 );

	expect( $issue->titel )->toBe( FieldSets::INKPOLS_TITEL );
	expect( $issue->items() )->toBe(
		array(
			array( 'tipe' => FieldSets::INKPOLS_GLANS_1_TIPE, 'title' => FieldSets::INKPOLS_GLANS_1_TITEL, 'writer' => FieldSets::INKPOLS_GLANS_1_SKRYWER ),
			array( 'tipe' => FieldSets::INKPOLS_GLANS_2_TIPE, 'title' => FieldSets::INKPOLS_GLANS_2_TITEL, 'writer' => FieldSets::INKPOLS_GLANS_2_SKRYWER ),
			array( 'tipe' => FieldSets::INKPOLS_GLANS_3_TIPE, 'title' => FieldSets::INKPOLS_GLANS_3_TITEL, 'writer' => FieldSets::INKPOLS_GLANS_3_SKRYWER ),
		)
	);
} );

test( 'all nine glans fields are required, and the retired Gedig/Storie/Artikel keys no longer are', function (): void {
	$glans = array();

	foreach ( FieldSets::glansKeys() as $keys ) {
		$glans = array_merge( $glans, array_values( $keys ) );
	}

	expect( $glans )->toHaveCount( 9 );

	foreach ( $glans as $key ) {
		expect( PublishGuard::REQUIRED )->toContain( $key );
	}

	foreach ( FieldSets::INKPOLS_RETIRED_KEYS as $key ) {
		expect( PublishGuard::REQUIRED )->not->toContain( $key );
	}
} );

test( 'each missing glans field alone is caught and named by its admin label', function ( string $key, string $label ): void {
	$values         = ink_full_issue();
	$values[ $key ] = '  ';

	expect( PublishGuard::missing( $values ) )->toBe( array( $key ) );

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $k ) => $values[ $k ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish' ) );

	expect( $guard->demoted )->toBe( array( 5, array( $label ) ) );
} )->with(
	( static function (): array {
		$cases = array();

		foreach ( array( 1, 2, 3 ) as $n ) {
			foreach ( array( 'titel', 'skrywer', 'tipe' ) as $part ) {
				$cases[ "glans {$n} {$part}" ] = array( "ink_inkpols_glans_{$n}_{$part}", "Glans {$n}: {$part}" );
			}
		}

		return $cases;
	} )()
);

test( 'several missing glans fields are all named in one notice, in display order', function (): void {
	$values                                         = ink_full_issue();
	$values[ FieldSets::INKPOLS_GLANS_3_TIPE ]      = '';
	$values[ FieldSets::INKPOLS_GLANS_1_SKRYWER ]   = '';

	Functions\when( 'get_post_meta' )->alias( fn ( int $id, string $k ) => $values[ $k ] ?? '' );
	Functions\when( 'get_post_thumbnail_id' )->justReturn( 12 );

	$guard = ink_guard();
	$guard->onSave( 5, ink_issue_post( 'publish' ) );

	expect( $guard->demoted )->toBe( array( 5, array( 'Glans 1: skrywer', 'Glans 3: tipe' ) ) );
} );

test( 'sanitizeTipe keeps one line of plain text and caps its length', function (): void {
	expect( FieldSets::sanitizeTipe( 'Onderhoud' ) )->toBe( 'Onderhoud' );
	expect( FieldSets::sanitizeTipe( null ) )->toBe( '' );
	expect( mb_strlen( FieldSets::sanitizeTipe( str_repeat( 'é', 200 ) ) ) )->toBe( FieldSets::GLANS_TIPE_MAX );
} );
