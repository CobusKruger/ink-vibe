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
	 * What the hero and the categories show: the current edition, else the default
	 * announcement an editor wrote, else the one built into the code. Never null, so the page
	 * always has a hero (owner, 2026-11-08).
	 *
	 * @return Edition
	 */
	public static function shown(): Edition {
		return self::pickShown( self::editions() );
	}

	/**
	 * The edition to show among these. Pure.
	 *
	 * @param list<Edition> $editions The editions.
	 * @return Edition
	 */
	public static function pickShown( array $editions ): Edition {
		$current = self::pickCurrent( $editions );

		if ( null !== $current ) {
			return $current;
		}

		foreach ( $editions as $edition ) {
			if ( $edition->isDefault() ) {
				return $edition;
			}
		}

		return Edition::builtIn();
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
			if ( ! $edition->isDefault() && $edition->hasPastGala() ) {
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
	public static function editions(): array {
		$out = array();

		foreach ( self::publishedPosts( PostTypes::KOMPETISIE, self::MAX_EDITIONS ) as $post ) {
			$out[] = new Edition( (int) $post->ID, (string) get_the_title( $post ), self::valuesOf( (int) $post->ID, FieldSets::KOMPETISIE_PREFIX ) );
		}

		$lacking = array();

		foreach ( $out as $edition ) {
			if ( array() === $edition->photoIds() ) {
				$lacking[] = $edition->postId;
			}
		}

		return array() === $lacking ? $out : self::withAttached( $out, self::attachedTo( $lacking ) );
	}

	/**
	 * Editions with no gallery list take the photos uploaded to their own post (owner,
	 * 2026-11-08): an editor uploads on the edit screen and nothing else is needed. A gallery
	 * list, as the imported galas have, wins. Pure.
	 *
	 * @param list<Edition>          $editions The editions.
	 * @param array<int, list<int>>  $attached Attachment ids by post, in display order.
	 * @return list<Edition>
	 */
	public static function withAttached( array $editions, array $attached ): array {
		$out = array();

		foreach ( $editions as $edition ) {
			$ids   = $attached[ $edition->postId ] ?? array();
			$out[] = array() !== $edition->photoIds() || array() === $ids
				? $edition
				: new Edition( $edition->postId, $edition->title, array( 'gala_fotos' => implode( ',', $ids ) ) + $edition->values() );
		}

		return $out;
	}

	/**
	 * The images and clips uploaded to these posts, by filename (the attachment title), one query.
	 *
	 * @param list<int> $post_ids The posts.
	 * @return array<int, list<int>>
	 */
	private static function attachedTo( array $post_ids ): array {
		$rows = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'post_parent__in'  => $post_ids,
				'post_mime_type'   => 'image,video',
				'posts_per_page'   => -1,
				'orderby'          => array(
					'title' => 'ASC',
					'ID'    => 'ASC',
				),
				'fields'           => 'id=>parent',
				'no_found_rows'    => true,
				'suppress_filters' => false,
			)
		);
		$out  = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			// `id=>parent` rows are bare objects with ID and post_parent, not WP_Post.
			if ( is_object( $row ) && isset( $row->ID, $row->post_parent ) ) {
				$out[ (int) $row->post_parent ][] = (int) $row->ID;
			}
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
