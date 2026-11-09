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
 * subscribers), not a cross-domain write path. It must never `use`/call
 * `Ink\Entitlement` or `Ink\Tiers` (THE conflation rule).
 *
 * Two entry points:
 *  - {@see send()} — recipient is an email address (system mail, legacy callers).
 *  - {@see fire()} — recipient is a user id; resolves address + name, honours the
 *    member's opt-out for `social` events, and queues social mail through Action
 *    Scheduler so a fan-out never blocks a request.
 *
 * Mail is HTML (basic tags) via `wp_mail`; every send is gated by the per-event
 * toggle in the {@see TemplateStore}.
 *
 * @package Ink\Core
 */
final class Notifier {

	/**
	 * Action Scheduler hook that performs a queued social delivery.
	 */
	public const DELIVER_HOOK = 'ink_notifications_deliver';

	/**
	 * Action Scheduler group (shared with the other INK scheduled actions).
	 */
	private const AS_GROUP = 'ink';

	/**
	 * Wire the notifier to its template store, resolver and (optional) send log.
	 *
	 * @param TemplateStore $store The form-letter store (toggle + text + lists).
	 * @param MergeResolver $merge The merge resolver.
	 * @param SendLog|null  $log   Send log; null = no logging.
	 */
	public function __construct(
		private readonly TemplateStore $store,
		private readonly MergeResolver $merge = new MergeResolver(),
		private readonly ?SendLog $log = null,
	) {}

	/**
	 * Compose + dispatch the email for $key to an address, gated by the send toggle.
	 *
	 * @param string                    $key        Template/event key.
	 * @param string                    $to         Recipient email address.
	 * @param array<string, int|string> $context    Merge context, e.g. `array( 'skrywer' => 'Jan' )`.
	 * @param string                    $footerHtml Optional trusted footer HTML (the unsubscribe line).
	 * @return bool True if `wp_mail` accepted the message; false if suppressed or it failed.
	 */
	public function send( string $key, string $to, array $context = array(), string $footerHtml = '' ): bool {
		if ( '' === $to || ! $this->store->isEnabled( $key ) ) {
			return false;
		}

		return $this->dispatch( $key, $to, $context, $footerHtml );
	}

	/**
	 * Send $key to $to IGNORING the on/off toggle — the admin "toets" button.
	 *
	 * @param string                    $key     Template/event key.
	 * @param string                    $to      Recipient email address.
	 * @param array<string, int|string> $context Merge context.
	 */
	public function preview( string $key, string $to, array $context = array() ): bool {
		if ( '' === $to || ! $this->store->isRegistered( $key ) ) {
			return false;
		}

		return $this->dispatch( $key, $to, $context, '' );
	}

	/**
	 * Fire event $key for a user: resolve recipient, honour opt-out, send (queued for social).
	 *
	 * @param string                    $key     Event key (a registered {@see Template}).
	 * @param int                       $user_id The recipient.
	 * @param array<string, int|string> $context Merge context (scalars only); `skrywer` defaults to the user's name.
	 * @return bool True if the send was made or queued; false if suppressed.
	 */
	public function fire( string $key, int $user_id, array $context = array() ): bool {
		$template = $this->store->get( $key );

		if ( ! $template instanceof Template || ! $this->store->isEnabled( $key ) ) {
			return false;
		}

		if ( $template->isSocial() ) {
			if ( Preferences::isOptedOut( $user_id, $template->category ) ) {
				return false;
			}

			if ( function_exists( 'as_enqueue_async_action' ) ) {
				as_enqueue_async_action( self::DELIVER_HOOK, array( $key, $user_id, $context ), self::AS_GROUP );

				return true;
			}
		}

		return $this->deliver( $key, $user_id, $context );
	}

	/**
	 * Perform the delivery for {@see fire()} (directly, or from the Action Scheduler job).
	 *
	 * @param string                    $key     Event key.
	 * @param int                       $user_id The recipient.
	 * @param array<string, int|string> $context Merge context.
	 */
	public function deliver( string $key, int $user_id, array $context = array() ): bool {
		$template = $this->store->get( $key );

		if ( ! $template instanceof Template ) {
			return false;
		}

		$user = get_userdata( $user_id );

		if ( ! ( $user instanceof \WP_User ) || '' === (string) $user->user_email ) {
			return false;
		}

		// Re-checked here: a queued job may run after the member opted out.
		if ( $template->isSocial() && Preferences::isOptedOut( $user_id, $template->category ) ) {
			return false;
		}

		if ( ! array_key_exists( 'skrywer', $context ) ) {
			$name               = (string) $user->display_name;
			$context['skrywer'] = '' !== $name ? $name : (string) $user->user_login;
		}

		$footer = $template->isSocial() ? Preferences::footerHtml( $user_id, $template->category, $template->categoryLabel ) : '';

		return $this->send( $key, (string) $user->user_email, $context, $footer );
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

	/**
	 * Merge, render and hand to `wp_mail`; log the outcome.
	 *
	 * @param string                    $key        Template/event key.
	 * @param string                    $to         Recipient.
	 * @param array<string, int|string> $context    Merge context.
	 * @param string                    $footerHtml Trusted footer HTML.
	 */
	private function dispatch( string $key, string $to, array $context, string $footerHtml ): bool {
		$subject = $this->merge->resolve( $this->store->subject( $key ), $context );
		$html    = Mailer::wrap( Mailer::renderBody( $this->store->body( $key ), $context, $this->merge ), $footerHtml );

		$ok = (bool) wp_mail( $to, $subject, $html, Mailer::HEADERS );

		$this->log?->record( $key, $to, $ok );

		return $ok;
	}
}
