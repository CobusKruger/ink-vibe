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
