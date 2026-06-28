<?php
/**
 * Once-off selective options carry-forward — Story 16.11 (FL 16.11).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Carries forward ONLY the deliberate `wp_options` values — a once-off,
 * idempotent migration step (FL 16.11).
 *
 * The new site is NOT a wholesale `wp_options` clone: it is set up cleanly and
 * only an allowlist of deliberate values transfers — site URL/home, site
 * name/description, and the confirmed `af` locale ({@see allowedOptions()}).
 * Everything else — SEO (Yoast retired; Rank Math is configured FRESH),
 * retired-plugin config, theme-framework cruft — is dropped by omission from the
 * allowlist. The locale is forced to `af` regardless of the legacy value.
 *
 * Once-off + guarded ({@see OPTION_DONE}; `--force` re-runs); WP-CLI only
 * (`wp ink migrate-options`) — never a web request. Conflation-clean: writes WP
 * core options only; zero cross-module coupling.
 *
 * Not `final`: the option methods are overridable seams so the filter logic is
 * unit-testable without the options table.
 *
 * @package Ink\Core
 */
class OptionsCarryForward {

	/**
	 * The completion flag option — set once the carry-forward has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_options_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-options`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-options';

	/**
	 * The confirmed site locale (forced regardless of the legacy value).
	 *
	 * @var string
	 */
	public const LOCALE = 'af';

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Opsies oorgedra: %d waardes, ligging "%s"%s.',
						(int) $summary['applied'],
						(string) $summary['locale'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The allowlist of option names that may carry forward. Pure.
	 *
	 * Everything NOT here is dropped (SEO config is set up fresh in Rank Math).
	 *
	 * @return list<string>
	 */
	public static function allowedOptions(): array {
		return array( 'siteurl', 'home', 'blogname', 'blogdescription', 'WPLANG' );
	}

	/**
	 * Filter the legacy options down to the deliberate carry-forward set. Pure.
	 *
	 * Keeps only the allowlisted keys present in `$legacy`, then forces
	 * `WPLANG => 'af'` (the confirmed locale) whether or not legacy carried it.
	 *
	 * @param array<string, mixed> $legacy The legacy option values.
	 * @return array<string, string>
	 */
	public static function filterCarryForward( array $legacy ): array {
		$carry = array();

		foreach ( self::allowedOptions() as $key ) {
			if ( array_key_exists( $key, $legacy ) ) {
				$carry[ $key ] = (string) $legacy[ $key ];
			}
		}

		// Force the confirmed Afrikaans locale (deliberate value, not inherited).
		$carry['WPLANG'] = self::LOCALE;

		return $carry;
	}

	/**
	 * Run the once-off carry-forward. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, applied:int, locale:string}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped' => true,
				'applied' => 0,
				'locale'  => self::LOCALE,
			);
		}

		$carry   = self::filterCarryForward( $this->legacyOptions() );
		$applied = 0;

		foreach ( $carry as $key => $value ) {
			$this->applyOption( $key, $value );
			++$applied;
		}

		$this->markDone();

		return array(
			'skipped' => false,
			'applied' => $applied,
			'locale'  => self::LOCALE,
		);
	}

	/**
	 * Whether the carry-forward has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the carry-forward complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The legacy option values. Overridable seam.
	 *
	 * SAFE DEFAULT: EMPTY. The legacy values are site-specific (read from the old
	 * site's export); a site MUST supply them. An un-overridden run still forces
	 * the `af` locale but carries nothing else.
	 *
	 * @return array<string, mixed>
	 */
	protected function legacyOptions(): array {
		return array();
	}

	/**
	 * Persist one carried-forward option. Overridable seam.
	 *
	 * @param string $key   The option name.
	 * @param string $value The option value.
	 */
	protected function applyOption( string $key, string $value ): void {
		update_option( $key, $value );
	}
}
