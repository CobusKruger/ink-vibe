<?php
/**
 * Transactional-notification dispatcher.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Composes and dispatches form-letter notifications (AD-9, Story 1.12).
 *
 * Notifications is a DOWNSTREAM event consumer (AD-6 `ink/{module}/{event}`
 * subscribers + Action Scheduler fan-out), not a cross-domain write path: a
 * later consumer subscribes an event (e.g. `ink/tier/promoted`) and calls
 * {@see Api::send()} → here. It must never `use`/call `Ink\Entitlement` or
 * `Ink\Tiers` (THE conflation rule — it listens to action *strings* from both
 * domains; the AD-8 Deptrac invariant stays green).
 *
 * Transactional email is sent via `wp_mail` (Woo/BP keep their own templated
 * mail). Every send is gated by the per-event toggle in the {@see TemplateStore}.
 *
 * @package Ink\Core
 */
final class Notifier {

	/**
	 * @param TemplateStore $store The form-letter store (toggle + text + lists).
	 * @param MergeResolver $merge The greeting-line name-merge resolver.
	 */
	public function __construct(
		private readonly TemplateStore $store,
		private readonly MergeResolver $merge = new MergeResolver(),
	) {}

	/**
	 * Compose + dispatch the transactional email for $key, gated by the send toggle.
	 *
	 * Returns false WITHOUT sending when the toggle is off or the recipient is
	 * empty; otherwise resolves the stored subject + body, applies the name-merge,
	 * and dispatches via `wp_mail`.
	 *
	 * @param string                    $key     Template/event key.
	 * @param string                    $to      Recipient email address.
	 * @param array<string, int|string> $context Merge context, e.g. `array( 'skrywer' => 'Jan' )`.
	 * @return bool True if `wp_mail` accepted the message; false if suppressed or it failed.
	 */
	public function send( string $key, string $to, array $context = array() ): bool {
		if ( '' === $to || ! $this->store->isEnabled( $key ) ) {
			return false;
		}

		$subject = $this->merge->resolve( $this->store->subject( $key ), $context );
		$body    = $this->merge->resolve( $this->store->body( $key ), $context );

		return (bool) wp_mail( $to, $subject, $body );
	}

	/**
	 * Return a random message from $key's stored list (the R7 mechanism).
	 *
	 * Empty list → ''. Single item → that item. Uses `wp_rand()` (WP's seeded
	 * RNG) so selection is unbiased and test-mockable.
	 */
	public function randomMessage( string $key ): string {
		$messages = $this->store->messages( $key );
		$count    = count( $messages );

		if ( 0 === $count ) {
			return '';
		}

		if ( 1 === $count ) {
			return $messages[0];
		}

		return $messages[ wp_rand( 0, $count - 1 ) ];
	}
}
