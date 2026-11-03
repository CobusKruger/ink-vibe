<?php
/**
 * The read model for one round's results — WP1 (`01-uitslae` §5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Engagement\Api as Engagement;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The results page's only data path. It reads {@see Placements::forRound()} today, and the
 * winner importer's store plugs in behind it through {@see ROWS_FILTER}, for the old wins
 * that have no post. Nothing above this class reads pool keys, so the Gradering fix changes
 * nothing here but the shape {@see Pools::splitPoolKey()} hands back.
 *
 * Citations go only through {@see ModeratorFeedback::feedbackFor()}: the opt-in gate (D6).
 *
 * @package Ink\Core
 */
final class RoundResults {

	/**
	 * Filter on a round's rows; the importer's seam, and the dev fixture's.
	 *
	 * @var string
	 */
	public const ROWS_FILTER = 'ink_uitslae_rows';

	/**
	 * How many opening lines of a poem stand as its excerpt: Lovable shows four.
	 *
	 * @var int
	 */
	public const POEM_EXCERPT_LINES = 4;

	/**
	 * A round's result rows.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{category:string, tier:string, position:int, post_id:int, title:string, author:string, user_id:int, excerpt:string, citation:string}>
	 */
	public static function forRound( int $uitdaging_id ): array {
		$rows     = array();
		$feedback = new ModeratorFeedback();

		foreach ( Placements::forRound( $uitdaging_id ) as $pool => $placed ) {
			list( $tier, $category ) = Pools::splitPoolKey( (string) $pool );

			foreach ( $placed as $row ) {
				$post = get_post( (int) $row['id'] );

				if ( ! $post instanceof \WP_Post ) {
					continue;
				}

				$rows[] = self::row(
					$category,
					$tier,
					$post,
					(int) $row['rank'],
					(string) get_the_author_meta( 'display_name', (int) $post->post_author ),
					$feedback->feedbackFor( (int) $post->ID )[0] ?? ''
				);
			}
		}

		$filtered = apply_filters( self::ROWS_FILTER, $rows, $uitdaging_id );

		return is_array( $filtered ) ? $filtered : $rows;
	}

	/**
	 * One row from a placed post.
	 *
	 * @param string   $category The category (post type).
	 * @param string   $tier     The Gradering pool.
	 * @param \WP_Post $post     The winning post.
	 * @param int      $position The stored position.
	 * @param string   $author   The writer's display name.
	 * @param string   $citation The opted-in citation, or ''.
	 * @return array{category:string, tier:string, position:int, post_id:int, title:string, author:string, user_id:int, excerpt:string, citation:string}
	 */
	private static function row( string $category, string $tier, \WP_Post $post, int $position, string $author, string $citation ): array {
		return array(
			'category' => $category,
			'tier'     => $tier,
			'position' => $position,
			'post_id'  => (int) $post->ID,
			'title'    => (string) get_the_title( $post ),
			'author'   => $author,
			'user_id'  => (int) $post->post_author,
			'excerpt'  => self::excerptFor( $post ),
			'citation' => $citation,
		);
	}

	/**
	 * A win's excerpt: a hand-written excerpt, else a poem's opening lines with their breaks,
	 * else the {@see SinglePage::excerptFor()} rule. Reads post data.
	 *
	 * @param \WP_Post $post The winning post.
	 * @return string
	 */
	public static function excerptFor( \WP_Post $post ): string {
		if ( PostTypes::GEDIG === $post->post_type && ! has_excerpt( $post ) ) {
			return Engagement::poemOpening( (string) $post->post_content, self::POEM_EXCERPT_LINES );
		}

		return SinglePage::excerptFor( $post );
	}

	/**
	 * A round's period label, e.g. "Oktober 2026": from its title, verified against its post
	 * date, through {@see Cadence}. '' for an annual round with no stored deadline.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	public static function periodFor( int $uitdaging_id ): string {
		$close = Cadence::closeFor( $uitdaging_id );

		return null === $close ? '' : Cadence::periodLabelFor( $uitdaging_id, $close );
	}

	/**
	 * A round's month name, e.g. "Oktober"; '' when it has none, or for an annual round.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	public static function monthFor( int $uitdaging_id ): string {
		$parts = explode( ' ', self::periodFor( $uitdaging_id ) );

		return 2 === count( $parts ) ? $parts[0] : '';
	}

	/**
	 * A round's theme: its field, else its title, entities decoded. Nothing is stripped from
	 * the title: that would be new parsing (`01-uitslae` §4).
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	public static function themeFor( int $uitdaging_id ): string {
		$theme = trim( Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_THEME, true ) ) );

		return html_entity_decode( '' !== $theme ? $theme : (string) get_the_title( $uitdaging_id ), ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * The date a round's results were announced: its newest announcement's post date, or ''.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	public static function announcedOn( int $uitdaging_id ): string {
		$announcements = self::announcements( $uitdaging_id );

		if ( array() === $announcements ) {
			return '';
		}

		$timestamp = get_post_timestamp( $announcements[0] );

		return false === $timestamp ? '' : (string) wp_date( 'j F Y', $timestamp );
	}

	/**
	 * Whether a round has results: a linked announcement or at least one row
	 * (`01-uitslae` §4). Without them, its results URL is a 404.
	 *
	 * @param int $uitdaging_id The round.
	 * @return bool
	 */
	public static function hasResults( int $uitdaging_id ): bool {
		if ( $uitdaging_id <= 0 ) {
			return false;
		}

		return array() !== self::announcements( $uitdaging_id ) || array() !== self::forRound( $uitdaging_id );
	}

	/**
	 * A round's published announcement posts, newest first. Round 68708 has two.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<int>
	 */
	public static function announcements( int $uitdaging_id ): array {
		$ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'meta_key'         => WinnersPost::UITDAGING_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- low-volume lookup, at most a few posts per round.
				'meta_value'       => (string) $uitdaging_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);

		return is_array( $ids ) ? array_map( 'intval', $ids ) : array();
	}

	/**
	 * A round's judges' letter, one string per paragraph. Pure.
	 *
	 * @param string $brief The stored {@see FieldSets::UITDAGING_BRIEF}.
	 * @return list<string>
	 */
	public static function letterParagraphs( string $brief ): array {
		$parts = preg_split( '/\R\s*\R/u', trim( $brief ) );

		return array_values( array_filter( array_map( 'trim', false === $parts ? array() : $parts ), static fn ( string $p ): bool => '' !== $p ) );
	}

	/**
	 * A round's honourable mentions from the field's `Titel | Skrywer` lines. Pure.
	 *
	 * @param string $stored The stored {@see FieldSets::UITDAGING_VERMELDINGS}.
	 * @return list<array{title:string, author:string}>
	 */
	public static function parseMentions( string $stored ): array {
		$mentions = array();
		$lines    = preg_split( '/\R/u', $stored );

		foreach ( false === $lines ? array() : $lines as $line ) {
			$parts = array_map( 'trim', explode( '|', $line, 2 ) );

			if ( '' !== $parts[0] ) {
				$mentions[] = array(
					'title'  => $parts[0],
					'author' => $parts[1] ?? '',
				);
			}
		}

		return $mentions;
	}

	/**
	 * The stored mentions of a round.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{title:string, author:string}>
	 */
	public static function mentionsFor( int $uitdaging_id ): array {
		return self::parseMentions( Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_VERMELDINGS, true ) ) );
	}

	/**
	 * The judges' letter of a round.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<string>
	 */
	public static function letterFor( int $uitdaging_id ): array {
		return self::letterParagraphs( Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_BRIEF, true ) ) );
	}
}
