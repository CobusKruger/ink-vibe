<?php
/**
 * Home current-challenge card — Story 19.3 (§3 hero card + §5 feature card).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/huidige-uitdaging` block: the current OPEN uitdaging as a card.
 *
 * The Tuisblad surfaces the live challenge twice (the Lovable design): a COMPACT card
 * in the hero right column (§3) and a larger FEATURE card in the feature band below the
 * hero (§5). Both are the same block; the `variant` attribute picks the DOM. The
 * open-uitdaging query + the deadline/entry-count reads live HERE in `ink-core`
 * (three-layer separation — the theme performs no query, no computation); the theme
 * styles the block's own `.ink-huidige-uitdaging*` markup via `home.css`.
 *
 * House style mirrors {@see HomepageStrip}/{@see FeaturedWinners}: a thin impure
 * {@see render()} over a pure {@see toHtml()} + a data seam ({@see DATA_FILTER}) with a
 * live default ({@see current()}), and **graceful collapse** — no open uitdaging yields
 * the empty string, so the hero aside / feature column simply shows nothing (no
 * placeholder teaser). Conflation-clean: references only `Ink\Content` (CPT + deadline
 * meta), the `Kernel` helpers, the {@see Terms} registry, {@see Deadline}/{@see SinglePage}
 * and WP core — zero `Ink\Tiers`/`Ink\Entitlement`. Viewing the open challenge is open.
 *
 * @package Ink\Core
 */
final class CurrentChallenge {

	/**
	 * The block name — single source for the renderer + the theme embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/huidige-uitdaging';

	/**
	 * Overridable data seam: a filter may supply the current-challenge payload
	 * (title/url/excerpt/deadline/eyebrow/entry_count) directly. Returns null by
	 * default, so {@see render()} falls through to the live {@see current()} query.
	 *
	 * @var string
	 */
	public const DATA_FILTER = 'ink_home_current_challenge';

	/**
	 * The compact hero-card variant (§3).
	 *
	 * @var string
	 */
	public const VARIANT_COMPACT = 'kompak';

	/**
	 * The larger feature-card variant (§5).
	 *
	 * @var string
	 */
	public const VARIANT_FEATURE = 'kenmerk';

	/**
	 * How many of the newest published uitdagings to scan for the first still-open one.
	 * Bounded so a busy archive never loads unboundedly; the current challenge is
	 * effectively always among the newest few.
	 *
	 * @var int
	 */
	public const SCAN_LIMIT = 10;

	/**
	 * Per-request memo for {@see current()} so the hero (§3) + feature (§5) instances
	 * on the same page share one query. Null-object pattern: a resolved value of null
	 * (no open challenge) is still cached via the companion flag.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $memo = null;

	/**
	 * Whether {@see $memo} has been resolved this request.
	 *
	 * @var bool
	 */
	private static bool $memo_set = false;

	// --- Lucide inner-SVG paths (§0.9 — emitted inline by the block PHP). ---

	private const ICON_SPARKLES = '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>';
	private const ICON_CALENDAR = '<path d="M8 2v4"/><path d="M16 2v4"/><rect width="18" height="18" x="3" y="4" rx="2"/><path d="M3 10h18"/>';
	private const ICON_USERS    = '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>';
	private const ICON_TROPHY   = '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>';
	private const ICON_ARROW    = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

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
	 * Register the `ink/huidige-uitdaging` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
				'attributes'      => array(
					'variant' => array(
						'type'    => 'string',
						'default' => self::VARIANT_COMPACT,
					),
				),
			)
		);
	}

	/**
	 * Build the `WP_Query` args for the newest published uitdagings. Pure.
	 *
	 * The open one is picked in PHP ({@see resolveCurrent()}) from a bounded newest-first
	 * scan — the deadline is a DATE-ONLY meta that can't be reliably range-compared in
	 * the query (the end-of-day-SAST boundary lives in {@see Sast}), mirroring how
	 * {@see Archive} computes each card's countdown after the fetch.
	 *
	 * @param int $limit How many newest challenges to fetch.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $limit ): array {
		return array(
			'post_type'           => PostTypes::UITDAGING,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, $limit ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}

	/**
	 * The current open-challenge payload, memoised per request. Impure (WP_Query + meta).
	 *
	 * @return array<string, mixed>|null Null when there is no open uitdaging.
	 */
	public static function current(): ?array {
		if ( self::$memo_set ) {
			return self::$memo;
		}

		self::$memo_set = true;
		self::$memo     = self::resolveCurrent();

		return self::$memo;
	}

	/**
	 * Resolve the first still-open uitdaging from the newest-first scan. Impure.
	 *
	 * @return array<string, mixed>|null
	 */
	private static function resolveCurrent(): ?array {
		$query = new \WP_Query( self::queryArgs( self::SCAN_LIMIT ) );
		$now   = Sast::now();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$deadline = Deadline::parse(
				Scalar::asString( get_post_meta( (int) $post->ID, FieldSets::UITDAGING_DEADLINE, true ) )
			);

			if ( ! $deadline instanceof \DateTimeImmutable || ! Sast::isThroughEndOfDay( $deadline, $now ) ) {
				continue;
			}

			return array(
				'title'       => get_the_title( $post ),
				'url'         => (string) get_permalink( $post ),
				'excerpt'     => self::excerptFor( $post ),
				'deadline'    => Deadline::format( $deadline ),
				'eyebrow'     => self::monthEyebrow( $deadline ),
				'entry_count' => self::entryCount( (int) $post->ID ),
			);
		}

		return null;
	}

	/**
	 * A trimmed prompt excerpt for the card body. Impure (WP excerpt helpers).
	 *
	 * @param \WP_Post $post The uitdaging.
	 * @return string
	 */
	private static function excerptFor( \WP_Post $post ): string {
		if ( has_excerpt( $post ) ) {
			return (string) get_the_excerpt( $post );
		}

		return (string) wp_trim_words( wp_strip_all_tags( (string) $post->post_content ), 32, '…' );
	}

	/**
	 * The "[Maand]-uitdaging" eyebrow for the feature card (ui-copy: "Januarie-uitdaging").
	 * Month is localised (Afrikaans) via `wp_date`; the join is authored copy. Impure.
	 *
	 * @param \DateTimeImmutable $deadline The deadline instant.
	 * @return string
	 */
	private static function monthEyebrow( \DateTimeImmutable $deadline ): string {
		$sast  = $deadline->setTimezone( new \DateTimeZone( Sast::TIMEZONE ) );
		$month = function_exists( 'wp_date' )
			? (string) wp_date( 'F', $sast->getTimestamp() )
			: $sast->format( 'F' );

		if ( '' === $month ) {
			return '';
		}

		/* translators: %s: the challenge's month name, e.g. "Januarie". */
		return sprintf( __( '%s-uitdaging', 'ink-core' ), $month );
	}

	/**
	 * The count of published entries linked to this round. Impure (bounded WP_Query).
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return int
	 */
	private static function entryCount( int $uitdaging_id ): int {
		$args           = SinglePage::entriesQueryArgs( $uitdaging_id );
		$args['fields'] = 'ids';

		$query = new \WP_Query( $args );

		return count( $query->posts );
	}

	/**
	 * Block render callback. Reads the variant + the (seam or live) payload, composes.
	 * Thin impure shell over the pure {@see toHtml()}.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public static function render( array $attributes = array() ): string {
		$variant = ( isset( $attributes['variant'] ) && self::VARIANT_FEATURE === $attributes['variant'] )
			? self::VARIANT_FEATURE
			: self::VARIANT_COMPACT;

		$data = apply_filters( self::DATA_FILTER, null );

		if ( ! is_array( $data ) ) {
			$data = self::current();
		}

		return self::toHtml( is_array( $data ) ? $data : array(), $variant );
	}

	/**
	 * Build the card HTML for the given payload + variant. Pure (Terms + escaping only).
	 *
	 * Collapses to '' when there is no open challenge (no title) — no empty chrome. The
	 * decorative corner tint + the icons are `aria-hidden`; the title is an `h2` (correct
	 * order under the hero `h1`). Emits the block's own `.ink-huidige-uitdaging*` classes;
	 * the theme (home.css) supplies the token styling.
	 *
	 * @param array<string, mixed> $data    The current-challenge payload.
	 * @param string               $variant One of VARIANT_COMPACT / VARIANT_FEATURE.
	 * @return string
	 */
	public static function toHtml( array $data, string $variant = self::VARIANT_COMPACT ): string {
		$title = (string) ( $data['title'] ?? '' );

		if ( '' === trim( $title ) ) {
			return '';
		}

		$variant = self::VARIANT_FEATURE === $variant ? self::VARIANT_FEATURE : self::VARIANT_COMPACT;
		$base    = 'ink-huidige-uitdaging';

		$url         = (string) ( $data['url'] ?? '' );
		$excerpt     = (string) ( $data['excerpt'] ?? '' );
		$deadline    = (string) ( $data['deadline'] ?? '' );
		$eyebrow     = (string) ( $data['eyebrow'] ?? '' );
		$entry_count = (int) ( $data['entry_count'] ?? 0 );

		$classes = $base . ' ' . $base . '--' . $variant;

		$out  = '<section class="' . esc_attr( $classes ) . '">';
		$out .= '<span class="' . esc_attr( $base . '__hoek' ) . '" aria-hidden="true"></span>';
		$out .= '<div class="' . esc_attr( $base . '__inhoud' ) . '">';
		$out .= self::headerHtml( $base, $variant, $deadline, $eyebrow );
		$out .= '<h2 class="' . esc_attr( $base . '__titel' ) . '">' . esc_html( $title ) . '</h2>';

		if ( '' !== $excerpt ) {
			$out .= '<p class="' . esc_attr( $base . '__uittreksel' ) . '">' . esc_html( $excerpt ) . '</p>';
		}

		if ( self::VARIANT_FEATURE === $variant ) {
			$out .= self::metaHtml( $base, $deadline, $entry_count );
		}

		if ( '' !== $url ) {
			$out .= '<a class="' . esc_attr( $base . '__aksie' ) . '" href="' . esc_url( $url ) . '">'
				. '<span>' . esc_html__( 'Skryf in', 'ink-core' ) . '</span>'
				. self::icon( self::ICON_ARROW )
				. '</a>';
		}

		return $out . '</div></section>';
	}

	/**
	 * The card header: a Sparkles type badge + deadline (compact), or an icon tile +
	 * "[Maand]-uitdaging" eyebrow (feature). Pure.
	 *
	 * @param string $base     The BEM base class.
	 * @param string $variant  The card variant.
	 * @param string $deadline The formatted deadline (may be '').
	 * @param string $eyebrow  The month eyebrow (may be '').
	 * @return string
	 */
	private static function headerHtml( string $base, string $variant, string $deadline, string $eyebrow ): string {
		if ( self::VARIANT_FEATURE === $variant ) {
			$out = '<div class="' . esc_attr( $base . '__kop' ) . '">'
				. '<span class="' . esc_attr( $base . '__ikoon' ) . '" aria-hidden="true">' . self::icon( self::ICON_TROPHY ) . '</span>';

			if ( '' !== $eyebrow ) {
				$out .= '<span class="' . esc_attr( $base . '__boskrif' ) . '">' . esc_html( $eyebrow ) . '</span>';
			}

			return $out . '</div>';
		}

		$out = '<div class="' . esc_attr( $base . '__kop' ) . '">'
			. '<span class="' . esc_attr( $base . '__kenteken' ) . '">'
			. self::icon( self::ICON_SPARKLES )
			. '<span class="' . esc_attr( $base . '__kenteken-teks' ) . '">' . esc_html( Terms::label( 'uitdaging' ) ) . '</span>'
			. '</span>';

		if ( '' !== $deadline ) {
			$out .= '<span class="' . esc_attr( $base . '__meta-datum' ) . '">'
				. self::icon( self::ICON_CALENDAR )
				/* translators: %s: the challenge deadline date. */
				. '<time>' . esc_html( sprintf( __( 'Sluit %s', 'ink-core' ), $deadline ) ) . '</time>'
				. '</span>';
		}

		return $out . '</div>';
	}

	/**
	 * The feature-card meta row: deadline + entry count (Calendar + Users). Pure.
	 *
	 * @param string $base        The BEM base class.
	 * @param string $deadline    The formatted deadline (may be '').
	 * @param int    $entry_count The published-entry count.
	 * @return string
	 */
	private static function metaHtml( string $base, string $deadline, int $entry_count ): string {
		$items = '';

		if ( '' !== $deadline ) {
			$items .= '<span class="' . esc_attr( $base . '__meta-item' ) . '">'
				. self::icon( self::ICON_CALENDAR )
				/* translators: %s: the challenge deadline date. */
				. '<span>' . esc_html( sprintf( __( 'Sluit %s', 'ink-core' ), $deadline ) ) . '</span>'
				. '</span>';
		}

		if ( $entry_count > 0 ) {
			/* translators: %d: the number of entries (inskrywings) submitted to the challenge. */
			$label  = sprintf( _n( '%d inskrywing', '%d inskrywings', $entry_count, 'ink-core' ), $entry_count );
			$items .= '<span class="' . esc_attr( $base . '__meta-item' ) . '">'
				. self::icon( self::ICON_USERS )
				. '<span>' . esc_html( $label ) . '</span>'
				. '</span>';
		}

		if ( '' === $items ) {
			return '';
		}

		return '<div class="' . esc_attr( $base . '__meta' ) . '">' . $items . '</div>';
	}

	/**
	 * A decorative inline Lucide icon (§0.9): 16px, currentColor, aria-hidden. Pure.
	 * `$paths` is trusted class-internal SVG literal (never user input).
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
