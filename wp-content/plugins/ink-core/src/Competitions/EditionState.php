<?php
/**
 * Where a competition edition is in its life.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

defined( 'ABSPATH' ) || exit;

/**
 * The derived state of an {@see Edition} (`05-kompetisies` §14.2). Never stored: always
 * computed by {@see Edition::state()}. Not persisted, so the backing strings are only
 * stable identifiers for CSS and tests.
 *
 * @package Ink\Core
 */
enum EditionState: string {

	case Announced = 'announced';
	case Open      = 'open';
	case Closed    = 'closed';
	case Results   = 'results';
	case Archive   = 'archive';

	/**
	 * Whether the edition's own announcement (and categories) shows: only while it is announced
	 * or open. Every other state shows the generic annual info (K3).
	 *
	 * @return bool
	 */
	public function isAnnouncement(): bool {
		return self::Announced === $this || self::Open === $this;
	}
}
