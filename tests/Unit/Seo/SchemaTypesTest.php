<?php
/**
 * Unit tests for the Rank Math schema `@type` refinement (Story 18.1, NFR-4).
 *
 * Target: {@see \Ink\Seo\SchemaTypes} — the per-CPT schema map + the
 * `rank_math/json_ld` callback. We test INK-owned OUTCOMES: the slug→@type map
 * (read from the Content single source), that the callback rewrites the Article
 * node's `@type` for an INK CPT and leaves a non-INK post untouched, and that
 * the integration is inert without Rank Math. Brain-Monkey-mocked — no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Seo;

use Ink\Seo\SchemaTypes;
use Ink\Content\PostTypes;
use Brain\Monkey;
use Brain\Monkey\Functions;

beforeEach( function (): void {
	Monkey\setUp();
} );

afterEach( function (): void {
	Monkey\tearDown();
} );

/**
 * A SchemaTypes double with ONLY the WordPress/Rank Math seams overridden, so
 * the real {@see SchemaTypes::register()} and {@see SchemaTypes::filterJsonLd()}
 * run against varied inputs without WordPress loaded (project-context: prefer an
 * overridable seam over inline function_exists()).
 */
function ink_schema_double( string $postType, bool $rankMathActive = true ): SchemaTypes {
	return new class( $postType, $rankMathActive ) extends SchemaTypes {
		public function __construct( private string $postType, private bool $rmActive ) {}

		protected function rankMathActive(): bool {
			return $this->rmActive;
		}

		protected function currentPostType(): string {
			return $this->postType;
		}
	};
}

// --- the CPT -> @type map (single source) ---

test( 'gedig and storie map to CreativeWork', function (): void {
	$schema = new SchemaTypes();

	expect( $schema->defaultTypeFor( PostTypes::GEDIG ) )->toBe( SchemaTypes::TYPE_CREATIVE_WORK );
	expect( $schema->defaultTypeFor( PostTypes::STORIE ) )->toBe( SchemaTypes::TYPE_CREATIVE_WORK );
} );

test( 'artikel maps to Article', function (): void {
	expect( ( new SchemaTypes() )->defaultTypeFor( PostTypes::ARTIKEL ) )->toBe( SchemaTypes::TYPE_ARTICLE );
} );

test( 'a non-readable INK CPT yields no override', function (): void {
	// skryfwerk is the unclassifiable bucket — not reader-facing, no schema override.
	expect( ( new SchemaTypes() )->defaultTypeFor( PostTypes::SKRYFWERK ) )->toBeNull();
} );

test( 'an unknown post type yields no override', function (): void {
	expect( ( new SchemaTypes() )->defaultTypeFor( 'page' ) )->toBeNull();
	expect( ( new SchemaTypes() )->defaultTypeFor( 'post' ) )->toBeNull();
} );

test( 'the map covers exactly the reader-facing CPTs', function (): void {
	$schema = new SchemaTypes();

	foreach ( PostTypes::readableTypes() as $cpt ) {
		expect( $schema->defaultTypeFor( $cpt ) )->not->toBeNull();
	}
} );

// --- filterJsonLd(): rewrites the Article node for an INK CPT ---

test( 'filterJsonLd rewrites the Article node @type for a gedig', function (): void {
	$schema = ink_schema_double( PostTypes::GEDIG );

	$out = $schema->filterJsonLd(
		array( 'richSnippet' => array( '@type' => 'Article', 'headline' => 'n Gedig' ) ),
		null
	);

	expect( $out['richSnippet']['@type'] )->toBe( SchemaTypes::TYPE_CREATIVE_WORK );
	// Non-@type data preserved.
	expect( $out['richSnippet']['headline'] )->toBe( 'n Gedig' );
} );

test( 'filterJsonLd rewrites an Article subtype (BlogPosting) too', function (): void {
	$schema = ink_schema_double( PostTypes::STORIE );

	$out = $schema->filterJsonLd(
		array( 'richSnippet' => array( '@type' => 'BlogPosting' ) ),
		null
	);

	expect( $out['richSnippet']['@type'] )->toBe( SchemaTypes::TYPE_CREATIVE_WORK );
} );

test( 'filterJsonLd leaves a non-INK post untouched', function (): void {
	$schema = ink_schema_double( 'post' );

	$in  = array( 'richSnippet' => array( '@type' => 'Article', 'headline' => 'A blog post' ) );
	$out = $schema->filterJsonLd( $in, null );

	expect( $out )->toBe( $in );
} );

test( 'filterJsonLd leaves non-Article nodes (e.g. Person, WebSite) untouched', function (): void {
	$schema = ink_schema_double( PostTypes::ARTIKEL );

	$out = $schema->filterJsonLd(
		array(
			'publisher' => array( '@type' => 'Organization', 'name' => 'INK' ),
			'richSnippet' => array( '@type' => 'Article', 'headline' => 'n Artikel' ),
		),
		null
	);

	expect( $out['publisher']['@type'] )->toBe( 'Organization' );
	expect( $out['richSnippet']['@type'] )->toBe( SchemaTypes::TYPE_ARTICLE );
} );

// --- inert without Rank Math ---

test( 'register wires the rank_math/json_ld filter when Rank Math is active', function (): void {
	Functions\expect( 'add_filter' )
		->once()
		->with( 'rank_math/json_ld', \Mockery::type( 'array' ), 99, 2 );

	ink_schema_double( PostTypes::GEDIG, true )->register();
} );

test( 'register is inert (adds no filter) when Rank Math is absent', function (): void {
	Functions\expect( 'add_filter' )->never();

	ink_schema_double( PostTypes::GEDIG, false )->register();
} );
