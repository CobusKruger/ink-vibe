<?php
/**
 * One year under "Evenings past" — WP5 (`05-kompetisies` §3.5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * A past gala: a year chip, its highlight, its five feature photos and the whole gallery. The
 * feature photos carry their own captions; the gallery's captions live on the attachments.
 *
 * @package Ink\Core
 */
final class GalaYear {

	/**
	 * Build a gala year.
	 *
	 * @param int                                $year      The year.
	 * @param string                             $highlight The highlight, or ''.
	 * @param list<int>                          $photoIds  Every photo, in gallery order.
	 * @param list<array{id:int, caption:string}> $featured  The photos shown on the page, in slot order.
	 * @param Edition|null                       $edition   The post this gala belongs to, for its details.
	 */
	public function __construct(
		public readonly int $year,
		public readonly string $highlight,
		public readonly array $photoIds,
		public readonly array $featured = array(),
		public readonly ?Edition $edition = null,
	) {
	}

	/**
	 * Whether this year's results are published, so its chip and photos page link to them.
	 *
	 * @return bool
	 */
	public function hasResults(): bool {
		return null !== $this->edition && $this->edition->hasPublishedResults();
	}

	/**
	 * The gala year of an edition. Pure.
	 *
	 * @param Edition $edition The edition.
	 * @return self
	 */
	public static function fromEdition( Edition $edition ): self {
		return new self( $edition->year(), $edition->field( 'gala_hoogtepunt' ), $edition->photoIds(), $edition->featuredPhotos(), $edition );
	}
}
