<?php
/**
 * Pure helpers for the ordered repeater input in {@see FieldSets} (K4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * A repeater is an ordered list of rows, each a flat map of column key to scalar. It is stored
 * as ONE post-meta string of JSON (so it fits the scalar-only FieldSets convention, reads through
 * {@see \Ink\Competitions\Data::valuesOf()} like any field, and needs no extra table). Order is
 * the list order. Nothing here touches WordPress except {@see Repeater::encode()}.
 *
 * The posted shape is `key[0][column]`, `key[1][column]`, ...; the marker row `key[__present]`
 * tells the saver the input was on the screen, so removing every row clears the list.
 *
 * @package Ink\Core
 */
final class Repeater {

	/**
	 * The posted marker that says the repeater was rendered (an all-removed list posts nothing else).
	 *
	 * @var string
	 */
	public const PRESENT = '__present';

	/**
	 * The most rows one repeater keeps.
	 *
	 * @var int
	 */
	public const MAX_ROWS = 100;

	/**
	 * The rows in a posted array or a stored/REST JSON string, in order, with only scalar cells and
	 * no all-blank rows. Pure. Cells are returned unsanitised: that is the column's job.
	 *
	 * @param mixed $input A posted array, a JSON string, or junk.
	 * @return list<array<string, scalar>>
	 */
	public static function rows( $input ): array {
		if ( is_string( $input ) ) {
			$decoded = json_decode( $input, true );
			$input   = is_array( $decoded ) ? $decoded : array();
		}

		if ( ! is_array( $input ) ) {
			return array();
		}

		$rows = array();

		foreach ( $input as $index => $row ) {
			if ( self::PRESENT === (string) $index || ! is_array( $row ) ) {
				continue;
			}

			$cells = array();

			foreach ( $row as $column => $cell ) {
				if ( is_scalar( $cell ) && is_string( $column ) ) {
					$cells[ $column ] = $cell;
				}
			}

			if ( array() !== array_filter( $cells, static fn ( $cell ): bool => '' !== trim( (string) $cell ) ) ) {
				$rows[] = $cells;
			}

			if ( count( $rows ) >= self::MAX_ROWS ) {
				break;
			}
		}

		return $rows;
	}

	/**
	 * Sanitise rows against columns, keep those the validator accepts. Pure given pure callbacks.
	 *
	 * @param mixed                                $input    A posted array or a JSON string.
	 * @param array<string, callable>              $columns  Column key to its sanitise callback.
	 * @param callable(array<string, mixed>): bool $accept   Whether a sanitised row is kept.
	 * @return list<array<string, mixed>>
	 */
	public static function clean( $input, array $columns, callable $accept ): array {
		$out = array();

		foreach ( self::rows( $input ) as $row ) {
			$clean = array();

			foreach ( $columns as $column => $sanitize ) {
				$clean[ $column ] = call_user_func( $sanitize, $row[ $column ] ?? '' );
			}

			if ( $accept( $clean ) ) {
				$out[] = $clean;
			}
		}

		return $out;
	}

	/**
	 * The stored form of these rows: JSON with Unicode and slashes left readable, '' for none.
	 * The caller must `wp_slash()` it before `update_post_meta()`, which strips one level.
	 *
	 * @param list<array<string, mixed>> $rows The rows.
	 * @return string
	 */
	public static function encode( array $rows ): string {
		if ( array() === $rows ) {
			return '';
		}

		$json = wp_json_encode( $rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );

		return is_string( $json ) ? $json : '';
	}

	/**
	 * The rows of a stored value, in order. Pure.
	 *
	 * @param string $stored The meta value.
	 * @return list<array<string, scalar>>
	 */
	public static function decode( string $stored ): array {
		return self::rows( $stored );
	}
}
