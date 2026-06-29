<?php
/**
 * Migration 301 redirect-integrity audit + flatten — Story 18.2 (NFR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the integrity of the Story-16.7 redirect map ({@see RedirectGenerator}).
 *
 * A migration map assembled from `old-path → current-permalink` records can carry
 * defects that serve correctly per-entry but harm SEO in aggregate:
 *  - a **chain** — a target whose path is itself a redirect key (post B moved into
 *    the slot post A vacated), so a visitor gets a 301-to-301 hop;
 *  - a **loop** — a target that normalises back to its own key;
 *  - an **empty** target — a key that would redirect nowhere.
 *
 * {@see audit()} reports these (pure); {@see flatten()} collapses chains so every
 * old URL issues a SINGLE 301 to the final live target (cycles are reported, never
 * followed). The CLI surface (`wp ink verify-redirects`) runs the audit against the
 * stored map and, with `--fix`, flattens and re-stores it. The live HTTP crawl +
 * 404 logging are documented in docs/redirect-integrity-runbook.md (need a running
 * site — the Redirection plugin owns the 404 log).
 *
 * Conflation-clean: reads only the 16.7 map + WP-CLI; zero Tiers/Entitlement. Not
 * `final`: the load/store seams are overridable so the audit + CLI are unit-testable
 * without WordPress.
 *
 * @package Ink\Core
 */
class RedirectIntegrity {

	/**
	 * The WP-CLI command name (`wp ink verify-redirects`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink verify-redirects';

	/**
	 * Hard cap on chain-resolution hops — a safety bound against a pathological
	 * (cyclic or adversarial) map; a chain longer than this is treated as unresolved.
	 *
	 * @var int
	 */
	private const MAX_HOPS = 25;

	/**
	 * Register the verify command — WP-CLI only (mirrors 16.7's generate-redirects).
	 *
	 * All `WP_CLI::*` I/O lives inside the closure, AFTER the `class_exists` guard
	 * (the house pattern — see {@see TierImport}/{@see RedirectGenerator}); the
	 * pure audit/flatten + the load/store seams do the work.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$fix = isset( $assoc['fix'] );
				$map = $this->loadMap();

				if ( array() === $map ) {
					\WP_CLI::warning( 'Geen aanstuurkaart gevind nie — laat eers `wp ink generate-redirects` loop.' );
					return;
				}

				$report = self::audit( $map );

				\WP_CLI::log(
					sprintf(
						'Aanstuurkaart: %d reëls — kettings: %d, lusse: %d, leë teikens: %d.',
						$report['count'],
						count( $report['chains'] ),
						count( $report['loops'] ),
						count( $report['empty'] )
					)
				);

				if ( $fix ) {
					$flattened = self::flatten( $map );
					$this->storeMap( $flattened );
					$after = self::audit( $flattened );

					// A cycle (or an over-long chain) is left on its original target by
					// flatten() — report honestly rather than claim a clean fix.
					if ( ! $after['ok'] ) {
						\WP_CLI::warning(
							sprintf(
								'Kettings platgemaak, maar %d ketting(s) + %d lus(se) bly oor (siklusse — los met die hand op).',
								count( $after['chains'] ),
								count( $after['loops'] )
							)
						);
						return;
					}

					\WP_CLI::success( 'Kettings platgemaak — aanstuurkaart is nou heel.' );
					return;
				}

				if ( $report['ok'] ) {
					\WP_CLI::success( 'Aanstuurkaart is heel — geen kettings, lusse of leë teikens nie.' );
					return;
				}

				\WP_CLI::warning( 'Aanstuurkaart het integriteitsfoute — loop weer met --fix om kettings plat te maak.' );
			}
		);
	}

	/**
	 * Audit a redirect map for chains, loops and empty targets. Pure.
	 *
	 * @param array<string, string> $map old-path → new-URL (16.7 map shape).
	 * @return array{count:int, chains:list<string>, loops:list<string>, empty:list<string>, ok:bool}
	 */
	public static function audit( array $map ): array {
		$chains = array();
		$loops  = array();
		$empty  = array();

		foreach ( $map as $key => $target ) {
			$key = (string) $key;

			if ( '' === trim( (string) $target ) ) {
				$empty[] = $key;
				continue;
			}

			$target_key = RedirectGenerator::normalisePath( (string) $target );

			if ( $target_key === $key ) {
				$loops[] = $key;
				continue;
			}

			// A chain: the target path is itself a redirect key (a 301-to-301 hop).
			if ( array_key_exists( $target_key, $map ) ) {
				$chains[] = $key;
			}
		}

		return array(
			'count'  => count( $map ),
			'chains' => $chains,
			'loops'  => $loops,
			'empty'  => $empty,
			'ok'     => array() === $chains && array() === $loops && array() === $empty,
		);
	}

	/**
	 * Collapse chains so each old URL points directly at its final target. Pure.
	 *
	 * Follows each entry's target through the map until it lands on a non-key (the
	 * live destination), bounded by {@see MAX_HOPS}. A cycle (or an over-long chain)
	 * leaves that entry's ORIGINAL target untouched — never invents a destination —
	 * so {@see audit()} still flags it. Empty targets are dropped. Idempotent: a
	 * flattened map flattens to itself.
	 *
	 * @param array<string, string> $map old-path → new-URL.
	 * @return array<string, string>
	 */
	public static function flatten( array $map ): array {
		$out = array();

		foreach ( $map as $key => $target ) {
			$key    = (string) $key;
			$target = (string) $target;

			if ( '' === trim( $target ) ) {
				continue; // drop empty targets — they redirect nowhere.
			}

			$resolved    = self::resolveTarget( $key, $target, $map );
			$out[ $key ] = $resolved;
		}

		return $out;
	}

	/**
	 * Follow a target through the map to its final non-key destination. Pure.
	 *
	 * @param string                $origin The starting key (cycle anchor).
	 * @param string                $target The first target URL.
	 * @param array<string, string> $map    The full map.
	 * @return string The final target, or the original target if a cycle/over-long chain is hit.
	 */
	private static function resolveTarget( string $origin, string $target, array $map ): string {
		$current = $target;
		$seen    = array( $origin => true );

		for ( $hop = 0; $hop < self::MAX_HOPS; $hop++ ) {
			$current_key = RedirectGenerator::normalisePath( $current );

			if ( ! array_key_exists( $current_key, $map ) ) {
				return $current; // landed on a live (non-redirected) destination.
			}

			if ( isset( $seen[ $current_key ] ) ) {
				return $target; // cycle — leave the original target for audit() to flag.
			}

			$seen[ $current_key ] = true;
			$next                 = (string) $map[ $current_key ];

			if ( '' === trim( $next ) ) {
				return $current; // chain ends at an empty entry — stop at the last good URL.
			}

			$current = $next;
		}

		return $target; // over-long chain — leave the original target.
	}

	/**
	 * Load the stored redirect map (16.7's option). Overridable seam.
	 *
	 * @return array<string, string>
	 */
	protected function loadMap(): array {
		$map = get_option( RedirectGenerator::OPTION_MAP, array() );

		return is_array( $map ) ? $map : array();
	}

	/**
	 * Persist the (flattened) redirect map. Overridable seam.
	 *
	 * @param array<string, string> $map The old-path → new-URL map.
	 */
	protected function storeMap( array $map ): void {
		update_option( RedirectGenerator::OPTION_MAP, $map, false );
	}
}
