<?php
/**
 * Home featured-bydraes stream ("Die redakteur se keuse") — Story 19.4 (§6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\Engagement\Api as EngagementApi;
use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/uitgesoekte-bydraes` block: the Tuisblad "Die redakteur se keuse"
 * featured-works stream (§6).
 *
 * The home page surfaces a small, curated stream of published bydraes
 * (`gedig`/`storie`/`artikel`) in an asymmetric grid — the first (featured) card spans
 * two columns, the rest fill a 2-column grid. Each card carries its category (genre)
 * pill, a **read-time computed from word count in `ink-core`** ({@see ReadingTime}),
 * an excerpt, the author (name + avatar with alt) and two engagement counts sourced
 * from the existing surfaces via the Engagement `Api` facade — Heart = hartjie count,
 * MessageCircle = Gemeenskapsreaksie count. All computation lives HERE (three-layer
 * separation); the theme styles the block's own `.ink-uitgesoekte-bydraes*` markup.
 *
 * House style mirrors {@see \Ink\Challenges\CurrentChallenge}/{@see \Ink\Sponsors\HomepageStrip}:
 * a thin impure {@see render()} over a pure {@see toHtml()}, a data seam
 * ({@see DATA_FILTER}) with a live default ({@see stream()}), and **graceful full
 * collapse** — an empty feed yields the empty string, so the WHOLE section (header
 * included) disappears rather than showing an orphan heading (owner decision: empty
 * feed hides entirely, no empty-state copy). No existing per-bydrae "featured" feed
 * exists (the Challenges featured feed is winner-announcements only), so ordering
 * defaults to newest-published; editorial curation can override the order + selection
 * through {@see DATA_FILTER} without touching the theme. Conflation-clean: references
 * only `Ink\Content` (CPT + taxonomy slugs), the Engagement `Api` facade and the
 * `Terms` registry — zero `Ink\Tiers`/`Ink\Entitlement` (surfacing published work is
 * open).
 *
 * @package Ink\Core
 */
final class FeaturedStream {

	/**
	 * The block name — single source for the renderer + the theme pattern embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitgesoekte-bydraes';

	/**
	 * Overridable data seam: a filter may supply (or re-order/curate) the featured
	 * stream directly. Returns null by default, so {@see render()} falls through to
	 * the live {@see stream()} newest-first default.
	 *
	 * @var string
	 */
	public const DATA_FILTER = 'ink_home_featured_stream';

	/**
	 * How many bydraes the stream shows (1 featured spanning card + 3 standard) —
	 * the Lovable design's four-card layout.
	 *
	 * @var int
	 */
	public const MAX_ITEMS = 4;

	/**
	 * How many of the newest published bydraes to scan for the first {@see MAX_ITEMS}
	 * REAL (non-QA-fixture) ones. Over-fetches beyond {@see MAX_ITEMS} so seeded QA
	 * fixture content (see {@see QaFixture}) never crowds out real editorial work in
	 * the displayed stream — mirrors {@see \Ink\Challenges\CurrentChallenge::SCAN_LIMIT}.
	 *
	 * @var int
	 */
	private const SCAN_LIMIT = 20;

	/**
	 * Per-request memo so repeat embeds share one query.
	 *
	 * @var list<array<string, mixed>>|null
	 */
	private static ?array $memo = null;

	// --- Lucide inner-SVG paths (§0.9 — emitted inline by the block PHP). ---

	private const ICON_CLOCK   = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
	private const ICON_HEART   = '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>';
	private const ICON_MESSAGE = '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>';

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
	 * Register the `ink/uitgesoekte-bydraes` dynamic block.
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
	 * Build the `WP_Query` args for the newest published bydraes. Pure.
	 *
	 * Reuses the single-source reader-facing type list ({@see PostTypes::readableTypes()}
	 * — the `skryfwerk` migration bucket is never reader-facing), newest-first, bounded
	 * to the stream size.
	 *
	 * @param int $limit How many works to fetch.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $limit ): array {
		return array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, $limit ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}

	/**
	 * The featured stream, memoised per request. Impure (WP_Query + meta reads).
	 *
	 * @return list<array<string, mixed>>
	 */
	public static function stream(): array {
		if ( null !== self::$memo ) {
			return self::$memo;
		}

		self::$memo = self::resolveStream();

		return self::$memo;
	}

	/**
	 * Resolve the newest published bydraes into fully-computed card rows. Impure.
	 *
	 * Scans up to {@see SCAN_LIMIT} newest bydraes, skipping any post whose title
	 * carries the {@see QaFixture} `QA FIXTURE — ` convention, and stops once
	 * {@see MAX_ITEMS} real rows are collected (Epic-19 theme-fidelity rework
	 * finding: seeded QA fixture bydraes were crowding out real work in the
	 * Tuisblad's "Uitgesoekte bydraes" stream).
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function resolveStream(): array {
		$query = new \WP_Query( self::queryArgs( self::SCAN_LIMIT ) );
		$items = array();

		foreach ( $query->posts as $post ) {
			if ( count( $items ) >= self::MAX_ITEMS ) {
				break;
			}

			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			if ( QaFixture::isFixtureTitle( get_the_title( $post ) ) ) {
				continue;
			}

			$post_id   = (int) $post->ID;
			$author_id = (int) $post->post_author;

			$items[] = array(
				'title'          => get_the_title( $post ),
				'url'            => (string) get_permalink( $post ),
				'category'       => self::categoryLabel( $post ),
				'read_minutes'   => ReadingTime::minutesFromText( wp_strip_all_tags( (string) $post->post_content ) ),
				'excerpt'        => self::excerptFor( $post ),
				'author'         => (string) get_the_author_meta( 'display_name', $author_id ),
				'avatar_url'     => (string) get_avatar_url( $author_id, array( 'size' => 64 ) ),
				'hart_count'     => EngagementApi::hartjieCountForPost( $post_id ),
				'response_count' => EngagementApi::responseCountForPost( $post_id ),
			);
		}

		return $items;
	}

	/**
	 * The category pill label — the first `genre` term name, else the CPT type label.
	 * Impure (term + Terms registry reads).
	 *
	 * @param \WP_Post $post The bydrae.
	 * @return string
	 */
	private static function categoryLabel( \WP_Post $post ): string {
		$terms = get_the_terms( $post, Taxonomies::GENRE );

		if ( is_array( $terms ) ) {
			foreach ( $terms as $term ) {
				if ( $term instanceof \WP_Term && '' !== $term->name ) {
					return $term->name;
				}
			}
		}

		return Terms::label( $post->post_type );
	}

	/**
	 * A trimmed excerpt for the card body. Impure (WP excerpt helpers).
	 *
	 * @param \WP_Post $post The bydrae.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 32, '…' );
	}

	/**
	 * Block render callback. Reads the (seam or live) stream, composes. Thin impure
	 * shell over the pure {@see toHtml()}.
	 *
	 * @return string
	 */
	public static function render(): string {
		$data = apply_filters( self::DATA_FILTER, null );

		if ( is_array( $data ) ) {
			return self::toHtml( $data );
		}

		return self::toHtml( self::stream() );
	}

	/**
	 * Build the featured-section HTML for the given items. Pure — read-time /
	 * count formatters + Terms + escaping only.
	 *
	 * Defensive by contract: the item list may come from the {@see DATA_FILTER} seam
	 * (arbitrary input), so each row is guarded to be an array with a non-empty title.
	 * Collapses to '' when nothing survives — the WHOLE section (header included)
	 * disappears (owner decision: empty feed hides entirely; brownfield data means it
	 * is never seen). Otherwise: the UPPERCASE eyebrow + serif title + focusable
	 * "Sien alle werke" link, then the asymmetric grid with the first card featured
	 * (spans two columns).
	 *
	 * @param array<array-key, mixed> $items The card rows (list of associative rows).
	 * @return string
	 */
	public static function toHtml( array $items ): string {
		$rows = array();

		foreach ( $items as $item ) {
			if ( is_array( $item ) && '' !== trim( (string) ( $item['title'] ?? '' ) ) ) {
				$rows[] = $item;
			}
		}

		if ( array() === $rows ) {
			return '';
		}

		$base     = 'ink-uitgesoekte-bydraes';
		$title_id = $base . '__titel';

		$header = '<div class="' . esc_attr( $base . '__kop' ) . '">'
			. '<div class="' . esc_attr( $base . '__kop-teks' ) . '">'
			. '<p class="' . esc_attr( $base . '__boskrif' ) . '">' . esc_html__( 'Die redakteur se keuse', 'ink-core' ) . '</p>'
			. '<h2 id="' . esc_attr( $title_id ) . '" class="' . esc_attr( $base . '__titel' ) . '">'
			. esc_html__( 'Hierdie week se uitgesoektes', 'ink-core' ) . '</h2>'
			. '</div>'
			. '<a class="' . esc_attr( $base . '__alles ink-underline-slide' ) . '" href="' . esc_url( self::allWorksUrl() ) . '">'
			. esc_html__( 'Sien alle werke', 'ink-core' ) . '</a>'
			. '</div>';

		$cards = '';

		foreach ( $rows as $index => $item ) {
			$cards .= self::cardHtml( $item, 0 === $index );
		}

		return '<section class="' . esc_attr( $base . ' alignfull' ) . '" aria-labelledby="' . esc_attr( $title_id ) . '">'
			. '<div class="' . esc_attr( $base . '__houer' ) . '">'
			. $header
			. '<div class="' . esc_attr( $base . '__rooster' ) . '">' . $cards . '</div>'
			. '</div></section>';
	}

	/**
	 * One featured-work card (§6). Pure — formatters + escaping only.
	 *
	 * The featured (first) card carries a `--uitgesoek` modifier (spans two columns,
	 * larger title/excerpt — styled by the theme). Card title is an `h3` (correct
	 * order under the section `h2`). Counts are verb-less (the icon does the verb):
	 * the visible glyph is `aria-hidden` and the count carries the full accessible
	 * label ("342 hartjies") so assistive tech reads it in full.
	 *
	 * @param array<array-key, mixed> $item     The card row.
	 * @param bool                    $featured Whether this is the featured (spanning) card.
	 * @return string
	 */
	private static function cardHtml( array $item, bool $featured ): string {
		$base    = 'ink-uitgesoekte-bydraes';
		$classes = $base . '__kaart' . ( $featured ? ' ' . $base . '__kaart--uitgesoek' : '' );

		$title    = (string) ( $item['title'] ?? '' );
		$url      = (string) ( $item['url'] ?? '' );
		$category = (string) ( $item['category'] ?? '' );
		$excerpt  = (string) ( $item['excerpt'] ?? '' );

		$title_html = '' !== $url
			? '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
			: esc_html( $title );

		$out = '<article class="' . esc_attr( $classes ) . '">'
			. '<div class="' . esc_attr( $base . '__kaart-inhoud' ) . '">'
			. self::metaTopHtml( $base, $category, (int) ( $item['read_minutes'] ?? 0 ) )
			. '<h3 class="' . esc_attr( $base . '__werk' ) . '">' . $title_html . '</h3>';

		if ( '' !== $excerpt ) {
			$out .= '<p class="' . esc_attr( $base . '__uittreksel' ) . '">' . esc_html( $excerpt ) . '</p>';
		}

		$out .= self::footerHtml( $base, $item );

		return $out . '</div></article>';
	}

	/**
	 * The card's top meta row: category pill + read-time (Clock icon + "N min"). Pure.
	 *
	 * @param string $base     The BEM base class.
	 * @param string $category The category (genre) label.
	 * @param int    $minutes  The read-time in whole minutes.
	 * @return string
	 */
	private static function metaTopHtml( string $base, string $category, int $minutes ): string {
		$out = '<div class="' . esc_attr( $base . '__meta-top' ) . '">';

		if ( '' !== $category ) {
			$out .= '<span class="' . esc_attr( $base . '__pil' ) . '">' . esc_html( $category ) . '</span>';
		}

		$read_time = ReadingTime::label( $minutes );

		if ( '' !== $read_time ) {
			$out .= '<span class="' . esc_attr( $base . '__leestyd' ) . '">'
				. self::icon( self::ICON_CLOCK )
				. '<span>' . esc_html( $read_time ) . '</span>'
				. '</span>';
		}

		return $out . '</div>';
	}

	/**
	 * The card footer: author (avatar + name) + the Heart / MessageCircle counts. Pure.
	 *
	 * @param string                  $base The BEM base class.
	 * @param array<array-key, mixed> $item The card row.
	 * @return string
	 */
	private static function footerHtml( string $base, array $item ): string {
		$author = (string) ( $item['author'] ?? '' );
		$avatar = (string) ( $item['avatar_url'] ?? '' );

		$author_html = '<div class="' . esc_attr( $base . '__outeur' ) . '">';

		if ( '' !== $avatar ) {
			// Avatar alt falls back to the author name so it is never empty (a11y).
			$author_html .= '<img class="' . esc_attr( $base . '__foto' ) . '" src="' . esc_url( $avatar ) . '" '
				. 'alt="' . esc_attr( $author ) . '" width="32" height="32" loading="lazy" decoding="async" />';
		}

		if ( '' !== $author ) {
			$author_html .= '<span class="' . esc_attr( $base . '__outeur-naam' ) . '">' . esc_html( $author ) . '</span>';
		}

		$author_html .= '</div>';

		$hart_count     = (int) ( $item['hart_count'] ?? 0 );
		$response_count = (int) ( $item['response_count'] ?? 0 );

		$counts = '<div class="' . esc_attr( $base . '__tellers' ) . '">'
			. self::countHtml( $base, self::ICON_HEART, $hart_count, EngagementApi::hartjieCountLabel( $hart_count ) )
			. self::countHtml( $base, self::ICON_MESSAGE, $response_count, self::responseCountLabel( $response_count ) )
			. '</div>';

		return '<div class="' . esc_attr( $base . '__voet' ) . '">' . $author_html . $counts . '</div>';
	}

	/**
	 * One engagement count: an aria-hidden icon + visible number, the whole span
	 * carrying the full verb-less accessible label. Pure.
	 *
	 * @param string $base  The BEM base class.
	 * @param string $icon  The Lucide inner-SVG paths.
	 * @param int    $count The count value.
	 * @param string $label The full accessible label (e.g. "342 hartjies").
	 * @return string
	 */
	private static function countHtml( string $base, string $icon, int $count, string $label ): string {
		return '<span class="' . esc_attr( $base . '__teller' ) . '" aria-label="' . esc_attr( $label ) . '">'
			. self::icon( $icon )
			. '<span aria-hidden="true">' . esc_html( (string) $count ) . '</span>'
			. '</span>';
	}

	/**
	 * The verb-less Gemeenskapsreaksie-count label (MessageCircle). Pure.
	 *
	 * Uses the controlled-vocabulary `gemeenskapsreaksie(_plural)` labels from the
	 * {@see Terms} registry — the same single source {@see \Ink\Engagement\ResponsesList}
	 * uses for its heading, so the count reads identically everywhere (e.g.
	 * "1 Gemeenskapsreaksie" / "12 Gemeenskapsreaksies").
	 *
	 * @param int $n The response count.
	 * @return string
	 */
	private static function responseCountLabel( int $n ): string {
		$label = 1 === $n ? Terms::label( 'gemeenskapsreaksie' ) : Terms::label( 'gemeenskapsreaksie_plural' );

		return (string) $n . ' ' . $label;
	}

	/**
	 * The "Sien alle werke" target — the Ontdek browse hub. Impure (URL builder).
	 *
	 * @return string
	 */
	private static function allWorksUrl(): string {
		return home_url( '/ontdek' );
	}

	/**
	 * A decorative inline Lucide icon (§0.9): 16px, currentColor, aria-hidden. Pure.
	 * `$paths` is a trusted class-internal SVG literal (never user input).
	 *
	 * @param string $paths The inner SVG markup.
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}
}
