<?php
/**
 * The single reusable guard/coercion for untrusted scalar input (Epic-2 debt #2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The ONE source of truth for "is this untrusted value safe to coerce, and how".
 *
 * Across ink-core, every value that arrives from outside our own code — a
 * `get_*_meta()` read, a stored option, a cart/row array, a REST payload — was
 * historically guarded with the same inline idiom before being handed to a
 * sanitiser:
 *
 *     if ( ! is_scalar( $value ) ) { ... }            // bail / default
 *     is_scalar( $raw ) ? max( 0, (int) $raw ) : 0;   // coerce with a floor
 *
 * A non-scalar (array/object) reaching `(int)`/`(string)`/`sanitize_*` is the
 * recurring Epic-2 bug class: at best a PHP notice, at worst a silently wrong cast.
 * This concentrates that idiom into one tested, WordPress-free utility so the guard
 * is defined ONCE and every call site reads the same intent.
 *
 * SCOPE: this is for values we read ourselves (meta/option/array/REST). Raw
 * superglobal type-guards (`! is_scalar( $_POST[ $k ] )`) deliberately stay inline —
 * the WordPress phpcs `ValidatedSanitizedInput` sniff recognises `is_scalar()` there
 * as a blessed guard but cannot see through a helper call, so routing `$_POST`/`$_GET`
 * through this class would raise false-positive sanitisation errors.
 *
 * Lives in the Kernel (the shared base every module already depends on, and which
 * depends on nothing — deptrac.yaml) so all domains reuse it with zero new
 * cross-module edges. It is a pure value utility — no WordPress state, no business
 * logic — exactly like {@see Sast} and the Kernel enums. WordPress-specific
 * sanitising (`sanitize_text_field`, `rest_sanitize_boolean`, `absint`, unslashing)
 * stays at the call site; this only answers "safe to coerce?" and "coerce to what".
 *
 * @package Ink\Core
 */
final class Scalar {

	/**
	 * Whether a value is safe to pass to a scalar coercion / sanitiser.
	 *
	 * The canonical predicate: arrays and objects are NOT safe (they cannot be
	 * meaningfully cast and must be treated as absent); every PHP scalar is.
	 *
	 * @param mixed $value Any incoming value.
	 * @return bool True when the value is a scalar.
	 */
	public static function safe( $value ): bool {
		return is_scalar( $value );
	}

	/**
	 * Coerce a value to a string, falling back to a default when not safe.
	 *
	 * No trimming or sanitising is applied — that is the caller's responsibility
	 * (e.g. `wp_unslash` + `sanitize_text_field`). This only guarantees a string.
	 *
	 * @param mixed  $value    Any incoming value.
	 * @param string $fallback Returned when the value is non-scalar.
	 * @return string The value cast to string, or the fallback.
	 */
	public static function asString( $value, string $fallback = '' ): string {
		return is_scalar( $value ) ? (string) $value : $fallback;
	}

	/**
	 * Coerce a value to an int, falling back to a default when not safe.
	 *
	 * @param mixed $value    Any incoming value.
	 * @param int   $fallback Returned when the value is non-scalar.
	 * @return int The value cast to int, or the fallback.
	 */
	public static function asInt( $value, int $fallback = 0 ): int {
		return is_scalar( $value ) ? (int) $value : $fallback;
	}

	/**
	 * Coerce a value to a non-negative int, floored at `$min`.
	 *
	 * A non-scalar value, or a stored negative (manual DB edit, a legacy
	 * pre-`absint` value), can never leak below the floor.
	 *
	 * @param mixed $value Any incoming value.
	 * @param int   $min   The inclusive floor (default 0).
	 * @return int The coerced value, never below `$min`.
	 */
	public static function asNonNegativeInt( $value, int $min = 0 ): int {
		return is_scalar( $value ) ? max( $min, (int) $value ) : $min;
	}
}
