<?php
/**
 * Community-response-type enum.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Structured community-response type — the fixed value set for
 * Gemeenskapsreaksies (Epic 7): Lof / Insig / Voorstel.
 *
 * Kernel-owned shared value type. The backing string is the persisted DB value
 * (lowercase Afrikaans); never duplicate these literals across the codebase. No
 * response/engagement logic lives here; it is a value type only.
 *
 * @package Ink\Core
 */
enum ResponseType: string {

	case Lof      = 'lof';
	case Insig    = 'insig';
	case Voorstel = 'voorstel';
}
