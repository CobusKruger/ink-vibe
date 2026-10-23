<?php
/**
 * Public Skrywerprofiel server block — Story 9.4 (FR-40).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use Ink\Discovery\ReadingTime;
use Ink\Engagement\Api as EngagementApi;
use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;
use Ink\Tiers\Api as TiersApi;
use WP_Query;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/skrywerprofiel` block: the PUBLIC author profile.
 *
 * Resolves the *queried* skrywer at render time (`get_queried_object_id()` on
 * the author template) — this MUST be a server-rendered block, not pattern PHP,
 * because a pattern's code runs at registration/`init`, before the main query
 * resolves. Renders the public card: cover image + avatar + name, the genre
 * pills + Gradering badge (Story 5.4) + joined date, the volgeling count
 * (Story 9.2), the Volg / Volg tans toggle (Story 9.2) + Share, a stats strip
 * (rating/works/volgelinge/hartjies), an "Oor" bio section, an accomplishments
 * rail sourced from the writer's real Gradering history, the pinned-works
 * grid (Story 9.5) rendered as reading-list cards, and a closing follow CTA.
 *
 * PUBLIC data only — it renders NO read counts and NO "wins needed" subtext
 * (those are private My Profiel surfaces, Stories 9.12 / 5.9). That separation
 * is the load-bearing FR-40 guarantee.
 *
 * Every added string below is copied VERBATIM from the already-ratified
 * `docs/ui-copy-translations.md` "Skrywerprofiel-bladsy (`Writer.tsx`)" sheet
 * (human-authored, approved) — none of it is newly translated here.
 *
 * The Gradering badge reads `Tiers\Api::gradingView()` for DISPLAY only (never a
 * gate — the same conflation-clean display read as Discovery→Tiers in 8.5);
 * follow data comes from `Social\Api`. The stats strip's hartjie total + each
 * pinned card's like/response counts read `Engagement\Api` for DISPLAY only
 * (Phase-2 fidelity pass; see deptrac.yaml Social ruleset). Renders its own
 * escaped HTML (the Discovery/Engagement block house style).
 *
 * @package Ink\Core
 */
final class SkrywerProfiel {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/skrywerprofiel';

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
	 * Register the `ink/skrywerprofiel` dynamic block.
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
	 * Block render callback — only on an author (skrywer) archive context.
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! ( function_exists( 'is_author' ) && is_author() ) ) {
			return '';
		}

		$author_id = (int) get_queried_object_id();

		if ( $author_id <= 0 ) {
			return '';
		}

		$profile = array(
			'name'            => (string) get_the_author_meta( 'display_name', $author_id ),
			// The short tagline the writer edits on My Profiel (Tagline, `ink_skrywer_leuse`);
			// Writer.tsx:219 shows it under the name (fidelity row 06, F2).
			'tagline'         => Tagline::get( $author_id ),
			'bio'             => (string) get_the_author_meta( 'description', $author_id ),
			'avatar'          => function_exists( 'get_avatar' ) ? (string) get_avatar( $author_id, 160, '', '', array( 'class' => 'ink-avatar ink-avatar--hero' ) ) : '',
			'cover'           => class_exists( CoverImage::class ) ? CoverImage::urlFor( $author_id, 'large' ) : '',
			'badge'           => self::graderingBadge( $author_id ),
			'volgeling'       => Api::volgelingLabel( Api::followerCount( $author_id ) ),
			'volg'            => FollowToggle::render( array( 'skrywerId' => $author_id ) ),
			'joined'          => self::joinedLabel( $author_id ),
			'genres'          => self::genreLabels( $author_id ),
			'works'           => self::worksBreakdown( $author_id ),
			'hartjies'        => self::hartjieTotal( $author_id ),
			'pinned'          => self::pinnedCards( $author_id ),
			'aggregate'       => Api::ratingAggregateFor( $author_id ),
			'reviews'         => Api::approvedReviewsFor( $author_id ),
			'accomplishments' => self::accomplishments( $author_id ),
			'shareUrl'        => (string) get_author_posts_url( $author_id ),
		);

		return self::toHtml( $profile );
	}

	/**
	 * The token-only Gradering badge for a skrywer (display only, never a gate).
	 *
	 * Reads the typed display view from the Tiers facade (the same source the
	 * theme bridge uses). The grade LABEL is always rendered as text (a11y); the
	 * mark is decorative. Empty when ink-core Tiers is unavailable.
	 *
	 * @param int $author_id The skrywer.
	 * @return string
	 */
	private static function graderingBadge( int $author_id ): string {
		if ( ! class_exists( TiersApi::class ) ) {
			return '';
		}

		$view = TiersApi::gradingView( $author_id );

		return sprintf(
			'<span class="ink-gradering ink-gradering--%1$s"><span class="ink-gradering__mark" aria-hidden="true">&#9733;</span><span class="ink-gradering__label">%2$s</span></span>',
			esc_attr( $view->cssModifier() ),
			esc_html( $view->label )
		);
	}

	/**
	 * The "Aangesluit [datum]" meta label (`user_registered` — real WP core data).
	 *
	 * @param int $author_id The skrywer.
	 * @return string Empty when the registration date can't be resolved.
	 */
	private static function joinedLabel( int $author_id ): string {
		$registered = (string) get_the_author_meta( 'user_registered', $author_id );

		if ( '' === $registered ) {
			return '';
		}

		$timestamp = strtotime( $registered );

		if ( false === $timestamp ) {
			return '';
		}

		/* translators: %s: the month and year the writer joined, e.g. "April 2024". */
		return sprintf( __( 'Aangesluit %s', 'ink-core' ), date_i18n( 'F Y', $timestamp ) );
	}

	/**
	 * The genre pills — the reader-facing bydrae types this skrywer has actually
	 * published in (real data derived from `Content\PostTypes::readableTypes()`,
	 * never an invented taxonomy). Fixture-excluded (see {@see publishedWorkIds()}).
	 *
	 * @param int $author_id The skrywer.
	 * @return list<string>
	 */
	private static function genreLabels( int $author_id ): array {
		$counts = self::publishedWorkCountsByType( $author_id );
		$labels = array();

		foreach ( PostTypes::readableTypes() as $type ) {
			if ( ( $counts[ $type ] ?? 0 ) > 0 ) {
				$labels[] = Terms::label( $type );
			}
		}

		return $labels;
	}

	/**
	 * The per-type published-work counts (e.g. "[N] stories · [N] gedigte ·
	 * [N] artikels" — zero-value types omitted per the ratified copy sheet's
	 * note 2). Fixture-excluded (see {@see publishedWorkIds()}).
	 *
	 * @param int $author_id The skrywer.
	 * @return array{total:int, items:list<array{label:string,count:int}>}
	 */
	private static function worksBreakdown( int $author_id ): array {
		$counts = self::publishedWorkCountsByType( $author_id );
		$items  = array();
		$total  = 0;

		foreach ( PostTypes::readableTypes() as $type ) {
			$count  = $counts[ $type ] ?? 0;
			$total += $count;

			if ( $count > 0 ) {
				$items[] = array(
					'label' => Terms::label( $type ),
					'count' => $count,
				);
			}
		}

		return array(
			'total' => $total,
			'items' => $items,
		);
	}

	/**
	 * The published (readable-type) post ids for a skrywer, EXCLUDING any
	 * `QA FIXTURE — ` titled post (same fixture-leak bug class already fixed on
	 * every other page this rework — a writer's real public work counts/genre
	 * pills must never be inflated by seeded QA content).
	 *
	 * @param int $author_id The skrywer.
	 * @return list<int>
	 */
	private static function publishedWorkIds( int $author_id ): array {
		$query = new WP_Query(
			array(
				'author'         => $author_id,
				'post_type'      => PostTypes::readableTypes(),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$ids = array_map( 'intval', $query->posts );

		return array_values(
			array_filter(
				$ids,
				static function ( int $post_id ): bool {
					return ! QaFixture::isFixtureTitle( get_the_title( $post_id ) );
				}
			)
		);
	}

	/**
	 * The fixture-excluded published-work counts, grouped by post type. Shared
	 * helper for {@see genreLabels()} and {@see worksBreakdown()} — a single
	 * query pass instead of one `count_user_posts()` call per type (which also
	 * can't exclude by title at all).
	 *
	 * @param int $author_id The skrywer.
	 * @return array<string,int> Post type => count.
	 */
	private static function publishedWorkCountsByType( int $author_id ): array {
		$counts = array();

		foreach ( self::publishedWorkIds( $author_id ) as $post_id ) {
			$type            = (string) get_post_type( $post_id );
			$counts[ $type ] = ( $counts[ $type ] ?? 0 ) + 1;
		}

		return $counts;
	}

	/**
	 * The skrywer's total hartjie (Heart reaction) count across all their
	 * published work — the "hartjies" stat (ratified copy sheet).
	 *
	 * @param int $author_id The skrywer.
	 * @return int
	 */
	private static function hartjieTotal( int $author_id ): int {
		if ( ! class_exists( EngagementApi::class ) ) {
			return 0;
		}

		$total = 0;

		foreach ( self::publishedWorkIds( $author_id ) as $post_id ) {
			$total += EngagementApi::hartjieCountForPost( $post_id );
		}

		return $total;
	}

	/**
	 * The queried author's pinned works, resolved to reading-list cards (Story 9.5).
	 *
	 * Reads {@see PinnedWorks::forUser()} (in pin = display order) and resolves
	 * each id to a card, skipping any that is no longer a published bydrae (a
	 * stale pin never renders a broken card). Each card carries real per-post
	 * data only — excerpt, publish-age, hartjie/gemeenskapsreaksie counts,
	 * the latter three via the shared {@see WorkCardFacts} helper (also used by
	 * {@see FollowingFeed} — the identical per-post shape, a second caller).
	 *
	 * @param int $author_id The skrywer.
	 * @return list<array{title:string, permalink:string, type:string, excerpt:string, daysAgo:string, readTime:string, hartjies:int, hartjieLabel:string, gemeenskap:int}>
	 */
	private static function pinnedCards( int $author_id ): array {
		$cards = array();

		foreach ( PinnedWorks::forUser( $author_id ) as $post_id ) {
			if ( 'publish' !== get_post_status( $post_id ) ) {
				continue;
			}

			if ( QaFixture::isFixtureTitle( get_the_title( $post_id ) ) ) {
				continue;
			}

			$engagement = WorkCardFacts::engagement( $post_id );

			$cards[] = array(
				'title'        => get_the_title( $post_id ),
				'permalink'    => (string) get_permalink( $post_id ),
				'type'         => (string) get_post_type( $post_id ),
				'excerpt'      => (string) get_the_excerpt( $post_id ),
				'daysAgo'      => WorkCardFacts::daysAgoLabelForPost( $post_id ),
				// Read time (Writer.tsx:430, F15) — the shared word-count rule + "%d min" label.
				'readTime'     => ReadingTime::label( ReadingTime::minutesFromText( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) ) ),
				'hartjies'     => $engagement['hartjies'],
				'hartjieLabel' => $engagement['hartjieLabel'],
				'gemeenskap'   => $engagement['gemeenskap'],
			);
		}

		return $cards;
	}

	/**
	 * The skrywer's real Gradering-history accomplishments (Tiers audit log),
	 * newest first, capped to 3 — the "Prestasies" rail. Every promotion/
	 * challenge-link is real data; no narrative copy is invented per row (only
	 * already-approved Gradering labels + real dates/challenge titles are used).
	 *
	 * @param int $author_id The skrywer.
	 * @return list<array{label:string, detail:string}>
	 */
	private static function accomplishments( int $author_id ): array {
		if ( ! class_exists( TiersApi::class ) ) {
			return array();
		}

		$rows = array();

		foreach ( array_slice( TiersApi::historyFor( $author_id ), 0, 3 ) as $entry ) {
			$detail = '';

			if ( $entry->isChallengeLinked() && 'publish' === get_post_status( $entry->challengeId ) ) {
				$detail = get_the_title( $entry->challengeId );
			} elseif ( '' !== $entry->createdAt ) {
				$timestamp = strtotime( $entry->createdAt . ' UTC' );
				$detail    = false !== $timestamp ? date_i18n( 'j F Y', $timestamp ) : '';
			}

			$rows[] = array(
				'label'  => Terms::label( $entry->to->value ),
				'detail' => $detail,
			);
		}

		return $rows;
	}

	/**
	 * The first name from a full display name. Pure.
	 *
	 * @param string $name The full display name.
	 * @return string
	 */
	private static function firstName( string $name ): string {
		$parts = preg_split( '/\s+/', trim( $name ) );

		return ( is_array( $parts ) && array() !== $parts ) ? (string) $parts[0] : $name;
	}

	/**
	 * Build the public profile card HTML. Pure — escaping only.
	 *
	 * Renders ONLY public data (name/bio/avatar/gradering/volgeling/volg + the
	 * cover/genres/joined meta, a stats strip, the pinned-works reading-list grid
	 * and accomplishments). It deliberately renders NO read-count and NO
	 * wins-needed subtext — those are private My Profiel surfaces (the FR-40
	 * separation).
	 *
	 * @param array{name:string, tagline?:string, bio:string, avatar:string, badge:string, volgeling:string, volg:string, cover?:string, joined?:string, genres?:list<string>, works?:array{total:int, items:list<array{label:string,count:int}>}, hartjies?:int, pinned?:list<array<string,mixed>>, aggregate?:array{count?:int, average?:float}, reviews?:list<array{user_id:int, score:int, resensie:string}>, accomplishments?:list<array{label:string,detail:string}>, shareUrl?:string} $profile The public profile data.
	 * @return string
	 */
	public static function toHtml( array $profile ): string {
		$name            = isset( $profile['name'] ) ? (string) $profile['name'] : '';
		$tagline         = isset( $profile['tagline'] ) ? trim( (string) $profile['tagline'] ) : '';
		$bio             = isset( $profile['bio'] ) ? (string) $profile['bio'] : '';
		$avatar          = isset( $profile['avatar'] ) ? (string) $profile['avatar'] : '';
		$badge           = isset( $profile['badge'] ) ? (string) $profile['badge'] : '';
		$volgeling       = isset( $profile['volgeling'] ) ? (string) $profile['volgeling'] : '';
		$volg            = isset( $profile['volg'] ) ? (string) $profile['volg'] : '';
		$cover           = isset( $profile['cover'] ) ? (string) $profile['cover'] : '';
		$joined          = isset( $profile['joined'] ) ? (string) $profile['joined'] : '';
		$genres          = isset( $profile['genres'] ) && is_array( $profile['genres'] ) ? $profile['genres'] : array();
		$works           = isset( $profile['works'] ) && is_array( $profile['works'] ) ? $profile['works'] : array(
			'total' => 0,
			'items' => array(),
		);
		$hartjies        = isset( $profile['hartjies'] ) ? (int) $profile['hartjies'] : 0;
		$pinned          = isset( $profile['pinned'] ) && is_array( $profile['pinned'] ) ? $profile['pinned'] : array();
		$aggregate       = isset( $profile['aggregate'] ) && is_array( $profile['aggregate'] ) ? $profile['aggregate'] : array();
		$reviews         = isset( $profile['reviews'] ) && is_array( $profile['reviews'] ) ? $profile['reviews'] : array();
		$accomplishments = isset( $profile['accomplishments'] ) && is_array( $profile['accomplishments'] ) ? $profile['accomplishments'] : array();
		$share_url       = isset( $profile['shareUrl'] ) ? (string) $profile['shareUrl'] : '';
		$first_name      = self::firstName( $name );

		$has_cover = '' !== $cover;

		// Page architecture (fidelity row 06, F1): Writer.tsx's <main> holds sibling
		// <section>s, each either a full-bleed band with a `.container` inside, or a
		// `.container` itself (max 1400px, px-4 — the `ink-container` primitive).
		// author.html's <main> is unconstrained, so these sections span the viewport.
		$html = '<div class="ink-skrywerprofiel' . ( $has_cover ? ' has-omslag' : '' ) . '" data-audit-id="skrywer-profiel">';

		// 1 · Cover + identity (Writer.tsx:183-252).
		$html .= '<section class="ink-skrywerprofiel__kop-seksie" data-audit-id="skrywer-kop">';

		// Cover band — a full-width image with a gradient fade into the page
		// background. Omitted entirely (not an empty placeholder) when the writer
		// has no cover set.
		if ( $has_cover ) {
			$html .= '<div class="ink-skrywerprofiel__omslag" data-audit-id="skrywer-cover">'
				. '<img class="ink-skrywerprofiel__omslag-beeld" data-audit-id="skrywer-cover-image" src="' . esc_url( $cover ) . '" alt="" />'
				. '<div class="ink-skrywerprofiel__omslag-skakering" data-audit-id="skrywer-cover-gradient" aria-hidden="true"></div>'
				. '</div>';
		}

		$html .= '<div class="ink-container" data-audit-id="skrywer-kop-container">';

		// Identity row: avatar | text column | actions column (Writer.tsx:193, F4).
		$html .= '<header class="ink-skrywerprofiel__kop" data-audit-id="skrywer-identity">';

		if ( '' !== $avatar ) {
			// Avatar is core-generated, already-escaped <img> markup. The audit-id
			// (fidelity join key, maps/skrywer.csv) is stamped onto its <img>.
			$avatar_tagged = preg_replace( '/<img\b/', '<img data-audit-id="skrywer-avatar"', $avatar, 1 );
			$html         .= '<div class="ink-skrywerprofiel__foto" data-audit-id="skrywer-avatar-wrap">' . ( null !== $avatar_tagged ? $avatar_tagged : $avatar ) . '</div>';
		}

		$html .= '<div class="ink-skrywerprofiel__identiteit" data-audit-id="skrywer-identity-text">';

		if ( array() !== $genres ) {
			$html .= '<div class="ink-skrywerprofiel__genres" data-audit-id="skrywer-genres">';
			foreach ( array_values( $genres ) as $genre_index => $genre ) {
				// Audit-id on the FIRST pill only (repeater convention, maps/skrywer.csv).
				$html .= '<span class="ink-pill ink-pill--outline ink-skrywerprofiel__genre"' . ( 0 === $genre_index ? ' data-audit-id="skrywer-genre-pill"' : '' ) . '>' . esc_html( (string) $genre ) . '</span>';
			}
			$html .= '</div>';
		}

		$html .= '<h1 class="ink-heading-h1-entity ink-skrywerprofiel__naam" data-audit-id="skrywer-name">' . esc_html( $name ) . '</h1>';

		if ( '' !== $tagline ) {
			$html .= '<p class="ink-skrywerprofiel__leuse" data-audit-id="skrywer-tagline">&#8220;' . esc_html( $tagline ) . '&#8221;</p>';
		}

		if ( '' !== $badge ) {
			// Badge is self-built escaped markup from graderingBadge().
			$html .= '<p class="ink-skrywerprofiel__gradering" data-audit-id="skrywer-gradering">' . $badge . '</p>';
		}

		$html .= '<div class="ink-skrywerprofiel__meta" data-audit-id="skrywer-meta">'
			. '<span class="ink-meta ink-meta--sm ink-skrywerprofiel__volgelinge" data-audit-id="skrywer-meta-followers">' . esc_html( $volgeling ) . '</span>';

		if ( '' !== $joined ) {
			$html .= '<span class="ink-meta ink-meta--sm ink-skrywerprofiel__aangesluit" data-audit-id="skrywer-meta-joined">' . self::icon( self::ICON_CALENDAR_DAYS ) . esc_html( $joined ) . '</span>';
		}

		$html .= '</div>'; // .ink-skrywerprofiel__meta
		$html .= '</div>'; // .ink-skrywerprofiel__identiteit

		$html .= '<div class="ink-skrywerprofiel__aksies" data-audit-id="skrywer-actions">';

		if ( '' !== $volg ) {
			// FollowToggle::render() returns escaped button markup (or '').
			$html .= $volg;
		}

		if ( '' !== $share_url ) {
			// The confirmation string is the ratified toast copy verbatim
			// (`ui-copy-translations.md` "Profile link copied to your clipboard" ->
			// "Profielskakel gekopieër na jou knipbord") — never invented here.
			$html .= '<button type="button" class="ink-btn ink-btn--outline ink-skrywerprofiel__deel" data-audit-id="skrywer-share-btn" data-ink-deel-url="' . esc_url( $share_url ) . '" data-ink-deel-label="' . esc_attr__( 'Deel', 'ink-core' ) . '" data-ink-deel-gekopieer="' . esc_attr__( 'Profielskakel gekopieër na jou knipbord', 'ink-core' ) . '">'
				. self::icon( self::ICON_SHARE ) . '<span class="ink-skrywerprofiel__deel-etiket">' . esc_html__( 'Deel', 'ink-core' ) . '</span></button>';
		}

		$html .= '</div>'; // .ink-skrywerprofiel__aksies
		$html .= '</header>';
		$html .= '</div>'; // .ink-container
		$html .= '</section>';

		// 2 · Stats strip — Lesergradering (rating) / Werke / Hartjies (Writer.tsx:255).
		$rating_count = isset( $aggregate['count'] ) ? (int) $aggregate['count'] : 0;
		if ( $rating_count > 0 || array() !== $works['items'] || $hartjies > 0 ) {
			// A full-bleed cream band (primitives `ink-band--cream` + border-y; SB-3);
			// the container carries the py-6 grid.
			$html .= '<section class="ink-band ink-band--flush ink-band--cream ink-band--border-y ink-skrywerprofiel__statistieke" data-audit-id="skrywer-stats">'
				. '<div class="ink-container ink-skrywerprofiel__statistieke-rooster" data-audit-id="skrywer-stats-grid">';

			if ( $rating_count > 0 ) {
				$average = isset( $aggregate['average'] ) ? (float) $aggregate['average'] : 0.0;
				$html   .= '<div class="ink-skrywerprofiel__stat ink-skrywerprofiel__stat--gradering" data-audit-id="skrywer-stat-rating">'
					. '<span class="ink-stat-label ink-skrywerprofiel__stat-etiket" data-audit-id="skrywer-stat-rating-label">' . esc_html__( 'Lesergradering', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-ry">'
					. '<span class="ink-skrywerprofiel__stat-waarde" data-audit-id="skrywer-rating-value">' . esc_html( number_format_i18n( $average, 1 ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-sterre" data-audit-id="skrywer-rating-stars" aria-hidden="true">' . self::starsHtml( $average ) . '</span>'
					. '</span>'
					. '</div>';
			}

			if ( array() !== $works['items'] ) {
				$breakdown = implode(
					' &middot; ',
					array_map(
						static fn ( array $item ): string => esc_html( number_format_i18n( $item['count'] ) . ' ' . $item['label'] ),
						$works['items']
					)
				);
				$html     .= '<div class="ink-skrywerprofiel__stat" data-audit-id="skrywer-stat-works">'
					. '<span class="ink-stat-label ink-skrywerprofiel__stat-etiket" data-audit-id="skrywer-stat-works-label">' . esc_html__( 'Werke', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-waarde" data-audit-id="skrywer-stat-works-value">' . esc_html( number_format_i18n( $works['total'] ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-detail" data-audit-id="skrywer-stat-works-detail">' . $breakdown . '</span>'
					. '</div>';
			}

			if ( $hartjies > 0 ) {
				$html .= '<div class="ink-skrywerprofiel__stat" data-audit-id="skrywer-stat-likes">'
					. '<span class="ink-stat-label ink-skrywerprofiel__stat-etiket" data-audit-id="skrywer-stat-likes-label">' . esc_html__( 'Hartjies', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-waarde" data-audit-id="skrywer-stat-likes-value">' . esc_html( number_format_i18n( $hartjies ) ) . '</span>'
					. '</div>';
			}

			$html .= '</div></section>';
		}

		// 3 · About + Accomplishments — `section.container grid lg:grid-cols-3`
		// (Writer.tsx:291): bio spans 2 columns, the accomplishments aside 1.
		$html .= '<section class="ink-container ink-skrywerprofiel__oor-prestasies" data-audit-id="skrywer-oor-prestasies-grid">';

		// About — the bio, under an "Oor [naam]" heading (ratified copy sheet).
		if ( '' !== trim( $bio ) ) {
			$html .= '<div class="ink-skrywerprofiel__oor" data-audit-id="skrywer-oor">'
				/* translators: %s: the writer's first name. */
				. '<h2 class="ink-heading-h2-sub ink-skrywerprofiel__oor-titel" data-audit-id="skrywer-oor-title">' . esc_html( sprintf( __( 'Oor %s', 'ink-core' ), $first_name ) ) . '</h2>'
				. '<p class="ink-skrywerprofiel__bio" data-audit-id="skrywer-bio">' . esc_html( $bio ) . '</p>'
				. '</div>';
		}

		// Accomplishments — real Gradering-history rows (Tiers audit log). The
		// heading is Lovable's `h3` eyebrow (Writer.tsx:305, F10).
		if ( array() !== $accomplishments ) {
			$html .= '<aside class="ink-skrywerprofiel__prestasies" data-audit-id="skrywer-prestasies" data-ink-slot="prestasies">'
				. '<h3 class="ink-eyebrow ink-eyebrow--muted ink-skrywerprofiel__prestasies-titel" data-audit-id="skrywer-prestasies-title">' . esc_html__( 'Prestasies', 'ink-core' ) . '</h3>'
				. '<div class="ink-skrywerprofiel__prestasies-lys" data-audit-id="skrywer-prestasies-list">';

			foreach ( array_values( $accomplishments ) as $item_index => $item ) {
				// Audit-ids on the FIRST row only (repeater convention, maps/skrywer.csv).
				$aid   = static fn ( string $role ): string => 0 === $item_index ? ' data-audit-id="skrywer-prestasie' . $role . '"' : '';
				$html .= '<div class="ink-card ink-skrywerprofiel__prestasie"' . $aid( '' ) . '>'
					. '<div class="ink-skrywerprofiel__prestasie-inhoud"' . $aid( '-body' ) . '>'
					. '<span class="ink-icon-tile ink-icon-tile--sm ink-skrywerprofiel__prestasie-ikoon"' . $aid( '-icon' ) . ' aria-hidden="true">' . self::icon( self::ICON_TROPHY ) . '</span>'
					. '<span class="ink-skrywerprofiel__prestasie-teks"' . $aid( '-text' ) . '>'
					. '<span class="ink-skrywerprofiel__prestasie-etiket"' . $aid( '-label' ) . '>' . esc_html( (string) $item['label'] ) . '</span>';

				if ( '' !== (string) $item['detail'] ) {
					$html .= '<span class="ink-skrywerprofiel__prestasie-detail"' . $aid( '-detail' ) . '>' . esc_html( (string) $item['detail'] ) . '</span>';
				}

				$html .= '</span></div></div>';
			}

			$html .= '</div></aside>';
		} else {
			// No Gradering history yet — keep the empty shell (pre-existing behaviour).
			$html .= '<aside class="ink-skrywerprofiel__prestasies" data-audit-id="skrywer-prestasies"><h3 class="ink-eyebrow ink-eyebrow--muted ink-skrywerprofiel__prestasies-titel" data-audit-id="skrywer-prestasies-title">' . esc_html__( 'Prestasies', 'ink-core' ) . '</h3></aside>';
		}

		$html .= '</section>'; // .ink-skrywerprofiel__oor-prestasies

		// 4 · Pinned / selected works — reading-list cards (Story 9.5, ratified
		// "Uitgesoekte werk" copy). Nothing when there are no pins.
		if ( array() !== $pinned ) {
			// A full-bleed cream band + border-t (Writer.tsx:317; SB-3); the
			// container carries the py-12 md:py-16.
			$html .= '<section class="ink-band ink-band--flush ink-band--cream ink-band--border-t ink-skrywerprofiel__vasgespel" data-audit-id="skrywer-werk" data-ink-slot="vasgespelde-werke">'
				. '<div class="ink-container ink-skrywerprofiel__vasgespel-houer" data-audit-id="skrywer-werk-container">'
				. '<div class="ink-skrywerprofiel__vasgespel-kop" data-audit-id="skrywer-werk-head">'
				. '<div class="ink-skrywerprofiel__vasgespel-kop-teks" data-audit-id="skrywer-werk-head-text">'
				. '<p class="ink-eyebrow ink-eyebrow--kicker ink-skrywerprofiel__vasgespel-kicker" data-audit-id="skrywer-werk-kicker">' . self::icon( self::ICON_BOOK_OPEN, 'skrywer-werk-kicker-icon' ) . '<span class="ink-skrywerprofiel__vasgespel-kicker-teks" data-audit-id="skrywer-werk-kicker-text">' . esc_html__( 'Uitgesoekte werk', 'ink-core' ) . '</span></p>'
				/* translators: %s: the writer's first name. */
				. '<h2 class="ink-heading-h2-section ink-skrywerprofiel__vasgespel-titel" data-audit-id="skrywer-werk-title">' . esc_html( sprintf( __( "'n Saamgestelde leeslys, in %s se eie volgorde.", 'ink-core' ), $first_name ) ) . '</h2>'
				. '</div>'
				// Reads the shared label through the Terms registry rather than repeating
				// the literal (house single-source convention); the bare duplicate here had
				// to be corrected by hand when the product owner replaced "werke" with
				// "skrywes" sitewide on 2026-09-05. Hidden below 768px, as in Writer.tsx:331.
				. '<a class="ink-link ink-skrywerprofiel__sien-alle" data-audit-id="skrywer-werk-all-link" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html( Terms::label( 'sien_alle_werke' ) ) . self::icon( self::ICON_ARROW ) . '</a>'
				. '</div>'
				. '<ul class="ink-skrywerprofiel__vasgespel-lys" data-audit-id="skrywer-werk-grid">';

			foreach ( array_values( $pinned ) as $card_index => $card ) {
				// Audit-ids on the FIRST card only (repeater convention, maps/skrywer.csv).
				$aid        = static fn ( string $role ): string => 0 === $card_index ? ' data-audit-id="skrywer-workcard' . $role . '"' : '';
				$type       = (string) $card['type'];
				$excerpt    = isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '';
				$days_ago   = isset( $card['daysAgo'] ) ? (string) $card['daysAgo'] : '';
				$read_time  = isset( $card['readTime'] ) ? (string) $card['readTime'] : '';
				$hartjies_n = isset( $card['hartjies'] ) ? (int) $card['hartjies'] : 0;
				$h_label    = isset( $card['hartjieLabel'] ) ? (string) $card['hartjieLabel'] : '';
				$gemeenskap = isset( $card['gemeenskap'] ) ? (int) $card['gemeenskap'] : 0;

				// The whole card is the link (Writer.tsx:421, F14).
				$html .= '<li class="ink-card ink-skrywerprofiel__vasgespel-item"' . $aid( '' ) . '>'
					. '<a class="ink-skrywerprofiel__vasgespel-skakel"' . $aid( '-link' ) . ' href="' . esc_url( (string) $card['permalink'] ) . '">'
					. '<div class="ink-skrywerprofiel__vasgespel-inhoud"' . $aid( '-body' ) . '>'
					. '<div class="ink-skrywerprofiel__vasgespel-meta"' . $aid( '-meta' ) . '>'
					. '<span class="ink-pill ink-pill--outline ink-skrywerprofiel__vasgespel-tipe"' . $aid( '-type' ) . '>' . self::typeIcon( $type ) . esc_html( Terms::label( $type ) ) . '</span>';

				if ( '' !== $read_time ) {
					$html .= '<span class="ink-meta ink-skrywerprofiel__vasgespel-leestyd"' . $aid( '-time' ) . '>' . self::icon( self::ICON_CLOCK ) . esc_html( $read_time ) . '</span>';
				}

				$html .= '</div>'
					. '<h3 class="ink-heading-h3-card ink-skrywerprofiel__vasgespel-titel-skakel"' . $aid( '-title' ) . '>' . esc_html( (string) $card['title'] ) . '</h3>';

				if ( '' !== $excerpt ) {
					$html .= '<p class="ink-skrywerprofiel__vasgespel-uittreksel"' . $aid( '-excerpt' ) . '>' . esc_html( $excerpt ) . '</p>';
				}

				$html .= '<div class="ink-skrywerprofiel__vasgespel-voet"' . $aid( '-footer' ) . '>';

				if ( '' !== $days_ago ) {
					$html .= '<span class="ink-skrywerprofiel__vasgespel-tyd"' . $aid( '-age' ) . '>' . esc_html( $days_ago ) . '</span>';
				}

				$html .= '<span class="ink-skrywerprofiel__vasgespel-tellings"' . $aid( '-stats' ) . '>'
					. '<span class="ink-skrywerprofiel__vasgespel-telling"' . $aid( '-stat' ) . ' aria-label="' . esc_attr( $h_label ) . '">' . self::icon( self::ICON_HEART ) . esc_html( number_format_i18n( $hartjies_n ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__vasgespel-telling">' . self::icon( self::ICON_MESSAGE ) . esc_html( number_format_i18n( $gemeenskap ) ) . '</span>'
					. '</span>';

				$html .= '</div></div></a></li>';
			}

			$html .= '</ul></div></section>';
		}

		// Lesergradering — reader ratings & reviews (Story 9.6). APPROVED only
		// (public); empty/held until the moderation path (18.4) approves any.
		// INK-only (F19): it sits in its own container section.
		$html .= self::ratingsHtml( $aggregate, $reviews );

		// 5 · Closing follow CTA (ratified "Don't miss …" copy) — `section.container`
		// > `max-w-3xl` centred inner (Writer.tsx:347).
		$html .= '<section class="ink-container ink-skrywerprofiel__cta" data-audit-id="skrywer-cta">'
			. '<div class="ink-skrywerprofiel__cta-binne" data-audit-id="skrywer-cta-inner">'
			/* translators: %s: the writer's first name. */
			. '<h2 class="ink-heading-h2-section ink-skrywerprofiel__cta-titel" data-audit-id="skrywer-cta-title">' . esc_html( sprintf( __( "Moenie %s se volgende stuk misloop nie.", 'ink-core' ), $first_name ) ) . '</h2>'
			. '<p class="ink-skrywerprofiel__cta-teks" data-audit-id="skrywer-cta-text">' . esc_html__( 'Volg om nuwe stories in jou leesvloei te ontvang, en wees die eerste om \'n deurdagte nota te los wanneer hulle publiseer.', 'ink-core' ) . '</p>'
			. '<div class="ink-skrywerprofiel__cta-aksies" data-audit-id="skrywer-cta-actions">';

		if ( '' !== $volg ) {
			// The same FollowToggle markup as the header action; re-keyed here so the
			// CTA instance carries its own fidelity join key (maps/skrywer.csv).
			// The CTA instance is Lovable's `size="lg"` follow button (Writer.tsx:357).
			$html .= str_replace(
				array( 'data-audit-id="skrywer-follow-btn"', 'class="ink-btn ink-btn--' ),
				array( 'data-audit-id="skrywer-cta-follow-btn"', 'class="ink-btn ink-btn--lg ink-btn--' ),
				$volg
			);
		}

		$html .= '<a class="ink-btn ink-btn--lg ink-btn--outline ink-skrywerprofiel__cta-ontdek" data-audit-id="skrywer-cta-discover-btn" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html__( 'Ontdek meer skrywers', 'ink-core' ) . '</a>'
			. '</div></div></section>';

		$html .= '</div>'; // .ink-skrywerprofiel

		return $html;
	}

	/**
	 * Lucide icon path data (Writer.tsx imports), rendered by {@see icon()}.
	 */
	private const ICON_CALENDAR_DAYS = '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/>';
	private const ICON_SHARE         = '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/>';
	private const ICON_BOOK_OPEN     = '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>';
	private const ICON_FEATHER       = '<path d="M12.67 19a2 2 0 0 0 1.416-.588l6.154-6.172a6 6 0 0 0-8.49-8.49L5.586 9.914A2 2 0 0 0 5 11.328V18a1 1 0 0 0 1 1z"/><path d="M16 8 2 22"/><path d="M17.5 15H9"/>';
	private const ICON_SCROLL_TEXT   = '<path d="M15 12h-5"/><path d="M15 8h-5"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M8 21h12a2 2 0 0 0 2-2v-1a1 1 0 0 0-1-1H11a1 1 0 0 0-1 1v1a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v2a1 1 0 0 0 1 1h3"/>';
	private const ICON_CLOCK         = '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>';
	private const ICON_HEART         = '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/>';
	private const ICON_MESSAGE       = '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>';
	private const ICON_ARROW         = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';
	private const ICON_TROPHY        = '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>';
	private const ICON_STAR          = '<path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.01a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z"/>';

	/**
	 * Render a small inline Lucide-style icon. Pure, self-escaping (trusted,
	 * hard-coded path data only — mirrors every other card-rendering class's
	 * own `icon()` helper, e.g. {@see FollowingFeed::icon()}).
	 *
	 * @param string $paths    Trusted inline SVG markup.
	 * @param string $audit_id Optional fidelity join key for the <svg>.
	 * @return string
	 */
	private static function icon( string $paths, string $audit_id = '' ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon"' . ( '' !== $audit_id ? ' data-audit-id="' . esc_attr( $audit_id ) . '"' : '' ) . ' aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}

	/**
	 * The type icon inside a work-card type pill (Writer.tsx:425-427, F17).
	 *
	 * @param string $type A readable bydrae post type.
	 * @return string
	 */
	private static function typeIcon( string $type ): string {
		switch ( $type ) {
			case PostTypes::GEDIG:
				return self::icon( self::ICON_FEATHER );
			case PostTypes::STORIE:
				return self::icon( self::ICON_BOOK_OPEN );
			case PostTypes::ARTIKEL:
				return self::icon( self::ICON_SCROLL_TEXT );
			default:
				return '';
		}
	}

	/**
	 * A decorative filled/half/empty star row for a 0-5 average. Pure,
	 * aria-hidden by the caller (the visible numeric average + review count
	 * carry the a11y text).
	 *
	 * Mirrors the Lovable `Writer.tsx` reference's exact rounding rule
	 * (`ratingStars` memo: `full = Math.floor(rating)`,
	 * `half = rating - full >= 0.5`, `empty = 5 - full - (half ? 1 : 0)`) —
	 * this WAS a flat floor()-then-empty computation with no half-star case at
	 * all, a real rendering-logic gap, not just a missing style.
	 *
	 * @param float $average A 0-5 rating average.
	 * @return string
	 */
	private static function starsHtml( float $average ): string {
		$full  = (int) floor( $average );
		$half  = ( $average - $full ) >= 0.5;
		$empty = max( 0, 5 - $full - ( $half ? 1 : 0 ) );

		// Lucide `Star` svgs (Writer.tsx:266-277, F18): full = filled terracotta,
		// half = terracotta/50 fill, empty = muted outline (colours in skrywer.css).
		$star = self::icon( self::ICON_STAR );
		$out  = str_repeat( '<span class="ink-skrywerprofiel__ster is-vol">' . $star . '</span>', min( $full, 5 ) );

		if ( $half ) {
			$out .= '<span class="ink-skrywerprofiel__ster is-half">' . $star . '</span>';
		}

		$out .= str_repeat( '<span class="ink-skrywerprofiel__ster is-leeg">' . $star . '</span>', $empty );

		return $out;
	}

	/**
	 * The Lesergradering (reader-rating) section. Pure — escaping only.
	 *
	 * Renders the approved aggregate + approved reviews; an empty "nog geen
	 * oordele" state when none are approved. Public = approved only — a held
	 * review is never shown here.
	 *
	 * @param array{count?:int, average?:float}                    $aggregate The approved aggregate.
	 * @param list<array{user_id:int, score:int, resensie:string}> $reviews   The approved reviews.
	 * @return string
	 */
	private static function ratingsHtml( array $aggregate, array $reviews ): string {
		$count = isset( $aggregate['count'] ) ? (int) $aggregate['count'] : 0;

		$html = '<section class="ink-container ink-skrywerprofiel__lesergradering" data-audit-id="skrywer-lesergradering">'
			. '<h2 class="ink-heading-h2-block ink-skrywerprofiel__lesergradering-titel" data-audit-id="skrywer-lesergradering-title">' . esc_html__( 'Lesergradering', 'ink-core' ) . '</h2>';

		if ( $count <= 0 ) {
			$html .= '<p class="ink-skrywerprofiel__lesergradering-leeg" data-audit-id="skrywer-lesergradering-empty">' . esc_html__( 'Nog geen oordele nie.', 'ink-core' ) . '</p></section>';

			return $html;
		}

		$average = isset( $aggregate['average'] ) ? (float) $aggregate['average'] : 0.0;

		$html .= '<p class="ink-skrywerprofiel__lesergradering-gem">'
			. esc_html( number_format_i18n( $average, 1 ) ) . ' &middot; '
			. esc_html( Api::leseroordeelLabel( $count ) )
			. '</p>';

		if ( array() !== $reviews ) {
			$html .= '<ul class="ink-skrywerprofiel__oordele">';

			foreach ( $reviews as $review ) {
				$html .= '<li class="ink-skrywerprofiel__oordeel">'
					. '<span class="ink-skrywerprofiel__oordeel-ster">' . esc_html( (string) (int) $review['score'] ) . '</span>'
					. '<p class="ink-skrywerprofiel__oordeel-teks">' . esc_html( (string) $review['resensie'] ) . '</p>'
					. '</li>';
			}

			$html .= '</ul>';
		}

		$html .= '</section>';

		return $html;
	}
}
