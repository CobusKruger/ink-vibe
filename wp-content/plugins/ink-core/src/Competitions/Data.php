<?php
/**
 * What `/kompetisies/` shows — WP5 (`05-kompetisies` §3.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Read directly at render, from the two post types. No options and no CSV: an edit on the
 * site is what changes the page. The choices are pure statics over the read models.
 *
 * @package Ink\Core
 */
final class Data {

	/**
	 * The most listings the directory reads.
	 *
	 * @var int
	 */
	public const MAX_LISTINGS = 200;

	/**
	 * The most editions read: one a year, plus a few old ones.
	 *
	 * @var int
	 */
	private const MAX_EDITIONS = 100;

	/**
	 * The current edition, or null. Pure over {@see self::editions()}.
	 *
	 * @return Edition|null
	 */
	public static function current(): ?Edition {
		return self::pickCurrent( self::editions() );
	}

	/**
	 * The past galas, newest first.
	 *
	 * @return list<GalaYear>
	 */
	public static function pastGalas(): array {
		return self::pickPastGalas( self::editions() );
	}

	/**
	 * The past gala of one year that has photos, or null: what `/kompetisies/gala/<jaar>/` shows.
	 *
	 * @param int $year The year.
	 * @return GalaYear|null
	 */
	public static function galaYear( int $year ): ?GalaYear {
		return self::pickGalaYear( self::pastGalas(), $year );
	}

	/**
	 * The gala of this year among these, only when it has photos. Pure.
	 *
	 * @param list<GalaYear> $galas The past galas.
	 * @param int            $year  The year.
	 * @return GalaYear|null
	 */
	public static function pickGalaYear( array $galas, int $year ): ?GalaYear {
		foreach ( $galas as $gala ) {
			if ( $year === $gala->year && array() !== $gala->photoIds ) {
				return $gala;
			}
		}

		return null;
	}

	/**
	 * The published listings, newest publish date first.
	 *
	 * @return list<Listing>
	 */
	public static function listings(): array {
		$out = array();

		foreach ( self::publishedPosts( PostTypes::EKSTERNE_KOMPETISIE, self::MAX_LISTINGS ) as $post ) {
			$out[] = new Listing( (int) $post->ID, (string) get_the_title( $post ), self::valuesOf( (int) $post->ID, FieldSets::EKSTERNE_PREFIX ) );
		}

		return $out;
	}

	/**
	 * The current edition among these: a description, then the highest year. Pure.
	 *
	 * @param list<Edition> $editions The editions.
	 * @return Edition|null
	 */
	public static function pickCurrent( array $editions ): ?Edition {
		$current = null;

		foreach ( $editions as $edition ) {
			if ( $edition->isCurrentEdition() && ( null === $current || $edition->year() > $current->year() ) ) {
				$current = $edition;
			}
		}

		return $current;
	}

	/**
	 * The past galas among these, highest year first. A later editor adding photos to this
	 * year's post makes this year's chip appear; nothing else is needed. Pure.
	 *
	 * @param list<Edition> $editions The editions.
	 * @return list<GalaYear>
	 */
	public static function pickPastGalas( array $editions ): array {
		$galas = array();

		foreach ( $editions as $edition ) {
			if ( $edition->hasPastGala() ) {
				$galas[] = GalaYear::fromEdition( $edition );
			}
		}

		usort( $galas, static fn ( GalaYear $a, GalaYear $b ): int => $b->year <=> $a->year );

		return $galas;
	}

	/**
	 * Every published edition.
	 *
	 * @return list<Edition>
	 */
	private static function editions(): array {
		$out = array();

		foreach ( self::publishedPosts( PostTypes::KOMPETISIE, self::MAX_EDITIONS ) as $post ) {
			$out[] = new Edition( (int) $post->ID, (string) get_the_title( $post ), self::valuesOf( (int) $post->ID, FieldSets::KOMPETISIE_PREFIX ) );
		}

		return $out;
	}

	/**
	 * Published posts of a type, newest first.
	 *
	 * @param string $post_type The post type.
	 * @param int    $limit     The cap.
	 * @return list<\WP_Post>
	 */
	private static function publishedPosts( string $post_type, int $limit ): array {
		$query = new \WP_Query(
			array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => $limit,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		return array_values( array_filter( $query->posts, static fn ( $post ): bool => $post instanceof \WP_Post ) );
	}

	/**
	 * A post's field values by key suffix.
	 *
	 * @param int    $post_id The post.
	 * @param string $prefix  The key prefix.
	 * @return array<string, string>
	 */
	public static function valuesOf( int $post_id, string $prefix ): array {
		$meta = get_post_meta( $post_id );
		$out  = array();

		foreach ( is_array( $meta ) ? $meta : array() as $key => $values ) {
			if ( str_starts_with( (string) $key, $prefix ) ) {
				$out[ substr( (string) $key, strlen( $prefix ) ) ] = Scalar::asString( is_array( $values ) ? ( $values[0] ?? '' ) : $values );
			}
		}

		return $out;
	}
}
