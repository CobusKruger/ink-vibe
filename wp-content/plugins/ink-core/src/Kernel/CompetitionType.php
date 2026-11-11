<?php
/**
 * Competition-type enum.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The fixed value set for a `kompetisie`'s `ink_kompetisie_tipe` meta (`05-kompetisies` §14.1):
 * `jaarliks` (the big annual competition: announcement, gala, results, photos) or `vennoot`
 * (run with an outside organisation: announcement and results, no gala).
 *
 * Kernel-owned so {@see \Ink\Content\FieldSets} (Content may depend only on Kernel) and
 * {@see \Ink\Competitions\Edition} read one source. NOT {@see CadenceType}: a competition is
 * never a challenge, and `jaarliks` here names a type of competition, not a period rule.
 * Labels are admin chrome on the field definition, not here.
 *
 * @package Ink\Core
 */
enum CompetitionType: string {

	case Jaarliks = 'jaarliks';
	case Vennoot  = 'vennoot';

	/**
	 * The type of a post that has none stored: every pre-existing edition is annual.
	 */
	public static function default(): self {
		return self::Jaarliks;
	}

	/**
	 * Coerce a stored meta value to a type; '', null and junk give {@see self::default()}. Pure.
	 *
	 * @param mixed $value The stored meta value.
	 * @return self
	 */
	public static function fromMeta( mixed $value ): self {
		return is_string( $value ) ? ( self::tryFrom( $value ) ?? self::default() ) : self::default();
	}

	/**
	 * The backing values, in declaration order.
	 *
	 * @return list<string>
	 */
	public static function values(): array {
		return array_map( static fn ( self $type ): string => $type->value, self::cases() );
	}

	/**
	 * Whether this type has a gala (its band, fields and photos).
	 *
	 * @return bool
	 */
	public function hasGala(): bool {
		return self::Jaarliks === $this;
	}
}
