<?php
/**
 * One-command migration orchestrator — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * `wp ink migrate-all` — runs the whole once-off pipeline in the committed order,
 * so a rebuild from a fresh prod copy is one command instead of fifteen.
 *
 * The database is disposable and every human decision lives in committed files, so
 * the supported cutover is "re-clone prod, run this". Each underlying step is already
 * idempotent; the orchestrator adds:
 *  - **pre-flight**: the tier CSV must exist before anything mutates;
 *  - **resume**: the outcome of each step is stored in {@see OPTION_STATE}; a re-run
 *    skips steps that succeeded (`--from=<step>` restarts at a step, `--reset` clears);
 *  - **stop at first failure**, never carrying on over a broken step;
 *  - **a verdict per step** ({@see verdict()}): a step that printed nothing or only
 *    zeros is flagged, because "silently did nothing" is how the last attempt hid
 *    that 0 library items and 0 challenges were migrated.
 *
 * Steps run through `WP_CLI::runcommand()` so each keeps its own guard and flags.
 * Pure decisions are static; process execution sits behind an overridable seam.
 *
 * @package Ink\Core
 */
class MigrateAll {

	/**
	 * The step outcomes, so a re-run can resume.
	 *
	 * @var string
	 */
	public const OPTION_STATE = 'ink_migrate_all_state';

	/**
	 * The WP-CLI command name (`wp ink migrate-all`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-all';

	public const VERDICT_OK       = 'ok';
	public const VERDICT_SILENT   = 'silent';
	public const VERDICT_ALL_ZERO = 'all-zero';

	/**
	 * Steps whose result is legitimately silent or zero on a clean run.
	 *
	 * @var list<string>
	 */
	private const MAY_BE_ZERO = array( 'verify-subscriptions', 'verify-redirects', 'verify-media', 'clean-shortcodes' );

	/**
	 * The pipeline, in order. `{tiers}` is replaced by the tier CSV path.
	 *
	 * @return list<array{0:string,1:string}> Pairs of [step id, WP-CLI command].
	 */
	public static function steps(): array {
		return array(
			array( 'sanitise', 'ink migrate-sanitise' ),
			array( 'users', 'ink migrate-users' ),
			array( 'tiers', 'ink migrate-tiers {tiers}' ),
			array( 'verify-subscriptions', 'ink verify-subscriptions' ),
			array( 'library-training', 'ink migrate-library-training' ),
			array( 'posts', 'ink migrate-posts' ),
			array( 'challenges', 'ink migrate-challenges' ),
			array( 'challenge-entries', 'ink sync-challenge-entries' ),
			array( 'inkpols', 'ink migrate-inkpols' ),
			array( 'clean-shortcodes', 'ink clean-shortcodes' ),
			array( 'rebuild-navigation', 'ink rebuild-navigation' ),
			array( 'follows', 'ink migrate-follows' ),
			array( 'options', 'ink migrate-options' ),
			array( 'recount', 'term recount category genre vaardigheid uitdagingsrondte' ),
			array( 'generate-redirects', 'ink generate-redirects' ),
			array( 'verify-redirects', 'ink verify-redirects --fix' ),
			array( 'verify-media', 'ink verify-media' ),
		);
	}

	/**
	 * The steps still to run. Pure.
	 *
	 * @param array<string, array{status?:string}> $state The stored step outcomes.
	 * @param string|null                          $from  Restart at this step id.
	 * @return list<string> Step ids, in order.
	 */
	public static function pending( array $state, ?string $from = null ): array {
		$ids     = array_map( static fn ( array $s ): string => $s[0], self::steps() );
		$started = null === $from;
		$out     = array();

		foreach ( $ids as $id ) {
			if ( ! $started && $id === $from ) {
				$started = true;
			}

			if ( ! $started ) {
				continue;
			}

			// Resuming (no --from): skip what already succeeded. --from re-runs everything after.
			if ( null === $from && 'ok' === ( $state[ $id ]['status'] ?? '' ) ) {
				continue;
			}

			$out[] = $id;
		}

		return $out;
	}

	/**
	 * Judge one step's output. Pure.
	 *
	 * @param string $step   The step id.
	 * @param string $output The step's stdout.
	 */
	public static function verdict( string $step, string $output ): string {
		if ( in_array( $step, self::MAY_BE_ZERO, true ) ) {
			return self::VERDICT_OK;
		}

		if ( '' === trim( $output ) ) {
			return self::VERDICT_SILENT;
		}

		if ( 1 === preg_match( '/oorgeslaan\s*[—-]\s*reeds gedoen|already done/iu', $output ) ) {
			return self::VERDICT_OK;
		}

		preg_match_all( '/\d+/', $output, $numbers );

		$non_zero = array_filter( $numbers[0], static fn ( string $n ): bool => (int) $n > 0 );

		return array() === $non_zero ? self::VERDICT_ALL_ZERO : self::VERDICT_OK;
	}

	/**
	 * Pre-flight problems that must stop the run before anything mutates. Pure.
	 *
	 * @param string $tiers_path   The tier CSV path argument.
	 * @param bool   $tiers_exists Whether that file exists.
	 * @return list<string>
	 */
	public static function preflight( string $tiers_path, bool $tiers_exists ): array {
		$problems = array();

		if ( '' === $tiers_path ) {
			$problems[] = 'Geen --tiers=<pad> verskaf nie (docs/data-cleanup/winners/tier-import.csv).';
		} elseif ( ! $tiers_exists ) {
			$problems[] = sprintf( 'Tier-CSV nie gevind nie: %s', $tiers_path );
		}

		return $problems;
	}

	/**
	 * Register the WP-CLI trigger — ONLY under WP-CLI (never a web request).
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
				$tiers = isset( $assoc['tiers'] ) ? (string) $assoc['tiers'] : '';
				$from  = isset( $assoc['from'] ) ? (string) $assoc['from'] : null;

				if ( isset( $assoc['reset'] ) ) {
					delete_option( self::OPTION_STATE );
				}

				$report = $this->run( $tiers, $from );

				foreach ( $report['lines'] as $line ) {
					\WP_CLI::log( $line );
				}

				if ( $report['failed'] ) {
					\WP_CLI::error( 'Migrasie gestaak — herstel die mislukte stap en hardloop weer (hervat outomaties).' );
				}

				\WP_CLI::success(
					0 === $report['flagged']
						? 'Migrasie voltooi.'
						: sprintf( 'Migrasie voltooi, maar %d stap(pe) is gemerk — kyk die opsomming.', $report['flagged'] )
				);
			}
		);
	}

	/**
	 * Run the pipeline.
	 *
	 * @param string      $tiers The tier CSV path.
	 * @param string|null $from  Restart at this step id.
	 * @return array{failed:bool, flagged:int, lines:list<string>}
	 */
	public function run( string $tiers, ?string $from = null ): array {
		$lines    = array();
		$problems = self::preflight( $tiers, '' !== $tiers && $this->fileExists( $tiers ) );

		if ( array() !== $problems ) {
			return array(
				'failed'  => true,
				'flagged' => 0,
				'lines'   => $problems,
			);
		}

		$state    = $this->loadState();
		$flagged  = 0;
		$commands = array();

		foreach ( self::steps() as $step ) {
			$commands[ $step[0] ] = str_replace( '{tiers}', escapeshellarg( $tiers ), $step[1] );
		}

		foreach ( self::pending( $state, $from ) as $id ) {
			$result  = $this->execute( $commands[ $id ] );
			$verdict = 0 === $result['code'] ? self::verdict( $id, $result['stdout'] ) : 'failed';

			$state[ $id ] = array(
				'status'  => 0 === $result['code'] ? 'ok' : 'failed',
				'verdict' => $verdict,
				'at'      => gmdate( 'c' ),
			);
			$this->saveState( $state );

			$lines[] = sprintf( '%-22s %-9s %s', $id, $verdict, self::lastLine( 0 === $result['code'] ? $result['stdout'] : $result['stderr'] . $result['stdout'] ) );

			if ( 0 !== $result['code'] ) {
				return array(
					'failed'  => true,
					'flagged' => $flagged,
					'lines'   => $lines,
				);
			}

			if ( self::VERDICT_OK !== $verdict ) {
				++$flagged;
			}
		}

		return array(
			'failed'  => false,
			'flagged' => $flagged,
			'lines'   => $lines,
		);
	}

	/**
	 * The last non-empty line of some output, for the summary. Pure.
	 *
	 * @param string $output Command output.
	 */
	public static function lastLine( string $output ): string {
		$rows = array_values( array_filter( array_map( 'trim', explode( "\n", $output ) ), static fn ( string $l ): bool => '' !== $l ) );

		return $rows[ count( $rows ) - 1 ] ?? '(geen uitvoer)';
	}

	/**
	 * Run one WP-CLI command. Overridable seam.
	 *
	 * @param string $command The command (without the leading `wp`).
	 * @return array{code:int, stdout:string, stderr:string}
	 */
	protected function execute( string $command ): array {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return array(
				'code'   => 1,
				'stdout' => '',
				'stderr' => 'WP-CLI nie beskikbaar nie.',
			);
		}

		$result = \WP_CLI::runcommand(
			$command,
			array(
				'return'     => 'all',
				'exit_error' => false,
				'launch'     => false,
			)
		);

		return array(
			'code'   => (int) ( $result->return_code ?? 1 ),
			'stdout' => (string) ( $result->stdout ?? '' ),
			'stderr' => (string) ( $result->stderr ?? '' ),
		);
	}

	/**
	 * Whether a file exists. Overridable seam.
	 *
	 * @param string $path The path.
	 */
	protected function fileExists( string $path ): bool {
		return is_file( $path );
	}

	/**
	 * The stored step outcomes. Overridable seam.
	 *
	 * @return array<string, array{status?:string}>
	 */
	protected function loadState(): array {
		$state = get_option( self::OPTION_STATE, array() );

		return is_array( $state ) ? $state : array();
	}

	/**
	 * Persist the step outcomes. Overridable seam.
	 *
	 * @param array<string, array<string, string>> $state The outcomes.
	 */
	protected function saveState( array $state ): void {
		update_option( self::OPTION_STATE, $state, false );
	}
}
