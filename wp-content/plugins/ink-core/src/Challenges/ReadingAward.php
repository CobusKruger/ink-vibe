<?php
/**
 * The reading-page winner aside — WP2 (N7; `02-lees-wenner`).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\ChallengeRound;
use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;
use Ink\I18n\Terms;
use Ink\Kernel\Scalar;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * The `ink/lees-wenner` block: on a placed gedig, storie or artikel, a link to the round's
 * results and, with the author's opt-in, the moderator's citation.
 *
 * {@see ModeratorFeedback::feedbackFor()} is the only read of the citation; it holds the
 * opt-in gate (D6). The aside's purpose is its link, so a win whose round can't be resolved
 * renders nothing (`02-lees-wenner` §4). {@see WinnerBanner} stays as it is: its markup isn't
 * this design.
 *
 * DIVERGENCE (accepted, `02-lees-wenner` §8): the label says "algehele wenner" / "wenner" (D11);
 * the citation and its attribution are absent without opt-in (D6).
 *
 * @package Ink\Core
 */
final class ReadingAward {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/lees-wenner';

	private const ICON_AWARD = '<path d="m15.477 12.89 1.515 8.526a.5.5 0 0 1-.81.47l-3.58-2.687a1 1 0 0 0-1.197 0l-3.586 2.686a.5.5 0 0 1-.81-.469l1.514-8.526"/><circle cx="12" cy="8" r="6"/>';

	/**
	 * Register the block. Runs inside the module's `init` dispatch.
	 */
	public function register(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type( self::BLOCK, array( 'render_callback' => array( self::class, 'render' ) ) );
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = (int) get_the_ID();
		$rank    = Placements::placementFor( $post_id );

		if ( ! Placements::isValidRank( $rank ) ) {
			return '';
		}

		$round = self::roundOf( $post_id );

		if ( $round <= 0 ) {
			return '';
		}

		return self::toHtml(
			ResultsRoute::urlFor( $round ),
			self::label(
				$rank,
				(string) get_post_type( $post_id ),
				Scalar::asString( get_post_meta( $post_id, Entry::GRADERING_META_KEY, true ) ),
				RoundResults::periodFor( $round )
			),
			( new ModeratorFeedback() )->feedbackFor( $post_id )[0] ?? ''
		);
	}

	/**
	 * The round a work entered, from its `uitdagingsrondte` term; 0 with none.
	 *
	 * @param int $post_id The work.
	 * @return int
	 */
	public static function roundOf( int $post_id ): int {
		$terms = get_the_terms( $post_id, Taxonomies::UITDAGINGSRONDTE );

		foreach ( is_array( $terms ) ? $terms : array() as $term ) {
			$round = ChallengeRound::uitdagingIdFromSlug( (string) $term->slug );

			if ( null !== $round && PostTypes::UITDAGING === get_post_type( $round ) ) {
				return $round;
			}
		}

		return 0;
	}

	/**
	 * The link's label: `{placement} · {type} · {Maand jaar}-uitdaging`. Pure (Terms).
	 *
	 * @param int    $rank      The stored position.
	 * @param string $post_type The work's post type.
	 * @param string $gradering The entry-time Gradering, or ''.
	 * @param string $period    The round's period.
	 * @return string
	 */
	public static function label( int $rank, string $post_type, string $gradering, string $period ): string {
		$parts = array( Placements::placementLabel( $rank ), self::typeLabel( $post_type, $gradering ) );

		if ( '' !== $period ) {
			/* translators: %s: the round's period, e.g. "Oktober 2026". */
			$parts[] = sprintf( __( '%s-uitdaging', 'ink-core' ), $period );
		}

		return implode( ' · ', array_filter( $parts, static fn ( string $part ): bool => '' !== $part ) );
	}

	/**
	 * The type part: a Gedig carries its grade, Meester showing as Goud (D11). Pure (Terms).
	 *
	 * @param string $post_type The work's post type.
	 * @param string $gradering The entry-time Gradering, or ''.
	 * @return string
	 */
	private static function typeLabel( string $post_type, string $gradering ): string {
		if ( PostTypes::STORIE === $post_type ) {
			return Terms::label( 'storie_plural' );
		}

		if ( PostTypes::ARTIKEL === $post_type ) {
			return Terms::label( 'artikel_plural' );
		}

		$plural = Terms::label( 'gedig_plural' );
		$tier   = Tier::tryFrom( $gradering );

		return null === $tier
			? $plural
			: Terms::label( $tier->competitionTier()->value ) . ' ' . mb_strtolower( $plural );
	}

	/**
	 * The aside. Pure.
	 *
	 * @param string $url      The round's results URL.
	 * @param string $label    The link's label.
	 * @param string $citation The opted-in citation, or ''.
	 * @return string
	 */
	public static function toHtml( string $url, string $label, string $citation ): string {
		$citation_html = '' === $citation ? '' : '<p class="ink-lees-wenner__kritiek" data-audit-id="reading-award-citation">' . esc_html( $citation ) . '</p>'
			. '<p class="ink-stat-label ink-lees-wenner__deur" data-audit-id="reading-award-citation-by">' . esc_html__( '— Terugvoer van die moderator', 'ink-core' ) . '</p>';

		return '<aside class="ink-card--award-aside ink-lees-wenner" data-audit-id="reading-award">'
			. '<a class="ink-eyebrow ink-eyebrow--card ink-eyebrow--gold ink-lees-wenner__skakel" data-audit-id="reading-award-link" href="' . esc_url( $url ) . '">'
			. '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon" data-audit-id="reading-award-icon" aria-hidden="true" focusable="false">' . self::ICON_AWARD . '</svg>'
			. esc_html( $label )
			. '</a>'
			. $citation_html
			. '</aside>';
	}
}
