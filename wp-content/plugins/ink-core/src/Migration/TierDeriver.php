<?php
/**
 * Derives writers' starting gradering from the old result posts — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Content\ChallengeResultTitle;
use Ink\Content\ResultPostAuthors;
use Ink\Kernel\Tier;
use Ink\Tiers\Api;
use Ink\Tiers\PromotionEngine;

defined( 'ABSPATH' ) || exit;

/**
 * `wp ink derive-tiers` — gives every writer the standing they would have reached had the
 * live engine seen their history. Prod stores no gradering, but it published the result of
 * every challenge: {@see LegacyResultsParser} reads each moderator result post, a Gedigte
 * win counts for the account {@see WriterResolver} finds behind the by-line, and
 * {@see PromotionEngine::standingForTotalWins()} (the live thresholds, the single source)
 * turns the total into a grade and the wins already counted at that grade.
 *
 * It replaces the hand-built tier CSV: nothing to supply, and the newest results are
 * included automatically. Writers with no Gedigte win keep the default grade. Wins whose
 * by-line cannot be resolved are NOT counted and are listed (never guessed); fuzzy matches
 * are listed too. Members only: a by-line with no account counts for nobody.
 *
 * Once-off + guarded; `--force` re-runs. `derive()` is the read-only half, used by
 * docs/migration-tools/validate-gradering.php.
 *
 * @package Ink\Core
 */
class TierDeriver {

	/**
	 * The completion flag option.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_tiers_derived_done';

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink derive-tiers';

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				if ( $this->hasRun() && ! isset( $assoc['force'] ) ) {
					\WP_CLI::success( 'Graderings reeds afgelei (oorgeslaan — reeds gedoen).' );
					return;
				}

				$report = $this->derive();

				foreach ( $report['fuzzy'] as $writer => $matched ) {
					\WP_CLI::log( sprintf( 'Benaderde ooreenkoms: "%s" -> "%s"', (string) $writer, (string) $matched ) );
				}

				foreach ( $report['unresolved'] as $writer => $count ) {
					\WP_CLI::warning( sprintf( 'Onopgelos: "%s" (%d wen(s) nie getel nie)', (string) $writer, (int) $count ) );
				}

				foreach ( $report['standings'] as $user_id => $standing ) {
					$this->applyStanding( (int) $user_id, $standing['tier'], $standing['count'] );
				}

				update_option( self::OPTION_DONE, true, false );

				\WP_CLI::success(
					sprintf(
						'Graderings afgelei uit %d uitslagplasings: %d skrywers gegradeer (brons %d, silwer %d, goud %d), %d onopgeloste by-lyne, %d reëls nie gelees nie.',
						$report['posts'],
						count( $report['standings'] ),
						$report['by_tier']['brons'],
						$report['by_tier']['silwer'],
						$report['by_tier']['goud'],
						count( $report['unresolved'] ),
						$report['unparsed']
					)
				);
			}
		);
	}

	/**
	 * Whether the derivation already ran.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Read the result posts and work out every writer's standing. Writes nothing.
	 *
	 * @return array{
	 *   posts:int, unparsed:int,
	 *   wins:array<int,int>,
	 *   standings:array<int, array{tier:Tier, count:int}>,
	 *   by_tier:array{brons:int, silwer:int, goud:int},
	 *   unresolved:array<string,int>, fuzzy:array<string,string>
	 * }
	 */
	public function derive(): array {
		$directory  = $this->directory();
		$resolver   = new WriterResolver( $directory['users'], $this->aliasMap() );
		$posts      = $this->resultPosts();
		$wins       = array();
		$unresolved = array();
		$fuzzy      = array();
		$unparsed   = 0;

		foreach ( $posts as $post ) {
			$parsed    = LegacyResultsParser::parse( (string) $post->html, $directory['names'] );
			$unparsed += count( $parsed['unparsed'] );

			foreach ( $parsed['rows'] as $row ) {
				if ( 'Gedigte' !== $row['category'] ) {
					continue; // Gradering counts poetry wins only.
				}

				$hit = $resolver->resolve( $row['writer'] );

				if ( null === $hit ) {
					$unresolved[ $row['writer'] ] = ( $unresolved[ $row['writer'] ] ?? 0 ) + 1;
					continue;
				}

				if ( 'fuzzy' === $hit['how'] ) {
					$fuzzy[ $row['writer'] ] = $hit['matched'];
				}

				$wins[ $hit['user'] ] = ( $wins[ $hit['user'] ] ?? 0 ) + 1;
			}
		}

		$standings = array();
		$by_tier   = array(
			'brons'  => 0,
			'silwer' => 0,
			'goud'   => 0,
		);

		foreach ( $wins as $user_id => $total ) {
			$standing              = PromotionEngine::standingForTotalWins( $total );
			$standings[ $user_id ] = $standing;
			$tier_key              = $standing['tier']->value;
			$by_tier[ $tier_key ]  = ( $by_tier[ $tier_key ] ?? 0 ) + 1;
		}

		arsort( $unresolved );

		return array(
			'posts'      => count( $posts ),
			'unparsed'   => $unparsed,
			'wins'       => $wins,
			'standings'  => $standings,
			'by_tier'    => $by_tier,
			'unresolved' => $unresolved,
			'fuzzy'      => $fuzzy,
		);
	}

	/**
	 * Write one writer's baseline standing. Overridable seam.
	 *
	 * @param int  $user_id The writer.
	 * @param Tier $tier    The grade.
	 * @param int  $count   The wins counted at that grade.
	 */
	protected function applyStanding( int $user_id, Tier $tier, int $count ): void {
		Api::importBaselineStanding( $user_id, $tier, $count );
	}

	/**
	 * Staff-authored result posts as `{id, html}`. Overridable seam.
	 *
	 * @return list<object>
	 */
	protected function resultPosts(): array {
		$posts = get_posts(
			array(
				'post_type'        => array( 'post', 'skryfwerk', 'artikel' ),
				'post_status'      => 'publish',
				'numberposts'      => -1,
				'suppress_filters' => false,
				'orderby'          => 'date',
				'order'            => 'ASC',
			)
		);

		$out = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			if ( ChallengeResultTitle::isResultTitle( (string) $post->post_title ) && ResultPostAuthors::isModerator( (int) $post->post_author ) ) {
				$out[] = (object) array(
					'id'   => (int) $post->ID,
					'html' => (string) $post->post_content,
				);
			}
		}

		return $out;
	}

	/**
	 * The account directory: name key → account id, and the plain names (for the parser).
	 * The committed by-line table wins over the site's own display names. Overridable seam.
	 *
	 * @return array{users:array<string,int>, names:list<string>}
	 */
	protected function directory(): array {
		$users = array();
		$names = array();

		foreach ( $this->readCsv( 'writer-accounts.csv' ) as $row ) {
			$account = get_user_by( 'login', (string) ( $row['user_login'] ?? '' ) );
			$key     = LegacyResultsParser::nameKey( (string) ( $row['name'] ?? '' ) );

			if ( false !== $account && '' !== $key ) {
				$users[ $key ] = (int) $account->ID;
				$names[]       = (string) $row['name'];
			}
		}

		foreach ( get_users( array( 'fields' => array( 'ID', 'display_name', 'user_nicename', 'user_login' ) ) ) as $account ) {
			foreach ( array( $account->display_name, $account->user_nicename, $account->user_login ) as $name ) {
				$key = LegacyResultsParser::nameKey( (string) $name );

				if ( '' !== $key && ! isset( $users[ $key ] ) ) {
					$users[ $key ] = (int) $account->ID;
					$names[]       = (string) $name;
				}
			}
		}

		return array(
			'users' => $users,
			'names' => $names,
		);
	}

	/**
	 * Variant name key → canonical name key, from the committed alias table. Overridable seam.
	 *
	 * @return array<string, string>
	 */
	protected function aliasMap(): array {
		$map = array();

		foreach ( $this->readCsv( 'writer-aliases.csv' ) as $row ) {
			$variant   = LegacyResultsParser::nameKey( (string) ( $row['variant'] ?? '' ) );
			$canonical = LegacyResultsParser::nameKey( (string) ( $row['canonical'] ?? '' ) );

			if ( '' !== $variant && '' !== $canonical ) {
				$map[ $variant ] = $canonical;
			}
		}

		return $map;
	}

	/**
	 * Rows of a committed CSV in the plugin's migration-data folder (header-keyed). Overridable seam.
	 *
	 * @param string $file The file name.
	 * @return list<array<string, string>>
	 */
	protected function readCsv( string $file ): array {
		$path = dirname( __DIR__, 2 ) . '/migration-data/' . $file;

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- once-off CLI reader of a committed data file.
		if ( ! is_readable( $path ) ) {
			return array();
		}

		$handle = fopen( $path, 'rb' );

		if ( false === $handle ) {
			return array();
		}

		$header = fgetcsv( $handle );
		$rows   = array();

		if ( is_array( $header ) ) {
			$header[0] = ltrim( (string) $header[0], "\xEF\xBB\xBF" );
			$line      = fgetcsv( $handle );

			while ( false !== $line ) {
				if ( count( $line ) === count( $header ) ) {
					$rows[] = array_map( 'strval', array_combine( $header, $line ) );
				}

				$line = fgetcsv( $handle );
			}
		}

		fclose( $handle );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $rows;
	}
}
