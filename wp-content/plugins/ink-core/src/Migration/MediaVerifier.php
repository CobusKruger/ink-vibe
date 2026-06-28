<?php
/**
 * Read-only media verification — Story 16.10 (FL 16.10).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Verifies the migrated media library — a READ-ONLY post-migration report
 * (FL 16.10). `wp-content/uploads/` and the attachment posts ride the DB clone;
 * this confirms uploads are accessible, audio/video present, and PDFs present.
 *
 * The report covers total attachments, counts per media class (image / audio /
 * video / pdf / other, derived from MIME), and a FLAGGED list of attachments
 * whose backing file is missing on disk (the "accessible" check). It mutates
 * nothing and carries no idempotency flag (naturally re-runnable).
 *
 * Conflation-clean: reads attachments + the uploads filesystem via WP core only;
 * zero cross-module coupling. Not `final`: the attachment-reading method is an
 * overridable seam so the report logic is unit-testable without the media library.
 *
 * @package Ink\Core
 */
class MediaVerifier {

	/**
	 * The WP-CLI command name (`wp ink verify-media`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink verify-media';

	/**
	 * Register the read-only WP-CLI trigger — ONLY under WP-CLI (never a web request).
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
			function (): void {
				$report = $this->verify();

				foreach ( $report['by_class'] as $class => $count ) {
					\WP_CLI::log( sprintf( '  • %s: %d', (string) $class, (int) $count ) );
				}

				\WP_CLI::success(
					sprintf(
						'Media geverifieer: %d aanhegsels, %d ontbrekende lêers.',
						(int) $report['total'],
						count( $report['missing'] )
					)
				);
			}
		);
	}

	/**
	 * The media class for a MIME type. Pure.
	 *
	 * @param string $mime The attachment MIME type.
	 * @return string image|audio|video|pdf|other.
	 */
	public static function mediaClassFor( string $mime ): string {
		$mime = strtolower( trim( $mime ) );

		if ( str_starts_with( $mime, 'image/' ) ) {
			return 'image';
		}

		if ( str_starts_with( $mime, 'audio/' ) ) {
			return 'audio';
		}

		if ( str_starts_with( $mime, 'video/' ) ) {
			return 'video';
		}

		if ( 'application/pdf' === $mime ) {
			return 'pdf';
		}

		return 'other';
	}

	/**
	 * Shape attachment records into the verification report. Pure.
	 *
	 * @param array<int, array{id?:int, mime?:string, exists?:bool}> $records The attachments.
	 * @return array{total:int, by_class:array<string,int>, missing:list<array{id:int, mime:string, class:string}>}
	 */
	public static function summarise( array $records ): array {
		$by_class = array();
		$missing  = array();

		foreach ( $records as $record ) {
			$id    = (int) ( $record['id'] ?? 0 );
			$mime  = (string) ( $record['mime'] ?? '' );
			$class = self::mediaClassFor( $mime );

			$by_class[ $class ] = ( $by_class[ $class ] ?? 0 ) + 1;

			if ( empty( $record['exists'] ) ) {
				$missing[] = array(
					'id'    => $id,
					'mime'  => $mime,
					'class' => $class,
				);
			}
		}

		ksort( $by_class );

		return array(
			'total'    => count( $records ),
			'by_class' => $by_class,
			'missing'  => $missing,
		);
	}

	/**
	 * Build the verification report. Read-only.
	 *
	 * @return array{total:int, by_class:array<string,int>, missing:list<array{id:int, mime:string, class:string}>}
	 */
	public function verify(): array {
		return self::summarise( $this->attachmentRecords() );
	}

	/**
	 * Read every attachment into `{id, mime, exists}` records. Overridable seam.
	 *
	 * `exists` reflects whether the backing file is present on disk
	 * (`get_attached_file()` + a filesystem check).
	 *
	 * @return list<array{id:int, mime:string, exists:bool}>
	 */
	protected function attachmentRecords(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'attachment',
				'post_status'      => 'inherit',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$records = array();

		foreach ( $ids as $id ) {
			$id   = (int) $id;
			$file = get_attached_file( $id );

			$records[] = array(
				'id'     => $id,
				'mime'   => (string) get_post_mime_type( $id ),
				'exists' => is_string( $file ) && '' !== $file && file_exists( $file ),
			);
		}

		return $records;
	}
}
