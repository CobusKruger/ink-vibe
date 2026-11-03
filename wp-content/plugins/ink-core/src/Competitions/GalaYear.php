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
 * A past gala: a year chip, its highlight, its photos. Captions live on the attachments.
 *
 * @package Ink\Core
 */
final class GalaYear {

	/**
	 * Build a gala year.
	 *
	 * @param int       $year      The year.
	 * @param string    $highlight The highlight, or ''.
	 * @param list<int> $photoIds  The photos, in gallery order.
	 */
	public function __construct(
		public readonly int $year,
		public readonly string $highlight,
		public readonly array $photoIds,
	) {
	}

	/**
	 * The gala year of an edition. Pure.
	 *
	 * @param Edition $edition The edition.
	 * @return self
	 */
	public static function fromEdition( Edition $edition ): self {
		return new self( $edition->year(), $edition->field( 'gala_hoogtepunt' ), $edition->photoIds() );
	}
}
