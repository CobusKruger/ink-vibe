<?php
/**
 * Home featured-slot winner spotlight + featured-feed ordering — Story 15.6 (FR-50-R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/wenner-kollig` home featured slot + the featured-feed ordering rule
 * (FR-50-R2). Surfaces the latest wenneraankondiging (winners announcement) in the
 * Tuisblad featured slot, with the **algehele wenner first**, ahead of ordinary
 * wenners.
 *
 * Forward-compatible seam (Epic 12A is unbuilt): the announcement generation (12A.4)
 * and the assembled ordered winner set (12A.7) do not exist yet. This block reads its
 * payload from the {@see self::FEATURED_FILTER} filter — which 12A.4/12A.7 will hook
 * to supply `['title','url','winners'=>[['id','rank','title','url'],…]]`. Until then
 * the filter yields nothing and {@see toHtml()} COLLAPSES to empty markup, exactly like
 * the 14.3 {@see HomepageStrip} when there are no active sponsors — no placeholder
 * winner is ever shown. This story owns the slot + the ordering contract; 12A fills it.
 *
 * Ordering ({@see order()}) reuses the {@see Placements} rank semantics (rank 1 =
 * algehele wenner). Conflation-clean: placements hang off the entry + its Gradering
 * pool — zero `Ink\Entitlement`; viewing published results is open. House style: thin
 * {@see render()} + pure {@see toHtml()}/{@see order()}.
 *
 * @package Ink\Core
 */
final class FeaturedWinners {

	/**
	 * The server block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/wenner-kollig';

	/**
	 * The filter 12A.4/12A.7 hooks to supply the featured wenneraankondiging payload.
	 * Returns null/empty when there is no current announcement (the slot collapses).
	 *
	 * @var string
	 */
	public const FEATURED_FILTER = 'ink_home_featured_winner';

	/**
	 * Lucide Crown inner-SVG paths — the rank cue + watermark (§0.9, §5). The rank is
	 * always paired with text; the icon is decorative (`aria-hidden`).
	 *
	 * @var string
	 */
	private const ICON_CROWN = '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>';

	/**
	 * Register the server block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/wenner-kollig` dynamic block.
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
	 * Block render callback. Reads the (12A-supplied) featured payload and renders the
	 * spotlight; collapses to nothing when there is no current announcement.
	 *
	 * @return string
	 */
	public static function render(): string {
		$featured = apply_filters( self::FEATURED_FILTER, null );

		return self::toHtml( is_array( $featured ) ? $featured : array() );
	}

	/**
	 * Order featured winners with the algehele wenner (rank 1) first, then ranks 2–3.
	 * Pure, deterministic — ties (same rank) break by ascending id, so order never
	 * depends on incidental query order. This is the featured-feed ordering contract
	 * 12A.7 consumes (FR-50-R2).
	 *
	 * @param list<array{id?:int, rank?:int, title?:string, url?:string}> $winners The winner rows.
	 * @return list<array{id:int, rank:int, title:string, url:string, is_algehele_wenner:bool, label:string}>
	 */
	public static function order( array $winners ): array {
		$rows = array();

		foreach ( $winners as $winner ) {
			$rank = (int) ( $winner['rank'] ?? 0 );
			$id   = (int) ( $winner['id'] ?? 0 );

			if ( $id <= 0 || ! Placements::isValidRank( $rank ) ) {
				continue;
			}

			$rows[] = array(
				'id'                 => $id,
				'rank'               => $rank,
				'title'              => (string) ( $winner['title'] ?? '' ),
				'url'                => (string) ( $winner['url'] ?? '' ),
				'is_algehele_wenner' => Placements::isAlgeheleWenner( $rank ),
				'label'              => Placements::placementLabel( $rank ),
			);
		}

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				$by_rank = $a['rank'] <=> $b['rank'];

				return 0 !== $by_rank ? $by_rank : ( $a['id'] <=> $b['id'] );
			}
		);

		// Collapse to one entry per rank — a defensive guard so a dirty payload (two
		// rank-1s) can never surface two "algehele wenners" in the slot; the lowest-id
		// placement wins. Mirrors the canonical one-per-rank invariant in
		// {@see Placements::arrange()}; authoritative dedup remains 12A.3's ingestion.
		$seen   = array();
		$unique = array();

		foreach ( $rows as $row ) {
			if ( isset( $seen[ $row['rank'] ] ) ) {
				continue;
			}

			$seen[ $row['rank'] ] = true;
			$unique[]             = $row;
		}

		return $unique;
	}

	/**
	 * Order the featured FEED: algehele wenner(s) first, then ordinary wenners (Story
	 * 12A.7, FR-50-R2). Pure, deterministic — by rank then ascending id.
	 *
	 * Unlike {@see order()} (the single-spotlight dedup), this keeps EVERY valid winner:
	 * the 12A.3 per-(Gradering × category) pools produce one algehele wenner PER category,
	 * and the feed must list them all (collapsing to one-per-rank would hide them). This
	 * is the ordering that "drives the home featured ordering" (15.6) without losing winners.
	 *
	 * The OPTIONAL presentation fields (month/author/quote/avatar/win_label) are passed
	 * through untouched so {@see cardHtml()} can render the §5 winner card; they are the
	 * forward-compatible seam that 12A.4/12A.7 fills — absent today, each card degrades
	 * gracefully (the sub-part is simply omitted). The 12A ingestion/commit path is
	 * unchanged: this is a markup-only upgrade that reads richer optional payload.
	 *
	 * @param list<array{id?:int, rank?:int, title?:string, url?:string, month?:string, author?:string, quote?:string, avatar_url?:string, avatar_alt?:string, win_label?:string}> $winners The winner rows.
	 * @return list<array{id:int, rank:int, title:string, url:string, is_algehele_wenner:bool, label:string, month:string, author:string, quote:string, avatar_url:string, avatar_alt:string, win_label:string}>
	 */
	public static function orderFeed( array $winners ): array {
		$rows = array();

		foreach ( $winners as $winner ) {
			$rank = (int) ( $winner['rank'] ?? 0 );
			$id   = (int) ( $winner['id'] ?? 0 );

			if ( $id <= 0 || ! Placements::isValidRank( $rank ) ) {
				continue;
			}

			$rows[] = array(
				'id'                 => $id,
				'rank'               => $rank,
				'title'              => (string) ( $winner['title'] ?? '' ),
				'url'                => (string) ( $winner['url'] ?? '' ),
				'is_algehele_wenner' => Placements::isAlgeheleWenner( $rank ),
				'label'              => Placements::placementLabel( $rank ),
				'month'              => (string) ( $winner['month'] ?? '' ),
				'author'             => (string) ( $winner['author'] ?? '' ),
				'quote'              => (string) ( $winner['quote'] ?? '' ),
				'avatar_url'         => (string) ( $winner['avatar_url'] ?? '' ),
				'avatar_alt'         => (string) ( $winner['avatar_alt'] ?? '' ),
				'win_label'          => (string) ( $winner['win_label'] ?? '' ),
			);
		}

		usort(
			$rows,
			static function ( array $a, array $b ): int {
				$by_rank = $a['rank'] <=> $b['rank'];

				return 0 !== $by_rank ? $by_rank : ( $a['id'] <=> $b['id'] );
			}
		);

		return $rows;
	}

	/**
	 * Build the featured-slot HTML — the §5 winner card(s). Pure (Placements labels +
	 * escaping only).
	 *
	 * Collapses to '' when there is no announcement title (no current wenneraankondiging)
	 * — no empty chrome. When populated, renders the announcement heading (linked to its
	 * permalink) and the ordered winners, each as a CARD ({@see cardHtml()}): a Crown
	 * watermark + rank eyebrow (text + Crown icon — never colour alone), the linked work
	 * title, the optional quote/author/avatar (seam-supplied), and a "Lees die volledige
	 * storie" link. The gradient token CLASS (`--goud-gradient`) is a hook the theme
	 * styles (`goud`/`gold-muted`); the block emits no colour itself.
	 *
	 * @param array{title?:string, url?:string, winners?:array<int,array<string,mixed>>} $featured The 12A payload.
	 * @return string
	 */
	public static function toHtml( array $featured ): string {
		$title = (string) ( $featured['title'] ?? '' );

		if ( '' === trim( $title ) ) {
			return '';
		}

		$url = (string) ( $featured['url'] ?? '' );
		// 12A.7: render the full feed (every winner, algehele wenner(s) first), not the
		// single-spotlight dedup — the per-category pools (12A.3) have one algehele wenner each.
		$winners = self::orderFeed( is_array( $featured['winners'] ?? null ) ? $featured['winners'] : array() );

		$heading = '' !== $url
			? '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
			: esc_html( $title );

		$html = '<section class="ink-wenner-kollig" aria-label="' . esc_attr__( 'Wenneraankondiging', 'ink-core' ) . '">'
			. '<h2 class="ink-wenner-kollig__titel">' . $heading . '</h2>';

		if ( array() !== $winners ) {
			$html .= '<div class="ink-wenner-kollig__kaarte">';

			foreach ( $winners as $winner ) {
				$html .= self::cardHtml( $winner );
			}

			$html .= '</div>';
		}

		return $html . '</section>';
	}

	/**
	 * One winner CARD (§5). Pure (escaping only).
	 *
	 * Rank is conveyed by TEXT (the eyebrow "[Maand] algehele wenner" / "[Maand]-wenner")
	 * paired with a Crown icon — never colour alone (a11y). The eyebrow join follows the
	 * authored ui-copy: a space for the algehele wenner ("Desember algehele wenner"), a
	 * hyphen for an ordinary wenner ("Desember-wenner"). The month + author + quote +
	 * avatar + win_label are OPTIONAL seam fields — each sub-part is omitted when absent,
	 * so the card degrades gracefully until 12A supplies the richer payload.
	 *
	 * @param array{id:int, rank:int, title:string, url:string, is_algehele_wenner:bool, label:string, month:string, author:string, quote:string, avatar_url:string, avatar_alt:string, win_label:string} $winner The winner row.
	 * @return string
	 */
	private static function cardHtml( array $winner ): string {
		$is_algehele = ! empty( $winner['is_algehele_wenner'] );
		$variant     = $is_algehele ? 'algehele' : 'wenner';

		$classes = 'ink-wenner-kollig__kaart ink-wenner-kollig__kaart--goud-gradient '
			. 'ink-wenner-kollig__kaart--' . $variant;

		// Eyebrow: month + placement label, joined per authored ui-copy (space for the
		// algehele wenner, hyphen otherwise). Month absent → just the placement label.
		$label   = (string) $winner['label'];
		$month   = (string) $winner['month'];
		$eyebrow = '' !== $month
			? ( $is_algehele ? $month . ' ' . $label : $month . '-' . $label )
			: $label;

		$url        = (string) $winner['url'];
		$title      = (string) $winner['title'];
		$title_html = '' !== $url
			? '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
			: esc_html( $title );

		$out = '<article class="' . esc_attr( $classes ) . '">'
			. '<span class="ink-wenner-kollig__watermerk" aria-hidden="true">' . self::icon( self::ICON_CROWN ) . '</span>'
			. '<p class="ink-wenner-kollig__rang">'
			. '<span class="ink-wenner-kollig__rang-merk" aria-hidden="true">' . self::icon( self::ICON_CROWN ) . '</span>'
			. '<span class="ink-wenner-kollig__rang-teks">' . esc_html( $eyebrow ) . '</span>'
			. '</p>'
			. '<h3 class="ink-wenner-kollig__werk">' . $title_html . '</h3>';

		$quote = (string) $winner['quote'];

		if ( '' !== $quote ) {
			$out .= '<blockquote class="ink-wenner-kollig__aanhaling">' . esc_html( $quote ) . '</blockquote>';
		}

		$out .= self::authorHtml( $winner );

		if ( '' !== $url ) {
			$out .= '<a class="ink-wenner-kollig__skakel" href="' . esc_url( $url ) . '">'
				. esc_html__( 'Lees die volledige storie', 'ink-core' ) . '</a>';
		}

		return $out . '</article>';
	}

	/**
	 * The author block: avatar (with alt) + name + optional win_label. Pure. Renders
	 * nothing when the seam supplies no author details (graceful).
	 *
	 * @param array{author:string, avatar_url:string, avatar_alt:string, win_label:string} $winner The winner row.
	 * @return string
	 */
	private static function authorHtml( array $winner ): string {
		$author    = (string) $winner['author'];
		$avatar    = (string) $winner['avatar_url'];
		$win_label = (string) $winner['win_label'];

		if ( '' === $author && '' === $avatar ) {
			return '';
		}

		$out = '<div class="ink-wenner-kollig__outeur">';

		if ( '' !== $avatar ) {
			// Avatar alt falls back to the author name so it is never empty (a11y).
			$alt  = '' !== $winner['avatar_alt'] ? (string) $winner['avatar_alt'] : $author;
			$out .= '<img class="ink-wenner-kollig__foto" src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $alt ) . '" '
				. 'width="48" height="48" loading="lazy" decoding="async" />';
		}

		$out .= '<div class="ink-wenner-kollig__outeur-besonderhede">';

		if ( '' !== $author ) {
			$out .= '<span class="ink-wenner-kollig__outeur-naam">' . esc_html( $author ) . '</span>';
		}

		if ( '' !== $win_label ) {
			// win_label is a seam-supplied, pre-composed Afrikaans string (e.g. "3de wen")
			// — the ordinal composition + copy live in 12A, not in this markup block.
			$out .= '<span class="ink-wenner-kollig__wenne">' . esc_html( $win_label ) . '</span>';
		}

		return $out . '</div></div>';
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
