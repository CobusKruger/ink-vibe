<?php
/**
 * Form-letter / notification template store (WP options).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Options-backed store for form-letter / notification templates (AD-9, Story 1.12).
 *
 * Holds, per registered template/event: the subject + body text, a per-event
 * send on/off toggle, and an optional randomized message list (R7). Reads fall
 * through to the Afrikaans-source defaults carried by the registered
 * {@see Template} definitions when no admin override exists (the §14.15
 * pattern). All persistence is via the WP options API under a single
 * `ink_`-prefixed option — NO custom table, NO raw SQL (AD-9: low-volume,
 * admin-edited config values are a natural fit for options).
 *
 * NFR-1 note: the stored override values are admin-authored Afrikaans text and
 * are a leak vector the build-time `.mo` + page-crawl scan does NOT see. They
 * are in scope for the standing English-leak scan (scan the option values
 * and/or enforce Afrikaans at the authoring boundary — built in Story 17.4 /
 * Epic 18, not here).
 *
 * The write methods are pure persistence: callers (the deferred admin settings
 * screen / a future `ink/v1` route) MUST capability-gate writes at that
 * boundary — capability checks belong there, not in this repository.
 *
 * @package Ink\Core
 */
final class TemplateStore {

	/**
	 * The single options row holding all template overrides, keyed by template key.
	 */
	public const OPTION = 'ink_notifications_templates';

	/**
	 * Registered template definitions (Afrikaans-source defaults), keyed by key.
	 *
	 * @var array<string, Template>
	 */
	private array $templates = array();

	/**
	 * Register a template definition (its Afrikaans-source defaults).
	 */
	public function register( Template $template ): void {
		$this->templates[ $template->key ] = $template;
	}

	/**
	 * Whether a template definition has been registered under $key.
	 */
	public function isRegistered( string $key ): bool {
		return isset( $this->templates[ $key ] );
	}

	/**
	 * Resolved subject for $key: admin override, else the Afrikaans default, else ''.
	 */
	public function subject( string $key ): string {
		$override = $this->overrides( $key );

		if ( isset( $override['subject'] ) ) {
			return (string) $override['subject'];
		}

		$definition = $this->definition( $key );

		return $definition instanceof Template ? $definition->defaultSubject : '';
	}

	/**
	 * Resolved body for $key: admin override, else the Afrikaans default, else ''.
	 */
	public function body( string $key ): string {
		$override = $this->overrides( $key );

		if ( isset( $override['body'] ) ) {
			return (string) $override['body'];
		}

		$definition = $this->definition( $key );

		return $definition instanceof Template ? $definition->defaultBody : '';
	}

	/**
	 * Whether $key sends. Override wins; else the template default; else false
	 * (an unregistered/unconfigured event is fail-safe OFF — never sends).
	 */
	public function isEnabled( string $key ): bool {
		$override = $this->overrides( $key );

		if ( array_key_exists( 'enabled', $override ) ) {
			return (bool) $override['enabled'];
		}

		$definition = $this->definition( $key );

		return $definition instanceof Template ? $definition->defaultEnabled : false;
	}

	/**
	 * Resolved randomized message list for $key (override wins, else default, else []).
	 *
	 * @return list<string>
	 */
	public function messages( string $key ): array {
		$override = $this->overrides( $key );

		if ( isset( $override['messages'] ) && is_array( $override['messages'] ) ) {
			return array_values( array_map( 'strval', $override['messages'] ) );
		}

		$definition = $this->definition( $key );

		return $definition instanceof Template ? $definition->defaultMessages : array();
	}

	/**
	 * Persist a body override. MUST be capability-gated at the call site.
	 */
	public function setBody( string $key, string $body ): void {
		$this->write( $key, array( 'body' => $body ) );
	}

	/**
	 * Persist a subject override. MUST be capability-gated at the call site.
	 */
	public function setSubject( string $key, string $subject ): void {
		$this->write( $key, array( 'subject' => $subject ) );
	}

	/**
	 * Persist a send-toggle override. MUST be capability-gated at the call site.
	 */
	public function setEnabled( string $key, bool $enabled ): void {
		$this->write( $key, array( 'enabled' => $enabled ) );
	}

	/**
	 * Persist a randomized message list override. MUST be capability-gated at the call site.
	 *
	 * @param list<string> $messages
	 */
	public function setMessages( string $key, array $messages ): void {
		$this->write( $key, array( 'messages' => array_values( array_map( 'strval', $messages ) ) ) );
	}

	/**
	 * The registered definition for $key, or null.
	 */
	private function definition( string $key ): ?Template {
		return $this->templates[ $key ] ?? null;
	}

	/**
	 * The stored override row for $key (empty array when none).
	 *
	 * @return array<string, mixed>
	 */
	private function overrides( string $key ): array {
		$all = get_option( self::OPTION, array() );

		if ( ! is_array( $all ) ) {
			return array();
		}

		$row = $all[ $key ] ?? array();

		return is_array( $row ) ? $row : array();
	}

	/**
	 * Merge $changes into $key's stored override row and persist.
	 *
	 * @param array<string, mixed> $changes
	 */
	private function write( string $key, array $changes ): void {
		$all = get_option( self::OPTION, array() );

		if ( ! is_array( $all ) ) {
			$all = array();
		}

		$row = ( isset( $all[ $key ] ) && is_array( $all[ $key ] ) ) ? $all[ $key ] : array();

		$all[ $key ] = array_merge( $row, $changes );

		update_option( self::OPTION, $all );
	}
}
