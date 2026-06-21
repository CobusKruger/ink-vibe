<?php
/**
 * INK taxonomy registration.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the four INK taxonomies (Story 2.2).
 *
 * The taxonomy keys are the migration-load-bearing CODE IDs (WordPress
 * categories/tags remap onto them; `uitdagingsrondte` terms are referenced from
 * the challenge entry record) and are declared once here as class constants —
 * the single source for the IDs (mirroring {@see PostTypes}; {@see \Ink\I18n\Terms}
 * holds only the display labels). All labels are sourced from that registry
 * (AC-3) — no controlled-vocabulary noun is inlined as a literal.
 *
 * `genre` and `vaardigheid` are SHARED across the bydrae CPTs and `opleiding_artikel`
 * (AC-2): a training resource and a contribution can carry the SAME term, so
 * discovery/training surfaces (Epics 8/11) query by shared term with no per-item
 * manual editorial linking (Principle 8 — the FR-55 editorial-low-friction
 * coupling, intentional). All four are `hierarchical` (controlled checkbox
 * vocabulary) so a free-text typo cannot fork a term and silently break that
 * shared-term matching.
 *
 * Registered inside the `Ink\Content` module (AD-1) on `init`, AFTER the CPTs
 * ({@see Module::register()}) so every `object_type` target exists. No
 * registration leaks into the theme (three-layer separation).
 *
 * @package Ink\Core
 */
final class Taxonomies {

	// Migration-load-bearing code IDs — the single source for the slugs.
	public const GENRE            = 'genre';
	public const VAARDIGHEID      = 'vaardigheid';
	public const UITDAGINGSRONDTE = 'uitdagingsrondte';
	public const STER_GRADERING   = 'ster_gradering';

	/**
	 * Every INK taxonomy slug, registration order preserved.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::GENRE,
			self::VAARDIGHEID,
			self::UITDAGINGSRONDTE,
			self::STER_GRADERING,
		);
	}

	/**
	 * Register every INK taxonomy. Invoked on `init` from {@see Module::register()}
	 * after the CPTs so the `object_type` targets are already registered.
	 */
	public function register(): void {
		foreach ( self::definitions() as $slug => $def ) {
			register_taxonomy( $slug, $def['object_types'], self::args( $def ) );
		}
	}

	/**
	 * Per-taxonomy registration config.
	 *
	 * Each entry: the singular/plural {@see Terms} keys, the `object_types` it
	 * attaches to (sourced from {@see PostTypes} constants — never re-typed CPT
	 * literals), and the rewrite slug. `genre`/`vaardigheid` span the bydrae CPTs
	 * AND `opleiding_artikel` (the auto-surfacing overlap). All are hierarchical.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function definitions(): array {
		$bydraes = PostTypes::bydraeTypes();

		// Bydraes + training: the shared-term auto-surfacing surface (+ library
		// for winning works).
		$bydraes_and_training = array_merge(
			$bydraes,
			array( PostTypes::OPLEIDING_ARTIKEL, PostTypes::BIBLIOTEEK_ITEM )
		);

		// Bydraes + library (winning works) — competition/rating classification.
		$works = array_merge( $bydraes, array( PostTypes::BIBLIOTEEK_ITEM ) );

		return array(
			self::GENRE => array(
				'singular'     => 'genre',
				'plural'       => 'genre_plural',
				'object_types' => $bydraes_and_training,
				'rewrite'      => self::GENRE,
			),
			self::VAARDIGHEID => array(
				'singular'     => 'vaardigheid',
				'plural'       => 'vaardigheid_plural',
				// Training is its primary home; shared with bydraes (+ library) so
				// contributions surface against the same skill areas.
				'object_types' => array_merge(
					array( PostTypes::OPLEIDING_ARTIKEL ),
					$works
				),
				'rewrite'      => self::VAARDIGHEID,
			),
			self::UITDAGINGSRONDTE => array(
				'singular'     => 'uitdagingsrondte',
				'plural'       => 'uitdagingsrondte_plural',
				// Entered works + winning works. The term stays for discovery; the
				// `ink_entries` table (Epic 12/12A) is the authoritative record.
				'object_types' => $works,
				'rewrite'      => self::UITDAGINGSRONDTE,
			),
			self::STER_GRADERING => array(
				'singular'     => 'ster_gradering',
				'plural'       => 'ster_gradering_plural',
				'object_types' => $works,
				'rewrite'      => 'ster-gradering',
			),
		);
	}

	/**
	 * Build the `register_taxonomy` args from a definition.
	 *
	 * @param array<string, mixed> $def One {@see Taxonomies::definitions()} entry.
	 * @return array<string, mixed>
	 */
	private static function args( array $def ): array {
		return array(
			'labels'            => self::labels( (string) $def['singular'], (string) $def['plural'] ),
			'public'            => true,
			'hierarchical'      => true, // Controlled checkbox vocabulary — no free-text term forks.
			'show_in_rest'      => true, // Block editor + REST (AD-6).
			'show_admin_column' => true,
			'show_ui'           => true,
			'rewrite'           => array( 'slug' => (string) $def['rewrite'] ),
		);
	}

	/**
	 * Build the full WP taxonomy labels array from the terminology registry.
	 *
	 * The singular/plural nouns come from {@see Terms} (the single source); the
	 * composed admin chrome is generic `ink-core`-domain Afrikaans scaffolding
	 * built around those nouns via `sprintf()`. No glossary noun is inlined, and
	 * `__()` is never wrapped around a variable — so `make-pot` stays clean. Note
	 * the taxonomy label key set differs from the post-type set.
	 *
	 * @param string $singularKey Terms key for the singular label.
	 * @param string $pluralKey   Terms key for the plural label.
	 * @return array<string, string>
	 */
	private static function labels( string $singularKey, string $pluralKey ): array {
		$singular = Terms::label( $singularKey );
		$plural   = Terms::label( $pluralKey );

		return array(
			'name'                       => $plural,
			'singular_name'              => $singular,
			'menu_name'                  => $plural,
			'all_items'                  => $plural,
			/* translators: %s: the singular taxonomy label (e.g. Genre). */
			'edit_item'                  => sprintf( __( 'Wysig %s', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'view_item'                  => sprintf( __( 'Sien %s', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'update_item'                => sprintf( __( 'Werk %s by', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'add_new_item'               => sprintf( __( 'Voeg nuwe %s by', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'new_item_name'              => sprintf( __( 'Nuwe %s-naam', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'parent_item'                => sprintf( __( 'Ouer-%s', 'ink-core' ), $singular ),
			/* translators: %s: the singular taxonomy label. */
			'parent_item_colon'          => sprintf( __( 'Ouer-%s:', 'ink-core' ), $singular ),
			/* translators: %s: the plural taxonomy label. */
			'search_items'               => sprintf( __( 'Soek %s', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'popular_items'              => sprintf( __( 'Gewilde %s', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'not_found'                  => sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'no_terms'                   => sprintf( __( 'Geen %s nie.', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'back_to_items'              => sprintf( __( '← Terug na %s', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'separate_items_with_commas' => sprintf( __( 'Skei %s met kommas', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'add_or_remove_items'        => sprintf( __( 'Voeg %s by of verwyder', 'ink-core' ), $plural ),
			/* translators: %s: the plural taxonomy label. */
			'choose_from_most_used'      => sprintf( __( 'Kies uit die mees gebruikte %s', 'ink-core' ), $plural ),
		);
	}
}
