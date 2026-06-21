<?php
/**
 * Reaction-type enum.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Reaction type — the fixed value set for line-highlight reactions (Epic 7).
 *
 * Kernel-owned shared value type. The backing string is the persisted DB value
 * (lowercase Afrikaans / snake_case); never duplicate these literals across the
 * codebase. No reaction/engagement logic lives here; it is a value type only.
 *
 * @package Ink\Core
 */
enum Reaction: string {

	case Hartjie = 'hartjie';
	case DuimOp  = 'duim_op';
	case Wow     = 'wow';
}
