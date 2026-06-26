<?php
/**
 * Per-bydrae-type submission behaviour (the type selector model) — Story 6.2.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * The content rules that differ per bydrae type in the Skryf form (FR-17).
 *
 * The Skryf selector lets a skrywer pick gedig / storie / artikel; the feedback
 * they get differs by form: a **gedig** is counted in lines AND words (verse has
 * line structure that matters); **prose** (storie / artikel) is counted in words
 * only. This is the single source for that rule — the view-model carries it to the
 * theme so the browser counter and the (future) server-side validation agree.
 *
 * Pure value logic, no WordPress state. Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class ContentType {

	/**
	 * Counter modes: verse (lines + words) vs prose (words only).
	 */
	public const MODE_LINES_AND_WORDS = 'lines_words';
	public const MODE_WORDS           = 'words';

	/**
	 * The counter mode for a bydrae type.
	 *
	 * A gedig is the only verse form among the submittable types, so it alone
	 * counts lines; every other type (storie / artikel — and any future prose
	 * type) counts words only.
	 *
	 * @param string $type The bydrae CPT slug.
	 * @return string One of {@see MODE_LINES_AND_WORDS} / {@see MODE_WORDS}.
	 */
	public static function counterMode( string $type ): string {
		return PostTypes::GEDIG === $type ? self::MODE_LINES_AND_WORDS : self::MODE_WORDS;
	}

	/**
	 * Whether a bydrae type's counter includes a line count.
	 *
	 * @param string $type The bydrae CPT slug.
	 * @return bool True for a gedig (verse), false for prose.
	 */
	public static function countsLines( string $type ): bool {
		return self::MODE_LINES_AND_WORDS === self::counterMode( $type );
	}
}
