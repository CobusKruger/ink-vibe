<?php
/**
 * Migration 301 redirect generation + runtime serving — Story 16.7 (FL 16.7, NFR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Builds and serves the migration 301 redirect layer (FL 16.7, NFR-4).
 *
 * The build (WP-CLI only, once-off + idempotent) reads each post's recorded
 * pre-migration permalink ({@see PostReclassifier::SOURCE_URL_META}, written by
 * Stories 16.5/16.6 BEFORE the post_type change) and compares it to the post's
 * CURRENT permalink. A redirect entry is created ONLY when the path changed — an
 * unchanged URL (a `/biblioteek/` single whose prefix was kept) produces no
 * redirect, so the kept prefixes carry no needless 301. The map is stored in the
 * {@see OPTION_MAP} option.
 *
 * The serve handler ({@see maybeRedirect()} on `template_redirect`, registered on
 * EVERY front-end request — NOT WP-CLI-gated) looks up the normalised request
 * path in the stored map and issues `wp_safe_redirect( $target, 301 )`. A
 * redirect-loop guard skips when the target path equals the request path.
 *
 * Conflation-clean: reads only the recorded source-URL meta + WP core; zero
 * `Tiers`/`Entitlement`. Not `final`: the option/permalink/request methods are
 * overridable seams so the build + serve logic is unit-testable without WordPress.
 *
 * @package Ink\Core
 */
class RedirectGenerator {

	/**
	 * The option storing the old-path → new-URL redirect map.
	 *
	 * @var string
	 */
	public const OPTION_MAP = 'ink_migration_redirects';

	/**
	 * The build-completion flag option.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_redirects_done';

	/**
	 * The WP-CLI command name (`wp ink generate-redirects`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink generate-redirects';

	/**
	 * Register the runtime serve handler (always) + the build command (WP-CLI only).
	 */
	public function register(): void {
		add_action( 'template_redirect', array( $this, 'maybeRedirect' ) );

		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->build( isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Aanstuurings (301) gegenereer: %d reëls%s.',
						(int) $summary['count'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * Normalise a URL or path to a leading-slashed, trailing-slash-free path. Pure.
	 *
	 * `https://x/foo/bar/` and `/foo/bar` both → `/foo/bar`; the site root → `/`.
	 *
	 * @param string $url A URL or path.
	 * @return string
	 */
	public static function normalisePath( string $url ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper: parses a path/URL string, no HTTP; kept WP-free so it is unit-testable without WordPress.
		$path    = (string) ( parse_url( $url, PHP_URL_PATH ) ?? '' );
		$trimmed = trim( $path, '/' );

		return '' === $trimmed ? '/' : '/' . $trimmed;
	}

	/**
	 * Build the old-path → new-URL redirect map from recorded records. Pure.
	 *
	 * An entry is emitted ONLY when the path changed (unchanged URLs — kept
	 * prefixes — produce nothing). The target is the full new URL.
	 *
	 * @param array<int, array{old?:string, new?:string}> $records The recorded sources.
	 * @return array<string, string> old-path → new-URL.
	 */
	public static function buildRedirectMap( array $records ): array {
		$map = array();

		foreach ( $records as $record ) {
			$old     = (string) ( $record['old'] ?? '' );
			$new     = (string) ( $record['new'] ?? '' );
			$old_key = self::normalisePath( $old );

			if ( '' === $old || '' === $new || '/' === $old_key ) {
				continue;
			}

			if ( self::normalisePath( $new ) === $old_key ) {
				continue; // unchanged URL (prefix kept) — no redirect.
			}

			$map[ $old_key ] = $new;
		}

		return $map;
	}

	/**
	 * The redirect target for a request path, or null when none. Pure.
	 *
	 * @param string                $request_path The incoming request path/URL.
	 * @param array<string, string> $map          The redirect map.
	 * @return string|null
	 */
	public static function redirectTargetFor( string $request_path, array $map ): ?string {
		$key = self::normalisePath( $request_path );

		return $map[ $key ] ?? null;
	}

	/**
	 * Build (and store) the redirect map. Idempotent unless `$force`.
	 *
	 * @param bool $force Rebuild even when already completed.
	 * @return array{skipped:bool, count:int}
	 */
	public function build( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped' => true,
				'count'   => 0,
			);
		}

		$records = array();

		foreach ( $this->recordedRedirectSources() as $source ) {
			$id = (int) ( $source->id ?? 0 );

			if ( $id <= 0 ) {
				continue;
			}

			$records[] = array(
				'old' => (string) ( $source->old ?? '' ),
				'new' => $this->currentPermalink( $id ),
			);
		}

		$map = self::buildRedirectMap( $records );

		$this->storeMap( $map );
		$this->markDone();

		return array(
			'skipped' => false,
			'count'   => count( $map ),
		);
	}

	/**
	 * Serve a 301 when the current request path matches the stored map. Runtime.
	 */
	public function maybeRedirect(): void {
		$map = $this->loadMap();

		if ( array() === $map ) {
			return;
		}

		$request = $this->requestPath();
		$target  = self::redirectTargetFor( $request, $map );

		if ( null === $target ) {
			return;
		}

		// Loop guard: never redirect a path onto itself.
		if ( self::normalisePath( $target ) === self::normalisePath( $request ) ) {
			return;
		}

		$this->doRedirect( $target );
	}

	/**
	 * Whether the build has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the build complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The posts carrying a recorded source URL, as `{id, old}` rows. Overridable seam.
	 *
	 * @return array<int, object>
	 */
	protected function recordedRedirectSources(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a once-off CLI build keyed on the migration source-URL marker, not a request-path query.
				'meta_query'       => array(
					array(
						'key'     => PostReclassifier::SOURCE_URL_META,
						'compare' => 'EXISTS',
					),
				),
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$sources = array();

		foreach ( $ids as $id ) {
			$id  = (int) $id;
			$old = get_post_meta( $id, PostReclassifier::SOURCE_URL_META, true );

			$sources[] = (object) array(
				'id'  => $id,
				'old' => is_string( $old ) ? $old : '',
			);
		}

		return $sources;
	}

	/**
	 * A post's current permalink. Overridable seam.
	 *
	 * @param int $post_id The post id.
	 * @return string
	 */
	protected function currentPermalink( int $post_id ): string {
		$url = get_permalink( $post_id );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Persist the redirect map. Overridable seam.
	 *
	 * @param array<string, string> $map The old-path → new-URL map.
	 */
	protected function storeMap( array $map ): void {
		update_option( self::OPTION_MAP, $map, false );
	}

	/**
	 * Load the stored redirect map. Overridable seam.
	 *
	 * @return array<string, string>
	 */
	protected function loadMap(): array {
		$map = get_option( self::OPTION_MAP, array() );

		return is_array( $map ) ? $map : array();
	}

	/**
	 * The current request path. Overridable seam.
	 *
	 * @return string
	 */
	protected function requestPath(): string {
		if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
			return '';
		}

		return esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- sanitised here via wp_unslash + esc_url_raw.
	}

	/**
	 * Issue the 301 and stop. Overridable seam.
	 *
	 * @param string $target The redirect target URL.
	 */
	protected function doRedirect( string $target ): void {
		wp_safe_redirect( $target, 301 );
		exit;
	}
}
