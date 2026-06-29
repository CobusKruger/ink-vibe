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
	 * Register the server block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * @param list<array{id?:int, rank?:int, title?:string, url?:string}> $winners The winner rows.
	 * @return list<array{id:int, rank:int, title:string, url:string, is_algehele_wenner:bool, label:string}>
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
	 * Build the featured-slot HTML. Pure (escaping + Placements labels only).
	 *
	 * Collapses to '' when there is no announcement title (no current wenneraankondiging)
	 * — no empty chrome. When populated, renders the announcement heading (linked to its
	 * permalink) and the ordered winners, each with its placement label + a "Lees die
	 * volledige storie" link.
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
			$html .= '<ul class="ink-wenner-kollig__lys">';

			foreach ( $winners as $winner ) {
				$item_class = $winner['is_algehele_wenner']
					? 'ink-wenner-kollig__item ink-wenner-kollig__item--algehele'
					: 'ink-wenner-kollig__item';

				$html .= '<li class="' . esc_attr( $item_class ) . '">'
					. '<span class="ink-wenner-kollig__plek">' . esc_html( $winner['label'] ) . '</span> '
					. '<span class="ink-wenner-kollig__naam">' . esc_html( $winner['title'] ) . '</span>';

				// "Lees die volledige storie" read-more link to the winning work (ui-copy
				// line 83). Rendered only when the entry has a permalink — never a dead link.
				if ( '' !== $winner['url'] ) {
					$html .= ' <a class="ink-wenner-kollig__skakel" href="' . esc_url( $winner['url'] ) . '">'
						. esc_html__( 'Lees die volledige storie', 'ink-core' ) . '</a>';
				}

				$html .= '</li>';
			}

			$html .= '</ul>';
		}

		return $html . '</section>';
	}
}
