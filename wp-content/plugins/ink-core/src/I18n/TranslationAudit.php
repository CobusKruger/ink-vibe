<?php
/**
 * Committed-translation presence audit — Story 18.7 (NFR-7 / NFR-1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the premium-plugin Afrikaans translations are present in the committed
 * languages home (`wp-content/languages/`).
 *
 * The committed `.mo`/`.json` is the only defence for premium/niche plugins with no
 * complete community language pack (project-context: WooCommerce Memberships, PayFast
 * gateway, Real3D Flipbook). A plugin update can ship new untranslated strings or a
 * file can go missing — this audit (`wp ink audit-translations`) is the standing
 * post-update recheck. The new-string detection itself is the leak scan (Story 17.4
 * static `copy:scan` + 18.8 live `wp i18n`); this checks file PRESENCE.
 *
 * The exact filenames are confirmed on staging (vendor plugins are not in the repo);
 * the expected set is filterable via `ink_i18n_required_translations`. The audit never
 * authors translations (AI Afrikaans is forbidden) — it only reports presence.
 *
 * Pure {@see missingTranslations()} takes primitives so it unit-tests without
 * WordPress; the languages-dir read is an overridable seam. Conflation-clean.
 *
 * @package Ink\Core
 */
class TranslationAudit {

	/**
	 * The WP-CLI command name (`wp ink audit-translations`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink audit-translations';

	/**
	 * The expected committed translation filenames (af locale) for the premium
	 * plugins whose Afrikaans is the committed `.mo`/`.json` — the single source.
	 * Filterable via `ink_i18n_required_translations`; exact slugs are confirmed on
	 * staging against the installed vendor plugins.
	 *
	 * @var list<string>
	 */
	public const REQUIRED_TRANSLATIONS = array(
		'woocommerce-memberships-af.mo',
		'woocommerce-gateway-payfast-af.mo',
		'real3d-flipbook-af.json',
	);

	/**
	 * Register the audit command — WP-CLI only.
	 *
	 * All `WP_CLI::*` I/O lives inside the closure, after the `class_exists` guard.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP-CLI command-callback signature; audit-translations takes no args.
				unset( $args, $assoc );

				$missing = self::missingTranslations( $this->presentTranslations(), $this->requiredSet() );

				if ( array() === $missing ) {
					\WP_CLI::success( 'Alle premie-inprop vertalings is teenwoordig in wp-content/languages/.' );
					return;
				}

				\WP_CLI::warning(
					sprintf(
						'%d vertaling(s) ontbreek (heroudit ná inprop-opdaterings; outeur op staging, moenie op produksie wysig nie): %s',
						count( $missing ),
						implode( ', ', $missing )
					)
				);
			}
		);
	}

	/**
	 * The required translation set (filterable). Single source + extension seam.
	 *
	 * @return list<string>
	 */
	public function requiredSet(): array {
		$set = (array) apply_filters( 'ink_i18n_required_translations', self::REQUIRED_TRANSLATIONS );

		return array_values( array_map( 'strval', $set ) );
	}

	/**
	 * The required translations that are absent from the present set. Pure.
	 *
	 * @param list<string> $present  The filenames present in the languages home.
	 * @param list<string> $required The expected filenames.
	 * @return list<string>
	 */
	public static function missingTranslations( array $present, array $required ): array {
		return array_values( array_diff( $required, $present ) );
	}

	/**
	 * The translation filenames present in the committed languages home. Overridable seam.
	 *
	 * @return list<string>
	 */
	protected function presentTranslations(): array {
		$dir = defined( 'WP_LANG_DIR' ) ? (string) constant( 'WP_LANG_DIR' ) : '';

		if ( '' === $dir || ! is_dir( $dir ) ) {
			return array();
		}

		$files = array();

		foreach ( array( '*.mo', '*.json' ) as $pattern ) {
			$matches = glob( trailingslashit( $dir ) . $pattern );

			foreach ( is_array( $matches ) ? $matches : array() as $path ) {
				$files[] = basename( (string) $path );
			}
		}

		return $files;
	}
}
