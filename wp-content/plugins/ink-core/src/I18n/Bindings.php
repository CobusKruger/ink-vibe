<?php
/**
 * Block Bindings source for terminology labels.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `ink/term` Block Bindings source (AD-10 bridge for static HTML).
 *
 * The theme cannot call `ink-core` PHP from static block-template HTML
 * (`templates/*.html`). The Block Bindings API (WP 6.5+) is the documented
 * bridge: a template binds a block's text to a registered source, so the same
 * glossary label rendered by {@see Terms} in PHP is reachable from static HTML
 * without inlining the literal. Example markup:
 *
 *   <!-- wp:heading {"metadata":{"bindings":{"content":{"source":"ink/term",
 *        "args":{"key":"gradering"}}}}} -->
 *
 * Registered on `init` from the Kernel boot ({@see \Ink\Kernel\Plugin}) as a
 * cross-cutting i18n concern — not a feature module, not the bootstrap.
 *
 * @package Ink\Core
 */
final class Bindings {

	/**
	 * The Block Bindings source name (the `source` value used in block metadata).
	 */
	public const SOURCE = 'ink/term';

	/**
	 * Register the `ink/term` binding source.
	 *
	 * Guarded for WP < 6.5 even though the build target is 7.0+, so the plugin
	 * degrades gracefully rather than fatals on an older engine.
	 */
	public static function register(): void {
		if ( ! function_exists( 'register_block_bindings_source' ) ) {
			return;
		}

		register_block_bindings_source(
			self::SOURCE,
			array(
				'label'              => __( 'INK-terminologie', 'ink-core' ),
				'get_value_callback' => array( self::class, 'resolve' ),
				'uses_context'       => array(),
			)
		);
	}

	/**
	 * Resolve a binding to its glossary label.
	 *
	 * The bound block escapes the returned value at render, per the Block
	 * Bindings contract; this returns the raw label by key.
	 *
	 * @param array<string, mixed> $source_args The block's binding `args` (expects `key`).
	 * @return string The label for the requested key, or the key if unregistered.
	 */
	public static function resolve( array $source_args ): string {
		$key = isset( $source_args['key'] ) ? (string) $source_args['key'] : '';

		return Terms::label( $key );
	}
}
