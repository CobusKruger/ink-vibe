<?php
/**
 * INK taxonomy registration.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\I18n\Terms;
use Ink\Kernel\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the three INK taxonomies (Story 2.2; `ster_gradering` removed — Gradering is not a taxonomy).
 *
 * The taxonomy keys are the migration-load-bearing CODE IDs (WordPress
 * categories/tags remap onto them; `uitdagingsrondte` terms are referenced from
 * the challenge entry record) and are declared once here as class constants —
 * the single source for the IDs (mirroring {@see PostTypes}; {@see \Ink\I18n\Terms}
 * holds only the display labels). All labels are sourced from that registry
 * (AC-3) — no controlled-vocabulary noun is inlined as a literal.
 *
 * `genre` classifies `biblioteek_item` only and `vaardigheid` `opleiding_artikel` only (owner: the two
 * vocabularies differ, and nothing shares terms between them). All are `hierarchical` (controlled
 * checkbox vocabulary) so a free-text typo cannot fork a term.
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
		);
	}

	/**
	 * Register every INK taxonomy. Invoked on `init` from {@see Module::register()}
	 * after the CPTs so the `object_type` targets are already registered.
	 */
	public function register(): void {
		foreach ( self::definitions() as $slug => $def ) {
			register_taxonomy( $slug, $def['object_types'], self::args( $slug, $def ) );
		}
	}

	/**
	 * The public rewrite slug for a taxonomy, derived from its code-id constant.
	 *
	 * The single source for the URL: underscores in a code id become hyphens for
	 * readability (a code id such as `foo_bar` → `foo-bar`), so the constant is the
	 * ONLY place a slug is declared and a constant edit propagates to the URL
	 * automatically. The other three ids have no underscore, so the transform is a
	 * no-op for them — every rewrite slug now reads identically from the constant
	 * (Story 8.1; closes the 2.2 hand-typed-`'ster-gradering'` single-source gap).
	 *
	 * @param string $code The migration-load-bearing taxonomy code id.
	 * @return string
	 */
	private static function rewriteSlug( string $code ): string {
		return str_replace( '_', '-', $code );
	}

	/**
	 * Per-taxonomy registration config.
	 *
	 * Each entry: the singular/plural {@see Terms} keys and the `object_types` it
	 * attaches to (sourced from {@see PostTypes} constants — never re-typed CPT
	 * literals). The rewrite slug is NOT declared here — it is derived from the
	 * code-id constant in {@see self::rewriteSlug()} (single source). All are hierarchical.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function definitions(): array {
		// Bydraes + library (winning works): the only holders of a challenge round.
		$works = array_merge( PostTypes::bydraeTypes(), array( PostTypes::BIBLIOTEEK_ITEM ) );

		return array(
			// Genre classifies Biblioteek items only; Opleiding has its own vocabulary (vaardigheid).
			self::GENRE            => array(
				'singular'     => 'genre',
				'plural'       => 'genre_plural',
				'object_types' => array( PostTypes::BIBLIOTEEK_ITEM ),
			),
			// Vaardigheid classifies Opleiding articles only.
			self::VAARDIGHEID      => array(
				'singular'     => 'vaardigheid',
				'plural'       => 'vaardigheid_plural',
				'object_types' => array( PostTypes::OPLEIDING_ARTIKEL ),
			),
			self::UITDAGINGSRONDTE => array(
				'singular'     => 'uitdagingsrondte',
				'plural'       => 'uitdagingsrondte_plural',
				// Entered works + winning works. The term stays for discovery; the
				// `ink_entries` table (Epic 12/12A) is the authoritative record.
				'object_types' => $works,
				// Code-managed join key (owner): terms are created on the fly by the submission
				// and migration paths, never captured by an admin, so no submenu, no column and
				// no editor panel.
				'show_ui'      => false,
			),
		);
	}

	/**
	 * Build the `register_taxonomy` args from a definition.
	 *
	 * @param string               $slug The taxonomy code id (the rewrite-slug source).
	 * @param array<string, mixed> $def  One {@see Taxonomies::definitions()} entry.
	 * @return array<string, mixed>
	 */
	private static function args( string $slug, array $def ): array {
		return array(
			'labels'            => self::labels( (string) $def['singular'], (string) $def['plural'] ),
			'public'            => true,
			'hierarchical'      => true, // Controlled checkbox vocabulary — no free-text term forks.
			'show_in_rest'      => true, // Block editor + REST (AD-6).
			'show_admin_column' => (bool) ( $def['show_ui'] ?? true ),
			'show_ui'           => (bool) ( $def['show_ui'] ?? true ),
			'rewrite'           => array( 'slug' => self::rewriteSlug( $slug ) ),
			'capabilities'      => self::termCapabilities(),
		);
	}

	/**
	 * The term-management capability map for every INK taxonomy (Story 3.3, 2.2 gap).
	 *
	 * Previously the four taxonomies used the WP default term caps — and crucially
	 * the registrar set NO `capabilities` arg, so `manage_terms`/`edit_terms`/
	 * `delete_terms` defaulted to `manage_categories`, but `assign_terms`
	 * defaulted to `edit_posts`. INK's controlled vocabulary (`genre`,
	 * `vaardigheid`, `uitdagingsrondte`) must NOT be forkable by a
	 * gratis lid / member: add/edit/delete is restricted to staff (the INK
	 * `ink_moderate` editorial cap, granted to `editor` at activation — closing the
	 * gap with a cap that IS granted to a real role, not a deny-everyone stub).
	 *
	 * `assign_terms` stays on `edit_posts` (broad): assigning an existing term to
	 * one's own contribution is an authoring action, not a vocabulary mutation — a
	 * member cannot add a NEW term, only pick from the controlled set. (The full
	 * per-tier author-cap policy is Epic 5 — deferred, noted.)
	 *
	 * @return array<string, string>
	 */
	private static function termCapabilities(): array {
		return array(
			'manage_terms' => Capabilities::MODERATE, // Staff-only (editor + admin).
			'edit_terms'   => Capabilities::MODERATE, // Staff-only.
			'delete_terms' => Capabilities::MODERATE, // Staff-only.
			'assign_terms' => 'edit_posts',           // Broad: authoring picks from the controlled set.
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
		self::assertTermKey( $singularKey );
		self::assertTermKey( $pluralKey );

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

	/**
	 * Assert a taxonomy-label concept key is registered before it is composed into
	 * labels (Story 17.4 — deferred Epic 2 review). Mirrors
	 * {@see PostTypes::assertTermKey()}: a missing/typo'd key would otherwise let
	 * `Terms::label()` fail safe to the raw machine key (e.g. `genre_plural`) and
	 * ship it as a visible taxonomy label. Surfaces the error in dev/CI via
	 * `_doing_it_wrong`; production stays fail-safe with the leak scan as backstop.
	 *
	 * @param string $key Terminology concept key.
	 */
	private static function assertTermKey( string $key ): void {
		if ( Terms::has( $key ) ) {
			return;
		}

		_doing_it_wrong(
			__METHOD__,
			sprintf( 'Taxonomy label uses unregistered terminology key "%s" — fix the definition or add the glossary term.', esc_html( $key ) ),
			'ink-core 17.4'
		);
	}
}
