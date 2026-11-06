<?php
/**
 * Uitdagings list page — Story 12.2 (FR-46).
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
use Ink\Kernel\ArchiveRender;
use Ink\Kernel\QaFixture;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/uitdaging-argief` block: the challenges list page (Archetype B).
 *
 * The design's heading and featured current-round card ({@see CurrentChallenge::current()}:
 * the open round, else the newest), then the other published `uitdaging` posts newest-first,
 * paginated, each as INK's standard card with the tema, the sluitingsdatum and a
 * server-computed **countdown** ("Nog N dae" while open, "Sluit vandag" on the deadline day,
 * "Gesluit" once closed) derived from the inclusive end-of-day-SAST deadline ({@see Sast}).
 *
 * Disclosed divergences from `Challenges.tsx` (`07-uitdagings`, owner rulings; R6):
 * - Past rounds are INK's cards and pager, not the design's rows (2026-11-01).
 * - The featured card's date slot holds the countdown, not a closing date (2026-11-02).
 * - A closed featured card says "Gesluit" and "Lees meer" and has no date slot.
 * - The meta items' icon gap is the small meta item's 6px (NR-4), where the design has 8px.
 *
 * Reads stay SERVER-RENDERED via
 * `WP_Query` (AD-7), mirroring the {@see \Ink\Library\Archive} house style: pure
 * {@see self::queryArgs()} + pure {@see self::countdownLabel()}/{@see self::toHtml()}
 * + a thin {@see self::render()}.
 *
 * Conflation-clean: references only `Ink\Content` (CPT + deadline/theme meta keys) +
 * the `Kernel` helpers + the `Terms` registry + WP core — zero `Ink\Tiers`/
 * `Ink\Entitlement`. Browsing published challenges is open (never gated).
 *
 * @package Ink\Core
 */
final class Archive {

	/**
	 * The block name — single source for the renderer + the theme pattern embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitdaging-argief';

	/**
	 * Challenges per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * Custom paged query var — avoids colliding with WP page pagination.
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'uitdaging_bladsy';

	/**
	 * Overridable data seam: turns QA-fixture-titled `uitdaging` posts back ON for
	 * this listing (Epic-19 theme-fidelity re-audit finding, workstream 7 — the
	 * archive query had NO fixture exclusion at all, so the real `/uitdaging/` page
	 * showed 3 QA FIXTURE cards alongside the one real published challenge; the same
	 * leak class already fixed on the sponsor strip, Opleiding hub, Biblioteek
	 * archive and the single-challenge entries list). Mirrors
	 * {@see \Ink\Training\Hub::INCLUDE_FIXTURES_FILTER} /
	 * {@see \Ink\Library\Archive::INCLUDE_FIXTURES_FILTER}.
	 *
	 * @var string
	 */
	public const INCLUDE_FIXTURES_FILTER = 'ink_uitdaging_argief_include_fixtures';

	/**
	 * Lucide `calendar` icon path data (Post-Epic-19 fidelity pass, workstream 7) —
	 * the SAME glyph {@see SinglePage::ICON_CALENDAR} uses for its sluitingsdatum
	 * row, so a card here and the single-page hero read as the same visual
	 * language. Local to this class, matching the per-class icon-constant house
	 * style (see {@see SinglePage}'s own docblock for precedent).
	 *
	 * @var string
	 */
	private const ICON_CALENDAR = '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>';

	/**
	 * Lucide `arrow-right` icon path data — the card's hover-reveal "Lees meer"
	 * affordance, mirroring {@see \Ink\Training\Hub::ICON_ARROW} /
	 * {@see \Ink\Library\Archive}'s own copy of the same glyph (per-class icon
	 * house style, no shared Kernel icon helper exists yet).
	 *
	 * @var string
	 */
	private const ICON_ARROW = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * Lucide `sparkles` icon path data: the featured card's badge.
	 *
	 * @var string
	 */
	private const ICON_SPARKLES = '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>';

	/**
	 * Lucide `users` icon path data: the featured card's entry counts.
	 *
	 * @var string
	 */
	private const ICON_USERS = '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>';

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
	 * Register the `ink/uitdaging-argief` dynamic block.
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
	 * Build the `WP_Query` args for the newest-first challenges list. Pure.
	 *
	 * @param int $paged    The requested page (clamped to >= 1).
	 * @param int $per_page Challenges per page.
	 * @param int $exclude  The featured round, left out on every page so page contents don't
	 *                      shift between pages; 0 for none.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page, int $exclude = 0 ): array {
		$args = array(
			'post_type'           => PostTypes::UITDAGING,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);

		if ( $exclude > 0 ) {
			$args['post__not_in'] = array( $exclude );
		}

		return $args;
	}

	/**
	 * The countdown label for a deadline relative to `now`. Pure.
	 *
	 * Empty without a deadline; "Gesluit" once past the inclusive end-of-day-SAST
	 * boundary; "Sluit vandag" on the deadline's own SAST day; otherwise "Nog N dae"
	 * (singular "Nog 1 dag"), counting whole SAST calendar days.
	 *
	 * @param \DateTimeImmutable|null $deadline The deadline instant, or null.
	 * @param \DateTimeInterface      $now      The instant to measure from.
	 * @return string
	 */
	public static function countdownLabel( ?\DateTimeImmutable $deadline, \DateTimeInterface $now ): string {
		if ( null === $deadline ) {
			return '';
		}

		if ( ! Sast::isThroughEndOfDay( $deadline, $now ) ) {
			return Terms::label( 'uitdaging_gesluit' );
		}

		$sast         = new \DateTimeZone( Sast::TIMEZONE );
		$deadline_day = \DateTimeImmutable::createFromInterface( $deadline )->setTimezone( $sast )->setTime( 0, 0, 0 );
		$now_day      = \DateTimeImmutable::createFromInterface( $now )->setTimezone( $sast )->setTime( 0, 0, 0 );

		$days = (int) $now_day->diff( $deadline_day )->format( '%r%a' );

		if ( $days <= 0 ) {
			return Terms::label( 'uitdaging_sluit_vandag' );
		}

		if ( 1 === $days ) {
			return __( 'Nog 1 dag', 'ink-core' );
		}

		/* translators: %d: whole days remaining until the challenge deadline. */
		return sprintf( __( 'Nog %d dae', 'ink-core' ), $days );
	}

	/**
	 * Run a `WP_Query`, excluding QA-fixture-titled posts by default (Epic-19
	 * theme-fidelity re-audit finding — see {@see INCLUDE_FIXTURES_FILTER}). The
	 * exclusion is applied at the SQL layer (a scoped `posts_where` filter, removed
	 * immediately after), keeping `found_posts`/`max_num_pages` accurate for
	 * pagination even with fixtures excluded. Mirrors
	 * {@see \Ink\Training\Hub::runQuery()} / {@see \Ink\Library\Archive::runQuery()} /
	 * {@see \Ink\Challenges\SinglePage::runQuery()}. Impure (WP_Query + filter).
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
	 * How many entries a round has (its round term's count). Side-effecting (reads the term).
	 *
	 * @param int $uitdaging_id The Uitdaging.
	 */
	private static function entryCount( int $uitdaging_id ): int {
		$term = get_term_by( 'slug', ChallengeRound::slugFor( $uitdaging_id ), Taxonomies::UITDAGINGSRONDTE );

		return $term instanceof \WP_Term ? (int) $term->count : 0;
	}

	/**
	 * Block render callback. Reads the page input, queries, builds cards, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$paged   = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$current = CurrentChallenge::current();
		$query   = self::runQuery( self::queryArgs( $paged, self::PER_PAGE, is_array( $current ) ? (int) $current['id'] : 0 ) );
		$now     = Sast::now();

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$deadline = Cadence::closeFor( (int) $post->ID );

			$cards[] = self::cardHtml(
				array(
					'title'     => get_the_title( $post ),
					'permalink' => (string) get_permalink( $post ),
					'month'     => null !== $deadline ? Cadence::periodLabelFor( (int) $post->ID, $deadline ) : '',
					'excerpt'   => wp_trim_words( trim( wp_strip_all_tags( (string) $post->post_content ) ), 28 ),
					'entries'   => self::entryCount( (int) $post->ID ),
					'tema'      => Scalar::asString( get_post_meta( (int) $post->ID, FieldSets::UITDAGING_THEME, true ) ),
					'deadline'  => null !== $deadline ? Deadline::format( $deadline ) : '',
					'countdown' => self::countdownLabel( $deadline, $now ),
					'is_open'   => null !== $deadline && Sast::isThroughEndOfDay( $deadline, $now ),
					'audit'     => array() === $cards,
				)
			);
		}

		return self::toHtml(
			$cards,
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => (int) $query->max_num_pages,
			),
			is_array( $current ) && max( 1, $paged ) === 1 ? self::currentHtml( $current, self::currentCountdown( $current, $now ) ) : ''
		);
	}

	/**
	 * The featured card's countdown: an open round's only, since a closed card has no date
	 * slot. Pure.
	 *
	 * @param array<string, mixed> $round The {@see CurrentChallenge::current()} payload.
	 * @param \DateTimeInterface   $now   The instant to measure from.
	 * @return string
	 */
	public static function currentCountdown( array $round, \DateTimeInterface $now ): string {
		$close = $round['close'] ?? null;

		return ! empty( $round['is_open'] ) && $close instanceof \DateTimeImmutable ? self::countdownLabel( $close, $now ) : '';
	}

	/**
	 * The current-round band, from the design's featured card. Pure: Terms + escaping only.
	 *
	 * @param array<string, mixed> $round     The {@see CurrentChallenge::current()} payload.
	 * @param string               $countdown The countdown for the date slot ('' for none).
	 * @return string
	 */
	public static function currentHtml( array $round, string $countdown ): string {
		$is_open     = ! empty( $round['is_open'] );
		$month       = (string) ( $round['month'] ?? '' );
		$excerpt     = (string) ( $round['excerpt'] ?? '' );
		$type_counts = is_array( $round['type_counts'] ?? null ) ? CurrentChallenge::typeCountsLabel( $round['type_counts'] ) : '';
		$status      = Terms::label( $is_open ? 'uitdaging_oop' : 'uitdaging_gesluit' );
		$action      = $is_open ? __( 'Skryf in', 'ink-core' ) : __( 'Lees meer', 'ink-core' );

		$items = '';

		if ( '' !== $countdown ) {
			$items .= '<span class="ink-meta ink-meta--sm" data-audit-id="uitdagings-current-meta-close">' . self::icon( self::ICON_CALENDAR ) . esc_html( $countdown ) . '</span>';
		}

		if ( '' !== $type_counts ) {
			$items .= '<span class="ink-meta ink-meta--sm" data-audit-id="uitdagings-current-meta-entries">' . self::icon( self::ICON_USERS ) . esc_html( $type_counts ) . '</span>';
		}

		return '<section class="ink-band ink-band--step" data-audit-id="uitdagings-current">'
			. '<div class="ink-uitdagings__houer ink-container" data-audit-id="uitdagings-current-container">'
			. '<div class="ink-card--soft ink-card--soft-lg" data-audit-id="uitdagings-current-card">'
			. '<div class="ink-uitdagings__huidig-kenteken ink-pill ink-pill--solid" data-audit-id="uitdagings-current-badge">'
			. self::icon( self::ICON_SPARKLES, 'uitdagings-current-badge-icon' )
			. esc_html( '' !== $month ? $status . ' · ' . $month : $status )
			. '</div>'
			. '<h2 class="ink-uitdagings__huidig-titel ink-heading-h2-section" data-audit-id="uitdagings-current-title">"' . esc_html( (string) ( $round['theme'] ?? '' ) ) . '"</h2>'
			. ( '' !== $excerpt ? '<p class="ink-uitdagings__huidig-beskrywing" data-audit-id="uitdagings-current-description">' . esc_html( $excerpt ) . '</p>' : '' )
			. ( '' !== $items ? '<div class="ink-uitdagings__huidig-meta ink-meta-row ink-meta-row--wide" data-audit-id="uitdagings-current-meta">' . $items . '</div>' : '' )
			. '<div class="ink-uitdagings__huidig-aksies">'
			. '<a class="ink-btn ink-btn--primary" data-audit-id="uitdagings-current-enter" href="' . esc_url( (string) ( $round['url'] ?? '' ) ) . '">' . esc_html( $action ) . self::icon( self::ICON_ARROW ) . '</a>'
			. '</div></div></div></section>';
	}

	/**
	 * One challenge card. Pure — Terms + escaping only.
	 *
	 * Post-Epic-19 fidelity pass (workstream 7, re-audited workstream 7b): a card-grid
	 * recipe matching the established `Ink\Training\Hub`/`Ink\Library\Archive`
	 * archive-card language (surface-alt / border / radius.lg / shadow.sm /
	 * hover-lift, footer border + hover-reveal "Lees meer" affordance) — this page had
	 * NO CSS at all before
	 * (same shape as Opleiding/Biblioteek pre-fix). The status/countdown pill and
	 * the icon-led sluitingsdatum row deliberately REUSE the exact
	 * `ink-uitdaging__toestand-pil` / `ink-uitdaging__sluitingsdatum-ry` classes
	 * {@see SinglePage::statusHtml()} already established (the "date readability"
	 * risk flagged for both pages in page-map.csv) — same colours/shape/icon
	 * treatment as the single-challenge page, not a reinvented visual language.
	 * The pill's TEXT is the countdown label (not a bare "Oop"/"Gesluit") — more
	 * useful when scanning a whole grid of cards than the single-page's static
	 * status word, while its is-oop/is-gesluit colour coding stays identical.
	 *
	 * Fidelity-audit join keys (`data-audit-id`, docs/fidelity-remediation/maps/uitdagings.csv)
	 * are emitted only when `audit` is true — the FIRST card of the list (repeaters:
	 * first instance only).
	 *
	 * @param array{title:string, permalink:string, month?:string, excerpt?:string, entries?:int, tema?:string, deadline?:string, countdown?:string, is_open?:bool, audit?:bool} $card The challenge.
	 * @return string
	 */
	public static function cardHtml( array $card ): string {
		$is_open   = ! empty( $card['is_open'] );
		$state     = $is_open ? 'is-oop' : 'is-gesluit';
		$tone      = $is_open ? 'ink-tone-success' : 'ink-tone-muted';
		$month     = isset( $card['month'] ) ? (string) $card['month'] : '';
		$excerpt   = isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '';
		$entries   = isset( $card['entries'] ) ? (int) $card['entries'] : 0;
		$tema      = isset( $card['tema'] ) ? (string) $card['tema'] : '';
		$deadline  = isset( $card['deadline'] ) ? (string) $card['deadline'] : '';
		$countdown = isset( $card['countdown'] ) ? (string) $card['countdown'] : '';
		$permalink = isset( $card['permalink'] ) ? (string) $card['permalink'] : '';
		$audit     = ! empty( $card['audit'] );
		$aid       = static fn ( string $id ): string => $audit ? ' data-audit-id="' . esc_attr( $id ) . '"' : '';

		// Same card language as Biblioteek, Opleiding and Ontdek: a pill top left, a small
		// meta item top right, the title, a clamped excerpt, a ruled footer. The pill is
		// the round's month ("Oktober 2026"), where those cards show the type or shelf.
		$left = '';

		if ( '' !== $month ) {
			$left .= '<span class="ink-uitdagings__maand ink-pill ink-pill--outline"' . $aid( 'challenge-card-month' ) . '>' . esc_html( $month ) . '</span>';
		}

		if ( '' !== $tema ) {
			$left .= '<span class="ink-uitdagings__tema-pil ink-pill ink-pill--soft"' . $aid( 'challenge-card-theme-pill' ) . '>' . esc_html( $tema ) . '</span>';
		}

		$right = '';

		if ( '' !== $countdown ) {
			$right = '<span class="ink-uitdaging__toestand-pil ink-pill ink-pill--tint-xs ' . esc_attr( $tone . ' ' . $state ) . '"' . $aid( 'challenge-card-status-pill' ) . '>' . esc_html( $countdown ) . '</span>';
		} elseif ( $entries > 0 ) {
			/* translators: %s: the number of entries in a challenge round. */
			$right = '<span class="ink-uitdagings__inskrywings ink-meta"' . $aid( 'challenge-card-entries' ) . '>' . esc_html( sprintf( _n( '%s inskrywing', '%s inskrywings', $entries, 'ink-core' ), number_format_i18n( $entries ) ) ) . '</span>';
		}

		$meta = '' !== $left || '' !== $right
			? '<div class="ink-uitdagings__item-meta"' . $aid( 'challenge-card-head' ) . '><div class="ink-uitdagings__item-pille">' . $left . '</div>' . $right . '</div>'
			: '';

		$excerpt_html = '' !== $excerpt
			? '<p class="ink-uitdagings__item-uittreksel"' . $aid( 'challenge-card-excerpt' ) . '>' . esc_html( $excerpt ) . '</p>'
			: '';

		$voet = '' !== $deadline
			? '<div class="ink-uitdagings__item-voet"' . $aid( 'challenge-card-meta' ) . '>'
				. '<span class="ink-uitdaging__sluitingsdatum-ry ink-meta ink-meta--sm"' . $aid( 'challenge-card-meta-item' ) . '>'
				. self::icon( self::ICON_CALENDAR )
				. '<span class="ink-uitdaging__sluitingsdatum-etiket">' . esc_html( Terms::label( 'sluitingsdatum' ) ) . ': </span>'
				. '<time class="ink-uitdaging__sluitingsdatum">' . esc_html( $deadline ) . '</time>'
				. '</span>'
				. '<a class="ink-uitdagings__lees ink-link"' . $aid( 'challenge-card-btn' ) . ' href="' . esc_url( $permalink ) . '" tabindex="-1" aria-hidden="true">'
				. esc_html__( 'Lees meer', 'ink-core' ) . self::icon( self::ICON_ARROW )
				. '</a>'
				. '</div>'
			: '';

		return '<li class="ink-uitdagings__item ink-card ' . esc_attr( $state ) . '"' . $aid( 'challenge-card' ) . '>'
			. '<div class="ink-uitdagings__item-inhoud">'
			. $meta
			. '<a class="ink-uitdagings__titel ink-heading-h3-card"' . $aid( 'challenge-card-title' ) . ' href="' . esc_url( $permalink ) . '">' . esc_html( $card['title'] ) . '</a>'
			. $excerpt_html
			. $voet
			. '</div></li>';
	}

	/**
	 * A decorative inline Lucide icon (§0.9): 16px, currentColor, aria-hidden. Pure.
	 * `$paths` is a trusted class-internal SVG literal (never user input). Mirrors
	 * {@see SinglePage}'s own copy of the same convention (each module keeps its
	 * own copy — no shared Kernel icon helper exists yet).
	 *
	 * @param string $paths    The inner SVG markup.
	 * @param string $audit_id The audit id, or '' for none.
	 * @return string
	 */
	private static function icon( string $paths, string $audit_id = '' ): string {
		return '<svg' . ( '' !== $audit_id ? ' data-audit-id="' . esc_attr( $audit_id ) . '"' : '' ) . ' xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	/**
	 * Build the page HTML: hero, the current-round band on page 1, then the past-rounds band.
	 * Pure: Terms + escaping only.
	 *
	 * Copy with no Afrikaans in the repo yet is a registered copy-debt marker
	 * (`docs/afrikaans-translation-sheet.md`). The pager and empty state are INK-only: `scope`.
	 *
	 * @param list<string>                    $cards   The pre-rendered past-round cards.
	 * @param array{paged:int, max_pages:int} $nav     The render context.
	 * @param string                          $current The current-round band ('' for none).
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav, string $current = '' ): string {
		$hero = '<section class="ink-band ink-band--step ink-band--border-b" data-audit-id="uitdagings-hero">'
			. '<div class="ink-uitdagings__houer ink-container" data-audit-id="uitdagings-hero-container">'
			. '<p class="ink-uitdagings__boskrif ink-eyebrow" data-audit-id="uitdagings-eyebrow">' . esc_html__( 'Maandelikse uitdagings', 'ink-core' ) . '</p>'
			. '<h1 class="ink-uitdagings__heading ink-heading-h1-index" data-audit-id="uitdagings-title">' . esc_html__( '\'n Nuwe uitdaging, elke maand', 'ink-core' ) . '</h1>'
			. '<p class="ink-uitdagings__inleiding" data-audit-id="uitdagings-intro">' . esc_html__( 'Elke maand kies ons \'n opdrag vir gedigte, stories en artikels. Alle inskrywings word anoniem beoordeel en die top drie in elke kategorie word spoedig aangekondig.', 'ink-core' ) . '</p>'
			. '</div></section>';

		return '<div class="ink-uitdagings alignfull">' . $hero . $current . self::pastHtml( $cards, $nav, '' !== $current ) . '</div>';
	}

	/**
	 * The past-rounds band; with no cards, the empty state, unless the current round is the
	 * only one there is. Pure.
	 *
	 * @param list<string>                    $cards       The pre-rendered past-round cards.
	 * @param array{paged:int, max_pages:int} $nav         The render context.
	 * @param bool                            $has_current Whether the page shows a current round.
	 * @return string
	 */
	private static function pastHtml( array $cards, array $nav, bool $has_current ): string {
		if ( array() === $cards && $has_current ) {
			return '';
		}

		if ( array() === $cards ) {
			/* translators: %s: the challenges (uitdagings) label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'uitdaging_plural' ) );

			return '<section class="ink-band ink-band--step"><div class="ink-container">'
				. '<p class="ink-uitdagings__leeg">' . esc_html( $empty ) . '</p></div></section>';
		}

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		return '<section class="ink-band ink-band--step ink-band--border-t" data-audit-id="uitdagings-past">'
			// The standard wide container, as on Biblioteek and Opleiding, because these are the
			// standard cards (DIVERGENCE, decided 2026-11-02; maps/uitdagings.csv). The design's
			// narrow column stays on the two bands above.
			. '<div class="ink-container" data-audit-id="uitdagings-past-container">'
			. '<h2 class="ink-uitdagings__vorige-titel ink-heading-h2-section" data-audit-id="uitdagings-past-title">' . esc_html__( 'Vorige uitdaging', 'ink-core' ) . '</h2>'
			. '<ul class="ink-uitdagings__list">' . implode( '', $cards ) . '</ul>'
			. ArchiveRender::pagination( $paged, $max_pages, 'ink-uitdagings', self::PAGED_VAR )
			. '</div></section>';
	}
}
