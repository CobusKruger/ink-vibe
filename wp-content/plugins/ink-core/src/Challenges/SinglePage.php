<?php
/**
 * Uitdaging single-page surface — Story 12.1 (FR-45).
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
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/uitdaging-besonderhede` block on a single `uitdaging`.
 *
 * Surfaces the dynamic half of the challenge page: the **sluitingsdatum** with an
 * **Oop/Gesluit** status (inclusive end-of-day-SAST rule, {@see Sast}) and the
 * **inskrywings** list — published bydraes linked to this round via the
 * `uitdagingsrondte` term slugged {@see ChallengeRound::slugFor()}. The editorial
 * brief (prompt, literary devices, submission rules, prize, resources) is authored
 * as the uitdaging post body and surfaces through the theme's core `post-content`.
 *
 * House style mirrors {@see \Ink\Library\Archive}: pure {@see self::entriesQueryArgs()}
 * + pure render helpers + a thin {@see self::render()}. Conflation-clean: reads only
 * `Ink\Content` (CPT/taxonomy/round-slug single sources) + `Kernel\Sast` + the `Terms`
 * registry + WP core — zero `Ink\Tiers`/`Ink\Entitlement` (viewing a published
 * challenge is open, never gated).
 *
 * @package Ink\Core
 */
final class SinglePage {

	/**
	 * The block name — single source for the renderer + the theme pattern embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitdaging-besonderhede';

	/**
	 * Upper bound on entries hydrated for the single-page list + the pool grouping.
	 *
	 * The entries list is request-rendered, so an unbounded `-1` would load every
	 * entry of a busy round on each page view (R12 review). A generous cap keeps the
	 * page bounded while comfortably covering a real round (≤ 3 entries/type/writer).
	 *
	 * @var int
	 */
	public const MAX_ENTRIES = 500;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/uitdaging-besonderhede` dynamic block.
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
	 * Build the `WP_Query` args for a round's entries — published readable bydraes
	 * carrying the round term, newest-first. Pure.
	 *
	 * A non-positive id (no current uitdaging) yields a match-nothing query
	 * (`post__in [0]`) rather than an unfiltered listing of every bydrae.
	 *
	 * @param int $uitdaging_id The producing uitdaging post id.
	 * @return array<string, mixed>
	 */
	public static function entriesQueryArgs( int $uitdaging_id ): array {
		$args = array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'posts_per_page'      => self::MAX_ENTRIES,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( $uitdaging_id <= 0 ) {
			$args['post__in'] = array( 0 );

			return $args;
		}

		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded round-slug facet on a published CPT; the AD-7 server-rendered entries list, no search plugin.
		$args['tax_query'] = array(
			array(
				'taxonomy' => Taxonomies::UITDAGINGSRONDTE,
				'field'    => 'slug',
				'terms'    => ChallengeRound::slugFor( $uitdaging_id ),
			),
		);

		return $args;
	}

	/**
	 * Whether the round is still open — `now` within the inclusive end-of-day-SAST
	 * deadline window (AD-3). Pure delegate to the single SAST boundary source.
	 *
	 * @param \DateTimeInterface      $deadline The stored deadline instant.
	 * @param \DateTimeInterface|null $now      The instant to test (defaults to now).
	 * @return bool
	 */
	public static function isOpen( \DateTimeInterface $deadline, ?\DateTimeInterface $now = null ): bool {
		return Sast::isThroughEndOfDay( $deadline, $now );
	}

	/**
	 * The sluitingsdatum status line. Pure — Terms + escaping only.
	 *
	 * Renders nothing without a formatted deadline (so a challenge with no deadline
	 * meta omits the line rather than showing a malformed date).
	 *
	 * @param string $formatted_deadline The SAST-formatted deadline, or ''.
	 * @param bool   $is_open            Whether the round is still open.
	 * @return string
	 */
	public static function statusHtml( string $formatted_deadline, bool $is_open ): string {
		if ( '' === $formatted_deadline ) {
			return '';
		}

		$state_key   = $is_open ? 'uitdaging_oop' : 'uitdaging_gesluit';
		$state_class = $is_open ? 'is-oop' : 'is-gesluit';

		return '<p class="ink-uitdaging__status ' . esc_attr( $state_class ) . '">'
			. '<span class="ink-uitdaging__sluitingsdatum-etiket">' . esc_html( Terms::label( 'sluitingsdatum' ) ) . ': </span>'
			. '<time class="ink-uitdaging__sluitingsdatum">' . esc_html( $formatted_deadline ) . '</time>'
			. ' · <span class="ink-uitdaging__toestand">' . esc_html( Terms::label( $state_key ) ) . '</span>'
			. '</p>';
	}

	/**
	 * The inskrywings (entries) list. Pure — Terms + escaping only.
	 *
	 * Renders a graceful empty state (no `<ul>`/`<li>` shell) when the round has no
	 * linked entries.
	 *
	 * @param list<array{title:string, permalink:string}> $entries The entries.
	 * @return string
	 */
	public static function entriesHtml( array $entries ): string {
		$heading = '<h2 class="ink-uitdaging__inskrywings-titel">' . esc_html( Terms::label( 'inskrywing_plural' ) ) . '</h2>';

		if ( array() === $entries ) {
			/* translators: %s: the entries (inskrywings) label. */
			$empty = sprintf( __( 'Geen %s nie.', 'ink-core' ), Terms::label( 'inskrywing_plural' ) );

			return '<div class="ink-uitdaging__inskrywings-blok">' . $heading
				. '<p class="ink-uitdaging__leeg">' . esc_html( $empty ) . '</p></div>';
		}

		$html = '<div class="ink-uitdaging__inskrywings-blok">' . $heading . '<ul class="ink-uitdaging__inskrywings">';

		foreach ( $entries as $entry ) {
			$html .= '<li class="ink-uitdaging__inskrywing">'
				. '<a class="ink-uitdaging__inskrywing-skakel" href="' . esc_url( $entry['permalink'] ) . '">'
				. esc_html( $entry['title'] ) . '</a></li>';
		}

		return $html . '</ul></div>';
	}

	/**
	 * Compose the block shell from the (pre-rendered) status line + entries list. Pure.
	 *
	 * @param string $status_html  The status line markup (may be '').
	 * @param string $entries_html The entries-list markup.
	 * @return string
	 */
	public static function toHtml( string $status_html, string $entries_html ): string {
		return '<section class="ink-uitdaging ink-uitdaging__besonderhede">' . $status_html . $entries_html . '</section>';
	}

	/**
	 * Block render callback. Resolves the current uitdaging, reads the deadline, queries
	 * its entries, and composes. Thin impure shell over the pure helpers above.
	 *
	 * @return string
	 */
	public static function render(): string {
		$uitdaging_id = (int) get_the_ID();

		if ( $uitdaging_id <= 0 || PostTypes::UITDAGING !== get_post_type( $uitdaging_id ) ) {
			return '';
		}

		$raw      = Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_DEADLINE, true ) );
		$deadline = Deadline::parse( $raw );

		$status_html = '';

		if ( $deadline instanceof \DateTimeImmutable ) {
			$status_html = self::statusHtml( Deadline::format( $deadline ), self::isOpen( $deadline ) );
		}

		$query   = new \WP_Query( self::entriesQueryArgs( $uitdaging_id ) );
		$entries = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$entries[] = array(
					'title'     => get_the_title( $post ),
					'permalink' => (string) get_permalink( $post ),
				);
			}
		}

		return self::toHtml( $status_html, self::entriesHtml( $entries ) );
	}
}
