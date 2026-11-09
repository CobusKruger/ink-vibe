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
 * Immutable definition of one email event / form-letter template (AD-9).
 *
 * Code defines the event (this record); the admin screen only edits its copy
 * (subject, body, on/off, per-term toggles). The defaults carried here are the
 * **Afrikaans source** text, already wrapped in `__( '…', 'ink-core' )` as literal
 * strings by the registering consumer (the foundation never sees the literals).
 * Admin overrides live in the options store and win over these defaults.
 *
 * `kind` decides whether a member can opt out: `system` events (account,
 * lidmaatskap, results) always send; `social` events honour the member's per-
 * `category` opt-out (see {@see Preferences}).
 *
 * @package Ink\Core
 */
final class Template {

	/** Always sent; members cannot opt out. */
	public const KIND_SYSTEM = 'system';

	/** Member can opt out per {@see $category}. */
	public const KIND_SOCIAL = 'social';

	/**
	 * Define one template's Afrikaans-source defaults and admin metadata.
	 *
	 * @param string       $key             Unique template/event key (`ink_`-scoped, snake_case).
	 * @param string       $defaultSubject  Afrikaans-source default subject (sentence case).
	 * @param string       $defaultBody     Afrikaans-source default body (plain text or basic HTML); may contain merge tokens.
	 * @param bool         $defaultEnabled  Whether this event sends by default.
	 * @param list<string> $defaultMessages Optional randomized message list (the R7 receipt case).
	 * @param string       $kind            {@see KIND_SYSTEM} or {@see KIND_SOCIAL}.
	 * @param string       $group           Admin-screen group heading (Afrikaans), e.g. "Lidmaatskap".
	 * @param string       $label           Admin-screen event name (Afrikaans).
	 * @param list<string> $tokens          Merge tokens the event supplies, without braces, e.g. `array( 'skrywer', 'werk' )`.
	 * @param string       $category        Social events: opt-out category slug (e.g. `reaksies`).
	 * @param string       $categoryLabel   Social events: the category's member-facing label (Afrikaans).
	 * @param list<int>    $terms           Lidmaatskap term lengths (months) that get their own on/off toggle, e.g. `array( 6, 12 )`.
	 */
	public function __construct(
		public readonly string $key,
		public readonly string $defaultSubject = '',
		public readonly string $defaultBody = '',
		public readonly bool $defaultEnabled = false,
		public readonly array $defaultMessages = array(),
		public readonly string $kind = self::KIND_SYSTEM,
		public readonly string $group = '',
		public readonly string $label = '',
		public readonly array $tokens = array( 'skrywer' ),
		public readonly string $category = '',
		public readonly string $categoryLabel = '',
		public readonly array $terms = array(),
	) {}

	/**
	 * Whether a member may opt out of this event.
	 */
	public function isSocial(): bool {
		return self::KIND_SOCIAL === $this->kind && '' !== $this->category;
	}
}
