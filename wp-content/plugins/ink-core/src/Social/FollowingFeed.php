<?php
/**
 * Following-feed server block — Story 9.3 (FR-39).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/volg-voer` block: the member's following-feed.
 *
 * New publications by the skrywers the current member follows, newest-first —
 * the profile "Aktiwiteit" tab (Story 9.4 places it on My Profiel). Reads the
 * followed ids through {@see Api::followeeIdsFor()} (the 9.2 facade), never
 * {@see FollowStore} directly. Server-rendered via `WP_Query` (AD-7 — no REST
 * for listings), mirroring the {@see \Ink\Discovery\WorksArchive} house style:
 * pure {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin
 * {@see self::render()}.
 *
 * The decisive correctness concern: a member who follows nobody must see the
 * empty state, NOT everyone's work. An empty `author__in` is silently ignored by
 * `WP_Query` (it would return all posts), so the render gate skips the query when
 * there are no followees and `queryArgs()` defensively yields `author__in =>
 * [0]` (matches nothing) for an empty list.
 *
 * Conflation-clean: references `Ink\Social\Api` (own module) + `Ink\Content`
 * (the readable-types single source) + `Terms` + WP core — zero
 * `Ink\Tiers`/`Ink\Entitlement` (seeing your feed is open to any lid, never
 * entitlement-gated).
 *
 * @package Ink\Core
 */
final class FollowingFeed {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/volg-voer';

	/**
	 * Recent publications shown in the feed.
	 *
	 * @var int
	 */
	public const PER_PAGE = 20;

	/**
	 * The author-avatar pixel size for each activity row (matches the Lovable
	 * `Profile.tsx` activity-feed reference's `w-10 h-10`, i.e. 40px).
	 *
	 * @var int
	 */
	public const AVATAR_SIZE = 40;

	/**
	 * Lucide `arrow-right` icon path data — the card's decorative "Lees"
	 * affordance, matching the exact sitewide convention (mirrors
	 * `Library\Archive`/`Training\Hub`/`Challenges\Archive`'s own `ICON_ARROW`).
	 *
	 * @var string
	 */
	private const ICON_ARROW = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * The "follows nobody yet" render state.
	 *
	 * @var string
	 */
	public const STATE_NO_FOLLOWS = 'geen-volg';

	/**
	 * The populated / "nothing published yet" render state.
	 *
	 * @var string
	 */
	public const STATE_FEED = 'voer';

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
	 * Register the `ink/volg-voer` dynamic block.
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
	 * Build the `WP_Query` args for the following-feed. Pure.
	 *
	 * Constrained to the followed skrywer ids via `author__in`. An empty id list
	 * yields `author__in => [0]` so the query matches NOTHING rather than every
	 * post (an empty `author__in` is silently dropped by `WP_Query`). Callers
	 * should still gate the no-followees case before querying (the empty state).
	 *
	 * @param list<int> $author_ids The followed skrywer ids.
	 * @param int       $per_page   Posts to show.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( array $author_ids, int $per_page ): array {
		$author_ids = array_values( array_filter( array_map( 'intval', $author_ids ) ) );

		return array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'author__in'          => array() === $author_ids ? array( 0 ) : $author_ids,
			'posts_per_page'      => $per_page,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * Block render callback (logged-in members only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$author_ids = Api::followeeIdsFor( get_current_user_id() );

		if ( array() === $author_ids ) {
			return self::toHtml( array(), self::STATE_NO_FOLLOWS );
		}

		$query = new \WP_Query( self::queryArgs( $author_ids, self::PER_PAGE ) );

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$title = get_the_title( $post );

			// Same fixture-leak bug class fixed on every other page this rework —
			// a member's real activity feed must never show a followed writer's
			// seeded QA content.
			if ( QaFixture::isFixtureTitle( $title ) ) {
				continue;
			}

			$post_id    = (int) $post->ID;
			$author_id  = (int) $post->post_author;
			$engagement = WorkCardFacts::engagement( $post_id );

			$cards[] = array(
				'title'        => $title,
				'permalink'    => (string) get_permalink( $post ),
				'type'         => $post->post_type,
				'author'       => (string) get_the_author_meta( 'display_name', $author_id ),
				'authorUrl'    => (string) get_author_posts_url( $author_id ),
				'authorAvatar' => function_exists( 'get_avatar' )
					? (string) get_avatar( $author_id, self::AVATAR_SIZE, '', '', array( 'class' => 'ink-volg-voer__avatar' ) )
					: '',
				'excerpt'      => (string) get_the_excerpt( $post ),
				'daysAgo'      => WorkCardFacts::daysAgoLabelForPost( $post_id ),
				'hartjies'     => $engagement['hartjies'],
				'hartjieLabel' => $engagement['hartjieLabel'],
				'gemeenskap'   => $engagement['gemeenskap'],
			);
		}

		return self::toHtml( $cards, self::STATE_FEED );
	}

	/**
	 * Build the feed HTML. Pure — Terms + escaping only.
	 *
	 * Each card renders the richer Lovable `Profile.tsx` activity-row shape:
	 * author avatar + name (linked to their public profile), a "published a new
	 * [type] · [N]d ago" action line, the title (linked), an italic excerpt, the
	 * hartjie/gemeenskap counts, and a decorative "Lees" affordance — the exact
	 * link-duplicates-the-title / `aria-hidden`+`tabindex="-1"` convention every
	 * other archive card in this codebase already uses (see
	 * {@see \Ink\Library\Archive::cardHtml()}), so a screen-reader/keyboard user
	 * still meets only the ONE real, focusable path to the work (the title link).
	 *
	 * @param list<array{title:string, permalink:string, type:string, author:string, authorUrl?:string, authorAvatar?:string, excerpt?:string, daysAgo?:string, hartjies?:int, hartjieLabel?:string, gemeenskap?:int}> $cards The works.
	 * @param string                                                                                                                                                                                                   $state One of STATE_NO_FOLLOWS / STATE_FEED.
	 * @return string
	 */
	public static function toHtml( array $cards, string $state ): string {
		// Heading + empty-state copy are human-authored, approved Afrikaans from
		// ui-copy-translations.md (My Profiel — Volg/Aktiwiteit, lines 751/753/755).
		// Zero AI Afrikaans.
		$heading = '<h2 class="ink-volg-voer__heading">' . esc_html__( 'Aktiwiteit van wie jy volg', 'ink-core' ) . '</h2>';

		if ( self::STATE_NO_FOLLOWS === $state ) {
			$msg = __( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien.", 'ink-core' );

			return '<section class="ink-volg-voer">' . $heading
				. '<p class="ink-volg-voer__leeg">' . esc_html( $msg ) . '</p></section>';
		}

		if ( array() === $cards ) {
			$msg = __( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.', 'ink-core' );

			return '<section class="ink-volg-voer">' . $heading
				. '<p class="ink-volg-voer__leeg">' . esc_html( $msg ) . '</p></section>';
		}

		$html = '<section class="ink-volg-voer">' . $heading . '<ul class="ink-volg-voer__list">';

		foreach ( $cards as $card ) {
			$html .= self::cardHtml( $card );
		}

		$html .= '</ul></section>';

		return $html;
	}

	/**
	 * One activity-feed card. Pure — Terms + escaping only.
	 *
	 * @param array{title:string, permalink:string, type:string, author:string, authorUrl?:string, authorAvatar?:string, excerpt?:string, daysAgo?:string, hartjies?:int, hartjieLabel?:string, gemeenskap?:int} $card The work.
	 * @return string
	 */
	private static function cardHtml( array $card ): string {
		$author_url = isset( $card['authorUrl'] ) ? (string) $card['authorUrl'] : '';
		$avatar     = isset( $card['authorAvatar'] ) ? (string) $card['authorAvatar'] : '';
		$excerpt    = isset( $card['excerpt'] ) ? (string) $card['excerpt'] : '';
		$days_ago   = isset( $card['daysAgo'] ) ? (string) $card['daysAgo'] : '';
		$hartjies   = isset( $card['hartjies'] ) ? (int) $card['hartjies'] : 0;
		$h_label    = isset( $card['hartjieLabel'] ) ? (string) $card['hartjieLabel'] : '';
		$gemeenskap = isset( $card['gemeenskap'] ) ? (int) $card['gemeenskap'] : 0;

		$html = '<li class="ink-volg-voer__item">';

		// Kop: author avatar + name (linked to their public profile) + the
		// "published a new [type] · [N]d ago" action line.
		$html .= '<div class="ink-volg-voer__kop">';

		$author_html = '';
		if ( '' !== $avatar ) {
			// Avatar is core-generated, already-escaped <img> markup.
			$author_html .= $avatar;
		}
		$author_html .= '<span class="ink-volg-voer__outeur-naam">' . esc_html( $card['author'] ) . '</span>';

		if ( '' !== $author_url ) {
			$html .= '<a class="ink-volg-voer__outeur-skakel" href="' . esc_url( $author_url ) . '">' . $author_html . '</a>';
		} else {
			$html .= '<span class="ink-volg-voer__outeur-skakel">' . $author_html . '</span>';
		}

		// The connecting "published a new [type]" action-phrase has no ratified
		// Afrikaans yet (no Lovable-sheet equivalent — a brand-new microcopy
		// line) — flagged per the standard [[afrikaans-copy-debt-process]]
		// rather than invented; see docs/afrikaans-translation-sheet.md
		// VOLG-VOER-AKSIE / docs/afrikaans-copy-worklist.md. The type label and
		// days-ago timing either side of it ARE already-ratified/established
		// sources (Terms::label() / WorkCardFacts::daysAgoLabel()), not new copy.
		$html .= '<span class="ink-volg-voer__aksie">'
			. esc_html__( '[NEEDS HUMAN AFRIKAANS] — Aktiwiteitsvoer se "published a new [type]" aksiefrase nog nie outeur in ui-copy-translations.md nie.', 'ink-core' )
			. ' <span class="ink-volg-voer__tipe">' . esc_html( Terms::label( $card['type'] ) ) . '</span>';

		if ( '' !== $days_ago ) {
			$html .= ' &middot; <span class="ink-volg-voer__tyd">' . esc_html( $days_ago ) . '</span>';
		}

		$html .= '</span>';
		$html .= '</div>'; // .ink-volg-voer__kop

		$html .= '<a class="ink-volg-voer__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>';

		if ( '' !== $excerpt ) {
			$html .= '<p class="ink-volg-voer__uittreksel">' . esc_html( $excerpt ) . '</p>';
		}

		$html .= '<div class="ink-volg-voer__voet">'
			. '<span class="ink-volg-voer__tellings">'
			. '<span class="ink-volg-voer__telling" aria-label="' . esc_attr( $h_label ) . '"><span aria-hidden="true">&#9825;</span> ' . esc_html( number_format_i18n( $hartjies ) ) . '</span>'
			. '<span class="ink-volg-voer__telling"><span aria-hidden="true">&#128172;</span> ' . esc_html( number_format_i18n( $gemeenskap ) ) . '</span>'
			. '</span>'
			. '<a class="ink-volg-voer__lees" href="' . esc_url( $card['permalink'] ) . '" tabindex="-1" aria-hidden="true">'
			. esc_html__( 'Lees', 'ink-core' ) . self::icon( self::ICON_ARROW )
			. '</a>'
			. '</div>';

		$html .= '</li>';

		return $html;
	}

	/**
	 * Render a small inline Lucide-style icon. Pure, self-escaping (trusted,
	 * hard-coded path data only — mirrors every other card-rendering class's
	 * own `icon()` helper, e.g. {@see \Ink\Library\Archive::icon()}).
	 *
	 * @param string $paths Trusted inline SVG `<path>` markup.
	 * @return string
	 */
	private static function icon( string $paths ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" '
			. 'fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}
}
