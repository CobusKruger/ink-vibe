<?php
/**
 * SEO module public facade — Story 18.1.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Seo;

defined( 'ABSPATH' ) || exit;

/**
 * SEO module facade.
 *
 * The sole public cross-module surface for SEO (AD-1). At 18.1 it exposes the
 * per-CPT schema `@type` map so other modules (or tooling) can ask "what schema
 * type does this INK CPT carry" without reaching into {@see SchemaTypes}.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The schema `@type` for a reader-facing INK CPT, or null for anything else.
	 *
	 * @param string $cpt A post-type slug.
	 */
	public static function defaultSchemaTypeFor( string $cpt ): ?string {
		return ( new SchemaTypes() )->defaultTypeFor( $cpt );
	}
}
