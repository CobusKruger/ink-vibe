<?php
/**
 * Uitdagings list page — Story 12.2 (FR-46).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeResultTitle;
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
 * Lists published `uitdaging` posts newest-first, paginated, each as a card with the
 * tema, the sluitingsdatum and a server-computed **countdown** ("Nog N dae" while
 * open, "Sluit vandag" on the deadline day, "Gesluit" once closed) derived from the
 * inclusive end-of-day-SAST deadline ({@see Sast}). Reads stay SERVER-RENDERED via
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
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page ): array {
		return array(
			'post_type'           => PostTypes::UITDAGING,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);
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
		$paged = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$query = self::runQuery( self::queryArgs( $paged, self::PER_PAGE ) );
		$now   = Sast::now();

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$deadline = Deadline::parse(
				Scalar::asString( get_post_meta( (int) $post->ID, FieldSets::UITDAGING_DEADLINE, true ) )
			);

			$cards[] = self::cardHtml(
				array(
					'title'     => get_the_title( $post ),
					'permalink' => (string) get_permalink( $post ),
					'month'     => ChallengeResultTitle::monthLabel( (string) $post->post_date ),
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
			)
		);
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
	 * @param string $paths The inner SVG markup.
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	/**
	 * Build the archive HTML. Pure — Terms + escaping only.
	 *
	 * The intro band + list band mirror `Hub::toHtml()`'s shell (Post-Epic-19
	 * fidelity pass, workstream 7's card-grid parity; fidelity Stage 4 row 14).
	 *
	 * @param list<string>                    $cards The pre-rendered card markup.
	 * @param array{paged:int, max_pages:int} $nav The render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		// Fidelity Stage 4 (row 14, Treatment C OFF-RECIPE): the index-page shell the
		// Lovable index pages use (`Library.tsx` intro) — a full-bleed tinted intro band
		// with its own container, then the list in a `container py-12 md:py-16` band.
		// Same shell as `Ink\Training\Hub` / `Ink\Library\Archive`; the root is
		// `alignfull` and the pattern adds no padding of its own.
		$intro = '<section class="ink-uitdagings__intro ink-band ink-band--intro ink-band--flush" data-audit-id="uitdagings-intro">'
			. '<div class="ink-uitdagings__intro-houer ink-container">'
			. '<div class="ink-uitdagings__intro-binne">'
			. '<h1 class="ink-uitdagings__heading ink-heading-h1-index" data-audit-id="uitdagings-h1">' . esc_html( Terms::label( 'uitdaging_plural' ) ) . '</h1>'
			. '</div></div></section>';

		$band = '<section class="ink-uitdagings__lys-band ink-container ink-band ink-band--step">';

		if ( array() === $cards ) {
			/* translators: %s: the challenges (uitdagings) label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'uitdaging_plural' ) );

			return '<div class="ink-uitdagings alignfull">' . $intro . $band
				. '<p class="ink-uitdagings__leeg">' . esc_html( $empty ) . '</p></section></div>';
		}

		$html = '<div class="ink-uitdagings alignfull">' . $intro . $band . '<ul class="ink-uitdagings__list" data-audit-id="uitdagings-list">' . implode( '', $cards ) . '</ul>';

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		return $html . ArchiveRender::pagination( $paged, $max_pages, 'ink-uitdagings', self::PAGED_VAR ) . '</section></div>';
	}
}
