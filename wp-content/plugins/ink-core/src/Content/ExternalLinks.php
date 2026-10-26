<?php
/**
 * Opens links that leave the site in a new tab.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

defined( 'ABSPATH' ) || exit;

/**
 * Members paste links into their writing; a reader who follows one should not
 * lose their place on INK. Every `<a href>` in post content that points to
 * another host gets `target="_blank"` and `rel="noopener noreferrer"`. Relative
 * links, same-host links, anchors, `mailto:` and `tel:` are left alone, and a
 * link that already sets a target keeps it.
 *
 * @package Ink\Core
 */
final class ExternalLinks {

	/**
	 * Register the content filter.
	 */
	public function register(): void {
		add_filter( 'the_content', array( $this, 'filter' ), 20 );
	}

	/**
	 * `the_content` callback.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public function filter( string $content ): string {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return self::rewrite( $content, is_string( $host ) ? $host : '' );
	}

	/**
	 * Add target and rel to the external anchors in a fragment. Pure.
	 *
	 * @param string $html      The HTML fragment.
	 * @param string $site_host The site's own host name.
	 * @return string
	 */
	public static function rewrite( string $html, string $site_host ): string {
		if ( false === stripos( $html, '<a' ) ) {
			return $html;
		}

		$out = preg_replace_callback(
			'/<a\s[^>]*>/i',
			static function ( array $m ) use ( $site_host ): string {
				return self::rewriteTag( $m[0], $site_host );
			},
			$html
		);

		return is_string( $out ) ? $out : $html;
	}

	/**
	 * Rewrite one opening `<a ...>` tag. Pure.
	 *
	 * @param string $tag       The opening tag.
	 * @param string $site_host The site's own host name.
	 * @return string
	 */
	private static function rewriteTag( string $tag, string $site_host ): string {
		if ( 1 !== preg_match( '/\shref\s*=\s*(?:"([^"]*)"|\'([^\']*)\')/i', $tag, $href ) ) {
			return $tag;
		}

		$url = html_entity_decode( '' !== $href[1] ? $href[1] : ( $href[2] ?? '' ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! self::isExternal( $url, $site_host ) ) {
			return $tag;
		}

		if ( 1 !== preg_match( '/\starget\s*=/i', $tag ) ) {
			$tag = (string) preg_replace( '/>$/', ' target="_blank">', $tag );
		}

		if ( 1 !== preg_match( '/\srel\s*=/i', $tag ) ) {
			$tag = (string) preg_replace( '/>$/', ' rel="noopener noreferrer">', $tag );
		}

		return $tag;
	}

	/**
	 * Whether a URL leaves the site. Pure.
	 *
	 * @param string $url       The href value.
	 * @param string $site_host The site's own host name.
	 */
	public static function isExternal( string $url, string $site_host ): bool {
		$url = trim( $url );

		if ( 1 !== preg_match( '#^(?:https?:)?//#i', $url ) ) {
			return false;
		}

		$host = parse_url( str_starts_with( $url, '//' ) ? 'https:' . $url : $url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			return false;
		}

		return 0 !== strcasecmp( preg_replace( '/^www\./i', '', $host ) ?? $host, preg_replace( '/^www\./i', '', $site_host ) ?? $site_host );
	}
}
