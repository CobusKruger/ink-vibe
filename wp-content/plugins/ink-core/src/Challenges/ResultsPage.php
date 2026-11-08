<?php
/**
 * The per-round results page — WP1 (N1; `01-uitslae`).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/uitslae` block: one server-rendered part per section of Lovable's
 * `ChallengeResults.tsx`, through the `deel` attribute. Each part owns its band, so a
 * section with nothing to show leaves no empty band. Thin {@see render()} over the pure
 * `*Html()` methods; {@see RoundResults} is the only data path.
 *
 * Tabs stay as designed (owner, 2026-11-01): all three panels are in the HTML, the inactive
 * two `hidden`, and `assets/js/uitslae-tabs.js` swaps them.
 *
 * DIVERGENCE (accepted, `01-uitslae` §10):
 *  - the pill says "algehele wenner" / "wenner" (D11), verbatim from Terms; the stored
 *    position still picks its tone;
 *  - a citation shows only with the author's opt-in (D6), so the block can be absent.
 * Audit ids follow `maps/uitslae.csv`; a tied position stamps its id on the first row only.
 *
 * @package Ink\Core
 */
final class ResultsPage {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitslae';

	/**
	 * The hero part.
	 *
	 * @var string
	 */
	public const PART_HERO = 'kop';

	/**
	 * The judges' letter part.
	 *
	 * @var string
	 */
	public const PART_LETTER = 'brief';

	/**
	 * The podium part.
	 *
	 * @var string
	 */
	public const PART_PODIUM = 'podium';

	/**
	 * The honourable mentions part.
	 *
	 * @var string
	 */
	public const PART_MENTIONS = 'vermeldings';

	/**
	 * The tier slots in display order: tier => audit slot.
	 *
	 * @var array<string, string>
	 */
	private const TIER_SLOTS = array(
		'goud'   => 'gold',
		'silwer' => 'silver',
		'brons'  => 'bronze',
	);

	/**
	 * The tabs in display order: category => [audit id suffix, Terms key].
	 *
	 * @var array<string, array{0:string, 1:string}>
	 */
	private const TABS = array(
		PostTypes::GEDIG   => array( 'poetry', 'gedig_plural' ),
		PostTypes::STORIE  => array( 'short-story', 'storie_plural' ),
		PostTypes::ARTIKEL => array( 'article', 'artikel_plural' ),
	);

	// --- Lucide inner-SVG paths. ---

	private const ICON_CROWN = '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>';
	private const ICON_QUOTE = '<path d="M16 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/><path d="M5 3a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2 1 1 0 0 1 1 1v1a2 2 0 0 1-2 2 1 1 0 0 0-1 1v2a1 1 0 0 0 1 1 6 6 0 0 0 6-6V5a2 2 0 0 0-2-2z"/>';
	private const ICON_AWARD = '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>';
	private const ICON_FEATHER = '<path d="M12.67 19a2 2 0 0 0 1.416-.588l6.154-6.172a6 6 0 0 0-8.49-8.49L5.586 9.914A2 2 0 0 0 5 11.328V18a1 1 0 0 0 1 1z"/><path d="M16 8 2 22"/><path d="M17.5 15H9"/>';
	private const ICON_ARROW ='<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * Register the block. Runs inside the module's `init` dispatch.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
				'attributes'      => array(
					'deel' => array(
						'type'    => 'string',
						'default' => self::PART_HERO,
					),
				),
			)
		);
	}

	/**
	 * Block render callback.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		$uitdaging_id = (int) get_queried_object_id();

		if ( ! ResultsRoute::isResultsView() || ! RoundResults::hasResults( $uitdaging_id ) ) {
			return '';
		}

		switch ( (string) ( $attributes['deel'] ?? self::PART_HERO ) ) {
			case self::PART_LETTER:
				return self::letterHtml( RoundResults::letterFor( $uitdaging_id ) );

			case self::PART_PODIUM:
				return self::podiumHtml( RoundResults::forRound( $uitdaging_id ) );

			case self::PART_MENTIONS:
				$current = CurrentChallenge::current();

				return self::mentionsHtml(
					RoundResults::mentionsFor( $uitdaging_id ),
					is_array( $current ) && ! empty( $current['is_open'] ) ? (string) ( $current['url'] ?? '' ) : ''
				);

			default:
				return self::heroHtml(
					RoundResults::periodFor( $uitdaging_id ),
					RoundResults::themeFor( $uitdaging_id ),
					RoundResults::announcedOn( $uitdaging_id ),
					SinglePage::entryCount( $uitdaging_id )
				);
		}
	}

	/**
	 * The hero. Pure.
	 *
	 * @param string $period    The round's period, e.g. "Oktober 2026".
	 * @param string $theme     The round's theme.
	 * @param string $announced The announced date, or ''.
	 * @param int    $entries   The entry count.
	 * @return string
	 */
	public static function heroHtml( string $period, string $theme, string $announced, int $entries ): string {
		/* translators: %s: the round's period, e.g. "Oktober 2026". */
		$period_label = sprintf( __( '%s-uitdaging', 'ink-core' ), $period );
		$eyebrow      = __( 'Uitslae', 'ink-core' ) . ( '' !== $period ? ' · ' . $period_label : '' );

		$meta = array();

		if ( '' !== $announced ) {
			/* translators: %s: the date the results were announced. */
			$meta[] = sprintf( __( 'Aangekondig %s', 'ink-core' ), $announced );
		}

		/* translators: %d: the number of entries (inskrywings) submitted to the challenge. */
		$meta[] = sprintf( _n( '%d inskrywing', '%d inskrywings', $entries, 'ink-core' ), $entries );
		$meta[] = __( 'Gedigte, stories en artikels', 'ink-core' );

		return '<section class="ink-band ink-band--step ink-band--intro alignfull" data-audit-id="results-hero">'
			. '<div class="ink-container ink-uitslae__kolom ink-uitslae__kolom--3xl" data-audit-id="results-hero-inner">'
			. '<div class="ink-eyebrow ink-eyebrow--card ink-eyebrow--gold ink-uitslae__boskrif" data-audit-id="results-hero-eyebrow">'
			. self::icon( self::ICON_CROWN, 'results-hero-eyebrow-icon' )
			. esc_html( $eyebrow )
			. '</div>'
			. '<h1 class="ink-heading-h1-index ink-uitslae__titel" data-audit-id="results-hero-h1">“' . esc_html( $theme ) . '” — ' . esc_html__( 'Uitslae', 'ink-core' ) . '</h1>'
			. '<p class="ink-uitslae__meta" data-audit-id="results-hero-meta">' . esc_html( implode( ' · ', $meta ) ) . '</p>'
			. '</div></section>';
	}

	/**
	 * The judges' letter; '' with no letter. Pure.
	 *
	 * @param list<string> $paragraphs The letter's paragraphs.
	 * @return string
	 */
	public static function letterHtml( array $paragraphs ): string {
		if ( array() === $paragraphs ) {
			return '';
		}

		$html = '';

		foreach ( $paragraphs as $index => $paragraph ) {
			// Lovable's letter has two paragraphs, so a longer letter's rest carries no id.
			$aid   = $index < 2 ? ' data-audit-id="results-letter-p-' . (int) $index . '"' : '';
			$html .= '<p class="ink-uitslae__brief-teks"' . $aid . '>' . esc_html( $paragraph ) . '</p>';
		}

		return '<section class="ink-band ink-band--step alignfull" data-audit-id="results-letter">'
			. '<div class="ink-container ink-uitslae__kolom--2xl" data-audit-id="results-letter-inner">'
			. self::icon( self::ICON_QUOTE, 'results-letter-icon', 'ink-uitslae__brief-ikoon' )
			. '<h2 class="ink-heading-h2-section ink-uitslae__brief-titel" data-audit-id="results-letter-h2">' . esc_html__( '\'n Nota van die moderator', 'ink-core' ) . '</h2>'
			. $html
			. '<p class="ink-uitslae__brief-nota" data-audit-id="results-letter-note">' . esc_html__( 'Alle inskrywings word anoniem beoordeel. Die moderator sien nie skrywers se name nie.', 'ink-core' ) . '</p>'
			. '</div></section>';
	}

	/**
	 * The podium: heading, tabs and the three panels. All three tabs always show. Pure.
	 *
	 * @param list<array<string, mixed>> $rows The round's rows.
	 * @return string
	 */
	public static function podiumHtml( array $rows ): string {
		$tabs   = '';
		$panels = '';
		$first  = true;

		foreach ( self::TABS as $category => $tab ) {
			list( $suffix, $term ) = $tab;

			$list = array_values( array_filter( $rows, static fn ( array $row ): bool => (string) ( $row['category'] ?? '' ) === (string) $category ) );

			$tabs .= '<button type="button" class="ink-segmented__tab" role="tab" id="ink-uitslae-tab-' . esc_attr( $suffix ) . '"'
				. ' aria-controls="ink-uitslae-paneel-' . esc_attr( $suffix ) . '" aria-selected="' . ( $first ? 'true' : 'false' ) . '"'
				. ' data-audit-id="results-tab-' . esc_attr( $suffix ) . '">' . esc_html( Terms::label( $term ) ) . '</button>';

			$panels .= '<div role="tabpanel" id="ink-uitslae-paneel-' . esc_attr( $suffix ) . '" aria-labelledby="ink-uitslae-tab-' . esc_attr( $suffix ) . '"' . ( $first ? '' : ' hidden' ) . '>'
				. ( PostTypes::GEDIG === $category ? self::tiersHtml( $list ) : self::podiumListHtml( $list, $suffix ) )
				. '</div>';

			$first = false;
		}

		return '<section class="ink-band ink-band--step ink-band--border-t alignfull" data-audit-id="results-podium">'
			. '<div class="ink-container ink-uitslae__kolom--5xl" data-audit-id="results-podium-inner">'
			. '<div class="ink-uitslae__podium-kop" data-audit-id="results-podium-head">'
			. '<h2 class="ink-heading-h2-section" data-audit-id="results-podium-h2">' . esc_html__( 'Die podium', 'ink-core' ) . '</h2>'
			. '<div class="ink-segmented ink-segmented--pill" role="tablist" data-audit-id="results-tabs">' . $tabs . '</div>'
			. '</div>'
			. $panels
			. '</div></section>';
	}

	/**
	 * The Gedigte panel: the three tiers, each with its heading even when empty (as Lovable
	 * codes it), then any win with no grade (D13) in the same shape, without ids. Pure.
	 *
	 * @param list<array<string, mixed>> $rows The Gedig rows.
	 * @return string
	 */
	private static function tiersHtml( array $rows ): string {
		$html  = '';
		$known = array();

		foreach ( self::TIER_SLOTS as $tier => $slot ) {
			$list = array_values( array_filter( $rows, static fn ( array $row ): bool => self::displayTier( $row ) === (string) $tier ) );

			$known = array_merge( $known, $list );
			$html .= '<div data-audit-id="results-tier-' . esc_attr( $slot ) . '">'
				. '<h3 class="ink-heading-h2-block ink-uitslae__afdeling" data-audit-id="results-tier-' . esc_attr( $slot ) . '-h3">'
				. '<span class="ink-status-dot ink-status-dot--tier ink-status-dot--' . esc_attr( $tier ) . '" data-audit-id="results-tier-' . esc_attr( $slot ) . '-dot"></span> '
				. esc_html( Terms::label( $tier ) )
				. '</h3>'
				. self::podiumListHtml( $list, $slot )
				. '</div>';
		}

		$ungraded = array_values( array_filter( $rows, static fn ( array $row ): bool => ! in_array( $row, $known, true ) ) );

		return '<div class="ink-uitslae__afdelings" data-audit-id="results-tiers">'
			. $html
			. ( array() === $ungraded ? '' : '<div>' . self::podiumListHtml( $ungraded, '' ) . '</div>' )
			. '</div>';
	}

	/**
	 * The tier a row shows under: a Meester row shows under Goud (D11). Pure.
	 *
	 * @param array<string, mixed> $row The row.
	 * @return string
	 */
	private static function displayTier( array $row ): string {
		$tier = (string) ( $row['tier'] ?? '' );

		return 'meester' === $tier ? 'goud' : $tier;
	}

	/**
	 * One podium: the first by position is the lead, the rest follow. '' with no rows, as
	 * Lovable's `Podium` returns null. An empty `$slot` emits no audit ids. Pure.
	 *
	 * @param list<array<string, mixed>> $rows The rows.
	 * @param string                     $slot The audit slot.
	 * @return string
	 */
	private static function podiumListHtml( array $rows, string $slot ): string {
		if ( array() === $rows ) {
			return '';
		}

		usort( $rows, static fn ( array $a, array $b ): int => (int) ( $a['position'] ?? 0 ) <=> (int) ( $b['position'] ?? 0 ) );

		$seen = array();
		$rest = '';
		$lead = '';

		foreach ( $rows as $index => $row ) {
			$position = (int) ( $row['position'] ?? 0 );
			$prefix   = '' !== $slot && ! isset( $seen[ $position ] ) ? 'results-award-' . $slot . '-' . $position : '';

			$seen[ $position ] = true;

			if ( 0 === $index ) {
				$lead = self::awardHtml( $row, $prefix, true );
			} else {
				$rest .= self::awardHtml( $row, $prefix, false );
			}
		}

		return '<div class="ink-uitslae__podium"' . self::aid( '' !== $slot ? 'results-podium-' . $slot : '' ) . '>'
			. $lead
			. '<div class="ink-uitslae__res"' . self::aid( '' !== $slot ? 'results-podium-' . $slot . '-rest' : '' ) . '>' . $rest . '</div>'
			. '</div>';
	}

	/**
	 * One award card. Pure (Terms, escaping and the avatar URL).
	 *
	 * @param array<string, mixed> $row    The row.
	 * @param string               $prefix The audit prefix, or ''.
	 * @param bool                 $lead   Whether this is the lead card.
	 * @return string
	 */
	private static function awardHtml( array $row, string $prefix, bool $lead ): string {
		$aid = static fn ( string $suffix ): string => self::aid( '' === $prefix ? '' : $prefix . $suffix );

		$position = (int) ( $row['position'] ?? 0 );
		$post_id  = (int) ( $row['post_id'] ?? 0 );
		$user_id  = (int) ( $row['user_id'] ?? 0 );
		$author   = (string) ( $row['author'] ?? '' );
		$excerpt  = (string) ( $row['excerpt'] ?? '' );
		$citation = (string) ( $row['citation'] ?? '' );
		$is_gedig = PostTypes::GEDIG === ( $row['category'] ?? '' );
		$avatar   = (string) get_avatar_url( $user_id, array( 'size' => 80 ) );
		$variant  = self::toneFor( $row );

		$citation_html = '' === $citation ? '' : '<blockquote class="ink-accent-block ink-accent-block--citation ink-uitslae__kritiek"' . $aid( '-citation' ) . '>'
			. '<p class="ink-uitslae__kritiek-teks"' . $aid( '-citation-text' ) . '>' . esc_html( $citation ) . '</p>'
			. '<footer class="ink-stat-label ink-uitslae__kritiek-deur"' . $aid( '-citation-by' ) . '>' . esc_html__( '— Terugvoer van die moderator', 'ink-core' ) . '</footer>'
			. '</blockquote>';

		$action = $post_id > 0
			? '<a class="ink-btn ink-btn--sm ink-btn--gold"' . $aid( '-btn' ) . ' href="' . esc_url( (string) get_permalink( $post_id ) ) . '">'
				. esc_html__( 'Lees', 'ink-core' ) . self::icon( self::ICON_ARROW, '' === $prefix ? '' : $prefix . '-btn-icon', 'ink-uitslae__knoppie-ikoon' ) . '</a>'
			: '';

		// The winner-card treatment of the home page and Ontdek (Lovable `ChallengeResults.tsx`): the metal
		// follows the Gradering (a Gedig only); Stories, Artikels and ungraded work are Goud, the base recipe and the elevated one.
		$card_cl = 'ink-card--winner ink-card--winner-podium ink-wenners__kaart'
			. ( '' === $variant ? ' ink-card--winner-elevated' : ' ink-card--winner-' . $variant )
			. ( $lead ? '' : ' ink-card--winner-compact' );

		return '<article class="' . $card_cl . '"' . $aid( '' ) . '>'
			. FeaturedWinners::watermark( $variant )
			. '<span class="ink-pill ink-pill--place ink-pill--place-' . ( 'silwer' === $variant ? '2' : ( 'brons' === $variant ? '3' : '1' ) ) . '"' . $aid( '-pill' ) . '>'
			. self::icon( 'brons' === $variant ? self::ICON_FEATHER : ( '' === $variant ? self::ICON_CROWN : self::ICON_AWARD ), '' === $prefix ? '' : $prefix . '-pill-icon' ) . ' ' . esc_html( Terms::label( (string) ( $row['category'] ?? '' ) ) )
			. '</span>'
			. '<div class="' . ( $lead ? 'ink-uitslae__toekenning-rooster' : '' ) . '"' . $aid( '-grid' ) . '>'
			. '<div' . $aid( '-main' ) . '>'
			. '<h3 class="' . ( $lead ? 'ink-heading-h2-sub' : 'ink-heading-h3' ) . ' ink-uitslae__werk"' . $aid( '-title' ) . '>' . esc_html( (string) ( $row['title'] ?? '' ) ) . '</h3>'
			. '<div class="ink-uitslae__skrywer"' . $aid( '-author' ) . '>'
			. '<img class="ink-avatar ink-avatar--gold-ring ink-avatar--ring-40"' . $aid( '-avatar' ) . ' src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $author ) . '" width="40" height="40" loading="lazy" decoding="async" />'
			. esc_html( $author )
			. '</div>'
			. '<p class="' . ( $is_gedig ? 'ink-accent-block ink-accent-block--poem ' : '' ) . 'ink-uitslae__uittreksel' . ( $is_gedig ? ' ink-uitslae__uittreksel--gedig' : '' ) . '"' . $aid( '-excerpt' ) . '>' . esc_html( $excerpt ) . '</p>'
			. '</div>'
			. $citation_html
			. '</div>'
			. '<div class="ink-uitslae__aksie"' . $aid( '-action' ) . '>' . $action . '</div>'
			. '</article>';
	}

	/**
	 * The winner-card variant for a row: '' (Goud), 'silwer' or 'brons'. A graded Gedig follows its
	 * Gradering (Meester counts as Goud, D11); anything else is Goud. Pure.
	 *
	 * @param array<string, mixed> $row The row.
	 * @return string
	 */
	private static function toneFor( array $row ): string {
		$tier = self::displayTier( $row );

		if ( PostTypes::GEDIG === ( $row['category'] ?? '' ) && isset( self::TIER_SLOTS[ $tier ] ) ) {
			return 'goud' === $tier ? '' : $tier;
		}

		// Stories and Artikels have no Gradering: Goud throughout. A Gedig with no grade (D13) too.
		return '';
	}

	/**
	 * The mentions band. The heading and list hide with no mentions; the thanks and the
	 * button stay, and the button hides with no open round. Pure.
	 *
	 * @param list<array{title:string, author:string}> $mentions The mentions.
	 * @param string                                   $open_url The open round's URL, or ''.
	 * @return string
	 */
	public static function mentionsHtml( array $mentions, string $open_url ): string {
		$list = '';

		if ( array() !== $mentions ) {
			$lines = array_map(
				static fn ( array $m ): string => '“' . $m['title'] . '” ' . __( 'deur', 'ink-core' ) . ' ' . $m['author'],
				$mentions
			);

			$list = '<h2 class="ink-heading-h2-section" data-audit-id="results-mentions-h2">' . esc_html__( 'Eervolle vermeldings', 'ink-core' ) . '</h2>'
				. '<p class="ink-uitslae__vermeldings" data-audit-id="results-mentions-list">' . esc_html( implode( ' · ', $lines ) ) . '</p>';
		}

		$button = '' === $open_url ? '' : '<a class="ink-btn ink-btn--primary ink-uitslae__skryf-in" data-audit-id="results-mentions-btn" href="' . esc_url( $open_url ) . '">' . esc_html__( 'Skryf in vir ons nuutste uitdaging', 'ink-core' ) . '</a>';

		return '<section class="ink-band ink-band--step ink-band--cream ink-band--border-t alignfull" data-audit-id="results-mentions">'
			. '<div class="ink-container ink-uitslae__kolom ink-uitslae__kolom--3xl" data-audit-id="results-mentions-inner">'
			. $list
			. '<p class="ink-uitslae__dankie" data-audit-id="results-mentions-thanks">' . esc_html__( 'Baie dankie aan almal wat aan hierdie uitdaging deelgeneem het.', 'ink-core' ) . '</p>'
			. $button
			. '</div></section>';
	}

	/**
	 * A ` data-audit-id="…"` attribute, or '' for an empty id. Pure.
	 *
	 * @param string $id The audit id.
	 * @return string
	 */
	private static function aid( string $id ): string {
		return '' === $id ? '' : ' data-audit-id="' . esc_attr( $id ) . '"';
	}

	/**
	 * A decorative inline Lucide icon. `$paths` is a class-internal SVG literal. Pure.
	 *
	 * @param string $paths The inner SVG markup.
	 * @param string $id    The audit id, or ''.
	 * @param string $extra An extra class.
	 * @return string
	 */
	private static function icon( string $paths, string $id = '', string $extra = '' ): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" '
			. 'class="ink-icon' . ( '' === $extra ? '' : ' ' . esc_attr( $extra ) ) . '"' . self::aid( $id ) . ' aria-hidden="true" focusable="false">' . $paths . '</svg>';
	}
}
