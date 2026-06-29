<?php
/**
 * Competition-cadence enum.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * Competition cadence — the fixed value set for a `uitdaging`'s
 * `ink_uitdaging_cadence` meta (Story 12B.1, R9): `maandeliks` (monthly — the
 * default) / `jaarliks` (annual).
 *
 * Kernel-owned (NOT in the Challenges module) so that BOTH the `Ink\Content`
 * meta registrar/field-set ({@see \Ink\Content\FieldSets}) and the
 * `Ink\Challenges` period derivation ({@see \Ink\Challenges\Cadence}) can read
 * it from the shared Kernel without an inter-module dependency edge — Content
 * may depend only on Kernel (deptrac), so a `Challenges`-owned value type would
 * be unreachable from Content. Mirrors the {@see Tier} / {@see ResponseType}
 * Kernel-owned-value-set precedent.
 *
 * The backing string is the persisted DB value (lowercase Afrikaans); never
 * duplicate these literals across the codebase. No period/scheduling logic lives
 * here (that is {@see \Ink\Challenges\Cadence}); it is a value type only, and
 * presentation (the Afrikaans admin option labels) stays OUT of the enum — those
 * are admin chrome on the {@see \Ink\Content\FieldSets} field definition, exactly
 * as {@see Tier} keeps its grade labels in the I18n layer.
 *
 * @package Ink\Core
 */
enum CadenceType: string {

	case Maandeliks = 'maandeliks';
	case Jaarliks   = 'jaarliks';

	/**
	 * The single-source default cadence for an unset/legacy `uitdaging`.
	 *
	 * Monthly — every existing round predates the cadence switch and must keep
	 * its monthly period derivation unchanged. Mirrors the `register_meta`
	 * string default (`''`), which {@see self::fromMeta()} also folds to this.
	 */
	public static function default(): self {
		return self::Maandeliks;
	}

	/**
	 * Coerce a stored meta value to a cadence case, defaulting to monthly.
	 *
	 * Any value that is not exactly a known backing string — `''`, `null`, a
	 * legacy/absent meta, or junk — resolves to {@see self::default()} so a
	 * round is never accidentally treated as annual. Pure.
	 *
	 * @param mixed $value The stored meta value (typically a string).
	 * @return self
	 */
	public static function fromMeta( mixed $value ): self {
		return is_string( $value ) ? ( self::tryFrom( $value ) ?? self::default() ) : self::default();
	}

	/**
	 * The backing values, in declaration order — the single source for any
	 * validation/`enum`-arg surface, so the literals are never duplicated.
	 *
	 * @return list<string> e.g. `['maandeliks', 'jaarliks']`.
	 */
	public static function values(): array {
		return array_map( static fn ( self $type ): string => $type->value, self::cases() );
	}
}
