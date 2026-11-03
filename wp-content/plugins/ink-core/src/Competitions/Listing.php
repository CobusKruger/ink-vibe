<?php
/**
 * One external competition in the directory — WP5 (`05-kompetisies` §3.3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;

defined( 'ABSPATH' ) || exit;

/**
 * Read model of an `eksterne_kompetisie` post.
 *
 * @package Ink\Core
 */
final class Listing {

	/**
	 * Build a listing.
	 *
	 * @param int                   $postId The post.
	 * @param string                $title  The post title: the competition's name.
	 * @param array<string, string> $values Field values by key suffix, e.g. `fooi`.
	 */
	public function __construct(
		public readonly int $postId,
		public readonly string $title,
		private readonly array $values,
	) {
	}

	/**
	 * One field, trimmed; '' when blank.
	 *
	 * @param string $suffix The key suffix.
	 * @return string
	 */
	public function field( string $suffix ): string {
		return trim( $this->values[ $suffix ] ?? '' );
	}

	/**
	 * A blank fee means free.
	 *
	 * @return bool
	 */
	public function isFree(): bool {
		return '' === $this->field( 'fooi' );
	}

	/**
	 * The genre, from the closed set.
	 *
	 * @return string
	 */
	public function genre(): string {
		return FieldSets::sanitizeGenre( $this->field( 'genre' ) );
	}
}
