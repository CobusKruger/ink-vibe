<?php
/**
 * Standing production-hygiene audit — Story 18.6 (NFR-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Catches staging/authoring-only plugins left active on PRODUCTION.
 *
 * Loco Translate, Code Snippets, WP Migrate (Lite), String Locator, Simple CSS,
 * Query Monitor, Debug Bar, … are staging/authoring-only (project-context: "nothing
 * diagnostic/migration on production"). This collaborator is the standing NFR-7 gate:
 * on a production environment it surfaces any forbidden active plugin via an admin
 * notice + `wp ink audit-production`, so a stray activation is caught rather than
 * silently lingering. Off-production (staging/local) it is inert — those tools belong
 * there.
 *
 * Pure {@see forbiddenActive()} intersects primitives so it unit-tests without
 * WordPress; the environment + active-plugin reads are overridable seams.
 * Conflation-clean: zero Tiers/Entitlement.
 *
 * @package Ink\Core
 */
class ProductionHygiene {

	/**
	 * The WP-CLI command name (`wp ink audit-production`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink audit-production';

	/**
	 * Forbidden-on-production plugin basenames — the single source (filterable via
	 * `ink_security_forbidden_plugins`). Matches project-context's staging/authoring-
	 * only list plus the common WP debug tools.
	 *
	 * @var list<string>
	 */
	public const FORBIDDEN_PLUGINS = array(
		'loco-translate/loco-translate.php',
		'code-snippets/code-snippets.php',
		'wp-migrate-db/wp-migrate-db.php',
		'wp-migrate-db-pro/wp-migrate-db-pro.php',
		'string-locator/string-locator.php',
		'simple-custom-css/simple-custom-css.php',
		'query-monitor/query-monitor.php',
		'debug-bar/debug-bar.php',
	);

	/**
	 * Register the audit — the admin notice (production only) + the CLI command.
	 */
	public function register(): void {
		if ( $this->isProduction() ) {
			add_action( 'admin_notices', array( $this, 'renderAdminNotice' ) );
		}

		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- WP-CLI command-callback signature; audit-production takes no args.
				unset( $args, $assoc );

				if ( ! $this->isProduction() ) {
					\WP_CLI::log( 'Nie ’n produksie-omgewing nie — hierdie kontrole is net vir produksie. (Geen aksie.)' );
					return;
				}

				$found = self::forbiddenActive( $this->activePlugins(), $this->forbiddenSet() );

				if ( array() === $found ) {
					\WP_CLI::success( 'Produksie is skoon — geen ontwikkel-/diagnostiese/migrasie-inproppe aktief nie.' );
					return;
				}

				\WP_CLI::warning(
					sprintf(
						'%d verbode inprop(pe) aktief op produksie: %s',
						count( $found ),
						implode( ', ', $found )
					)
				);
			}
		);
	}

	/**
	 * The forbidden-plugin set (filterable). The list single source + extension seam.
	 *
	 * @return list<string>
	 */
	public function forbiddenSet(): array {
		$set = (array) apply_filters( 'ink_security_forbidden_plugins', self::FORBIDDEN_PLUGINS );

		return array_values( array_map( 'strval', $set ) );
	}

	/**
	 * The forbidden plugins that are currently active. Pure.
	 *
	 * @param list<string> $active    The active-plugin basenames.
	 * @param list<string> $forbidden The forbidden basenames.
	 * @return list<string>
	 */
	public static function forbiddenActive( array $active, array $forbidden ): array {
		return array_values( array_intersect( $active, $forbidden ) );
	}

	/**
	 * Render the admin notice listing any forbidden active plugin (production only).
	 */
	public function renderAdminNotice(): void {
		$found = self::forbiddenActive( $this->activePlugins(), $this->forbiddenSet() );

		if ( array() === $found ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p><p>%s</p></div>',
			esc_html__( 'INK produksie-higiëne: ontwikkel-/diagnostiese inproppe is aktief op produksie en moet gedeaktiveer word:', 'ink-core' ),
			esc_html( implode( ', ', $found ) )
		);
	}

	/**
	 * Whether this is a production environment. Overridable seam.
	 */
	protected function isProduction(): bool {
		return 'production' === wp_get_environment_type();
	}

	/**
	 * The active-plugin basenames. Overridable seam.
	 *
	 * @return list<string>
	 */
	protected function activePlugins(): array {
		$active = get_option( 'active_plugins', array() );

		return is_array( $active ) ? array_values( array_map( 'strval', $active ) ) : array();
	}
}
