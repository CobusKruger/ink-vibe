<?php
/**
 * Unit tests for the Ontdek skrywers tab (Story 8.3, FR-34, AD-7).
 *
 * Target: {@see \Ink\Discovery\SkrywersTab}. The pure `queryArgs()` (the
 * WP_User_Query writer/genre/sort shape) and the pure `toHtml()`/`controlsHtml()`
 * are unit-testable without WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Discovery;

use Ink\Discovery\SkrywersTab;
use Ink\Discovery\SkrywerIndex;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * Register the WP stubs the render path needs (escaping + URL builders).
 */
function ink_skrywers_render_stubs(): void {
	Functions\when( '__' )->returnArg( 1 );
	Functions\when( 'esc_html__' )->returnArg( 1 );
	Functions\when( 'esc_html' )->returnArg( 1 );
	Functions\when( 'esc_url' )->returnArg( 1 );
	Functions\when( 'esc_attr' )->returnArg( 1 );
	Functions\when( 'remove_query_arg' )->justReturn( '/ontdek' );
	Functions\when( 'add_query_arg' )->alias(
		static fn ( string $key, $value = '', $url = '' ): string => '/ontdek?' . $key . '=' . $value
	);
}

test( 'queryArgs always restricts to writers (EXISTS on the first-publication meta)', function (): void {
	$args = SkrywersTab::queryArgs( null, SkrywersTab::SORT_NUWE, 1, 12 );

	expect( $args['meta_query']['writer'] )->toBe(
		array( 'key' => SkrywerIndex::FIRST_PUBLISH_META, 'compare' => 'EXISTS' )
	);
	expect( $args['fields'] )->toBe( 'ID' );
	expect( $args['number'] )->toBe( 12 );
	expect( $args['paged'] )->toBe( 1 );
	// No genre filter → no form clause.
	expect( $args['meta_query'] )->not->toHaveKey( 'vorm' );
} );

test( 'a genre filter adds the matching form-flag clause', function (): void {
	$digkuns = SkrywersTab::queryArgs( 'digkuns', SkrywersTab::SORT_NUWE, 1, 12 );
	expect( $digkuns['meta_query']['vorm'] )->toBe(
		array( 'key' => 'ink_skrywer_het_gedig', 'value' => '1' )
	);

	$prosa = SkrywersTab::queryArgs( 'prosa', SkrywersTab::SORT_NUWE, 1, 12 );
	expect( $prosa['meta_query']['vorm']['key'] )->toBe( 'ink_skrywer_het_storie' );
} );

test( 'a garbage genre adds no form clause (all writers)', function (): void {
	$args = SkrywersTab::queryArgs( 'paddas', SkrywersTab::SORT_NUWE, 1, 12 );
	expect( $args['meta_query'] )->not->toHaveKey( 'vorm' );
} );

test( 'meeste-gelees sorts by the read total, nuwe-stemme by first publication, both desc', function (): void {
	$gelees = SkrywersTab::queryArgs( null, SkrywersTab::SORT_GELEES, 1, 12 );
	expect( $gelees['meta_query']['sorteer']['key'] )->toBe( SkrywerIndex::READ_TOTAL_META );
	expect( $gelees['meta_query']['sorteer']['type'] )->toBe( 'NUMERIC' );
	expect( $gelees['orderby'] )->toBe( array( 'sorteer' => 'DESC' ) );

	$nuwe = SkrywersTab::queryArgs( null, SkrywersTab::SORT_NUWE, 1, 12 );
	expect( $nuwe['meta_query']['sorteer']['key'] )->toBe( SkrywerIndex::FIRST_PUBLISH_META );
} );

test( 'an unknown sort degrades to nuwe stemme (first publication)', function (): void {
	$args = SkrywersTab::queryArgs( null, 'gewildste', 1, 12 );
	expect( $args['meta_query']['sorteer']['key'] )->toBe( SkrywerIndex::FIRST_PUBLISH_META );
} );

test( 'controlsHtml renders the genre pills + both sorts and marks the active ones', function (): void {
	ink_skrywers_render_stubs();

	$html = SkrywersTab::controlsHtml( 'digkuns', SkrywersTab::SORT_GELEES );

	expect( $html )->toContain( 'Almal' );
	expect( $html )->toContain( 'Digkuns' );
	expect( $html )->toContain( 'Prosa' );
	expect( $html )->toContain( 'Artikels' );
	expect( $html )->toContain( 'Meeste gelees' );
	expect( $html )->toContain( 'Nuwe stemme' );
	expect( $html )->toContain( 'is-active' );
	expect( $html )->toContain( 'aria-current="true"' );
} );

test( 'toHtml renders a card per skrywer (name, gradering, bio) and escapes', function (): void {
	ink_skrywers_render_stubs();

	$cards = array(
		array( 'name' => 'Anna Visser', 'profile_url' => '/skrywer/anna', 'gradering' => 'Silwer', 'bio' => 'Digter uit die Karoo.' ),
	);

	$html = SkrywersTab::toHtml( $cards, array( 'paged' => 1, 'max_pages' => 1, 'genre' => null, 'sort' => SkrywersTab::SORT_NUWE ) );

	expect( $html )->toContain( 'Skrywers' );          // heading
	expect( $html )->toContain( 'Anna Visser' );
	expect( $html )->toContain( '/skrywer/anna' );
	expect( $html )->toContain( 'Silwer' );            // gradering
	expect( $html )->toContain( 'Digter uit die Karoo.' );
	expect( $html )->toContain( 'ink-ontdek-skrywers__kontroles' );
} );

test( 'toHtml shows the empty-state line (with controls) when there are no skrywers', function (): void {
	ink_skrywers_render_stubs();

	$html = SkrywersTab::toHtml( array(), array( 'paged' => 1, 'max_pages' => 0, 'genre' => null, 'sort' => SkrywersTab::SORT_NUWE ) );

	expect( $html )->toContain( 'Skrywers' );
	expect( $html )->toContain( 'ink-ontdek-skrywers__kontroles' );
	expect( $html )->toContain( 'Geen' );
	expect( $html )->toContain( 'ink-ontdek-skrywers__leeg' );
	expect( $html )->not->toContain( 'ink-ontdek-skrywers__list' );
} );
