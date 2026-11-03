<?php
/**
 * Home featured-slot winner spotlight + featured-feed ordering — Story 15.6 (FR-50-R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/wenner-kollig` block: the winners section of Tuis (Lovable `ChallengeSection.tsx`).
 *
 * Its payload comes from {@see self::FEATURED_FILTER}, which {@see WinnersPost::featured()}
 * hooks; a round with no placed winner collapses the section.
 *
 * DIVERGENCE (accepted, D10, handoff permitted #1): four cards in two pairs, not Lovable's two:
 * Goud Gedig + Storie, then Silwer Gedig + Artikel. **Pair by type, not position:** Lovable's
 * `winner-card` is the prose card and `winner-gedig-card` the poem, and Lovable puts prose left
 * where D10 puts Gedig left, so horizontal position drift on those ids is expected. The second
 * pair repeats the recipes with no ids: Lovable has no node for it, and a repeated id stops the
 * diff.
 *
 * Copy: D10 labels, not "Best Story" (`03-tuis` §5). `Lees die volledige storie` is ratified for
 * the older "Read Full Story"; the current design says "Read Story", and README E1 gives
 * `Lees storie`.
 *
 * Conflation-clean: zero `Ink\Entitlement`; viewing published results is open.
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
	 * The filter supplying the section's payload. Null or empty collapses the section.
	 *
	 * @var string
	 */
	public const FEATURED_FILTER = 'ink_home_featured_winner';

	private const ICON_CROWN   = '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>';
	private const ICON_BOOK    = '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>';
	private const ICON_FEATHER = '<path d="M12.67 19a2 2 0 0 0 1.416-.588l6.154-6.172a6 6 0 0 0-8.49-8.49L5.586 9.914A2 2 0 0 0 5 11.328V18a1 1 0 0 0 1 1z"/><path d="M16 8 2 22"/><path d="M17.5 15H9"/>';
	private const ICON_ARROW   = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

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
	 * Block render callback. Reads the payload and renders the section; collapses to nothing
	 * with no winner.
	 *
	 * @return string
	 */
	public static function render(): string {
		$featured = apply_filters( self::FEATURED_FILTER, null );

		return self::toHtml( is_array( $featured ) ? $featured : array() );
	}

	/**
	 * The slots in D10's order. Pure (Terms).
	 *
	 * @return array<string, array{prefix:string, poem:bool, icon:string, label:string, button:string}>
	 */
	private static function slots(): array {
		$lees_gedig  = __( 'Lees gedig', 'ink-core' );
		$lees_storie = __( 'Lees storie', 'ink-core' );

		return array(
			'goud-gedig'   => array(
				'prefix' => 'winner-gedig',
				'poem'   => true,
				'icon'   => self::ICON_FEATHER,
				'label'  => Terms::label( 'goud' ) . ' ' . Terms::label( 'gedig' ),
				'button' => $lees_gedig,
			),
			'storie'       => array(
				'prefix' => 'winner',
				'poem'   => false,
				'icon'   => self::ICON_BOOK,
				'label'  => Terms::label( 'storie' ),
				'button' => $lees_storie,
			),
			'silwer-gedig' => array(
				'prefix' => '',
				'poem'   => true,
				'icon'   => self::ICON_FEATHER,
				'label'  => Terms::label( 'silwer' ) . ' ' . Terms::label( 'gedig' ),
				'button' => $lees_gedig,
			),
			'artikel'      => array(
				'prefix' => '',
				'poem'   => false,
				'icon'   => self::ICON_BOOK,
				'label'  => Terms::label( 'artikel' ),
				'button' => __( '[NEEDS HUMAN AFRIKAANS] TUIS-WENNER-LEES-ARTIKEL', 'ink-core' ),
			),
		);
	}

	/**
	 * The section. Collapses to '' with no winner: no empty band, no placeholder. Pure.
	 *
	 * @param array<string, mixed> $featured The payload ({@see WinnersPost::featuredPayload()}).
	 * @return string
	 */
	public static function toHtml( array $featured ): string {
		$slots = is_array( $featured['slots'] ?? null ) ? $featured['slots'] : array();
		$cards = '';

		foreach ( self::slots() as $key => $slot ) {
			if ( is_array( $slots[ $key ] ?? null ) ) {
				$cards .= self::cardHtml( $slots[ $key ], $slot );
			}
		}

		if ( '' === $cards ) {
			return '';
		}

		$month = (string) ( $featured['month'] ?? '' );
		$theme = (string) ( $featured['theme'] ?? '' );
		/* translators: %s: the round's month, e.g. "Oktober". */
		$eyebrow = ( '' !== $month ? sprintf( __( '%s-uitdaging', 'ink-core' ), $month ) . ' · ' : '' ) . '"' . $theme . '"';

		return '<section class="ink-band alignfull" data-audit-id="challenge">'
			. '<div class="ink-container" data-audit-id="challenge-container">'
			. '<div class="ink-wenners__kop" data-audit-id="challenge-head">'
			. self::icon( self::ICON_CROWN, 'challenge-head-icon', 'ink-wenners__kop-ikoon' )
			. '<span class="ink-eyebrow ink-eyebrow--card ink-eyebrow--gold" data-audit-id="challenge-eyebrow">' . esc_html( $eyebrow ) . '</span>'
			. '</div>'
			. '<h2 class="ink-heading-h2-section ink-wenners__titel" data-audit-id="challenge-h2">' . esc_html__( '[NEEDS HUMAN AFRIKAANS] TUIS-WENNERS-H2', 'ink-core' ) . '</h2>'
			. '<div class="ink-wenners__rooster" data-audit-id="challenge-grid">' . $cards . '</div>'
			. '</div></section>';
	}

	/**
	 * The "Nth wen" label; '' for no count. `ui-copy-translations.md` gives "3de wen"; the
	 * ordinal follows Afrikaans spelling: 1ste, 8ste and 20ste on, -de otherwise. Pure.
	 *
	 * @param int $count The lifetime win count.
	 * @return string
	 */
	public static function winLabel( int $count ): string {
		if ( $count <= 0 ) {
			return '';
		}

		$ordinal = $count . ( 1 === $count || 8 === $count || $count >= 20 ? 'ste' : 'de' );

		/* translators: %s: an ordinal, e.g. "3de". */
		return sprintf( __( '%s wen', 'ink-core' ), $ordinal );
	}

	/**
	 * One winner card. Only the two slots with a Lovable counterpart carry audit ids. Pure.
	 *
	 * @param array<string, mixed>                                                      $winner The slot's view.
	 * @param array{prefix:string, poem:bool, icon:string, label:string, button:string} $slot   The slot's definition.
	 * @return string
	 */
	private static function cardHtml( array $winner, array $slot ): string {
		$prefix = $slot['prefix'];
		$aid    = static fn ( string $suffix ): string => self::aid( '' === $prefix ? '' : $prefix . $suffix );
		$icon   = static fn ( string $suffix ): string => '' === $prefix ? '' : $prefix . $suffix;

		$url    = (string) ( $winner['url'] ?? '' );
		$author = (string) ( $winner['author'] ?? '' );
		$quote  = (string) ( $winner['quote'] ?? '' );
		$avatar = (string) ( $winner['avatar_url'] ?? '' );
		$wins   = (string) ( $winner['win_label'] ?? '' );

		$quote_html = $slot['poem']
			? '<p class="ink-accent-block ink-accent-block--poem ink-wenners__aanhaling"' . $aid( '-quote' ) . '>' . esc_html( $quote ) . '</p>'
			: '<p class="ink-wenners__aanhaling ink-wenners__aanhaling--prosa"' . $aid( '-quote' ) . '>"' . esc_html( $quote ) . '"</p>';

		$avatar_html = '' === $avatar ? '' : '<img class="ink-avatar ink-avatar--gold-ring"' . $aid( '-avatar' ) . ' src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $author ) . '" width="48" height="48" loading="lazy" decoding="async" />';

		$button = '' === $url ? '' : '<a class="ink-btn ink-btn--gold"' . $aid( '-btn' ) . ' href="' . esc_url( $url ) . '">'
			. esc_html( $slot['button'] ) . self::icon( self::ICON_ARROW, $icon( '-btn-icon' ), 'ink-wenners__knoppie-ikoon' ) . '</a>';

		return '<article class="ink-card--winner ink-wenners__kaart"' . $aid( '-card' ) . '>'
			. self::icon( self::ICON_CROWN, $icon( '-watermark' ), 'ink-wenners__watermerk' )
			. '<span class="ink-pill ink-pill--caps ink-pill--caps-gold ink-wenners__kategorie"' . $aid( '-eyebrow' ) . '>'
			. self::icon( $slot['icon'], $icon( '-eyebrow-icon' ) ) . esc_html( $slot['label'] )
			. '</span>'
			. '<h3 class="ink-heading-h2-block ink-wenners__werk"' . $aid( '-title' ) . '>"' . esc_html( (string) ( $winner['title'] ?? '' ) ) . '"</h3>'
			. $quote_html
			. '<div class="ink-wenners__voet"' . $aid( '-footer' ) . '>'
			. '<div class="ink-wenners__skrywer"' . $aid( '-author' ) . '>'
			. $avatar_html
			. '<div' . $aid( '-author-details' ) . '>'
			. '<p class="ink-wenners__skrywer-naam"' . $aid( '-author-name' ) . '>' . esc_html( $author ) . '</p>'
			. ( '' === $wins ? '' : '<p class="ink-wenners__wenne"' . $aid( '-author-wins' ) . '>' . esc_html( $wins ) . '</p>' )
			. '</div></div>'
			. $button
			. '</div></article>';
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
