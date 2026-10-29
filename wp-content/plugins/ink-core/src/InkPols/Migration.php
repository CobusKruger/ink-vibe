<?php
/**
 * InkPols back-catalogue migration from the committed CSV — WP4 (`inkpols.md` §3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\MigrationData;
use Ink\Kernel\Scalar;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Creates one `inkpols_uitgawe` per row of `migration-data/inkpols-uitgawes.csv`
 * (`docs/lovable-delta-2026-10/inkpols.md` §3).
 *
 * Prod keeps its issues as root Pages with an embedded PDF, plus one `/inkpols/` index
 * Page, and carries no structured metadata, so every field comes from the committed CSV
 * the owner fills in:
 *  - the post title is the `naam` ("Julie/Augustus 2026"), the slug is the CSV `slug`
 *    (set explicitly, never derived from the title), and the publish date is `datum` (the
 *    issue's first month), so published order and issue order agree;
 *  - `pdf` and `voorblad` are matched to EXISTING attachments by upload path
 *    (`_wp_attached_file`); nothing is uploaded or copied. The cover is also the featured
 *    image, so the SEO plugin uses it as the social image;
 *  - `titel`, `beskrywing` and the three featured items go to the {@see FieldSets} meta.
 *
 * **Required to publish** (§2): PDF, cover, naam, titel, beskrywing and the six featured
 * cells, with no exemption for imported issues. A row missing any of them is still
 * created, as a DRAFT, and every missing field is reported; the run itself succeeds, so
 * owner-pending blanks never stop `migrate-all`. Filling the CSV and re-running publishes.
 *
 * **Redirects and retirement.** Each issue records its `ou_url` under the shared
 * migration source-URL key ({@see SOURCE_URL_META}), so `wp ink generate-redirects`
 * (which runs after this step) writes the 301 to the issue's permalink, published issues
 * only. The old issue Page is moved to the trash once its issue is published, which is
 * when the 301 can take over (the redirect only fires on a 404); while the issue is a
 * draft the old Page stays live. The `/inkpols/` index Page is trashed on every run: the
 * CPT archive owns that URL. Trash, never delete: the prod content stays recoverable.
 *
 * Once-off + guarded: a completion option ({@see OPTION_DONE}) makes a re-run a no-op;
 * `--force` re-runs and reconciles through the {@see SOURCE_SLUG_META} get-or-create
 * marker, never duplicating. WP-CLI only (`wp ink migrate-inkpols`). Rows that cannot
 * become an issue at all (bad slug or datum, duplicate slug, wrong cell count) are
 * skipped and reported; a missing CSV or missing columns fail the step.
 *
 * Not `final`: the I/O methods are overridable seams so the orchestration is
 * unit-testable without the WordPress post/meta API; the decisions are pure statics.
 *
 * @package Ink\Core
 */
class Migration {

	/**
	 * The completion flag option — set once the CSV migration has run. A new key: the
	 * flag the old no-op step set must not make this step skip.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_inkpols_csv_migration_done';

	/**
	 * The WP-CLI command name (`wp ink migrate-inkpols`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-inkpols';

	/**
	 * The input file, located by {@see MigrationData}.
	 *
	 * @var string
	 */
	public const CSV_FILE = 'inkpols-uitgawes.csv';

	/**
	 * Meta key linking an issue to its CSV row (the `slug`) — the get-or-create marker
	 * that makes a `--force` re-run reconcile instead of duplicating.
	 *
	 * @var string
	 */
	public const SOURCE_SLUG_META = 'ink_inkpols_source_slug';

	/**
	 * The migration's shared old-URL key, read by `wp ink generate-redirects`. The same
	 * value as `Ink\Migration\PostReclassifier::SOURCE_URL_META`, repeated because the
	 * module rules keep InkPols off Migration; a unit test holds the two together.
	 *
	 * @var string
	 */
	public const SOURCE_URL_META = 'ink_migration_source_url';

	/**
	 * The prod `/inkpols/` index Page's path, retired because the CPT archive owns it.
	 *
	 * @var string
	 */
	public const INDEX_PAGE_PATH = 'inkpols';

	/**
	 * The columns the CSV must carry (`migration-data/README.md`).
	 *
	 * @var list<string>
	 */
	public const COLUMNS = array(
		'slug',
		'naam',
		'titel',
		'beskrywing',
		'datum',
		'pdf',
		'voorblad',
		'ou_url',
		'gedig_titel',
		'gedig_skrywer',
		'storie_titel',
		'storie_skrywer',
		'artikel_titel',
		'artikel_skrywer',
	);

	/**
	 * The fields required to publish (`inkpols.md` §2), in report order.
	 *
	 * @var list<string>
	 */
	public const REQUIRED = array(
		'pdf',
		'voorblad',
		'naam',
		'titel',
		'beskrywing',
		'gedig_titel',
		'gedig_skrywer',
		'storie_titel',
		'storie_skrywer',
		'artikel_titel',
		'artikel_skrywer',
	);

	/**
	 * CSV text column → issue meta key.
	 *
	 * @var array<string, string>
	 */
	public const TEXT_META = array(
		'titel'           => FieldSets::INKPOLS_TITEL,
		'beskrywing'      => FieldSets::INKPOLS_TEASER,
		'gedig_titel'     => FieldSets::INKPOLS_GEDIG_TITEL,
		'gedig_skrywer'   => FieldSets::INKPOLS_GEDIG_SKRYWER,
		'storie_titel'    => FieldSets::INKPOLS_STORIE_TITEL,
		'storie_skrywer'  => FieldSets::INKPOLS_STORIE_SKRYWER,
		'artikel_titel'   => FieldSets::INKPOLS_ARTIKEL_TITEL,
		'artikel_skrywer' => FieldSets::INKPOLS_ARTIKEL_SKRYWER,
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

				if ( '' !== $summary['error'] ) {
					\WP_CLI::error( $summary['error'] );
				}

				foreach ( $summary['problems'] as $problem ) {
					\WP_CLI::warning( $problem );
				}

				foreach ( $summary['incomplete'] as $slug => $missing ) {
					\WP_CLI::warning( sprintf( 'Uitgawe "%s" as konsep gestoor; verpligte velde ontbreek: %s.', $slug, implode( ', ', $missing ) ) );
				}

				\WP_CLI::success( self::summaryLine( $summary ) );
			}
		);
	}

	/**
	 * The one-line result `migrate-all` shows and judges. Pure.
	 *
	 * @param array{skipped:bool, created:int, updated:int, published:int, drafts:int, retired:int, kept:int, problems:list<string>} $summary A {@see run()} summary.
	 */
	public static function summaryLine( array $summary ): string {
		return sprintf(
			'InkPols-uitgawes gemigreer: %d geskep, %d bygewerk, %d gepubliseer, %d as konsep (verpligte velde ontbreek), %d rye oorgeslaan, %d ou bladsye na die asblik, %d ou bladsye behou tot hul uitgawe gepubliseer is%s.',
			$summary['created'],
			$summary['updated'],
			$summary['published'],
			$summary['drafts'],
			count( $summary['problems'] ),
			$summary['retired'],
			$summary['kept'],
			$summary['skipped'] ? ' (oorgeslaan — reeds gedoen)' : ''
		);
	}

	/**
	 * Parse the CSV text into header-keyed, trimmed rows. Pure.
	 *
	 * Quoted cells may span lines (a `beskrywing` with a line break). Blank lines are
	 * ignored. A row with the wrong number of cells is reported and left out. A header
	 * missing any of {@see COLUMNS} is fatal (`error`): every row would be misread.
	 *
	 * @param string $csv The file contents.
	 * @return array{rows:list<array<string, string>>, problems:list<string>, error:string}
	 */
	public static function parseCsv( string $csv ): array {
		$out = array(
			'rows'     => array(),
			'problems' => array(),
			'error'    => '',
		);

		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- an in-memory stream, so fgetcsv can read quoted multi-line cells; no file is touched.
		$handle = fopen( 'php://temp', 'r+b' );

		if ( false === $handle ) {
			$out['error'] = 'Die InkPols-CSV kon nie gelees word nie.';
			return $out;
		}

		fwrite( $handle, $csv );
		rewind( $handle );

		$header = fgetcsv( $handle, null, ',', '"', '' );

		if ( ! is_array( $header ) ) {
			fclose( $handle );
			$out['error'] = 'Die InkPols-CSV is leeg.';
			return $out;
		}

		$header    = array_map( static fn ( $cell ): string => trim( (string) $cell ), $header );
		$header[0] = ltrim( $header[0], "\xEF\xBB\xBF" );
		$absent    = array_values( array_diff( self::COLUMNS, $header ) );

		if ( array() !== $absent ) {
			fclose( $handle );
			$out['error'] = sprintf( 'Kolomme ontbreek in die InkPols-CSV: %s.', implode( ', ', $absent ) );
			return $out;
		}

		$number = 1;
		$line   = fgetcsv( $handle, null, ',', '"', '' );

		while ( false !== $line ) {
			++$number;

			// fgetcsv yields array( null ) for a blank line.
			if ( array( null ) !== $line ) {
				if ( count( $line ) === count( $header ) ) {
					$out['rows'][] = array_combine( $header, array_map( static fn ( $cell ): string => trim( (string) $cell ), $line ) );
				} else {
					$out['problems'][] = sprintf( 'Ry %d oorgeslaan: %d selle, %d verwag.', $number, count( $line ), count( $header ) );
				}
			}

			$line = fgetcsv( $handle, null, ',', '"', '' );
		}

		fclose( $handle );
		// phpcs:enable WordPress.WP.AlternativeFunctions.file_system_operations_fopen, WordPress.WP.AlternativeFunctions.file_system_operations_fwrite, WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return $out;
	}

	/**
	 * Keep only the rows that can become an issue: a valid slug, a real `datum`, and a
	 * slug no earlier row used. Owner-pending blanks do NOT disqualify a row. Pure.
	 *
	 * @param list<array<string, string>> $rows The parsed rows.
	 * @return array{rows:list<array<string, string>>, problems:list<string>}
	 */
	public static function validRows( array $rows ): array {
		$out  = array(
			'rows'     => array(),
			'problems' => array(),
		);
		$seen = array();

		foreach ( $rows as $row ) {
			$slug = $row['slug'] ?? '';

			if ( 1 !== preg_match( '/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug ) ) {
				$out['problems'][] = sprintf( 'Ry met slug "%s" oorgeslaan: die slug moet kleinletters, syfers en koppeltekens wees.', $slug );
				continue;
			}

			if ( '' === self::normalisedDate( $row['datum'] ?? '' ) ) {
				$out['problems'][] = sprintf( 'Ry "%s" oorgeslaan: datum "%s" is nie \'n geldige JJJJ-MM-DD nie.', $slug, $row['datum'] ?? '' );
				continue;
			}

			if ( isset( $seen[ $slug ] ) ) {
				$out['problems'][] = sprintf( 'Ry "%s" oorgeslaan: die slug kom reeds vroeër in die CSV voor.', $slug );
				continue;
			}

			$seen[ $slug ] = true;
			$out['rows'][] = $row;
		}

		return $out;
	}

	/**
	 * A `Y-m-d` string when it is a real calendar date, else ''. Pure.
	 *
	 * @param string $date The CSV `datum`.
	 */
	public static function normalisedDate( string $date ): string {
		$date = trim( $date );

		if ( 1 !== preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m ) ) {
			return '';
		}

		return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ? $date : '';
	}

	/**
	 * A CSV upload path in the `_wp_attached_file` shape (`2025/12/x.pdf`). Pure.
	 *
	 * Accepts a bare path, one with a leading slash or `wp-content/uploads/`, Windows
	 * separators, or a full uploads URL.
	 *
	 * @param string $path The CSV `pdf`/`voorblad` cell.
	 */
	public static function uploadPath( string $path ): string {
		$path = str_replace( '\\', '/', trim( $path ) );
		$at   = strpos( $path, 'wp-content/uploads/' );

		if ( false !== $at ) {
			$path = substr( $path, $at + strlen( 'wp-content/uploads/' ) );
		}

		return ltrim( $path, '/' );
	}

	/**
	 * The old Page's path from `ou_url` (`/inkpols-2025-11/` → `inkpols-2025-11`), or ''. Pure.
	 *
	 * @param string $url The CSV `ou_url` (a path or a full URL).
	 */
	public static function oldPath( string $url ): string {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- pure helper on a path/URL string, kept WP-free so it unit-tests without WordPress.
		$path = parse_url( trim( $url ), PHP_URL_PATH );

		return is_string( $path ) ? trim( $path, '/' ) : '';
	}

	/**
	 * The fields this row still lacks before it may be published, in report order. Pure.
	 *
	 * A PDF or cover that is named but has no attachment at that path is reported with
	 * the path, so it reads as a wrong path rather than a blank cell.
	 *
	 * @param array<string, string> $row      The CSV row.
	 * @param int                   $pdf_id   The matched PDF attachment, or 0.
	 * @param int                   $cover_id The matched cover attachment, or 0.
	 * @return list<string>
	 */
	public static function missingRequired( array $row, int $pdf_id, int $cover_id ): array {
		$missing  = array();
		$attached = array(
			'pdf'      => $pdf_id,
			'voorblad' => $cover_id,
		);

		foreach ( self::REQUIRED as $field ) {
			$value = trim( $row[ $field ] ?? '' );

			if ( '' === $value ) {
				$missing[] = $field;
				continue;
			}

			if ( isset( $attached[ $field ] ) && $attached[ $field ] <= 0 ) {
				$missing[] = sprintf( '%s (geen aanhegsel by %s nie)', $field, self::uploadPath( $value ) );
			}
		}

		return $missing;
	}

	/**
	 * Build the `wp_insert_post` array for a row. Pure.
	 *
	 * `edit_date` keeps WordPress from replacing the date of a draft with "now" when it is
	 * later published.
	 *
	 * @param array<string, string> $row    A valid CSV row.
	 * @param string                $status `publish` or `draft`.
	 * @return array<string, mixed>
	 */
	public static function issuePostArr( array $row, string $status ): array {
		return array(
			'post_type'   => PostTypes::INKPOLS_UITGAWE,
			'post_title'  => $row['naam'] ?? '',
			'post_name'   => $row['slug'] ?? '',
			'post_status' => $status,
			'post_date'   => self::normalisedDate( $row['datum'] ?? '' ) . ' 00:00:00',
			'edit_date'   => true,
		);
	}

	/**
	 * The issue meta for a row: date, attachments, the text fields and the CSV marker. Pure.
	 *
	 * Blank text cells are written as '' so a re-run clears a value the CSV no longer has.
	 *
	 * @param array<string, string> $row      A valid CSV row.
	 * @param int                   $pdf_id   The matched PDF attachment, or 0.
	 * @param int                   $cover_id The matched cover attachment, or 0.
	 * @return array<string, int|string>
	 */
	public static function issueMeta( array $row, int $pdf_id, int $cover_id ): array {
		$meta = array(
			FieldSets::INKPOLS_ISSUE_DATE => self::normalisedDate( $row['datum'] ?? '' ),
			FieldSets::INKPOLS_PDF_ID     => max( 0, $pdf_id ),
			FieldSets::INKPOLS_COVER_ID   => max( 0, $cover_id ),
			self::SOURCE_SLUG_META        => $row['slug'] ?? '',
		);

		foreach ( self::TEXT_META as $column => $key ) {
			$meta[ $key ] = $row[ $column ] ?? '';
		}

		return $meta;
	}

	/**
	 * Run the migration. Idempotent unless `$force`.
	 *
	 * `created` counts new inserts and `updated` the issues a `--force` re-run reconciled.
	 * `incomplete` maps each draft's slug to its missing fields.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, error:string, created:int, updated:int, published:int, drafts:int, retired:int, kept:int, problems:list<string>, incomplete:array<string, list<string>>}
	 */
	public function run( bool $force = false ): array {
		$summary = array(
			'skipped'    => false,
			'error'      => '',
			'created'    => 0,
			'updated'    => 0,
			'published'  => 0,
			'drafts'     => 0,
			'retired'    => 0,
			'kept'       => 0,
			'problems'   => array(),
			'incomplete' => array(),
		);

		if ( $this->hasRun() && ! $force ) {
			$summary['skipped'] = true;
			return $summary;
		}

		$csv = $this->readCsv();

		if ( null === $csv ) {
			$summary['error'] = sprintf( 'InkPols-CSV nie gevind of nie leesbaar nie: %s', $this->csvPath() );
			return $summary;
		}

		$parsed = self::parseCsv( $csv );

		if ( '' !== $parsed['error'] ) {
			$summary['error'] = $parsed['error'];
			return $summary;
		}

		$valid               = self::validRows( $parsed['rows'] );
		$summary['problems'] = array_merge( $parsed['problems'], $valid['problems'] );

		foreach ( $valid['rows'] as $row ) {
			$slug     = $row['slug'];
			$pdf_id   = '' !== $row['pdf'] ? $this->attachmentIdFor( self::uploadPath( $row['pdf'] ) ) : 0;
			$cover_id = '' !== $row['voorblad'] ? $this->attachmentIdFor( self::uploadPath( $row['voorblad'] ) ) : 0;
			$missing  = self::missingRequired( $row, $pdf_id, $cover_id );
			$status   = array() === $missing ? 'publish' : 'draft';

			$result   = $this->ensureIssue( $slug, self::issuePostArr( $row, $status ) );
			$issue_id = $result['id'];

			if ( $issue_id <= 0 ) {
				$summary['problems'][] = sprintf( 'Uitgawe "%s" kon nie gestoor word nie.', $slug );
				continue;
			}

			if ( $result['created'] ) {
				++$summary['created'];
			} else {
				++$summary['updated'];
			}

			foreach ( self::issueMeta( $row, $pdf_id, $cover_id ) as $key => $value ) {
				$this->setIssueMeta( $issue_id, $key, $value );
			}

			if ( $cover_id > 0 ) {
				$this->setCover( $issue_id, $cover_id );
			}

			$old_path = self::oldPath( $row['ou_url'] );

			if ( '' !== $old_path ) {
				$this->setIssueMeta( $issue_id, self::SOURCE_URL_META, $this->homeUrl( '/' . $old_path . '/' ) );
			}

			$old_page = '' !== $old_path ? $this->pageIdForPath( $old_path ) : 0;

			if ( 'publish' === $status ) {
				++$summary['published'];

				if ( $old_page > 0 && $this->trashPage( $old_page ) ) {
					++$summary['retired'];
				}

				continue;
			}

			++$summary['drafts'];
			$summary['incomplete'][ $slug ] = $missing;

			if ( $old_page > 0 ) {
				++$summary['kept'];
			}
		}

		$index = $this->pageIdForPath( self::INDEX_PAGE_PATH );

		if ( $index > 0 && $this->trashPage( $index ) ) {
			++$summary['retired'];
		}

		$this->markDone();

		return $summary;
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
	 * Where the CSV is read from. Overridable seam.
	 */
	protected function csvPath(): string {
		return MigrationData::path( self::CSV_FILE );
	}

	/**
	 * The CSV contents, or null when the file is missing or unreadable. Overridable seam.
	 */
	protected function readCsv(): ?string {
		$path = $this->csvPath();

		if ( ! is_readable( $path ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- once-off CLI read of a committed local data file.
		$contents = file_get_contents( $path );

		return false === $contents ? null : $contents;
	}

	/**
	 * The attachment stored at an upload path (`_wp_attached_file`), or 0. The lowest id
	 * wins if the path is stored twice. Overridable seam.
	 *
	 * @param string $path The path relative to the uploads folder.
	 */
	protected function attachmentIdFor( string $path ): int {
		if ( '' === $path ) {
			return 0;
		}

		$ids = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'orderby'          => 'ID',
				'order'            => 'ASC',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a once-off CLI lookup of seven attachments, not a request-path query.
				'meta_query'       => array(
					array(
						'key'   => '_wp_attached_file',
						'value' => $path,
					),
				),
			)
		);

		return ( is_array( $ids ) && isset( $ids[0] ) ) ? Scalar::asNonNegativeInt( $ids[0] ) : 0;
	}

	/**
	 * Get-or-create the issue for a CSV slug (idempotent). Overridable seam. An issue
	 * already made from this row (matched by {@see SOURCE_SLUG_META}) is updated in place,
	 * so a `--force` re-run reconciles title, slug, date and status without duplicating.
	 *
	 * @param string               $slug    The CSV slug.
	 * @param array<string, mixed> $postarr The {@see issuePostArr()} args.
	 * @return array{id:int, created:bool} The issue id (0 on failure) + insert flag.
	 */
	protected function ensureIssue( string $slug, array $postarr ): array {
		$existing = $this->findIssueForSlug( $slug );

		if ( $existing > 0 ) {
			return array(
				'id'      => $this->updateIssue( array( 'ID' => $existing ) + $postarr ) ? $existing : 0,
				'created' => false,
			);
		}

		$id = $this->createIssue( $postarr );

		return array(
			'id'      => $id,
			'created' => $id > 0,
		);
	}

	/**
	 * The id of the issue already made from this CSV slug, or 0. Overridable seam.
	 *
	 * @param string $slug The CSV slug.
	 */
	protected function findIssueForSlug( string $slug ): int {
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
						'key'   => self::SOURCE_SLUG_META,
						'value' => $slug,
					),
				),
			)
		);

		return ( is_array( $ids ) && isset( $ids[0] ) ) ? Scalar::asNonNegativeInt( $ids[0] ) : 0;
	}

	/**
	 * Insert an issue. Overridable seam.
	 *
	 * @param array<string, mixed> $postarr The `wp_insert_post` args.
	 * @return int The new issue id, or 0 on failure.
	 */
	protected function createIssue( array $postarr ): int {
		$id = wp_insert_post( self::withGmtDate( $postarr ), true );

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Update an existing issue. Overridable seam.
	 *
	 * @param array<string, mixed> $postarr The `wp_update_post` args, with `ID`.
	 * @return bool Whether it was saved.
	 */
	protected function updateIssue( array $postarr ): bool {
		$id = wp_update_post( self::withGmtDate( $postarr ), true );

		return ! is_wp_error( $id ) && (int) $id > 0;
	}

	/**
	 * Add the GMT twin of `post_date`, so a draft keeps its date too.
	 *
	 * @param array<string, mixed> $postarr The post args.
	 * @return array<string, mixed>
	 */
	private static function withGmtDate( array $postarr ): array {
		$postarr['post_date_gmt'] = get_gmt_from_date( Scalar::asString( $postarr['post_date'] ?? '' ) );

		return $postarr;
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

	/**
	 * Make the cover the issue's featured image (the social image). Overridable seam.
	 *
	 * @param int $issue_id The issue post id.
	 * @param int $cover_id The cover attachment id.
	 */
	protected function setCover( int $issue_id, int $cover_id ): void {
		set_post_thumbnail( $issue_id, $cover_id );
	}

	/**
	 * The Page at a path that is not yet in the trash, or 0. Overridable seam.
	 *
	 * @param string $path The page path, without slashes.
	 */
	protected function pageIdForPath( string $path ): int {
		$page = get_page_by_path( $path, OBJECT, 'page' );

		return ( $page instanceof WP_Post && 'trash' !== $page->post_status ) ? (int) $page->ID : 0;
	}

	/**
	 * Move a retired Page to the trash (recoverable, never deleted). Overridable seam.
	 *
	 * @param int $page_id The page id.
	 * @return bool Whether it was trashed.
	 */
	protected function trashPage( int $page_id ): bool {
		return wp_trash_post( $page_id ) instanceof WP_Post;
	}

	/**
	 * A site URL for a path. Overridable seam.
	 *
	 * @param string $path The path.
	 */
	protected function homeUrl( string $path ): string {
		return home_url( $path );
	}
}
