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
 * Resolves `{token}` placeholders in form-letter text (AD-9).
 *
 * Deliberately NOT a template engine: plain substitution of the tokens the
 * sending code supplies in its context (`{skrywer}`, `{werk}`, `{skakel}` …).
 * No conditionals, loops, nesting or WYSIWYG — the constraint IS the decision.
 *
 * Tokens not present in the context are LEFT LITERAL: a visible `{werk}` in the
 * output signals a misconfigured caller rather than a silently broken sentence.
 *
 * @package Ink\Core
 */
final class MergeResolver {

	/**
	 * The greeting token every event supplies by default.
	 *
	 * @var list<string>
	 */
	public const TOKENS = array( 'skrywer' );

	/**
	 * Substitute every token present in $context into $body.
	 *
	 * @param string                    $body    Stored form-letter text.
	 * @param array<string, int|string> $context Merge values, e.g. `array( 'skrywer' => 'Jan' )`.
	 * @return string The merged text (tokens absent from the context left literal).
	 */
	public function resolve( string $body, array $context ): string {
		foreach ( $context as $token => $value ) {
			$token = (string) $token;

			if ( 1 !== preg_match( '/^[a-z_]+$/', $token ) ) {
				continue;
			}

			$body = str_replace( '{' . $token . '}', (string) $value, $body );
		}

		return $body;
	}
}
