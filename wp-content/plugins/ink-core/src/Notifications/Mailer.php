<?php
/**
 * Email rendering: body to branded HTML.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Pure rendering helpers for notification emails (basic HTML, AD-9 amendment).
 *
 * Bodies are either legacy plain text or basic HTML (the admin edits the latter,
 * sanitised on save with {@see allowedHtml()}). Merge values are HTML-escaped in
 * HTML mode so a work title or display name can never inject markup.
 *
 * @package Ink\Core
 */
final class Mailer {

	/**
	 * `wp_mail` headers for an HTML message.
	 *
	 * @var list<string>
	 */
	public const HEADERS = array( 'Content-Type: text/html; charset=UTF-8' );

	/**
	 * The tags an admin may use in a body (for `wp_kses` on save).
	 *
	 * @return array<string, array<string, bool>>
	 */
	public static function allowedHtml(): array {
		return array(
			'a'      => array(
				'href'  => true,
				'title' => true,
			),
			'p'      => array(),
			'br'     => array(),
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'ul'     => array(),
			'ol'     => array(),
			'li'     => array(),
		);
	}

	/**
	 * Merge $template with $context into an HTML fragment.
	 *
	 * @param string                    $template Stored body (plain text or basic HTML).
	 * @param array<string, int|string> $context  Merge values.
	 * @param MergeResolver             $merge    The resolver.
	 */
	public static function renderBody( string $template, array $context, MergeResolver $merge ): string {
		if ( 1 === preg_match( '/<\s*(p|br|a|strong|b|em|i|ul|ol|li)\b/i', $template ) ) {
			$escaped = array();

			foreach ( $context as $token => $value ) {
				$escaped[ $token ] = htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' );
			}

			return $merge->resolve( $template, $escaped );
		}

		// Legacy plain text: merge, escape, then paragraphs / line breaks.
		$text  = htmlspecialchars( $merge->resolve( $template, $context ), ENT_QUOTES, 'UTF-8' );
		$paras = preg_split( '/\R{2,}/', trim( $text ) );
		$html  = '';

		foreach ( false === $paras ? array() : $paras as $para ) {
			$html .= '<p>' . nl2br( $para, false ) . '</p>';
		}

		return $html;
	}

	/**
	 * Wrap a rendered body in the standard INK email frame.
	 *
	 * @param string $bodyHtml   The rendered body fragment.
	 * @param string $footerHtml Optional extra footer HTML (e.g. the unsubscribe line); trusted, already escaped.
	 */
	public static function wrap( string $bodyHtml, string $footerHtml = '' ): string {
		return '<!doctype html><html><body style="margin:0;padding:24px;background:#f5f3ef;font-family:Arial,Helvetica,sans-serif;color:#222;">'
			. '<div style="max-width:560px;margin:0 auto;background:#ffffff;border-top:4px solid #EA4015;padding:24px;line-height:1.5;font-size:15px;">'
			. $bodyHtml
			. '</div>'
			. '<div style="max-width:560px;margin:12px auto 0;font-size:12px;color:#777777;text-align:center;">INK'
			. ( '' !== $footerHtml ? '<br>' . $footerHtml : '' )
			. '</div></body></html>';
	}
}
