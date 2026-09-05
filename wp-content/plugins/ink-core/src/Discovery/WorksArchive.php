<?php
/**
 * Ontdek works-archive server block — Story 8.1 (FR-32).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\Engagement\Api as EngagementApi;
use Ink\Kernel\ArchiveRender;
use Ink\Kernel\QaFixture;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-werke` block: the Ontdek works archive.
 *
 * Lists published bydraes (`gedig`/`storie`/`artikel` — the `skryfwerk` migration
 * bucket is never reader-facing), newest-first, paginated, with optional
 * year/month date-archive browse. Reads stay SERVER-RENDERED via `WP_Query`
 * (AD-7 — no REST for discovery listings), mirroring the {@see \Ink\Engagement\SuggestedReads}
 * house style: pure {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin
 * {@see self::render()}.
 *
 * The type filter + the Nuut/Opspraakwekkend/Mees geliefd sorts are Story 8.2;
 * this story is the hub shell + default newest-first listing. Conflation-clean:
 * references only `Ink\Content\PostTypes` (the migration-load-bearing slug source)
 * + the `Terms` registry + WP core — zero `Ink\Tiers`/`Ink\Entitlement`. Browsing
 * published work is open (never entitlement-gated).
 *
 * @package Ink\Core
 */
final class WorksArchive {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/ontdek-werke';

	/**
	 * Works per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * Custom paged query var — avoids colliding with WP page pagination on the
	 * host page.
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'werke_bladsy';

	/**
	 * Year archive-browse query var.
	 *
	 * @var string
	 */
	public const YEAR_VAR = 'werke_jaar';

	/**
	 * Month archive-browse query var.
	 *
	 * @var string
	 */
	public const MONTH_VAR = 'werke_maand';

	/**
	 * Type-filter query var (a readable bydrae slug, or absent for "Alles").
	 *
	 * @var string
	 */
	public const TYPE_VAR = 'werke_tipe';

	/**
	 * Sort query var.
	 *
	 * @var string
	 */
	public const SORT_VAR = 'werke_sorteer';

	/**
	 * Sort: newest first (default).
	 *
	 * @var string
	 */
	public const SORT_NUUT = 'nuut';

	/**
	 * Sort: trending (stored `ink_trending_score`, descending).
	 *
	 * @var string
	 */
	public const SORT_OPSPRAAK = 'opspraakwekkend';

	/**
	 * Sort: most reactions (denormalized `ink_reaksie_telling`, descending).
	 *
	 * @var string
	 */
	public const SORT_GELIEFD = 'mees_geliefd';

	/**
	 * Overridable data seam: turns the default QA-fixture exclusion back ON for
	 * the QA gallery page only (Epic-19 theme-fidelity rework finding — the
	 * `/ontdek/` Bydraes archive had no exclusion at all, so seeded
	 * `QA FIXTURE — ` titled works leaked onto the real page unfiltered).
	 * Mirrors {@see \Ink\Library\Archive::INCLUDE_FIXTURES_FILTER}.
	 *
	 * @var string
	 */
	public const INCLUDE_FIXTURES_FILTER = 'ink_ontdek_werke_include_fixtures';

	/**
	 * The valid sort keys (anything else degrades to {@see self::SORT_NUUT}).
	 *
	 * @return list<string>
	 */
	public static function allowedSorts(): array {
		return array( self::SORT_NUUT, self::SORT_OPSPRAAK, self::SORT_GELIEFD );
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
	 * Register the `ink/ontdek-werke` dynamic block.
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
	 * The readable bydrae types for the archive (skryfwerk bucket excluded).
	 *
	 * Delegates to {@see PostTypes::readableTypes()} — the single source shared
	 * with the following-feed (Story 9.3) and any other "list published work"
	 * surface.
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return PostTypes::readableTypes();
	}

	/**
	 * Build the `WP_Query` args for the newest-first works archive. Pure.
	 *
	 * A `date_query` is added only for a sane 4-digit year (and only carries the
	 * month for 1–12), so a hostile/garbage query string degrades to the
	 * unfiltered newest-first listing rather than an empty or broken query.
	 *
	 * @param int         $paged    The requested page (clamped to >= 1).
	 * @param int         $per_page Works per page.
	 * @param int|null    $year     Optional archive year.
	 * @param int|null    $month    Optional archive month (1–12; ignored without a year).
	 * @param string|null $type     Optional single readable type (else all three).
	 * @param string      $sort     One of {@see self::allowedSorts()} (else nuut).
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page, ?int $year, ?int $month, ?string $type = null, string $sort = self::SORT_NUUT ): array {
		$types = ( null !== $type && in_array( $type, self::readableTypes(), true ) )
			? array( $type )
			: self::readableTypes();

		$sort = in_array( $sort, self::allowedSorts(), true ) ? $sort : self::SORT_NUUT;

		$args = array(
			'post_type'           => $types,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'ignore_sticky_posts' => true,
		);

		$args = array_merge( $args, self::sortArgs( $sort ) );

		$clause = self::dateClause( $year, $month );

		if ( array() !== $clause ) {
			$args['date_query'] = array( $clause );
		}

		return $args;
	}

	/**
	 * The `orderby`/`meta_key` args for a (validated) sort key. Pure.
	 *
	 * The count sorts order by indexed post-meta (AD-7 — denormalized, never a
	 * live COUNT join) with date as the stable tiebreaker; `nuut` is plain date.
	 *
	 * @param string $sort A validated sort key.
	 * @return array<string, mixed>
	 */
	private static function sortArgs( string $sort ): array {
		switch ( $sort ) {
			case self::SORT_GELIEFD:
				return array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- denormalized, indexed sort meta (AD-7); not a live COUNT.
					'meta_key' => EngagementApi::reactionTotalMetaKey(),
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case self::SORT_OPSPRAAK:
				return array(
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- stored, indexed trending score (AD-7); not computed live.
					'meta_key' => TrendingScore::META_KEY,
					'orderby'  => array(
						'meta_value_num' => 'DESC',
						'date'           => 'DESC',
					),
				);
			case self::SORT_NUUT:
			default:
				return array(
					'orderby' => 'date',
					'order'   => 'DESC',
				);
		}
	}

	/**
	 * One `date_query` clause for a valid year (+ optional 1–12 month), else [].
	 *
	 * @param int|null $year  The archive year.
	 * @param int|null $month The archive month.
	 * @return array<string, int>
	 */
	private static function dateClause( ?int $year, ?int $month ): array {
		if ( null === $year || $year < 1000 || $year > 9999 ) {
			return array();
		}

		$clause = array( 'year' => $year );

		if ( null !== $month && $month >= 1 && $month <= 12 ) {
			$clause['month'] = $month;
		}

		return $clause;
	}

	/**
	 * Block render callback. Reads the browse inputs defensively, queries, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$paged    = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$year     = ArchiveRender::requestInt( self::YEAR_VAR, 0 );
		$month    = ArchiveRender::requestInt( self::MONTH_VAR, 0 );
		$type_raw = ArchiveRender::requestKey( self::TYPE_VAR );
		$sort_raw = ArchiveRender::requestKey( self::SORT_VAR );

		// Normalise to the active values the controls highlight (and the query uses).
		$active_type = in_array( $type_raw, self::readableTypes(), true ) ? $type_raw : null;
		$active_sort = in_array( $sort_raw, self::allowedSorts(), true ) ? $sort_raw : self::SORT_NUUT;

		$query = self::runQuery(
			self::queryArgs(
				$paged,
				self::PER_PAGE,
				$year > 0 ? $year : null,
				$month > 0 ? $month : null,
				$active_type,
				$active_sort
			)
		);

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$post_id = (int) $post->ID;

			$cards[] = array(
				'title'          => get_the_title( $post ),
				'permalink'      => (string) get_permalink( $post ),
				'type'           => $post->post_type,
				'author'         => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
				'avatar_url'     => (string) get_avatar_url( (int) $post->post_author, array( 'size' => 64 ) ),
				'excerpt'        => self::excerptFor( $post ),
				'read_minutes'   => ReadingTime::minutesFromText( wp_strip_all_tags( (string) $post->post_content ) ),
				'hart_count'     => EngagementApi::hartjieCountForPost( $post_id ),
				'response_count' => EngagementApi::responseCountForPost( $post_id ),
			);
		}

		$current_year = (int) current_time( 'Y' );

		return self::toHtml(
			$cards,
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => (int) $query->max_num_pages,
				'type'      => $active_type,
				'sort'      => $active_sort,
				'year'      => $year > 0 ? $year : null,
				'years'     => range( $current_year, $current_year - 5 ),
			)
		);
	}

	/**
	 * Build the archive HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string, author:string, avatar_url:string, excerpt:string, read_minutes:int, hart_count:int, response_count:int}> $cards The works.
	 * @param array{paged:int, max_pages:int, type?:string|null, sort?:string, year?:int|null, years?:list<int>} $nav Render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		$heading   = '<h1 class="ink-ontdek-werke__heading">' . esc_html( Terms::label( 'bydrae_plural' ) ) . '</h1>';
		$controls  = self::controlsHtml(
			$nav['type'] ?? null,
			isset( $nav['sort'] ) ? (string) $nav['sort'] : self::SORT_NUUT
		);
		$controls .= self::dateBrowseHtml(
			$nav['year'] ?? null,
			isset( $nav['years'] ) && is_array( $nav['years'] ) ? $nav['years'] : array()
		);

		if ( array() === $cards ) {
			/* translators: %s: the plural bydraes label (e.g. Bydraes). */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'bydrae_plural' ) );

			return '<section class="ink-ontdek-werke">' . $heading . $controls
				. '<p class="ink-ontdek-werke__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-ontdek-werke">' . $heading . $controls . '<ul class="ink-ontdek-werke__list">';

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card );
		}

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		$html .= '</ul>' . ArchiveRender::pagination( $paged, $max_pages, 'ink-ontdek-werke', self::PAGED_VAR ) . '</section>';

		return $html;
	}

	/**
	 * Run the works `WP_Query`, excluding QA-fixture-titled posts by default
	 * (Epic-19 theme-fidelity rework finding — see {@see INCLUDE_FIXTURES_FILTER}).
	 * The exclusion is applied at the SQL layer (a scoped `posts_where` filter,
	 * removed immediately after) rather than by filtering `$query->posts` in PHP,
	 * so `max_num_pages` stays accurate for pagination even when fixtures are
	 * excluded. Impure (WP_Query + filter). Mirrors
	 * {@see \Ink\Library\Archive::runQuery()} / {@see \Ink\Training\Hub::runQuery()}.
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
	 * A trimmed excerpt for the card body. Impure (WP excerpt helpers). Mirrors
	 * {@see \Ink\Discovery\FeaturedStream::excerptFor()}.
	 *
	 * @param \WP_Post $post The bydrae.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 24, '…' );
	}

	// --- Lucide inner-SVG paths (§0.9 — emitted inline by the block PHP), mirroring
	// {@see \Ink\Discovery\FeaturedStream}'s icon set. ---

	private const ICON_CLOCK   = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
	private const ICON_HEART   = '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>';
	private const ICON_MESSAGE = '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>';

	/**
	 * One works-archive card. Pure — formatters + escaping only. Mirrors
	 * {@see \Ink\Discovery\FeaturedStream::cardHtml()}'s meta-top/footer shape
	 * (category pill + read-time, author avatar + name, Heart/MessageCircle
	 * counts) so the Ontdek card and the Tuisblad featured card read identically.
	 *
	 * @param array<array-key, mixed> $card The card row.
	 * @return string
	 */
	private static function cardHtml( array $card ): string {
		$base    = 'ink-ontdek-werke';
		$title   = (string) ( $card['title'] ?? '' );
		$url     = (string) ( $card['permalink'] ?? '' );
		$excerpt = (string) ( $card['excerpt'] ?? '' );

		$html = '<li class="' . esc_attr( $base . '__item is-style-card' ) . '">'
			. '<div class="' . esc_attr( $base . '__meta-top' ) . '">'
			. self::typePillHtml( $base, (string) ( $card['type'] ?? '' ) );

		$read_time = ReadingTime::label( (int) ( $card['read_minutes'] ?? 0 ) );

		if ( '' !== $read_time ) {
			$html .= '<span class="' . esc_attr( $base . '__leestyd' ) . '">'
				. self::icon( self::ICON_CLOCK )
				. '<span>' . esc_html( $read_time ) . '</span>'
				. '</span>';
		}

		$html .= '</div>'
			. '<a class="' . esc_attr( $base . '__title' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>';

		if ( '' !== $excerpt ) {
			$html .= '<p class="' . esc_attr( $base . '__uittreksel' ) . '">' . esc_html( $excerpt ) . '</p>';
		}

		$html .= '<div class="' . esc_attr( $base . '__voet' ) . '">'
			. self::authorHtml( $base, $card )
			. self::countsHtml( $base, $card )
			. '</div></li>';

		return $html;
	}

	/**
	 * The card's type pill — the SAME `.ink-lees-tipe` badge + per-type colour
	 * convention as the reading pages (sage `gedig` / brand-orange `storie` /
	 * grey `artikel`), not a bespoke Ontdek-only style. Pure.
	 *
	 * Direct product-owner instruction (Theme-Fidelity fourth-pass re-audit,
	 * Ontdek page, 2026-09-05, item #7): "The excerpt cards must use pills that
	 * match the reading pages: sage for poems, brand orange for stories and
	 * gray for articles." Before this fix `.ink-ontdek-werke__type` hardcoded
	 * `color: primary` (orange) for every type — Storie was already correct by
	 * accident, Gedig and Artikel were not. Mirrors reading-gedig.php /
	 * reading-storie.php / reading-artikel.php's exact class+style pattern
	 * (`has-accent-color` + a 15% inline background tint for Gedig only, the
	 * other two types at `.ink-lees-tipe`'s shared 10%) so the two surfaces
	 * cannot drift apart. Any type outside the three readable ones keeps the
	 * neutral (uncoloured) pill.
	 *
	 * @param string $base The BEM base class.
	 * @param string $type The bydrae post type (`gedig`/`storie`/`artikel`), or ''.
	 * @return string
	 */
	private static function typePillHtml( string $base, string $type ): string {
		$classes = $base . '__type ink-lees-tipe';
		$style   = '';

		switch ( $type ) {
			case PostTypes::GEDIG:
				$classes .= ' has-accent-color has-text-color';
				// The shared `.ink-lees-tipe` rule tints at 10%; the reading pages
				// bump Gedig specifically to 15% (see reading-gedig.php) — matched here.
				$style = ' style="background-color:color-mix(in srgb, currentColor 15%, transparent)"';
				break;
			case PostTypes::STORIE:
				$classes .= ' has-primary-color has-text-color';
				break;
			case PostTypes::ARTIKEL:
				$classes .= ' has-muted-text-color has-text-color';
				break;
		}

		return '<span class="' . esc_attr( $classes ) . '"' . $style . '>' . esc_html( Terms::label( $type ) ) . '</span>';
	}

	/**
	 * The card's author (avatar + name). Pure.
	 *
	 * @param string                  $base The BEM base class.
	 * @param array<array-key, mixed> $card The card row.
	 * @return string
	 */
	private static function authorHtml( string $base, array $card ): string {
		$author = (string) ( $card['author'] ?? '' );
		$avatar = (string) ( $card['avatar_url'] ?? '' );

		$html = '<div class="' . esc_attr( $base . '__author' ) . '">';

		if ( '' !== $avatar ) {
			$html .= '<img class="' . esc_attr( $base . '__foto' ) . '" src="' . esc_url( $avatar ) . '" '
				. 'alt="' . esc_attr( $author ) . '" width="28" height="28" loading="lazy" decoding="async" />';
		}

		if ( '' !== $author ) {
			$html .= '<span class="' . esc_attr( $base . '__author-naam' ) . '">' . esc_html( $author ) . '</span>';
		}

		return $html . '</div>';
	}

	/**
	 * The card's Heart / MessageCircle engagement counts. Pure.
	 *
	 * @param string                  $base The BEM base class.
	 * @param array<array-key, mixed> $card The card row.
	 * @return string
	 */
	private static function countsHtml( string $base, array $card ): string {
		$hart_count     = (int) ( $card['hart_count'] ?? 0 );
		$response_count = (int) ( $card['response_count'] ?? 0 );

		return '<div class="' . esc_attr( $base . '__tellers' ) . '">'
			. self::countHtml( $base, self::ICON_HEART, $hart_count, EngagementApi::hartjieCountLabel( $hart_count ) )
			. self::countHtml( $base, self::ICON_MESSAGE, $response_count, self::responseCountLabel( $response_count ) )
			. '</div>';
	}

	/**
	 * One engagement count: an aria-hidden icon + visible number, the whole span
	 * carrying the full verb-less accessible label. Pure.
	 *
	 * @param string $base  The BEM base class.
	 * @param string $icon  The Lucide inner-SVG paths.
	 * @param int    $count The count value.
	 * @param string $label The full accessible label.
	 * @return string
	 */
	private static function countHtml( string $base, string $icon, int $count, string $label ): string {
		return '<span class="' . esc_attr( $base . '__teller' ) . '" aria-label="' . esc_attr( $label ) . '">'
			. self::icon( $icon )
			. '<span aria-hidden="true">' . esc_html( (string) $count ) . '</span>'
			. '</span>';
	}

	/**
	 * The verb-less Gemeenskapsreaksie-count label (MessageCircle). Pure. Mirrors
	 * {@see \Ink\Discovery\FeaturedStream::responseCountLabel()}.
	 *
	 * @param int $n The response count.
	 * @return string
	 */
	private static function responseCountLabel( int $n ): string {
		$label = 1 === $n ? Terms::label( 'gemeenskapsreaksie' ) : Terms::label( 'gemeenskapsreaksie_plural' );

		return (string) $n . ' ' . $label;
	}

	/**
	 * A decorative inline Lucide icon (§0.9): 16px, currentColor, aria-hidden.
	 * Pure. `$paths` is a trusted class-internal SVG literal (never user input).
	 *
	 * @param string $paths The inner SVG markup.
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	/**
	 * The type-filter pills + sort control. Pure — escaping + URL builders only.
	 *
	 * Each control is a GET link that preserves the OTHER dimension (sort is kept
	 * when changing type, and vice-versa) and resets the page; the active type and
	 * sort are visually marked.
	 *
	 * @param string|null $active_type The active single type, or null for "Alles".
	 * @param string      $active_sort The active sort key.
	 * @return string
	 */
	public static function controlsHtml( ?string $active_type, string $active_sort ): string {
		$html = '<div class="ink-ontdek-werke__kontroles">';

		// Type filter: Alles + one pill per readable type.
		$html .= '<div class="ink-ontdek-werke__filter">';

		$alles_active = ( null === $active_type );
		$html        .= ArchiveRender::pill(
			(string) remove_query_arg( array( self::TYPE_VAR, self::PAGED_VAR ) ),
			__( 'Alles', 'ink-core' ),
			$alles_active,
			'ink-ontdek-werke__filter-knoppie'
		);

		foreach ( self::readableTypes() as $type ) {
			$url   = (string) add_query_arg( self::TYPE_VAR, $type, remove_query_arg( self::PAGED_VAR ) );
			$html .= ArchiveRender::pill(
				$url,
				Terms::label( $type . '_plural' ),
				$type === $active_type,
				'ink-ontdek-werke__filter-knoppie'
			);
		}

		$html .= '</div>';

		// Sort control: the three options.
		$html .= '<div class="ink-ontdek-werke__sorteer">';

		foreach ( self::allowedSorts() as $sort ) {
			$url   = (string) add_query_arg( self::SORT_VAR, $sort, remove_query_arg( self::PAGED_VAR ) );
			$html .= ArchiveRender::pill(
				$url,
				self::sortLabel( $sort ),
				$sort === $active_sort,
				'ink-ontdek-werke__sorteer-knoppie'
			);
		}

		$html .= '</div></div>';

		return $html;
	}

	/**
	 * The date-archive browse control — "Alle datums" + a pill per year. Pure.
	 *
	 * Each link sets/clears the `werke_jaar` query var (resetting the page); the
	 * active year is marked. Renders nothing without a year list (so callers that
	 * pass no years — e.g. a sort-only context — get no date row).
	 *
	 * @param int|null  $active_year The active archive year, or null for all dates.
	 * @param list<int> $years       The years to offer.
	 * @return string
	 */
	public static function dateBrowseHtml( ?int $active_year, array $years ): string {
		if ( array() === $years ) {
			return '';
		}

		$html = '<div class="ink-ontdek-werke__datums">';

		$html .= ArchiveRender::pill(
			(string) remove_query_arg( array( self::YEAR_VAR, self::MONTH_VAR, self::PAGED_VAR ) ),
			__( 'Alle datums', 'ink-core' ),
			( null === $active_year ),
			'ink-ontdek-werke__datum-knoppie'
		);

		foreach ( $years as $year ) {
			$year  = (int) $year;
			$url   = (string) add_query_arg( self::YEAR_VAR, $year, remove_query_arg( array( self::MONTH_VAR, self::PAGED_VAR ) ) );
			$html .= ArchiveRender::pill(
				$url,
				(string) $year,
				$year === $active_year,
				'ink-ontdek-werke__datum-knoppie'
			);
		}

		$html .= '</div>';

		return $html;
	}

	/**
	 * The Afrikaans label for a sort key.
	 *
	 * Authored source copy (ui-copy-translations.md) — Nuut / Opspraakwekkend /
	 * Mees geliefd; copy-debt to ratify into the glossary on the next pass.
	 *
	 * @param string $sort A sort key.
	 * @return string
	 */
	private static function sortLabel( string $sort ): string {
		switch ( $sort ) {
			case self::SORT_OPSPRAAK:
				return __( 'Opspraakwekkend', 'ink-core' );
			case self::SORT_GELIEFD:
				return __( 'Mees geliefd', 'ink-core' );
			case self::SORT_NUUT:
			default:
				return __( 'Nuut', 'ink-core' );
		}
	}
}
