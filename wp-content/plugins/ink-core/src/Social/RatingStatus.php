<?php
/**
 * Reader-rating moderation status enum — Story 9.6 (FR-42).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * The moderation state of a reader rating/review.
 *
 * A closed value set (the enum rule) — the persisted DB value is the lowercase
 * Afrikaans string; never duplicate these literals across the codebase. A newly
 * submitted rating is {@see self::Hangend} (held) and is NEVER shown publicly
 * until a moderator (Story 18.4) approves it to {@see self::Goedgekeur}. The
 * public Skrywerprofiel aggregate + reviews read `goedgekeur` only — so with no
 * approval path yet (pre-18.4) the surface is simply empty, never leaking an
 * unmoderated review (POPIA).
 *
 * @package Ink\Core
 */
enum RatingStatus: string {

	case Hangend    = 'hangend';
	case Goedgekeur = 'goedgekeur';
	case Verwerp    = 'verwerp';
}
