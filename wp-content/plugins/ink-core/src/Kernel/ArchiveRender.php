<?php
/**
 * Shared archive-render primitives — Story 11.1 (Epic-10 carry-forward).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The genuinely-shared, stable, pure primitives behind the INK server-rendered
 * archives — extracted so the Opleiding hub (Story 11.1) is not a third copy of
 * {@see \Ink\Discovery\WorksArchive} + {@see \Ink\Library\Archive} (the Epic-10
 * retrospective carry-forward).
 *
 * Each archive keeps its own CSS prefix, query vars and `queryArgs()`/`toHtml()`
 * shape; only the truly-identical pieces live here: the active-markable control
 * link ({@see self::pill()}), the prev/next pager ({@see self::pagination()}) and
 * the defensive query-var→GET request reads. Uses only WP core (`esc_*`,
 * `add_query_arg`, `get_query_var`, `filter_input`, `absint`, `sanitize_*`) — zero
 * `Ink\*` dependencies, so it belongs in Kernel (every module already depends on
 * Kernel; no new deptrac edge).
 *
 * @package Ink\Core
 */
final class ArchiveRender {

	/**
	 * One control link, marked active when selected. Pure — escaping only.
	 *
	 * The href is escaped HERE via {@see esc_url()} (the single escape point — a
	 * caller can never pass an unescaped URL through), folding in the Epic-10
	 * review's deferred `pill()` pre-escape hardening. Callers therefore pass the
	 * RAW url (the `add_query_arg`/`remove_query_arg` result), not a pre-escaped one.
	 *
	 * @param string $url       The (unescaped) href.
	 * @param string $label     The (unescaped) label.
	 * @param bool   $is_active Whether this is the active option.
	 * @param string $base      The base CSS class.
	 * @return string
	 */
	public static function pill( string $url, string $label, bool $is_active, string $base ): string {
		$class = $base . ( $is_active ? ' is-active' : '' );

		return '<a class="' . esc_attr( $class ) . '"'
			. ( $is_active ? ' aria-current="true"' : '' )
			. ' href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}

	/**
	 * Prev/next archive-browse links — only when more than one page. Pure.
	 *
	 * Builds `<nav class="{prefix}__blaai">` with `{prefix}__vorige`/`{prefix}__volgende`
	 * links that set `{paged_var}` while preserving the rest of the query string.
	 *
	 * @param int    $paged      The current page (clamped to >= 1).
	 * @param int    $max_pages  The total number of pages.
	 * @param string $css_prefix The archive's CSS prefix (e.g. `ink-opleiding`).
	 * @param string $paged_var  The paged query var (e.g. `opleiding_bladsy`).
	 * @return string
	 */
	public static function pagination( int $paged, int $max_pages, string $css_prefix, string $paged_var ): string {
		$paged = max( 1, $paged );

		if ( $max_pages <= 1 ) {
			return '';
		}

		$html = '<nav class="' . esc_attr( $css_prefix . '__blaai' ) . '">';

		if ( $paged > 1 ) {
			$html .= '<a class="' . esc_attr( $css_prefix . '__vorige' ) . '" href="'
				. esc_url( (string) add_query_arg( $paged_var, $paged - 1 ) ) . '">'
				. esc_html__( 'Vorige', 'ink-core' ) . '</a>';
		}

		if ( $paged < $max_pages ) {
			$html .= '<a class="' . esc_attr( $css_prefix . '__volgende' ) . '" href="'
				. esc_url( (string) add_query_arg( $paged_var, $paged + 1 ) ) . '">'
				. esc_html__( 'Volgende', 'ink-core' ) . '</a>';
		}

		return $html . '</nav>';
	}

	/**
	 * Read an absint browse input (custom query var, falling back to GET).
	 *
	 * Read-only navigation (idempotent GET — listings never mutate state), so no
	 * nonce applies; `filter_input()` reads GET without touching the superglobal and
	 * sanitises to digits before `absint()`.
	 *
	 * @param string $key      The query-var / GET key.
	 * @param int    $fallback Returned when the input is absent.
	 * @return int
	 */
	public static function requestInt( string $key, int $fallback ): int {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key, FILTER_SANITIZE_NUMBER_INT );
		}

		if ( null === $value || false === $value || '' === $value ) {
			return $fallback;
		}

		return absint( $value );
	}

	/**
	 * Read a sanitised key-style browse input (query var, falling back to GET).
	 *
	 * @param string $key The query-var / GET key.
	 * @return string The sanitised value, or '' when absent.
	 */
	public static function requestKey( string $key ): string {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_key( $value ) : '';
	}

	/**
	 * Read a sanitised free-text browse input (query var, falling back to GET).
	 *
	 * @param string $key The query-var / GET key.
	 * @return string The sanitised value, or '' when absent.
	 */
	public static function requestText( string $key ): string {
		$value = get_query_var( $key, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, $key );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_text_field( $value ) : '';
	}
}
