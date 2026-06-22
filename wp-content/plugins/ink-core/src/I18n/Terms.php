<?php
/**
 * Terminology label registry — glossary-backed, single-source UI labels.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for every code-rendered INK UI display LABEL (AD-10).
 *
 * INK's controlled vocabulary lives in `docs/afrikaans-terms.md` (the human
 * source of truth). This class is its MACHINE PROJECTION of the UI-term column:
 * each glossary concept maps to one literal `__( '<Afrikaans>', 'ink-core' )`
 * definition, so re-deciding a term's wording is a one-file edit here, not a
 * codebase-wide search-and-replace.
 *
 * Relationship to the glossary (AC-6):
 *  - The glossary remains the human source of truth; a term change is made there
 *    FIRST, then reflected here.
 *  - This registry projects only the UI-term column (display labels).
 *  - DB content (page bodies, nav menus, migrated posts) is OUT OF SCOPE — those
 *    term changes remain a `wp search-replace` operation.
 *
 * gettext / make-pot contract (AC-3): the map values are LITERAL `__()` calls,
 * so `wp i18n make-pot` extracts every label — this file IS the extraction
 * surface. NEVER wrap `__()` around a variable (`__( $label )`): make-pot cannot
 * extract a dynamic string. Dynamic resolution happens by KEY (see
 * {@see Terms::label()}); the only `__()` literals for these concepts live here.
 *
 * Source language (Story 1.10 / §14.15): the literals are Afrikaans, the gettext
 * SOURCE. `ink-core` ships no English `.mo`, so gettext returns the Afrikaans
 * source unchanged — the literal IS what a member reads.
 *
 * Boundary (AC-4): this registry governs DISPLAY LABELS only. Code IDs, CPT and
 * taxonomy slugs, and enum backing values remain the existing enum/constant
 * single-source ({@see \Ink\Kernel\Tier} and the slug constants registered in
 * Stories 2.1/2.2). The registry complements — does not replace — that rule.
 *
 * @package Ink\Core
 */
final class Terms {

	/**
	 * Concept-key → literal-`__()` label map (the seed from the UI-term column).
	 *
	 * Rebuilt on every call (NOT memoized) so a runtime `switch_to_locale()` is
	 * honoured. Under the no-English-`.mo` policy the Afrikaans source is returned
	 * regardless of locale, but rebuilding keeps the lookup correct if a future
	 * Afrikaans/community `.mo` is ever added. The map is small and gettext caches
	 * its own lookups, so the cost is negligible.
	 *
	 * @return array<string, string> Concept key → translated (Afrikaans) label.
	 */
	private static function map(): array {
		return array(
			// Core people / access concepts (afrikaans-terms.md Deel 1).
			'lid'                      => __( 'Lid', 'ink-core' ),
			'skrywer'                  => __( 'Skrywer', 'ink-core' ),
			'membership'               => __( 'Lidmaatskap', 'ink-core' ),
			'betaalde_lid'             => __( 'Betaalde lid', 'ink-core' ),
			'gratis_lid'               => __( 'Gratis lid', 'ink-core' ),

			// Account-approval backstop (R6, Story 3.6 — OFF by default). These
			// labels surface ONLY when a redakteur enables the optional approval
			// queue; until then no member ever sees them (the frictionless UJ-1
			// path). Controlled-vocabulary projection of afrikaans-terms.md
			// "Rekening-goedkeuring" — flagged [NEEDS HUMAN AFRIKAANS] / pending
			// redakteur ratification there. The full member-facing PROSE (notice
			// sentences, result messages, email bodies) stays gated in
			// ui-copy-translations.md, not invented here.
			'account_pending'          => __( 'Wag op goedkeuring', 'ink-core' ),
			'account_approve'          => __( 'Goedkeur', 'ink-core' ),
			'account_reject'           => __( 'Verwerp', 'ink-core' ),
			'account_approval_queue'   => __( 'Rekening-goedkeuringstou', 'ink-core' ),

			// Writer Gradering (the progression system + grades).
			'gradering'                => __( 'Gradering', 'ink-core' ),
			'brons'                    => __( 'Brons', 'ink-core' ),
			'silwer'                   => __( 'Silwer', 'ink-core' ),
			'goud'                     => __( 'Goud', 'ink-core' ),
			'meester'                  => __( 'Meester', 'ink-core' ),

			// Contribution (general noun + the CPTs).
			'bydrae'                   => __( 'Bydrae', 'ink-core' ),
			'bydrae_plural'            => __( 'Bydraes', 'ink-core' ),
			'gedig'                    => __( 'Gedig', 'ink-core' ),
			'gedig_plural'             => __( 'Gedigte', 'ink-core' ),
			'storie'                   => __( 'Storie', 'ink-core' ),
			'storie_plural'            => __( 'Stories', 'ink-core' ),
			'artikel'                  => __( 'Artikel', 'ink-core' ),
			'artikel_plural'           => __( 'Artikels', 'ink-core' ),
			'skryfwerk'                => __( 'Skryfwerk', 'ink-core' ),
			'skryfwerk_plural'         => __( 'Skrywes', 'ink-core' ),
			'biblioteek_item'          => __( 'Biblioteekitem', 'ink-core' ),
			'biblioteek_item_plural'   => __( 'Biblioteekitems', 'ink-core' ),
			'opleiding_artikel'        => __( 'Hulpbronartikel', 'ink-core' ),
			'opleiding_artikel_plural' => __( 'Hulpbronartikels', 'ink-core' ),
			'uitdaging'                => __( 'Uitdaging', 'ink-core' ),
			'uitdaging_plural'         => __( 'Uitdagings', 'ink-core' ),
			'inkpols_uitgawe'          => __( 'Uitgawe', 'ink-core' ),
			'inkpols_uitgawe_plural'   => __( 'Uitgawes', 'ink-core' ),
			'borg'                     => __( 'Borg', 'ink-core' ),
			'borg_plural'              => __( 'Borge', 'ink-core' ),

			// Sections.
			'biblioteek'               => __( 'Biblioteek', 'ink-core' ),
			'opleiding'                => __( 'Opleiding', 'ink-core' ),

			// Taxonomies (singular / plural).
			'genre'                    => __( 'Genre', 'ink-core' ),
			'genre_plural'             => __( 'Genres', 'ink-core' ),
			'vaardigheid'              => __( 'Vaardigheidsarea', 'ink-core' ),
			'vaardigheid_plural'       => __( 'Vaardigheidsareas', 'ink-core' ),
			'uitdagingsrondte'         => __( 'Uitdagingsrondte', 'ink-core' ),
			'uitdagingsrondte_plural'  => __( 'Uitdagingsrondtes', 'ink-core' ),
			'ster_gradering'           => __( 'Ster gradering', 'ink-core' ),
			'ster_gradering_plural'    => __( 'Ster graderings', 'ink-core' ),
		);
	}

	/**
	 * Return the Afrikaans display label for a glossary concept key.
	 *
	 * Callers pass the KEY and never inline the literal. An unknown key fails
	 * safe: it returns the key itself (never a fatal, never an English string to
	 * a visitor) and, under `WP_DEBUG`, emits a developer notice so the missing
	 * concept is caught in development rather than shipped silently.
	 *
	 * @param string $key Glossary concept key (e.g. 'membership', 'gradering').
	 * @return string The label, or the key itself if unregistered.
	 */
	public static function label( string $key ): string {
		$map = self::map();

		if ( ! array_key_exists( $key, $map ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				wp_trigger_error(
					__METHOD__,
					sprintf( 'unknown terminology key "%s".', $key ),
					E_USER_NOTICE
				);
			}

			return $key;
		}

		return $map[ $key ];
	}

	/**
	 * Whether a concept key is registered.
	 *
	 * @param string $key Glossary concept key.
	 */
	public static function has( string $key ): bool {
		return array_key_exists( $key, self::map() );
	}

	/**
	 * The full key → label map.
	 *
	 * The inspectable surface for the NFR-1 English-leak scan (Story 17.4): a
	 * deterministic list of every registered label to assert is Afrikaans.
	 *
	 * @return array<string, string>
	 */
	public static function all(): array {
		return self::map();
	}
}
