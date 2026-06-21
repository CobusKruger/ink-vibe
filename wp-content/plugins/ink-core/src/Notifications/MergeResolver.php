<?php
/**
 * Name-merge resolver for form-letter text.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves the single greeting-line merge token in form-letter text (AD-9, Story 1.12).
 *
 * Deliberately NOT a template engine: it substitutes only the whitelisted
 * greeting-line token(s) — `{skrywer}` (e.g. `Beste {skrywer}, …`) — from a
 * send-time context. No conditionals, no loops, no nesting, no WYSIWYG. The
 * constraint IS the decision (AD-9, owner §2.1); anything richer is out of scope.
 *
 * Unknown/unprovided tokens are LEFT LITERAL: a visible `{skrywer}` in the
 * output signals a misconfigured caller (one that failed to supply the writer
 * name) rather than silently producing a broken greeting like `Beste , …`.
 * Callers MUST supply `skrywer` for any greeting-bearing template.
 *
 * @package Ink\Core
 */
final class MergeResolver {

	/**
	 * The whitelisted merge tokens. A single greeting-line token today (AD-9);
	 * the list is the explicit, closed set — not an open variable surface.
	 *
	 * @var list<string>
	 */
	public const TOKENS = array( 'skrywer' );

	/**
	 * Substitute the whitelisted tokens present in $context into $body.
	 *
	 * @param string                    $body    Stored form-letter text.
	 * @param array<string, int|string> $context Merge values, e.g. `array( 'skrywer' => 'Jan' )`.
	 * @return string The merged text (unprovided tokens left literal).
	 */
	public function resolve( string $body, array $context ): string {
		foreach ( self::TOKENS as $token ) {
			if ( ! array_key_exists( $token, $context ) ) {
				continue;
			}

			$body = str_replace( '{' . $token . '}', (string) $context[ $token ], $body );
		}

		return $body;
	}
}
