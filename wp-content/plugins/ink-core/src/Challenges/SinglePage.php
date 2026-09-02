<?php
/**
 * Uitdaging single-page surface — Story 12.1 (FR-45).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeRound;
use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/uitdaging-besonderhede` block on a single `uitdaging`.
 *
 * Surfaces the dynamic half of the challenge page: the **sluitingsdatum** with an
 * **Oop/Gesluit** status (inclusive end-of-day-SAST rule, {@see Sast}) and the
 * **inskrywings** list — published bydraes linked to this round via the
 * `uitdagingsrondte` term slugged {@see ChallengeRound::slugFor()}. The editorial
 * brief (prompt, literary devices, submission rules, prize, resources) is authored
 * as the uitdaging post body and surfaces through the theme's core `post-content`.
 *
 * House style mirrors {@see \Ink\Library\Archive}: pure {@see self::entriesQueryArgs()}
 * + pure render helpers + a thin {@see self::render()}. Conflation-clean: reads only
 * `Ink\Content` (CPT/taxonomy/round-slug single sources) + `Kernel\Sast` + the `Terms`
 * registry + WP core — zero `Ink\Tiers`/`Ink\Entitlement` (viewing a published
 * challenge is open, never gated).
 *
 * @package Ink\Core
 */
final class SinglePage {

	/**
	 * The block name — single source for the renderer + the theme pattern embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitdaging-besonderhede';

	/**
	 * Upper bound on entries hydrated for the single-page list + the pool grouping.
	 *
	 * The entries list is request-rendered, so an unbounded `-1` would load every
	 * entry of a busy round on each page view (R12 review). A generous cap keeps the
	 * page bounded while comfortably covering a real round (≤ 3 entries/type/writer).
	 *
	 * @var int
	 */
	public const MAX_ENTRIES = 500;

	/**
	 * Overridable data seam: turns QA-fixture-titled entries back ON for the
	 * entries list (Epic-19 theme-fidelity rework finding — the entries list read
	 * a live `WP_Query` with no gating, so a fixture-titled bydrae/storie/artikel
	 * linked to a real round would leak into the real page). Mirrors
	 * {@see \Ink\Training\Hub::INCLUDE_FIXTURES_FILTER} /
	 * {@see \Ink\Library\Archive::INCLUDE_FIXTURES_FILTER}. Not currently wired to
	 * a QA gallery embed (the block is post-context-bound, unlike the archive
	 * blocks) — the seam exists for a future one, exclusion is simply always the
	 * default today.
	 *
	 * @var string
	 */
	public const INCLUDE_FIXTURES_FILTER = 'ink_uitdaging_besonderhede_include_fixtures';

	/**
	 * Lucide `calendar` icon path data (Post-Epic-19 fidelity pass, workstream 6) —
	 * mirrors the sluitingsdatum meta-row icon in the Lovable `Challenge.tsx`
	 * reference. Local to this class, matching the per-class icon-constant house
	 * style ({@see \Ink\Training\Hub::ICON_CLOCK}, {@see \Ink\Library\Archive::ICON_CLOCK}).
	 *
	 * @var string
	 */
	private const ICON_CALENDAR = '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>';

	/**
	 * Render a 16px inline SVG icon. Pure markup, decorative (the adjacent label
	 * always carries the meaning) — mirrors the shared per-class icon() convention.
	 *
	 * @param string $paths Inner SVG markup (Lucide `<path>`/`<rect>` elements).
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	/**
	 * Register the server-rendered block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/uitdaging-besonderhede` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Build the `WP_Query` args for a round's entries — published readable bydraes
	 * carrying the round term, newest-first. Pure.
	 *
	 * A non-positive id (no current uitdaging) yields a match-nothing query
	 * (`post__in [0]`) rather than an unfiltered listing of every bydrae.
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return array<string, mixed>
	 */
	public static function entriesQueryArgs( int $uitdaging_id ): array {
		$args = array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'posts_per_page'      => self::MAX_ENTRIES,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( $uitdaging_id <= 0 ) {
			$args['post__in'] = array( 0 );

			return $args;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded round-slug facet on a published CPT; the AD-7 server-rendered entries list, no search plugin.
		$args['tax_query'] = array(
			array(
				'taxonomy' => Taxonomies::UITDAGINGSRONDTE,
				'field'    => 'slug',
				'terms'    => ChallengeRound::slugFor( $uitdaging_id ),
			),
		);

		return $args;
	}

	/**
	 * Run a `WP_Query`, excluding QA-fixture-titled posts by default (Epic-19
	 * theme-fidelity rework finding — see {@see INCLUDE_FIXTURES_FILTER}). The
	 * exclusion is applied at the SQL layer (a scoped `posts_where` filter, removed
	 * immediately after) rather than by filtering `$query->posts` in PHP, mirroring
	 * {@see \Ink\Training\Hub::runQuery()}. Impure (WP_Query + filter).
	 *
	 * @param array<string, mixed> $args The `WP_Query` args.
	 * @return \WP_Query
	 */
	private static function runQuery( array $args ): \WP_Query {
		if ( (bool) apply_filters( self::INCLUDE_FIXTURES_FILTER, false ) ) {
			return new \WP_Query( $args );
		}

		$exclude_fixtures = static function ( string $where, \WP_Query $wp_query ): string {
			global $wpdb;

			return $where . $wpdb->prepare(
				" AND {$wpdb->posts}.post_title NOT LIKE %s",
				$wpdb->esc_like( QaFixture::TITLE_PREFIX ) . '%'
			);
		};

		add_filter( 'posts_where', $exclude_fixtures, 10, 2 );
		$query = new \WP_Query( $args );
		remove_filter( 'posts_where', $exclude_fixtures, 10 );

		return $query;
	}

	/**
	 * The count of published, non-fixture entries linked to this round. Impure
	 * (bounded WP_Query via {@see self::runQuery()}). The single source for this
	 * count — {@see \Ink\Challenges\CurrentChallenge::entryCount()} delegates here
	 * rather than duplicating the query.
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return int
	 */
	public static function entryCount( int $uitdaging_id ): int {
		$args           = self::entriesQueryArgs( $uitdaging_id );
		$args['fields'] = 'ids';

		return count( self::runQuery( $args )->posts );
	}

	/**
	 * Whether the round is still open — `now` within the inclusive end-of-day-SAST
	 * deadline window (AD-3). Pure delegate to the single SAST boundary source.
	 *
	 * @param \DateTimeInterface      $deadline The stored deadline instant.
	 * @param \DateTimeInterface|null $now      The instant to test (defaults to now).
	 * @return bool
	 */
	public static function isOpen( \DateTimeInterface $deadline, ?\DateTimeInterface $now = null ): bool {
		return Sast::isThroughEndOfDay( $deadline, $now );
	}

	/**
	 * The sluitingsdatum status line. Pure — Terms + escaping only.
	 *
	 * Renders nothing without a formatted deadline (so a challenge with no deadline
	 * meta omits the line rather than showing a malformed date).
	 *
	 * @param string $formatted_deadline The SAST-formatted deadline, or ''.
	 * @param bool   $is_open            Whether the round is still open.
	 * @return string
	 */
	public static function statusHtml( string $formatted_deadline, bool $is_open ): string {
		if ( '' === $formatted_deadline ) {
			return '';
		}

		$state_key   = $is_open ? 'uitdaging_oop' : 'uitdaging_gesluit';
		$state_class = $is_open ? 'is-oop' : 'is-gesluit';

		// Post-Epic-19 fidelity pass (workstream 6): a plain inline text line is easy
		// to miss and doesn't scale down cleanly ("date readability", page-map.csv's
		// flagged responsive risk for this page). Restyled as an icon-led meta row
		// (matches the Lovable `Challenge.tsx` deadline row) plus a coloured Oop/
		// Gesluit pill — legible at a glance, distinct at any viewport width, never
		// relies on inline punctuation (the old " · ") to separate the two facts.
		return '<div class="ink-uitdaging__status ' . esc_attr( $state_class ) . '">'
			. '<span class="ink-uitdaging__sluitingsdatum-ry">'
			. self::icon( self::ICON_CALENDAR )
			. '<span class="ink-uitdaging__sluitingsdatum-etiket">' . esc_html( Terms::label( 'sluitingsdatum' ) ) . ': </span>'
			. '<time class="ink-uitdaging__sluitingsdatum">' . esc_html( $formatted_deadline ) . '</time>'
			. '</span>'
			. '<span class="ink-uitdaging__toestand-pil ' . esc_attr( $state_class ) . '">' . esc_html( Terms::label( $state_key ) ) . '</span>'
			. '</div>';
	}

	/**
	 * The inskrywings (entries) list. Pure — Terms + escaping only.
	 *
	 * Renders a graceful empty state (no `<ul>`/`<li>` shell) when the round has no
	 * linked entries. Each entry is rendered as a card — type-label pill, title,
	 * excerpt and author (Post-Epic-19 fidelity pass, workstream 6; matches the
	 * Lovable `Challenge.tsx` "Entries from the community" card grid). `excerpt`/
	 * `type_label`/`author` are optional so a minimal `{title, permalink}` entry
	 * (e.g. a caller with no excerpt/author context) still renders a valid, if
	 * plainer, card rather than erroring.
	 *
	 * @param list<array{title:string, permalink:string, excerpt?:string, type_label?:string, author?:string}> $entries The entries.
	 * @return string
	 */
	public static function entriesHtml( array $entries ): string {
		$heading = '<h2 class="ink-uitdaging__inskrywings-titel">' . esc_html( Terms::label( 'inskrywing_plural' ) ) . '</h2>';

		if ( array() === $entries ) {
			/* translators: %s: the entries (inskrywings) label. */
			$empty = sprintf( __( 'Geen %s nie.', 'ink-core' ), Terms::label( 'inskrywing_plural' ) );

			return '<div class="ink-uitdaging__inskrywings-blok">' . $heading
				. '<p class="ink-uitdaging__leeg">' . esc_html( $empty ) . '</p></div>';
		}

		$html = '<div class="ink-uitdaging__inskrywings-blok">' . $heading . '<ul class="ink-uitdaging__inskrywings">';

		foreach ( $entries as $entry ) {
			$type_label = (string) ( $entry['type_label'] ?? '' );
			$excerpt    = (string) ( $entry['excerpt'] ?? '' );
			$author     = (string) ( $entry['author'] ?? '' );

			$html .= '<li class="ink-uitdaging__inskrywing">'
				. '<a class="ink-uitdaging__inskrywing-skakel" href="' . esc_url( $entry['permalink'] ) . '">';

			if ( '' !== $type_label ) {
				$html .= '<span class="ink-uitdaging__inskrywing-tipe">' . esc_html( $type_label ) . '</span>';
			}

			$html .= '<span class="ink-uitdaging__inskrywing-titel">' . esc_html( $entry['title'] ) . '</span>';

			if ( '' !== $excerpt ) {
				$html .= '<p class="ink-uitdaging__inskrywing-uittreksel">' . esc_html( $excerpt ) . '</p>';
			}

			if ( '' !== $author ) {
				$html .= '<span class="ink-uitdaging__inskrywing-outeur">' . esc_html( $author ) . '</span>';
			}

			$html .= '</a></li>';
		}

		return $html . '</ul></div>';
	}

	/**
	 * A trimmed excerpt for an entry card. Impure (WP excerpt helpers) — mirrors
	 * {@see \Ink\Discovery\FeaturedStream::excerptFor()}'s local, dependency-free
	 * copy of the same rule (no new cross-module edge for a one-line fallback).
	 *
	 * @param \WP_Post $post The entry.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 32, '…' );
	}

	/**
	 * The closing-CTA subtitle paragraph — "Sluit aan by N skrywers wat reeds
	 * hierdie uitdaging verken. …". Ratified copy from `docs/ui-copy-translations.md`
	 * ("Uitdaging-detailbladsy" section, "Sluitende oproep tot aksie"), wired in
	 * here rather than left as unused Afrikaans copy-debt (Post-Epic-19 fidelity
	 * pass, workstream 6 — matches the `gemeenskapsreaksie_instruksie` precedent
	 * from the lees-storie re-audit). Pure — Terms/`__()` + escaping only.
	 *
	 * Renders nothing for a round with no entries yet — the sentence names real
	 * participants, so a "0 skrywers" reading would misstate an empty round rather
	 * than gracefully collapsing (the same empty-state convention as
	 * {@see self::entriesHtml()}/{@see self::statusHtml()}).
	 *
	 * @param int $entry_count The round's published, non-fixture entry count.
	 * @return string
	 */
	public static function ctaSubtitleHtml( int $entry_count ): string {
		if ( $entry_count <= 0 ) {
			return '';
		}

		/* translators: %d: the number of writers who already entered this challenge. */
		$text = sprintf(
			__( "Sluit aan by %d skrywers wat reeds hierdie uitdaging verken. Of dit 'n verfynde konsep is of 'n dapper eerste poging — jou stem hoort hier.", 'ink-core' ),
			$entry_count
		);

		return '<p class="wp-block-paragraph has-text-align-center ink-uitdaging-cta__subtitel">' . esc_html( $text ) . '</p>';
	}

	/**
	 * Compose the block shell from the (pre-rendered) status line + entries list. Pure.
	 *
	 * @param string $status_html  The status line markup (may be '').
	 * @param string $entries_html The entries-list markup.
	 * @return string
	 */
	public static function toHtml( string $status_html, string $entries_html ): string {
		// The `id` is the anchor target for the pattern-level "Lees inskrywings"
		// CTA button (reading-uitdaging.php) — mirrors Lovable's `href="#submissions"`
		// jump-link (Post-Epic-19 fidelity pass, workstream 6).
		return '<section id="inskrywings" class="ink-uitdaging ink-uitdaging__besonderhede">' . $status_html . $entries_html . '</section>';
	}

	/**
	 * Block render callback. Resolves the current uitdaging, reads the deadline, queries
	 * its entries, and composes. Thin impure shell over the pure helpers above.
	 *
	 * @return string
	 */
	public static function render(): string {
		$uitdaging_id = (int) get_the_ID();

		if ( $uitdaging_id <= 0 || PostTypes::UITDAGING !== get_post_type( $uitdaging_id ) ) {
			return '';
		}

		$raw      = Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_DEADLINE, true ) );
		$deadline = Deadline::parse( $raw );

		$status_html = '';

		if ( $deadline instanceof \DateTimeImmutable ) {
			$status_html = self::statusHtml( Deadline::format( $deadline ), self::isOpen( $deadline ) );
		}

		$query   = self::runQuery( self::entriesQueryArgs( $uitdaging_id ) );
		$entries = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				// type_label/excerpt/author are drawn from data already on $post — no
				// new cross-module dependency (deptrac's Challenges->Content/Kernel-only
				// edge is unchanged; Terms is already an allowed import for the
				// sluitingsdatum/toestand labels above).
				$entries[] = array(
					'title'      => get_the_title( $post ),
					'permalink'  => (string) get_permalink( $post ),
					'type_label' => Terms::label( $post->post_type ),
					'excerpt'    => self::excerptFor( $post ),
					'author'     => get_the_author_meta( 'display_name', (int) $post->post_author ),
				);
			}
		}

		return self::toHtml( $status_html, self::entriesHtml( $entries ) );
	}
}
