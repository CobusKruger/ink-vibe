<?php
/**
 * Public Skrywerprofiel server block — Story 9.4 (FR-40).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
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
			'bio'             => (string) get_the_author_meta( 'description', $author_id ),
			'avatar'          => function_exists( 'get_avatar' ) ? (string) get_avatar( $author_id, 160 ) : '',
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
	 * The "[N] dae/dag gelede" timestamp label for a work. Pure formatting.
	 *
	 * @param int $timestamp_gmt A GMT unix timestamp.
	 * @return string
	 */
	private static function daysAgoLabel( int $timestamp_gmt ): string {
		$days = max( 0, (int) floor( ( time() - $timestamp_gmt ) / DAY_IN_SECONDS ) );

		/* translators: %s: the number of days since publication. */
		$format = _n( '%s dag gelede', '%s dae gelede', $days, 'ink-core' );

		return sprintf( $format, number_format_i18n( $days ) );
	}

	/**
	 * The queried author's pinned works, resolved to reading-list cards (Story 9.5).
	 *
	 * Reads {@see PinnedWorks::forUser()} (in pin = display order) and resolves
	 * each id to a card, skipping any that is no longer a published bydrae (a
	 * stale pin never renders a broken card). Each card carries real per-post
	 * data only — excerpt, publish-age, hartjie/gemeenskapsreaksie counts.
	 *
	 * @param int $author_id The skrywer.
	 * @return list<array{title:string, permalink:string, type:string, excerpt:string, daysAgo:string, hartjies:int, hartjieLabel:string, gemeenskap:int}>
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

			$timestamp = get_post_time( 'U', true, $post_id );
			$hartjies  = class_exists( EngagementApi::class ) ? EngagementApi::hartjieCountForPost( $post_id ) : 0;

			$cards[] = array(
				'title'        => get_the_title( $post_id ),
				'permalink'    => (string) get_permalink( $post_id ),
				'type'         => (string) get_post_type( $post_id ),
				'excerpt'      => (string) get_the_excerpt( $post_id ),
				'daysAgo'      => is_int( $timestamp ) ? self::daysAgoLabel( $timestamp ) : '',
				'hartjies'     => $hartjies,
				'hartjieLabel' => class_exists( EngagementApi::class ) ? EngagementApi::hartjieCountLabel( $hartjies ) : '',
				'gemeenskap'   => class_exists( EngagementApi::class ) ? EngagementApi::responseCountForPost( $post_id ) : 0,
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
	 * @param array{name:string, bio:string, avatar:string, badge:string, volgeling:string, volg:string, cover?:string, joined?:string, genres?:list<string>, works?:array{total:int, items:list<array{label:string,count:int}>}, hartjies?:int, pinned?:list<array<string,mixed>>, aggregate?:array{count?:int, average?:float}, reviews?:list<array{user_id:int, score:int, resensie:string}>, accomplishments?:list<array{label:string,detail:string}>, shareUrl?:string} $profile The public profile data.
	 * @return string
	 */
	public static function toHtml( array $profile ): string {
		$name            = isset( $profile['name'] ) ? (string) $profile['name'] : '';
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

		$html = '<section class="ink-skrywerprofiel' . ( $has_cover ? ' has-omslag' : '' ) . '">';

		// Cover banner (Phase-2 fidelity pass) — a wide image with a gradient fade
		// into the page background, matching the Lovable `Writer.tsx` reference.
		// Omitted entirely (not an empty placeholder) when the writer has no cover set.
		if ( $has_cover ) {
			$html .= '<div class="ink-skrywerprofiel__omslag">'
				. '<img class="ink-skrywerprofiel__omslag-beeld" src="' . esc_url( $cover ) . '" alt="" />'
				. '<div class="ink-skrywerprofiel__omslag-skakering" aria-hidden="true"></div>'
				. '</div>';
		}

		// Header: avatar + genre pills + name + gradering + joined + volgelinge + actions.
		$html .= '<header class="ink-skrywerprofiel__kop">';

		if ( '' !== $avatar ) {
			// Avatar is core-generated, already-escaped <img> markup.
			$html .= '<div class="ink-skrywerprofiel__foto">' . $avatar . '</div>';
		}

		$html .= '<div class="ink-skrywerprofiel__identiteit">';

		if ( array() !== $genres ) {
			$html .= '<div class="ink-skrywerprofiel__genres" data-audit-id="skrywer-genres">';
			foreach ( $genres as $genre ) {
				$html .= '<span class="ink-skrywerprofiel__genre" data-audit-id="skrywer-genre-pill">' . esc_html( (string) $genre ) . '</span>';
			}
			$html .= '</div>';
		}

		$html .= '<h1 class="ink-skrywerprofiel__naam" data-audit-id="skrywer-name">' . esc_html( $name ) . '</h1>';

		if ( '' !== $badge ) {
			// Badge is self-built escaped markup from graderingBadge().
			$html .= '<p class="ink-skrywerprofiel__gradering">' . $badge . '</p>';
		}

		$html .= '<div class="ink-skrywerprofiel__meta">'
			. '<span class="ink-skrywerprofiel__volgelinge">' . esc_html( $volgeling ) . '</span>';

		if ( '' !== $joined ) {
			$html .= '<span class="ink-skrywerprofiel__aangesluit">' . esc_html( $joined ) . '</span>';
		}

		$html .= '</div>';

		$html .= '<div class="ink-skrywerprofiel__aksies">';

		if ( '' !== $volg ) {
			// FollowToggle::render() returns escaped button markup (or '').
			$html .= $volg;
		}

		if ( '' !== $share_url ) {
			// The confirmation string is the ratified toast copy verbatim
			// (`ui-copy-translations.md` "Profile link copied to your clipboard" ->
			// "Profielskakel gekopieër na jou knipbord") — never invented here.
			$html .= '<button type="button" class="ink-skrywerprofiel__deel" data-audit-id="skrywer-share-btn" data-ink-deel-url="' . esc_url( $share_url ) . '" data-ink-deel-label="' . esc_attr__( 'Deel', 'ink-core' ) . '" data-ink-deel-gekopieer="' . esc_attr__( 'Profielskakel gekopieër na jou knipbord', 'ink-core' ) . '">'
				. esc_html__( 'Deel', 'ink-core' ) . '</button>';
		}

		$html .= '</div>'; // .ink-skrywerprofiel__aksies
		$html .= '</div>'; // .ink-skrywerprofiel__identiteit
		$html .= '</header>';

		// Stats strip — Lesergradering (rating) / Werke / Volgelinge / Hartjies.
		$rating_count = isset( $aggregate['count'] ) ? (int) $aggregate['count'] : 0;
		if ( $rating_count > 0 || array() !== $works['items'] || $hartjies > 0 ) {
			$html .= '<div class="ink-skrywerprofiel__statistieke">';

			if ( $rating_count > 0 ) {
				$average = isset( $aggregate['average'] ) ? (float) $aggregate['average'] : 0.0;
				$html   .= '<div class="ink-skrywerprofiel__stat" data-audit-id="skrywer-stat-rating">'
					. '<span class="ink-skrywerprofiel__stat-etiket">' . esc_html__( 'Lesergradering', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-waarde" data-audit-id="skrywer-rating-value">' . esc_html( number_format_i18n( $average, 1 ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-sterre" data-audit-id="skrywer-rating-stars" aria-hidden="true">' . self::starsHtml( $average ) . '</span>'
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
					. '<span class="ink-skrywerprofiel__stat-etiket">' . esc_html__( 'Werke', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-waarde">' . esc_html( number_format_i18n( $works['total'] ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-detail">' . $breakdown . '</span>'
					. '</div>';
			}

			if ( $hartjies > 0 ) {
				$html .= '<div class="ink-skrywerprofiel__stat">'
					. '<span class="ink-skrywerprofiel__stat-etiket">' . esc_html__( 'Hartjies', 'ink-core' ) . '</span>'
					. '<span class="ink-skrywerprofiel__stat-waarde">' . esc_html( number_format_i18n( $hartjies ) ) . '</span>'
					. '</div>';
			}

			$html .= '</div>';
		}

		// About + Accomplishments — a shared 2/1 grid on desktop (Lovable's
		// `grid lg:grid-cols-3`: bio takes 2 cols, the accomplishments rail 1)
		// rather than two independently-stacked full-width blocks.
		$html .= '<div class="ink-skrywerprofiel__oor-prestasies" data-audit-id="skrywer-oor-prestasies-grid">';

		// About — the bio, under an "Oor [naam]" heading (ratified copy sheet).
		if ( '' !== trim( $bio ) ) {
			$html .= '<div class="ink-skrywerprofiel__oor">'
				/* translators: %s: the writer's first name. */
				. '<h2 class="ink-skrywerprofiel__oor-titel">' . esc_html( sprintf( __( 'Oor %s', 'ink-core' ), $first_name ) ) . '</h2>'
				. '<p class="ink-skrywerprofiel__bio" data-audit-id="skrywer-bio">' . esc_html( $bio ) . '</p>'
				. '</div>';
		}

		// Accomplishments — real Gradering-history rows (Tiers audit log).
		if ( array() !== $accomplishments ) {
			$html .= '<div class="ink-skrywerprofiel__prestasies" data-audit-id="skrywer-prestasies" data-ink-slot="prestasies">'
				. '<h2 class="ink-skrywerprofiel__prestasies-titel">' . esc_html__( 'Prestasies', 'ink-core' ) . '</h2>'
				. '<div class="ink-skrywerprofiel__prestasies-lys">';

			foreach ( $accomplishments as $item ) {
				$html .= '<div class="ink-skrywerprofiel__prestasie">'
					. '<span class="ink-skrywerprofiel__prestasie-ikoon" aria-hidden="true">&#9733;</span>'
					. '<span class="ink-skrywerprofiel__prestasie-teks">'
					. '<span class="ink-skrywerprofiel__prestasie-etiket">' . esc_html( (string) $item['label'] ) . '</span>';

				if ( '' !== (string) $item['detail'] ) {
					$html .= '<span class="ink-skrywerprofiel__prestasie-detail">' . esc_html( (string) $item['detail'] ) . '</span>';
				}

				$html .= '</span></div>';
			}

			$html .= '</div></div>';
		} else {
			// No Gradering history yet — keep the empty shell (pre-existing behaviour).
			$html .= '<div class="ink-skrywerprofiel__prestasies" data-audit-id="skrywer-prestasies"><h2 class="ink-skrywerprofiel__prestasies-titel">' . esc_html__( 'Prestasies', 'ink-core' ) . '</h2></div>';
		}

		$html .= '</div>'; // .ink-skrywerprofiel__oor-prestasies

		// Pinned / selected works — reading-list cards (Story 9.5, ratified "Uitgesoekte
		// werk" copy). Heading only when there is at least one pin; nothing when empty.
		if ( array() !== $pinned ) {
			$html .= '<div class="ink-skrywerprofiel__vasgespel" data-ink-slot="vasgespelde-werke">'
				. '<p class="ink-skrywerprofiel__vasgespel-kicker">' . esc_html__( 'Uitgesoekte werk', 'ink-core' ) . '</p>'
				/* translators: %s: the writer's first name. */
				. '<h2 class="ink-skrywerprofiel__vasgespel-titel">' . esc_html( sprintf( __( "'n Saamgestelde leeslys, in %s se eie volgorde.", 'ink-core' ), $first_name ) ) . '</h2>'
				. '<ul class="ink-skrywerprofiel__vasgespel-lys">';

			foreach ( $pinned as $card ) {
				$excerpt    = isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '';
				$days_ago   = isset( $card['daysAgo'] ) ? (string) $card['daysAgo'] : '';
				$hartjies_n = isset( $card['hartjies'] ) ? (int) $card['hartjies'] : 0;
				$h_label    = isset( $card['hartjieLabel'] ) ? (string) $card['hartjieLabel'] : '';
				$gemeenskap = isset( $card['gemeenskap'] ) ? (int) $card['gemeenskap'] : 0;

				$html .= '<li class="ink-skrywerprofiel__vasgespel-item is-style-card" data-audit-id="skrywer-workcard">'
					. '<span class="ink-skrywerprofiel__vasgespel-tipe">' . esc_html( Terms::label( (string) $card['type'] ) ) . '</span>'
					. '<a class="ink-skrywerprofiel__vasgespel-titel-skakel" data-audit-id="skrywer-workcard-title" href="' . esc_url( (string) $card['permalink'] ) . '">' . esc_html( (string) $card['title'] ) . '</a>';

				if ( '' !== $excerpt ) {
					$html .= '<p class="ink-skrywerprofiel__vasgespel-uittreksel">' . esc_html( $excerpt ) . '</p>';
				}

				$html .= '<div class="ink-skrywerprofiel__vasgespel-voet">';

				if ( '' !== $days_ago ) {
					$html .= '<span class="ink-skrywerprofiel__vasgespel-tyd">' . esc_html( $days_ago ) . '</span>';
				}

				$html .= '<span class="ink-skrywerprofiel__vasgespel-tellings">'
					. '<span class="ink-skrywerprofiel__vasgespel-telling" aria-label="' . esc_attr( $h_label ) . '"><span aria-hidden="true">&#9825;</span> ' . esc_html( number_format_i18n( $hartjies_n ) ) . '</span>'
					. '<span class="ink-skrywerprofiel__vasgespel-telling"><span aria-hidden="true">&#128172;</span> ' . esc_html( number_format_i18n( $gemeenskap ) ) . '</span>'
					. '</span>';

				$html .= '</div></li>';
			}

			$html .= '</ul>';

			// Reads the shared label through the Terms registry rather than repeating
			// the literal (house single-source convention); the bare duplicate here had
			// to be corrected by hand when the product owner replaced "werke" with
			// "skrywes" sitewide on 2026-09-05.
			$html .= '<a class="ink-skrywerprofiel__sien-alle" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html( Terms::label( 'sien_alle_werke' ) ) . '</a>';

			$html .= '</div>';
		}

		// Lesergradering — reader ratings & reviews (Story 9.6). APPROVED only
		// (public); empty/held until the moderation path (18.4) approves any.
		$html .= self::ratingsHtml( $aggregate, $reviews );

		// Closing follow CTA (ratified "Don't miss …" copy) — shown whenever a
		// visitor could plausibly follow (a Volg toggle rendered) or, at minimum,
		// discover more writers.
		$html .= '<div class="ink-skrywerprofiel__cta">'
			/* translators: %s: the writer's first name. */
			. '<h2 class="ink-skrywerprofiel__cta-titel">' . esc_html( sprintf( __( "Moenie %s se volgende stuk misloop nie.", 'ink-core' ), $first_name ) ) . '</h2>'
			. '<p class="ink-skrywerprofiel__cta-teks">' . esc_html__( 'Volg om nuwe stories in jou leesvloei te ontvang, en wees die eerste om \'n deurdagte nota te los wanneer hulle publiseer.', 'ink-core' ) . '</p>'
			. '<div class="ink-skrywerprofiel__cta-aksies">';

		if ( '' !== $volg ) {
			$html .= $volg;
		}

		$html .= '<a class="ink-skrywerprofiel__cta-ontdek" href="' . esc_url( home_url( '/ontdek/' ) ) . '">' . esc_html__( 'Ontdek meer skrywers', 'ink-core' ) . '</a>'
			. '</div></div>';

		$html .= '</section>';

		return $html;
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

		$out = str_repeat( '<span class="ink-skrywerprofiel__ster is-vol">&#9733;</span>', min( $full, 5 ) );

		if ( $half ) {
			$out .= '<span class="ink-skrywerprofiel__ster is-half"><span class="ink-skrywerprofiel__ster-agter">&#9734;</span><span class="ink-skrywerprofiel__ster-voor">&#9733;</span></span>';
		}

		$out .= str_repeat( '<span class="ink-skrywerprofiel__ster is-leeg">&#9734;</span>', $empty );

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

		$html = '<section class="ink-skrywerprofiel__lesergradering">'
			. '<h2 class="ink-skrywerprofiel__lesergradering-titel">' . esc_html__( 'Lesergradering', 'ink-core' ) . '</h2>';

		if ( $count <= 0 ) {
			$html .= '<p class="ink-skrywerprofiel__lesergradering-leeg">' . esc_html__( 'Nog geen oordele nie.', 'ink-core' ) . '</p></section>';

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
