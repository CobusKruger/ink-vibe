<?php
/**
 * Form-letter / notification template definition (value object).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable definition of one form-letter / notification template (AD-9, Story 1.12).
 *
 * A consumer (R2 12A.4 winners post, R3 5.10 promotion email, R5 4.8 lifecycle
 * emails, R7 9.11 receipt notification) registers a `Template` with the
 * {@see TemplateStore} via {@see Api::registerTemplate()}. The defaults carried
 * here are the **Afrikaans source** text (gettext source language; `ink-core`
 * ships no English `.mo`, so they survive a staff member's forced-English admin
 * locale — §14.15 / Story 1.10). Admin overrides live in the options store and
 * win over these defaults.
 *
 * Gettext contract (decision 5a): the consumer passes these Afrikaans defaults
 * already wrapped in `__( '…', 'ink-core' )` as literal strings at registration
 * (see {@see Api::registerTemplate()}); the foundation does not wrap them.
 *
 * This is a plain config record — NOT a template engine. The only dynamic part
 * is the single greeting-line merge token resolved by {@see MergeResolver}.
 *
 * @package Ink\Core
 */
final class Template {

	/**
	 * Define one template's Afrikaans-source defaults.
	 *
	 * @param string       $key             Unique template/event key (`ink_`-scoped, snake_case), e.g. `tier_promotion`.
	 * @param string       $defaultSubject  Afrikaans-source default email subject (sentence case).
	 * @param string       $defaultBody     Afrikaans-source default body; may contain the `{skrywer}` merge token.
	 * @param bool         $defaultEnabled  Whether this event sends by default.
	 * @param list<string> $defaultMessages Optional randomized message list (the R7 receipt-notification case).
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $defaultSubject = '',
		public readonly string $defaultBody = '',
		public readonly bool $defaultEnabled = false,
		public readonly array $defaultMessages = array(),
	) {}
}
