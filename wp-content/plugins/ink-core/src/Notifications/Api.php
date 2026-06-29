<?php
/**
 * Notifications module public facade.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Notifications module facade — the sole public cross-module surface (AD-1, Story 1.12).
 *
 * Other modules reach the form-letter / notification capability only through
 * this facade. Most delivery is event-driven: a consumer subscribes an
 * `ink/{module}/{event}` action (AD-6) and, in the handler, calls
 * {@see Api::send()} (or {@see Api::randomMessage()}). Consumers register their
 * Afrikaans-source templates via {@see Api::registerTemplate()}.
 *
 * The facade delegates to the module's collaborators, wired by
 * {@see Module::register()} via {@see Api::bootstrap()}. If reached before
 * bootstrap, it lazily builds a default store + notifier so the surface is
 * never null.
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The shared template store, wired by {@see Module::register()}.
	 */
	private static ?TemplateStore $store = null;

	/**
	 * The shared notifier, wired by {@see Module::register()}.
	 */
	private static ?Notifier $notifier = null;

	/**
	 * Wire the facade to the module's collaborators (called by {@see Module::register()}).
	 */
	public static function bootstrap( TemplateStore $store, Notifier $notifier ): void {
		// No-op if the facade is already wired. A consumer that touches Api on
		// `plugins_loaded` (before the `init` module dispatch runs Module::register())
		// lazily builds a store and may register templates into it; clobbering it
		// here would silently drop those registrations. First wiring wins.
		self::$store    ??= $store;
		self::$notifier ??= $notifier;
	}

	/**
	 * Register a consumer's Afrikaans-source template/event definition.
	 *
	 * Gettext contract (Story 1.12, decision 5a): the consumer passes the Afrikaans
	 * default subject/body/messages already wrapped in `__( '…', 'ink-core' )` as
	 * LITERAL strings, e.g. `new Template( 'tier_promotion', __( 'Baie geluk!', 'ink-core' ), … )`.
	 * The foundation does not wrap them (it never sees the literals). Keep the strings
	 * literal — never `__( $var )` — so `wp i18n make-pot` can extract them. No English
	 * `.mo` ships, so the Afrikaans source is also the rendered output (§14.15).
	 */
	public static function registerTemplate( Template $template ): void {
		self::store()->register( $template );
	}

	/**
	 * Dispatch the transactional email for $key (gated by its send toggle).
	 *
	 * @param string                    $key     Template/event key.
	 * @param string                    $to      Recipient email.
	 * @param array<string, int|string> $context Merge context, e.g. `array( 'skrywer' => 'Jan' )`.
	 */
	public static function send( string $key, string $to, array $context = array() ): bool {
		return self::notifier()->send( $key, $to, $context );
	}

	/**
	 * Return a random message from $key's stored list (the R7 mechanism).
	 */
	public static function randomMessage( string $key ): string {
		return self::notifier()->randomMessage( $key );
	}

	/**
	 * The resolved (merge-applied) BODY of a registered form-letter template — WITHOUT
	 * sending. The read path for a consumer that composes a document FROM a form-letter
	 * template rather than emailing it (Story 12A.4: the wenneraankondiging post body is
	 * framed by the `ink_wenneraankondiging` template). Honours admin body overrides + the
	 * whitelisted greeting merge, exactly like {@see send()} — it just returns the text.
	 *
	 * @param string                    $key     Template/event key.
	 * @param array<string, int|string> $context Optional merge context.
	 * @return string The resolved body ('' for an unregistered key).
	 */
	public static function templateBody( string $key, array $context = array() ): string {
		return ( new MergeResolver() )->resolve( self::store()->body( $key ), $context );
	}

	/**
	 * Create an in-app kennisgewing (Story 9.9; guarded — no-op without BuddyPress).
	 *
	 * @param int              $user_id  The recipient.
	 * @param NotificationType $type     The kennisgewing category.
	 * @param int              $item_id  The primary subject id.
	 * @param int              $actor_id The triggering user (0 = system).
	 * @return bool True when a notification was written.
	 */
	public static function notify( int $user_id, NotificationType $type, int $item_id, int $actor_id = 0 ): bool {
		return Kennisgewings::add( $user_id, $type, $item_id, $actor_id );
	}

	/**
	 * "Merk alles as gelees" — move the per-user read boundary to now (Story 9.9).
	 *
	 * @param int $user_id The user.
	 */
	public static function markAllRead( int $user_id ): void {
		Kennisgewings::markAllRead( $user_id );
	}

	/**
	 * The shared store, lazily built if {@see Api::bootstrap()} has not run.
	 */
	private static function store(): TemplateStore {
		return self::$store ??= new TemplateStore();
	}

	/**
	 * The shared notifier (over the same store), lazily built if needed.
	 */
	private static function notifier(): Notifier {
		return self::$notifier ??= new Notifier( self::store() );
	}
}
