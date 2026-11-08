<?php
/**
 * Unit tests for the Ontdek search block (Story 8.4, FR-35, AD-7).
 *
 * Target: {@see \Ink\Discovery\Search}. The pure `worksQueryArgs()` /
 * `skrywersQueryArgs()` (the folded-index LIKE shape) and the pure `toHtml()`
 * (form + result groups + empty-state) are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\Search;
use Ink\Discovery\SearchIndex;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

function ink_search_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_attr__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
}

/**
 * A `$wpdb` stand-in: table names, `esc_like` and a `prepare` that quotes and substitutes in order.
 */
function ink_search_fake_db(): object {
	return new class() {
		public string $posts    = 'wp_posts';
		public string $postmeta = 'wp_postmeta';
		public string $users    = 'wp_users';
		public string $usermeta = 'wp_usermeta';

		public function esc_like( string $text ): string {
			return addcslashes( $text, '_%\\' );
		}

		/** @param mixed ...$args */
		public function prepare( string $query, ...$args ): string {
			$args = ( 1 === count( $args ) && is_array( $args[0] ) ) ? $args[0] : $args;
			$i    = 0;

			return (string) preg_replace_callback(
				'/%[sd]/',
				static function ( array $m ) use ( &$i, $args ): string {
					$v = $args[ $i++ ];

					return '%d' === $m[0] ? (string) (int) $v : "'" . str_replace( "'", "''", (string) $v ) . "'";
				},
				$query
			);
		}
	};
}

test( 'queryKey ignores case, accents, spacing, line endings and the index separator', function (): void {
	expect( Search::queryKey( "  Die\r\nREËN | " ) )->toBe( 'die reen' );
	expect( Search::queryKey( 'an na' ) )->not->toBe( Search::queryKey( 'anna' ) ); // words never run together
	expect( Search::queryKey( 'Môre' ) )->toBe( Search::queryKey( 'more' ) );
	expect( Search::queryKey( '  ' ) )->toBe( '' );
} );

test( 'worksSql ranks exact author, exact title, partial author, partial title, then body', function (): void {
	$sql = Search::worksSql( ink_search_fake_db(), 'anna visser', 24 );

	$exact_author = strpos( $sql, "a.meta_value LIKE '%|anna visser|%' THEN 0" );
	$exact_title  = strpos( $sql, "t.meta_value = 'anna visser' THEN 1" );
	$part_author  = strpos( $sql, "a.meta_value LIKE '%anna visser%' THEN 2" );
	$part_title   = strpos( $sql, "t.meta_value LIKE '%anna visser%' THEN 3" );

	expect( $exact_author )->toBeInt();
	expect( $exact_author )->toBeLessThan( $exact_title );
	expect( $exact_title )->toBeLessThan( $part_author );
	expect( $part_author )->toBeLessThan( $part_title );
	expect( $sql )->toContain( 'ELSE 4 END AS tier' );
	expect( $sql )->toContain( 'ORDER BY tier ASC' );
	expect( $sql )->toContain( "p.post_status = 'publish'" );
	expect( $sql )->toContain( "p.post_type IN ('gedig','storie','artikel')" );
	expect( $sql )->toContain( "b.meta_key = '" . SearchIndex::BODY_META . "'" );
	expect( $sql )->toContain( 'OR b.meta_value LIKE' );
	expect( $sql )->toEndWith( 'LIMIT 24' );
} );

test( 'worksSql leaves the body out for keys shorter than three characters', function (): void {
	expect( Search::worksSql( ink_search_fake_db(), 'an', 24 ) )->not->toContain( 'OR b.meta_value LIKE' );
	expect( Search::worksSql( ink_search_fake_db(), 'ann', 24 ) )->toContain( 'OR b.meta_value LIKE' );
} );

test( 'worksSql escapes LIKE wildcards in the key', function (): void {
	expect( Search::worksSql( ink_search_fake_db(), '100%_', 5 ) )->toContain( '100\\%\\_' );
} );

test( 'skrywersSql matches the name index, exact names first', function (): void {
	$sql = Search::skrywersSql( ink_search_fake_db(), 'anna', 12 );

	expect( $sql )->toContain( "m.meta_key = '" . SearchIndex::SKRYWER_META . "'" );
	expect( $sql )->toContain( "m.meta_value LIKE '%|anna|%' THEN 0" );
	expect( $sql )->toContain( "WHERE m.meta_value LIKE '%anna%'" );
	expect( $sql )->toContain( 'ORDER BY tier ASC' );
	expect( $sql )->toEndWith( 'LIMIT 12' );
} );

test( 'toHtml with no query renders the search form only (no Keer terug)', function (): void {
	ink_search_render_stubs();

	$html = Search::toHtml( '' );

	expect( $html )->toContain( 'ink-ontdek-soek__form' );
	expect( $html )->toContain( 'Vind stories, gedigte of skrywers...' ); // placeholder
	expect( $html )->not->toContain( 'Keer terug' );
} );

test( 'toHtml with a query keeps the raw query in the input and offers Keer terug; results live in the panels', function (): void {
	ink_search_render_stubs();

	$html = Search::toHtml( 'reën' );

	expect( $html )->toContain( 'value="reën"' ); // raw (unfolded) query echoed back
	expect( $html )->toContain( 'ink-ontdek-soek__terug' );
	expect( $html )->toContain( 'Keer terug' );
	expect( $html )->not->toContain( 'ink-ontdek-soek__werke' ); // no bespoke result lists any more
} );

test( 'search mode restricts the Bydraes and Skrywers panels to the matching ids (none = no results)', function (): void {
	expect( \Ink\Discovery\WorksArchive::queryArgs( 1, 12, null, null, null, 'nuut', array( 5, 9 ) )['post__in'] )->toBe( array( 5, 9 ) );
	expect( \Ink\Discovery\WorksArchive::queryArgs( 1, 12, null, null, null, 'nuut', array() )['post__in'] )->toBe( array( 0 ) );
	expect( \Ink\Discovery\WorksArchive::queryArgs( 1, 12, null, null )  )->not->toHaveKey( 'post__in' );

	expect( \Ink\Discovery\SkrywersTab::queryArgs( null, 'nuwe_stemme', 1, 12, array(), array( 3 ) )['include'] )->toBe( array( 3 ) );
	expect( \Ink\Discovery\SkrywersTab::queryArgs( null, 'nuwe_stemme', 1, 12, array(), array() )['include'] )->toBe( array( 0 ) );
	expect( \Ink\Discovery\SkrywersTab::queryArgs( null, 'onlangs_aktief', 1, 12, array( 7, 3, 4 ), array( 3, 4 ) )['include'] )->toBe( array( 3, 4 ) );
} );
