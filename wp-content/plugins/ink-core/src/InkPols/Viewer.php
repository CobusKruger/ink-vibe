<?php
/**
 * InkPols PDF flipbook viewer server block — Story 13.3 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/inkpols-leser` block: the issue PDF as a Real3D Flipbook
 * (Story 13.3, FR-57).
 *
 * The flipbook itself is produced by the Real3D Flipbook plugin — a commodity
 * capability INK never reimplements. This block delegates to the plugin's own
 * shortcode (behind a {@see shortcode_exists()} guard), passing the issue's
 * {@see Issue::pdfUrl()} (the whole PDF — no per-article extraction). When the
 * plugin is inactive it degrades gracefully to a direct-PDF "Lees die uitgawe"
 * link (the FR-57 / validation-L-3 a11y fallback), and with no PDF it renders
 * nothing.
 *
 * The flipbook is a KNOWN, accepted exception to NFR-3 (light front-end JS) and
 * NFR-5 (a11y); its viewer controls are plugin JavaScript, localised via the
 * plugin's JS translations ({@see registerScriptTranslations()} + the standing
 * translation workflow), not an ink-core `.mo`.
 *
 * Conflation-clean: reads only `Ink\InkPols` + the `Terms` registry + WP core —
 * zero `Ink\Tiers`/`Ink\Entitlement`. Reading a published issue's PDF is open.
 *
 * @package Ink\Core
 */
final class Viewer {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/inkpols-leser';

	/**
	 * The Real3D Flipbook shortcode tag (the single integration point — reconcile
	 * against the installed plugin version if it differs).
	 *
	 * @var string
	 */
	public const SHORTCODE_TAG = 'real3dflipbook';

	/**
	 * The Real3D Flipbook front-end script handle, for JS-translation wiring.
	 *
	 * @var string
	 */
	public const SCRIPT_HANDLE = 'real3dflipbook';

	/**
	 * Register the server-rendered block + the guarded script-translation wiring.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'registerScriptTranslations' ), 20 );
	}

	/**
	 * Register the `ink/inkpols-leser` dynamic block.
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
	 * Point the flipbook front-end script at its Afrikaans JS translations.
	 *
	 * No-op-safe: only wires when both `wp_set_script_translations` and the
	 * plugin's registered script handle exist (the plugin is assembled at build
	 * time, not in-repo). The `.json` translation authoring is the standing
	 * translation workflow (Epic 17) — this records the obligation in code.
	 */
	public static function registerScriptTranslations(): void {
		if ( ! function_exists( 'wp_set_script_translations' ) || ! function_exists( 'wp_script_is' ) ) {
			return;
		}

		if ( ! wp_script_is( self::SCRIPT_HANDLE, 'registered' ) ) {
			return;
		}

		// Point at the committed wp-content/languages/ dir (the translation-workflow
		// home for surviving third-party plugins) so the flipbook .json loads from
		// there once authored, not only the default plugin path (R13 review).
		$path = defined( 'WP_LANG_DIR' ) ? constant( 'WP_LANG_DIR' ) : '';

		wp_set_script_translations( self::SCRIPT_HANDLE, 'ink-core', $path );
	}

	/**
	 * Build the Real3D Flipbook shortcode for a PDF URL. Pure.
	 *
	 * @param string $pdf_url The issue PDF URL.
	 * @return string
	 */
	public static function shortcodeFor( string $pdf_url ): string {
		// esc_url_raw, not esc_url: the URL is a shortcode ATTRIBUTE consumed by the
		// plugin's parser, not direct HTML output — esc_url would entity-encode `&`
		// (`&#038;`) and mangle a PDF URL carrying query args (R13 review).
		$clean = function_exists( 'esc_url_raw' ) ? esc_url_raw( $pdf_url ) : $pdf_url;

		return '[' . self::SHORTCODE_TAG . ' pdf="' . $clean . '"]';
	}

	/**
	 * Build the viewer HTML. Pure (Terms + escaping only).
	 *
	 * Renders the flipbook (the already-expanded shortcode output) when the plugin
	 * is available; otherwise the direct-PDF "Lees die uitgawe" fallback link. ''
	 * when there is no PDF URL.
	 *
	 * @param string $pdf_url            The issue PDF URL.
	 * @param bool   $flipbook_available Whether the Real3D Flipbook shortcode is active.
	 * @param string $shortcode_output   The expanded shortcode output (when available).
	 * @return string
	 */
	public static function embedHtml( string $pdf_url, bool $flipbook_available, string $shortcode_output ): string {
		if ( '' === $pdf_url ) {
			return '';
		}

		if ( $flipbook_available ) {
			return '<div class="ink-inkpols-leser">' . $shortcode_output . '</div>';
		}

		return '<div class="ink-inkpols-leser ink-inkpols-leser--terugval">'
			. '<a class="ink-inkpols-leser__skakel" href="' . esc_url( $pdf_url ) . '">'
			. esc_html( Terms::label( 'inkpols_lees_uitgawe' ) )
			. '</a></div>';
	}

	/**
	 * Block render callback. Resolves the issue, guards the PDF, branches on the
	 * plugin, composes.
	 *
	 * @return string
	 */
	public static function render(): string {
		$id    = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;
		$issue = Api::issueFor( $id );

		if ( null === $issue || ! $issue->hasPdf() ) {
			return '';
		}

		$pdf_url   = $issue->pdfUrl();
		$available = function_exists( 'shortcode_exists' ) && shortcode_exists( self::SHORTCODE_TAG );

		$output = ( $available && function_exists( 'do_shortcode' ) )
			? (string) do_shortcode( self::shortcodeFor( $pdf_url ) )
			: '';

		return self::embedHtml( $pdf_url, $available, $output );
	}
}
