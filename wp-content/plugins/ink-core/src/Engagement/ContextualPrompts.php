<?php
/**
 * Contextual reading prompts server block — Story 7.5 (FR-30).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leesprompte` block: a guided prompt after a finished piece.
 *
 * After the work (above the Gemeenskapsreaksie form) this nudges the reader to
 * respond thoughtfully — pairing with the line reactions (7.3) and the typed
 * response form (7.4) below it. The copy is human-authored Afrikaans used VERBATIM
 * from `docs/ui-copy-translations.md` (lines 286–287); nothing is invented or
 * AI-translated. The mechanism accepts the content type so prompts MAY vary by
 * type (AC) — v1 returns the same authored framing for every bydrae type; richer
 * per-type question prompts are a later, authoring-gated addition.
 *
 * Reads only, not entitlement-gated. Three-layer: structure + escaping here; the
 * theme owns presentation.
 *
 * @package Ink\Core
 */
final class ContextualPrompts {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leesprompte';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/leesprompte` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * The guided prompt for a content type. Pure.
	 *
	 * `$postType` is accepted so prompts MAY vary by content type (AC); v1 returns
	 * the same authored framing for every bydrae type. The strings are verbatim
	 * human-authored Afrikaans (ui-copy-translations.md 286–287) — never invented.
	 *
	 * @param string $postType The current content type (gedig/storie/artikel).
	 * @return array{heading:string, body:string}
	 */
	public static function promptsFor( string $postType ): array {
		unset( $postType ); // reserved for future per-type variation.

		return array(
			'heading' => __( 'Reageer met bedoeling', 'ink-core' ),
			'body'    => __( 'Merk \'n sin uit. Los \'n gestruktureerde nota. Sê vir \'n skrywer wat geraak het, in plaas van om verby te blaai.', 'ink-core' ),
		);
	}

	/**
	 * Block render callback for the current work.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_type = function_exists( 'get_post_type' ) ? (string) get_post_type() : '';

		if ( '' === $post_type ) {
			return '';
		}

		return self::toHtml( self::promptsFor( $post_type ) );
	}

	/**
	 * Build the prompt-area HTML. Pure — escaping only.
	 *
	 * @param array{heading:string, body:string} $prompt The prompt copy.
	 * @return string
	 */
	public static function toHtml( array $prompt ): string {
		return '<aside class="ink-leesprompte">'
			. '<h2 class="ink-leesprompte__heading">' . esc_html( $prompt['heading'] ) . '</h2>'
			. '<p class="ink-leesprompte__body">' . esc_html( $prompt['body'] ) . '</p>'
			. '</aside>';
	}
}
