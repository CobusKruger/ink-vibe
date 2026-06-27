<?php
/**
 * Once-off InkPols back-catalogue migration — Story 13.4 (FL 13.4, §14.6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Converts legacy InkPols issues into the `inkpols_uitgawe` model — a once-off,
 * idempotent DB update (FL 13.4).
 *
 * Each legacy issue becomes an `inkpols_uitgawe` post whose EXISTING PDF is
 * re-linked (the attachment id written to {@see FieldSets::INKPOLS_PDF_ID} — the
 * back catalogue rides the cloned DB, media is never re-uploaded) and whose
 * month/year naming is replaced with structured meta: a normalised issue date
 * ({@see FieldSets::INKPOLS_ISSUE_DATE}, parsed by {@see issueDateFromName()})
 * plus a volume label ({@see FieldSets::INKPOLS_VOLUME}). An unparseable name
 * leaves the issue date empty rather than fabricating one.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a
 * no-op (a `--force` re-run is opt-in and reconciles via the
 * {@see SOURCE_LEGACY_META} get-or-create marker, never duplicating), and the
 * trigger is WP-CLI only (`wp ink migrate-inkpols`) — NEVER auto-run on a web
 * request. Conflation-clean: reads `Ink\Content` + WP core, zero Tiers/Entitlement.
 *
 * Not `final`: the I/O methods are overridable seams so the orchestration is
 * unit-testable without the WordPress post/meta API (the {@see \Ink\Challenges\Migration}
 * precedent).
 *
 * @package Ink\Core
 */
class Migration {

	/**
	 * The completion flag option — set once the migration has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_inkpols_migration_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-inkpols`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-inkpols';

	/**
	 * Meta key linking a migrated issue back to its source legacy issue id — the
	 * get-or-create marker that makes a `--force` re-run reconcile (12.8 R12).
	 *
	 * @var string
	 */
	public const SOURCE_LEGACY_META = 'ink_inkpols_source_id';

	/**
	 * Afrikaans month name (lowercase) → two-digit month, for parsing legacy
	 * "Maand JJJJ" issue naming. Migration-local (does not depend on Challenges).
	 *
	 * @var array<string, string>
	 */
	public const MONTHS = array(
		'januarie'  => '01',
		'februarie' => '02',
		'maart'     => '03',
		'april'     => '04',
		'mei'       => '05',
		'junie'     => '06',
		'julie'     => '07',
		'augustus'  => '08',
		'september' => '09',
		'oktober'   => '10',
		'november'  => '11',
		'desember'  => '12',
	);

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
						'InkPols-uitgawes gemigreer: %d geskep, %d PDF\'s herkoppel%s.',
						(int) $summary['created'],
						(int) $summary['relinked'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * Parse legacy month/year naming into a normalised `Y-m-01` issue date. Pure.
	 *
	 * Handles the Afrikaans "Maand JJJJ" shape, plus numeric `YYYY-MM` and
	 * `MM/YYYY`. Returns '' when nothing parseable is present (no fabricated date).
	 *
	 * @param string $name The legacy issue name/title.
	 * @return string A `Y-m-01` date, or ''.
	 */
	public static function issueDateFromName( string $name ): string {
		$name = strtolower( trim( $name ) );

		if ( '' === $name ) {
			return '';
		}

		// Afrikaans "Maand JJJJ" (in either order).
		foreach ( self::MONTHS as $label => $mm ) {
			if ( false !== strpos( $name, $label ) && 1 === preg_match( '/(\d{4})/', $name, $y ) ) {
				return $y[1] . '-' . $mm . '-01';
			}
		}

		// Numeric YYYY-MM (or YYYY/MM).
		if ( 1 === preg_match( '#(\d{4})[-/](\d{1,2})#', $name, $m ) ) {
			$month = str_pad( $m[2], 2, '0', STR_PAD_LEFT );
			if ( (int) $month >= 1 && (int) $month <= 12 ) {
				return $m[1] . '-' . $month . '-01';
			}
		}

		// Numeric MM/YYYY (or MM-YYYY).
		if ( 1 === preg_match( '#(\d{1,2})[-/](\d{4})#', $name, $m ) ) {
			$month = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
			if ( (int) $month >= 1 && (int) $month <= 12 ) {
				return $m[2] . '-' . $month . '-01';
			}
		}

		return '';
	}

	/**
	 * Build the `wp_insert_post` array for the issue from a legacy issue. Pure.
	 *
	 * @param object $issue A legacy issue row (carries a `name`/`title`).
	 * @return array<string, mixed>
	 */
	public static function issuePostArr( object $issue ): array {
		return array(
			'post_type'   => PostTypes::INKPOLS_UITGAWE,
			'post_title'  => self::legacyName( $issue ),
			'post_status' => 'publish',
		);
	}

	/**
	 * The legacy issue's name/title. Pure.
	 *
	 * @param object $issue The legacy issue row.
	 * @return string
	 */
	public static function legacyName( object $issue ): string {
		$name = Scalar::asString( $issue->name ?? '' );

		if ( '' === $name ) {
			$name = Scalar::asString( $issue->title ?? '' );
		}

		return trim( $name );
	}

	/**
	 * Run the once-off migration. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, created:int, relinked:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped'  => true,
				'created'  => 0,
				'relinked' => 0,
			);
		}

		$created  = 0;
		$relinked = 0;

		foreach ( $this->legacyIssues() as $issue ) {
			// Skip a malformed/empty-name issue: it would otherwise create an
			// untitled published uitgawe (12.8 R12 precedent).
			if ( '' === self::legacyName( $issue ) ) {
				continue;
			}

			$issue_id = $this->ensureIssue( $issue );

			if ( $issue_id <= 0 ) {
				continue;
			}

			++$created;

			// Month/year naming → date + volume meta.
			$date = self::issueDateFromName( self::legacyName( $issue ) );

			if ( '' !== $date ) {
				$this->setIssueMeta( $issue_id, FieldSets::INKPOLS_ISSUE_DATE, $date );
			}

			$volume = $this->volumeFor( $issue );

			if ( '' !== $volume ) {
				$this->setIssueMeta( $issue_id, FieldSets::INKPOLS_VOLUME, $volume );
			}

			// Re-link the existing PDF attachment (never re-uploaded).
			$pdf_id = $this->pdfIdFor( $issue );

			if ( $pdf_id > 0 ) {
				$this->setIssueMeta( $issue_id, FieldSets::INKPOLS_PDF_ID, $pdf_id );
				++$relinked;
			}
		}

		$this->markDone();

		return array(
			'skipped'  => false,
			'created'  => $created,
			'relinked' => $relinked,
		);
	}

	/**
	 * Whether the migration has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the migration complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The legacy InkPols issues to migrate. Overridable seam.
	 *
	 * SAFE DEFAULT (12.8 precedent): returns an EMPTY list. The legacy-issue
	 * selection is site-specific (the old `inkpols` CPT / a category / a query),
	 * and a blanket default would mis-convert unrelated posts. A site MUST override
	 * this with the actual legacy issues before the once-off run; an un-overridden
	 * run is a deliberate no-op.
	 *
	 * @return array<int, object>
	 */
	protected function legacyIssues(): array {
		return array();
	}

	/**
	 * Get-or-create the `inkpols_uitgawe` post for a legacy issue (idempotent).
	 * Overridable seam. Reuses an issue already migrated from this source (matched
	 * by the {@see SOURCE_LEGACY_META} marker) so a `--force` re-run reconciles
	 * instead of inserting a duplicate (12.8 R12).
	 *
	 * @param object $issue The legacy issue row.
	 * @return int The issue post id, or 0 on failure.
	 */
	protected function ensureIssue( object $issue ): int {
		$legacy_id = (int) ( $issue->id ?? 0 );

		$existing = $this->findIssueForLegacy( $legacy_id );

		if ( $existing > 0 ) {
			return $existing;
		}

		$id = $this->createIssue( self::issuePostArr( $issue ) );

		if ( $id > 0 && $legacy_id > 0 ) {
			update_post_meta( $id, self::SOURCE_LEGACY_META, $legacy_id );
		}

		return $id;
	}

	/**
	 * The id of an issue already migrated from this legacy source, or 0.
	 * Overridable seam.
	 *
	 * @param int $legacy_id The legacy issue id.
	 * @return int
	 */
	protected function findIssueForLegacy( int $legacy_id ): int {
		if ( $legacy_id <= 0 ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'        => PostTypes::INKPOLS_UITGAWE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a once-off CLI migration lookup keyed on the source marker, not a request-path query.
				'meta_query'       => array(
					array(
						'key'   => self::SOURCE_LEGACY_META,
						'value' => $legacy_id,
					),
				),
			)
		);

		return ( is_array( $ids ) && isset( $ids[0] ) ) ? (int) $ids[0] : 0;
	}

	/**
	 * Create the `inkpols_uitgawe` post for an issue. Overridable seam.
	 *
	 * @param array<string, mixed> $postarr The `wp_insert_post` args.
	 * @return int The new issue id, or 0 on failure.
	 */
	protected function createIssue( array $postarr ): int {
		$id = wp_insert_post( $postarr, true );

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * The existing PDF attachment id for a legacy issue (0 when none). Overridable
	 * seam — the back catalogue rides the cloned DB; resolving the attachment is
	 * site-specific (a legacy meta key, an attached child, a filename match).
	 *
	 * @param object $issue The legacy issue row.
	 * @return int
	 */
	protected function pdfIdFor( object $issue ): int {
		return Scalar::asNonNegativeInt( $issue->pdf_id ?? 0 );
	}

	/**
	 * The volume label for a legacy issue. Overridable seam — defaults to the
	 * explicit legacy volume where present, else the legacy name.
	 *
	 * @param object $issue The legacy issue row.
	 * @return string
	 */
	protected function volumeFor( object $issue ): string {
		$volume = Scalar::asString( $issue->volume ?? '' );

		return '' !== trim( $volume ) ? trim( $volume ) : self::legacyName( $issue );
	}

	/**
	 * Persist one issue meta value. Overridable seam.
	 *
	 * @param int        $issue_id The issue post id.
	 * @param string     $key      The meta key.
	 * @param int|string $value    The meta value.
	 */
	protected function setIssueMeta( int $issue_id, string $key, int|string $value ): void {
		update_post_meta( $issue_id, $key, $value );
	}
}
