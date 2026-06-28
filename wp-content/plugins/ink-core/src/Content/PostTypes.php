<?php
/**
 * INK custom post-type registration.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the nine INK custom post types (Story 2.1).
 *
 * The post-type keys are the migration-load-bearing CODE IDs (old `verhaal` →
 * `storie`, `inkpols` → `inkpols_uitgawe`) and are declared once here as class
 * constants — the single source for the IDs (mirroring AD-10's enum/constant
 * discipline; {@see \Ink\I18n\Terms} holds only the display labels). All labels
 * are sourced from that registry (AC-2) — no controlled-vocabulary noun is
 * inlined as a literal.
 *
 * Registered inside the `Ink\Content` module (AD-1: Content owns CPTs/taxonomies/
 * meta), dispatched on `init` via the Kernel module seam. No registration leaks
 * into the theme (three-layer separation).
 *
 * @package Ink\Core
 */
final class PostTypes {

	/**
	 * The shared INK content `capability_type` (singular/plural pair).
	 *
	 * Story 3.3 closes the 2.1 false-isolation gap: the nine CPTs previously used
	 * `map_meta_cap => true` with NO `capability_type`, silently inheriting bare
	 * default `post` caps (every contributor/subscriber with a `post` cap could
	 * touch INK content). Mapping them to a dedicated `ink_content` capability
	 * type means INK content is governed by `edit_ink_contents` /
	 * `publish_ink_contents` / … rather than `edit_posts`. `map_meta_cap => true`
	 * stays on, so per-object meta caps (`edit_ink_content` for a specific item)
	 * are derived from these primitives with correct ownership checks.
	 *
	 * These primitive caps are granted to `administrator` + `editor` at activation
	 * ({@see capabilities()} feeds {@see \Ink\Kernel\Activation}); they are NOT
	 * granted to `subscriber`, which closes the gap. The full per-tier author cap
	 * scheme (who among members may author which CPT) is Epic 5 — deferred, noted.
	 */
	public const CAPABILITY_TYPE_SINGULAR = 'ink_content';
	public const CAPABILITY_TYPE_PLURAL   = 'ink_contents';

	// Migration-load-bearing code IDs — the single source for the slugs.
	public const GEDIG             = 'gedig';
	public const STORIE            = 'storie';
	public const ARTIKEL           = 'artikel';
	public const SKRYFWERK         = 'skryfwerk';
	public const BIBLIOTEEK_ITEM   = 'biblioteek_item';
	public const OPLEIDING_ARTIKEL = 'opleiding_artikel';
	public const UITDAGING         = 'uitdaging';
	public const INKPOLS_UITGAWE   = 'inkpols_uitgawe';
	public const BORG              = 'borg';

	/**
	 * Every INK post-type slug, registration order preserved.
	 *
	 * @return list<string>
	 */
	public static function all(): array {
		return array(
			self::GEDIG,
			self::STORIE,
			self::ARTIKEL,
			self::SKRYFWERK,
			self::BIBLIOTEEK_ITEM,
			self::OPLEIDING_ARTIKEL,
			self::UITDAGING,
			self::INKPOLS_UITGAWE,
			self::BORG,
		);
	}

	/**
	 * The member-submission CPTs ("bydraes") — the typed homes a writer plaas to.
	 *
	 * @return list<string>
	 */
	public static function bydraeTypes(): array {
		return array(
			self::GEDIG,
			self::STORIE,
			self::ARTIKEL,
			self::SKRYFWERK,
		);
	}

	/**
	 * The reader-facing bydrae types — the single source for any "list published
	 * work" surface (Ontdek archive, following-feed). The `skryfwerk` migration
	 * bucket is deliberately EXCLUDED: it is never reader-facing.
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array(
			self::GEDIG,
			self::STORIE,
			self::ARTIKEL,
		);
	}

	/**
	 * Register every INK post type. Invoked on `init` from {@see Module::register()}.
	 */
	public function register(): void {
		foreach ( self::definitions() as $slug => $def ) {
			register_post_type( $slug, self::args( $def ) );
		}
	}

	/**
	 * Per-CPT registration config.
	 *
	 * Each entry: the singular/plural {@see Terms} keys, `supports`, visibility,
	 * archive (a string archive slug, or false for no public archive), a dashicon,
	 * and the rewrite slug. `biblioteek_item`/`opleiding_artikel` keep the
	 * documented `/biblioteek/` and `/opleiding/` URL prefixes (migration plan).
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function definitions(): array {
		$bydrae_supports = array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields', 'revisions' );

		return array(
			self::GEDIG             => array(
				'singular' => 'gedig',
				'plural'   => 'gedig_plural',
				'supports' => $bydrae_supports,
				'public'   => true,
				'archive'  => self::GEDIG,
				'icon'     => 'dashicons-edit',
				'rewrite'  => self::GEDIG,
			),
			self::STORIE            => array(
				'singular' => 'storie',
				'plural'   => 'storie_plural',
				'supports' => $bydrae_supports,
				'public'   => true,
				'archive'  => self::STORIE,
				'icon'     => 'dashicons-book',
				'rewrite'  => self::STORIE,
			),
			self::ARTIKEL           => array(
				'singular' => 'artikel',
				'plural'   => 'artikel_plural',
				'supports' => $bydrae_supports,
				'public'   => true,
				'archive'  => self::ARTIKEL,
				'icon'     => 'dashicons-media-document',
				'rewrite'  => self::ARTIKEL,
			),
			self::SKRYFWERK         => array(
				'singular' => 'skryfwerk',
				'plural'   => 'skryfwerk_plural',
				'supports' => $bydrae_supports,
				'public'   => true,
				'archive'  => self::SKRYFWERK,
				'icon'     => 'dashicons-welcome-write-blog',
				'rewrite'  => self::SKRYFWERK,
			),
			self::BIBLIOTEEK_ITEM   => array(
				'singular' => 'biblioteek_item',
				'plural'   => 'biblioteek_item_plural',
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
				'public'   => true,
				'archive'  => 'biblioteek', // Documented URL prefix /biblioteek/.
				'icon'     => 'dashicons-book-alt',
				'rewrite'  => 'biblioteek',
			),
			self::OPLEIDING_ARTIKEL => array(
				'singular' => 'opleiding_artikel',
				'plural'   => 'opleiding_artikel_plural',
				'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt' ),
				'public'   => true,
				'archive'  => 'opleiding', // Documented URL prefix /opleiding/.
				'icon'     => 'dashicons-welcome-learn-more',
				'rewrite'  => 'opleiding',
			),
			self::UITDAGING         => array(
				'singular' => 'uitdaging',
				'plural'   => 'uitdaging_plural',
				'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'public'   => true,
				'archive'  => self::UITDAGING,
				'icon'     => 'dashicons-awards',
				'rewrite'  => self::UITDAGING,
			),
			self::INKPOLS_UITGAWE   => array(
				'singular' => 'inkpols_uitgawe',
				'plural'   => 'inkpols_uitgawe_plural',
				'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
				'public'   => true,
				'archive'  => 'inkpols',
				'icon'     => 'dashicons-media-text',
				'rewrite'  => 'inkpols',
			),
			self::BORG              => array(
				'singular' => 'borg',
				'plural'   => 'borg_plural',
				'supports' => array( 'title', 'editor', 'thumbnail' ),
				'public'   => true,
				'archive'  => false, // Rendered on the "Ons borge" page, not its own archive.
				'icon'     => 'dashicons-heart',
				'rewrite'  => self::BORG,
			),
		);
	}

	/**
	 * Build the `register_post_type` args from a definition.
	 *
	 * @param array<string, mixed> $def One {@see PostTypes::definitions()} entry.
	 * @return array<string, mixed>
	 */
	private static function args( array $def ): array {
		return array(
			'labels'          => self::labels( (string) $def['singular'], (string) $def['plural'] ),
			'public'          => (bool) $def['public'],
			'show_in_rest'    => true, // Block editor + REST (AD-6).
			'has_archive'     => $def['archive'],
			'supports'        => $def['supports'],
			'menu_icon'       => $def['icon'],
			'rewrite'         => array( 'slug' => (string) $def['rewrite'] ),
			// Story 3.3 (2.1 gap): a dedicated INK content capability type so the
			// CPTs no longer inherit bare default `post` caps (false isolation).
			'capability_type' => array( self::CAPABILITY_TYPE_SINGULAR, self::CAPABILITY_TYPE_PLURAL ),
			'map_meta_cap'    => true, // Derive per-object meta caps from the primitives (ownership-aware).
		);
	}

	/**
	 * The primitive INK-content capabilities `register_post_type` derives from the
	 * shared {@see CAPABILITY_TYPE_PLURAL} capability type.
	 *
	 * These are the caps that must be GRANTED to a role for INK content to be
	 * manageable (deny-everyone guard, Story 3.3): with a custom `capability_type`,
	 * even an administrator does not implicitly hold them, so the activation step
	 * ({@see \Ink\Kernel\Activation}) grants this set to `administrator` + `editor`.
	 * `subscriber` is intentionally NOT granted them — closing the 2.1 gap.
	 *
	 * @return list<string>
	 */
	public static function capabilities(): array {
		$plural   = self::CAPABILITY_TYPE_PLURAL;
		$singular = self::CAPABILITY_TYPE_SINGULAR;

		return array(
			'edit_' . $singular,
			'read_' . $singular,
			'delete_' . $singular,
			'edit_' . $plural,
			'edit_others_' . $plural,
			'publish_' . $plural,
			'read_private_' . $plural,
			'delete_' . $plural,
			'delete_private_' . $plural,
			'delete_published_' . $plural,
			'delete_others_' . $plural,
			'edit_private_' . $plural,
			'edit_published_' . $plural,
			'create_' . $plural,
		);
	}

	/**
	 * The roles that manage INK content (administrator + editor / redakteur).
	 *
	 * @return list<string>
	 */
	private static function contentRoles(): array {
		return array( 'administrator', 'editor' );
	}

	/**
	 * Grant the INK-content primitive caps to the content-managing roles.
	 *
	 * Story 3.3 deny-everyone guard: with a custom `capability_type`, NO role
	 * (not even administrator) implicitly holds {@see capabilities()} — so the
	 * activation step grants them to `administrator` + `editor`. `subscriber`
	 * (the gratis lid) is intentionally NOT granted them, closing the 2.1
	 * false-isolation gap. Persists in the DB → runs from
	 * {@see \Ink\Kernel\Activation::activate()}, not on `init`. Idempotent +
	 * fail-safe (a missing role is skipped, never fatals).
	 */
	public static function grantContentCaps(): void {
		foreach ( self::contentRoles() as $role_name ) {
			$role = get_role( $role_name );

			if ( null === $role ) {
				continue;
			}

			foreach ( self::capabilities() as $cap ) {
				$role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove the INK-content primitive caps from the content-managing roles.
	 *
	 * The activation grant's inverse, for {@see \Ink\Kernel\Activation::deactivate()}.
	 * Fail-safe when a role is absent.
	 */
	public static function revokeContentCaps(): void {
		foreach ( self::contentRoles() as $role_name ) {
			$role = get_role( $role_name );

			if ( null === $role ) {
				continue;
			}

			foreach ( self::capabilities() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	/**
	 * Build the full WP post-type labels array from the terminology registry.
	 *
	 * The singular/plural nouns come from {@see Terms} (the single source); the
	 * composed admin chrome is generic `ink-core`-domain Afrikaans scaffolding
	 * built around those nouns via `sprintf()`. No glossary noun is inlined, and
	 * `__()` is never wrapped around a variable (the scaffolding strings are
	 * literals; the noun is interpolated by `sprintf`) — so `make-pot` stays clean.
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
			'name'                  => $plural,
			'singular_name'         => $singular,
			'menu_name'             => $plural,
			'name_admin_bar'        => $singular,
			'all_items'             => $plural,
			/* translators: %s: the singular content-type label (e.g. Gedig). */
			'add_new'               => sprintf( __( 'Voeg nuwe %s', 'ink-core' ), $singular ),
			/* translators: %s: the singular content-type label (e.g. Gedig). */
			'add_new_item'          => sprintf( __( 'Voeg nuwe %s by', 'ink-core' ), $singular ),
			/* translators: %s: the singular content-type label. */
			'edit_item'             => sprintf( __( 'Wysig %s', 'ink-core' ), $singular ),
			/* translators: %s: the singular content-type label. */
			'new_item'              => sprintf( __( 'Nuwe %s', 'ink-core' ), $singular ),
			/* translators: %s: the singular content-type label. */
			'view_item'             => sprintf( __( 'Sien %s', 'ink-core' ), $singular ),
			/* translators: %s: the plural content-type label. */
			'view_items'            => sprintf( __( 'Sien %s', 'ink-core' ), $plural ),
			/* translators: %s: the plural content-type label (lowercased in a sentence). */
			'search_items'          => sprintf( __( 'Soek %s', 'ink-core' ), $plural ),
			/* translators: %s: the plural content-type label. */
			'not_found'             => sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), $plural ),
			/* translators: %s: the plural content-type label. */
			'not_found_in_trash'    => sprintf( __( 'Geen %s in die asblik nie.', 'ink-core' ), $plural ),
			/* translators: %s: the plural content-type label. */
			'archives'              => sprintf( __( '%s-argief', 'ink-core' ), $singular ),
			'featured_image'        => __( 'Uitgeligte beeld', 'ink-core' ),
			'set_featured_image'    => __( 'Stel uitgeligte beeld', 'ink-core' ),
			'remove_featured_image' => __( 'Verwyder uitgeligte beeld', 'ink-core' ),
			'use_featured_image'    => __( 'Gebruik as uitgeligte beeld', 'ink-core' ),
			/* translators: %s: the singular content-type label. */
			'item_published'        => sprintf( __( '%s gepubliseer.', 'ink-core' ), $singular ),
			/* translators: %s: the singular content-type label. */
			'item_updated'          => sprintf( __( '%s opgedateer.', 'ink-core' ), $singular ),
		);
	}

	/**
	 * Assert a CPT-label concept key is registered before it is composed into
	 * labels (Story 17.4 — deferred Epic 2 review). A missing/typo'd key would
	 * otherwise let `Terms::label()` fail safe to the raw machine key (e.g.
	 * `storie_plural`) and ship it as a visible CPT label. This surfaces the
	 * developer error in dev/CI via `_doing_it_wrong`; production stays fail-safe
	 * (no fatal) with the English-leak scan/crawl as the runtime backstop.
	 *
	 * @param string $key Terminology concept key.
	 */
	private static function assertTermKey( string $key ): void {
		if ( Terms::has( $key ) ) {
			return;
		}

		_doing_it_wrong(
			__METHOD__,
			sprintf( 'CPT label uses unregistered terminology key "%s" — fix the definition or add the glossary term.', esc_html( $key ) ),
			'ink-core 17.4'
		);
	}
}
