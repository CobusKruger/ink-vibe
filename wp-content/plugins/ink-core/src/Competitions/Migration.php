<?php
/**
 * Old galas and competitions, from the old page tree — WP5 (`05-kompetisies` §3.6).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;
use Ink\Migration\StaticRedirects;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * `wp ink migrate-kompetisies`, the `kompetisies` step of `migrate-all`: one `kompetisie`
 * per old gala, for what existed before the cutover. **After go-live this step is finished**:
 * anything new, or any correction, is made on the site. `--force` is for rehearsals only and
 * never runs on the live site, because it would overwrite site edits to imported posts.
 *
 * The galas and the 2022 competition are discovered from the old page tree ({@see discoverGalas()}),
 * **by the meta the owner sets on production first** (`ink_old_gala`, `ink_old_gala_fotos`,
 * `ink_old_competition`, each holding the year; `docs/migration-tools/tag-old-competitions.sql`),
 * then, as a fallback that warns "found by title; tag it", by the Page's title and its "Foto..." child.
 * Name, year and body come from the gala Page, photos from its Fotos Page. No file is read: the
 * highlight and the captions are edited on the site.
 *
 * Photos are the ids in the Fotos Page's `vc_media_grid`, `vc_masonry_media_grid`, `vc_gallery`
 * and `vc_single_image` shortcodes, in page order, without repeats ({@see photoIds()}). A gallery
 * form the parser does not handle is reported, never skipped silently. Videos are attachments
 * too and link the same way; {@see GalaPage} plays them. Nothing the pages cannot give is
 * invented: the highlight and captions stay blank.
 *
 * Once-off (`OPTION_DONE`), get-or-create on {@see SOURCE_KEY_META}. Re-running with `--force`
 * rewrites the same photos (the Pages are read even once trashed). After a gala's photos are
 * linked, its gala Page and Fotos Page go to the trash, with the `/ink-se-gala-aande/` index.
 * Their old paths are stored ({@see StaticRedirects::OPTION_GALA_PATHS}) and 301 to Kompetisies
 * beside the fixed list in {@see StaticRedirects::EXACT}; a row with no Fotos Page keeps its Page
 * (the competition, 49428). Trash, never delete; attachments stay.
 *
 * Decisions are pure statics; the I/O sits behind overridable seams.
 *
 * @package Ink\Core
 */
class Migration {

	/**
	 * The completion flag.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_kompetisies_csv_migration_done';

	/**
	 * The WP-CLI command name.
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink migrate-kompetisies';

	/**
	 * Meta linking a post to its gala or competition key: the get-or-create marker.
	 *
	 * @var string
	 */
	public const SOURCE_KEY_META = 'ink_kompetisie_source_key';

	/**
	 * The prod `/ink-se-gala-aande/` Page.
	 *
	 * @var int
	 */
	public const GALA_INDEX_PAGE = 28477;

	/**
	 * Page meta (value = year) the owner sets on production with
	 * `docs/migration-tools/tag-old-competitions.sql`: a gala Page.
	 *
	 * @var string
	 */
	public const META_GALA = 'ink_old_gala';

	/**
	 * A gala's Fotos Page (value = year).
	 *
	 * @var string
	 */
	public const META_FOTOS = 'ink_old_gala_fotos';

	/**
	 * An old annual competition Page (value = year).
	 *
	 * @var string
	 */
	public const META_COMPETITION = 'ink_old_competition';

	/**
	 * Register the WP-CLI trigger, only under WP-CLI.
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );

				if ( '' !== $summary['error'] ) {
					\WP_CLI::error( $summary['error'] );
				}

				foreach ( $summary['per_row'] as $row_key => $count ) {
					\WP_CLI::log( sprintf( '%s: %d photos', $row_key, $count ) );
				}

				foreach ( array_merge( $summary['warnings'], $summary['problems'] ) as $line ) {
					\WP_CLI::warning( $line );
				}

				\WP_CLI::success( self::summaryLine( $summary ) );
			}
		);
	}

	/**
	 * The one-line result `migrate-all` shows and judges. Pure.
	 *
	 * @param array{skipped:bool, created:int, updated:int, photos:int, retired:int, problems:list<string>} $summary A {@see run()} summary.
	 * @return string
	 */
	public static function summaryLine( array $summary ): string {
		return sprintf(
			'Old competitions: %d created, %d updated, %d photos linked, %d rows or photos skipped, %d old Pages trashed%s.',
			$summary['created'],
			$summary['updated'],
			$summary['photos'],
			count( $summary['problems'] ),
			$summary['retired'],
			$summary['skipped'] ? ' (skipped, already done)' : ''
		);
	}

	/**
	 * The galas and old competitions in the old page tree, as rows. Pure.
	 *
	 * **Tags first.** The owner tags the Pages on production ({@see META_GALA}, {@see META_FOTOS},
	 * {@see META_COMPETITION}, each holding the year; `docs/migration-tools/tag-old-competitions.sql`).
	 * A tagged gala Page is a gala; its Fotos Page is its tagged child, else the tagged Page with the
	 * same year. A tagged competition Page is a row keyed `kompetisie-<jaar>`.
	 *
	 * **Title fallback, with a warning.** A top-level Page whose title holds "gala" and which has a
	 * child Page titled "Foto…" is a gala even untagged, and a tagged gala without a tagged Fotos Page
	 * takes its "Foto…" child; each is reported in `warnings` ("found by title; tag it"). The year is
	 * the tag, else the first four-digit year in the title, else the date's. The key is `gala-<jaar>`,
	 * with `-2`, `-3` for a second gala in one year. Pages in any status count, so a re-run still
	 * finds Pages already in the trash.
	 *
	 * @param list<array{id:int, parent:int, title:string, date:string, gala?:string, fotos?:string, competition?:string}> $pages Pages that are tagged, or whose title holds "gala" or starts with "Foto".
	 * @return array{galas:list<array<string, string>>, notes:list<string>, warnings:list<string>}
	 */
	public static function discoverGalas( array $pages ): array {
		$by_id    = array();
		$children = array();
		$tagged   = array();

		foreach ( $pages as $page ) {
			$by_id[ $page['id'] ] = $page;
		}

		ksort( $by_id );

		$is_fotos = static fn ( array $page ): bool => 1 === preg_match( '/^\s*foto/iu', $page['title'] );
		$year_of  = static fn ( array $page, string $tag ): string => 1 === preg_match( '/^\d{4}$/', $page[ $tag ] ?? '' ) ? $page[ $tag ] : '';

		foreach ( $by_id as $page ) {
			if ( $is_fotos( $page ) && $page['parent'] > 0 ) {
				$children[ $page['parent'] ][] = $page['id'];
			}

			if ( '' !== $year_of( $page, 'fotos' ) ) {
				$tagged[ $year_of( $page, 'fotos' ) ][] = $page['id'];
			}
		}

		$galas    = array();
		$notes    = array();
		$warnings = array();
		$years    = array();

		foreach ( $by_id as $page ) {
			$tag = $year_of( $page, 'gala' );

			if ( '' === $tag && ( 0 !== $page['parent'] || $is_fotos( $page ) || self::GALA_INDEX_PAGE === $page['id'] || false === mb_stripos( $page['title'], 'gala' ) ) ) {
				continue;
			}

			// The Fotos Page: a tagged child, else the tagged Page of the same year, else a "Foto…" child by title.
			$fotos = 0;

			foreach ( $children[ $page['id'] ] ?? array() as $child ) {
				if ( '' !== $year_of( $by_id[ $child ], 'fotos' ) ) {
					$fotos = $child;
					break;
				}
			}

			if ( 0 === $fotos && '' !== $tag && isset( $tagged[ $tag ] ) ) {
				$fotos = $tagged[ $tag ][0];
			}

			$by_title = 0 === $fotos;

			if ( $by_title && isset( $children[ $page['id'] ] ) ) {
				$fotos = $children[ $page['id'] ][0];

				if ( count( $children[ $page['id'] ] ) > 1 ) {
					$notes[] = sprintf( 'Gala Page %d has %d Fotos Pages; only the first (%d) is read.', $page['id'], count( $children[ $page['id'] ] ), $fotos );
				}
			}

			if ( 0 === $fotos ) {
				$notes[] = sprintf( 'Page %d "%s" looks like a gala but has no Fotos Page: not imported.', $page['id'], $page['title'] );
				continue;
			}

			if ( '' === $tag ) {
				$warnings[] = sprintf( 'Gala Page %d "%s" found by title; tag it (%s).', $page['id'], $page['title'], self::META_GALA );
			}

			if ( $by_title ) {
				$warnings[] = sprintf( 'Fotos Page %d of gala Page %d found by title; tag it (%s).', $fotos, $page['id'], self::META_FOTOS );
			}

			$year           = '' !== $tag ? $tag : ( 1 === preg_match( '/\b(?:19|20)\d{2}\b/u', $page['title'], $found ) ? $found[0] : substr( $page['date'], 0, 4 ) );
			$years[ $year ] = ( $years[ $year ] ?? 0 ) + 1;
			$n              = $years[ $year ];

			$galas[] = array(
				'sleutel'      => 'gala-' . $year . ( $n > 1 ? '-' . $n : '' ),
				'naam'         => $page['title'],
				'jaar'         => $year,
				'bladsy'       => (string) $page['id'],
				'fotos_bladsy' => (string) $fotos,
			);
		}

		usort( $galas, static fn ( array $a, array $b ): int => array( $b['jaar'], $a['sleutel'] ) <=> array( $a['jaar'], $b['sleutel'] ) );

		// Old annual competitions: tagged Pages only, no gala and no photos.
		foreach ( $by_id as $page ) {
			if ( '' === $year_of( $page, 'competition' ) ) {
				continue;
			}

			$galas[] = array(
				'sleutel'      => 'kompetisie-' . $page['competition'],
				'naam'         => $page['title'],
				'jaar'         => $page['competition'],
				'bladsy'       => (string) $page['id'],
				'fotos_bladsy' => '',
			);
		}

		return array(
			'galas'    => $galas,
			'notes'    => $notes,
			'warnings' => $warnings,
		);
	}

	/**
	 * The attachment ids a Fotos Page's galleries hold, in page order, without repeats. Pure.
	 *
	 * @param string $content The Page's body.
	 * @return list<int>
	 */
	public static function photoIds( string $content ): array {
		$ids = array();

		if ( preg_match_all( '/\[(?:vc_media_grid|vc_masonry_media_grid|vc_gallery|vc_single_image)\b[^\]]*?\b(?:include|images|image)="([^"]*)"/', $content, $found ) < 1 ) {
			return $ids;
		}

		foreach ( $found[1] as $list ) {
			foreach ( explode( ',', $list ) as $item ) {
				$id = (int) trim( $item );

				if ( $id > 0 && ! in_array( $id, $ids, true ) ) {
					$ids[] = $id;
				}
			}
		}

		return $ids;
	}

	/**
	 * Gallery forms on a Fotos Page that {@see photoIds()} does not read, for the report. Pure.
	 *
	 * @param string $content The Page's body.
	 * @return list<string>
	 */
	public static function unhandledGalleries( string $content ): array {
		$out = array();

		if ( preg_match_all( '/\[(vc_[a-z_]*(?:grid|gallery|image|carousel|slider)[a-z_]*|gallery|nggallery|envira-gallery)\b[^\]]*\]/', $content, $found, PREG_SET_ORDER ) > 0 ) {
			foreach ( $found as $match ) {
				if ( ! in_array( $match[1], array( 'vc_media_grid', 'vc_masonry_media_grid', 'vc_gallery', 'vc_single_image' ), true ) || array() === self::photoIds( $match[0] ) ) {
					$out[] = substr( $match[0], 0, 80 );
				}
			}
		}

		if ( 1 === preg_match( '/<img\b|<!-- wp:(?:image|gallery)\b/', $content ) ) {
			$out[] = 'inline <img> or image/gallery block';
		}

		return $out;
	}

	/**
	 * Which old Pages a row retires: its two Pages, only when it has a Fotos Page. Pure.
	 *
	 * @param array<string, string> $row The row.
	 * @return list<int>
	 */
	public static function pagesToRetire( array $row ): array {
		$fotos = (int) ( $row['fotos_bladsy'] ?? 0 );

		if ( $fotos <= 0 ) {
			return array();
		}

		return array_values( array_filter( array( (int) ( $row['bladsy'] ?? 0 ), $fotos ) ) );
	}

	/**
	 * Run the migration. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed. Rehearsals only.
	 * @return array{skipped:bool, error:string, created:int, updated:int, photos:int, retired:int, problems:list<string>, warnings:list<string>, per_row:array<string, int>}
	 */
	public function run( bool $force = false ): array {
		$summary = array(
			'skipped'  => false,
			'error'    => '',
			'created'  => 0,
			'updated'  => 0,
			'photos'   => 0,
			'retired'  => 0,
			'problems' => array(),
			'warnings' => array(),
			'per_row'  => array(),
		);

		if ( $this->hasRun() && ! $force ) {
			$summary['skipped'] = true;
			return $summary;
		}

		$found  = self::discoverGalas( $this->candidatePages() );
		$retire = array( self::GALA_INDEX_PAGE );

		$summary['problems'] = $found['notes'];
		$summary['warnings'] = $found['warnings'];

		foreach ( $found['galas'] as $row ) {
			$key    = $row['sleutel'];
			$result = $this->ensurePost( $key, $this->postArr( $row ) );

			if ( $result['id'] <= 0 ) {
				$summary['problems'][] = sprintf( 'Competition "%s" could not be saved.', $key );
				continue;
			}

			++$summary[ $result['created'] ? 'created' : 'updated' ];

			$photo_ids = array();
			$fotos     = (int) $row['fotos_bladsy'];

			if ( $fotos > 0 ) {
				$body = $this->pageBody( $fotos );

				if ( '' === $body ) {
					$summary['problems'][] = sprintf( 'Competition "%s": Fotos Page %d not found or empty; its photos are not linked and the Page is kept.', $key, $fotos );
					$fotos                 = 0;
				}

				foreach ( self::unhandledGalleries( $body ) as $form ) {
					$summary['problems'][] = sprintf( 'Competition "%s": Fotos Page %d holds a gallery form that is not read: %s', $key, $fotos, $form );
				}

				foreach ( self::photoIds( $body ) as $attachment ) {
					if ( ! $this->isAttachment( $attachment ) ) {
						$summary['problems'][] = sprintf( 'Competition "%s": id %d on the Fotos Page is not an attachment.', $key, $attachment );
						continue;
					}

					$photo_ids[] = $attachment;
				}

				// Never wipe photos already linked, never trash a Page whose photos were not read.
				if ( $fotos > 0 && array() === $photo_ids ) {
					$summary['problems'][] = sprintf( 'Competition "%s": no photos read from Fotos Page %d (shortcodes already cleaned?); nothing linked and the Pages are kept.', $key, $fotos );
					$fotos                 = 0;
				}
			}

			$summary['photos']         += count( $photo_ids );
			$summary['per_row'][ $key ] = count( $photo_ids );

			$this->setMeta( $result['id'], self::SOURCE_KEY_META, $key );
			$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'jaar' ), (int) $row['jaar'] );

			if ( array() !== $photo_ids ) {
				$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'gala_fotos' ), implode( ',', $photo_ids ) );
			}

			if ( $fotos > 0 ) {
				$retire = array_merge( $retire, self::pagesToRetire( $row ) );
			}
		}

		// Old paths first (a trashed Page's permalink is lost), so the redirect can serve them.
		$paths = array_values( array_filter( array_map( array( $this, 'oldPath' ), array_unique( $retire ) ) ) );

		$this->storeOldPaths( $paths );

		foreach ( array_unique( $retire ) as $page_id ) {
			if ( $this->trashPage( $page_id ) ) {
				++$summary['retired'];
			}
		}

		$this->markDone();

		return $summary;
	}

	/**
	 * The post arguments for a row: the old Page's body, verbatim, for reference.
	 *
	 * @param array<string, string> $row The row.
	 * @return array<string, mixed>
	 */
	private function postArr( array $row ): array {
		return array(
			'post_type'    => PostTypes::KOMPETISIE,
			'post_status'  => 'publish',
			'post_title'   => $row['naam'],
			'post_content' => $this->pageBody( (int) $row['bladsy'] ),
		);
	}

	/**
	 * Whether the migration has completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark it complete.
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * Every Page that is tagged (the three `ink_old_*` metas) or whose title holds "gala" or starts with "Foto", in any status. Overridable seam.
	 *
	 * @return list<array{id:int, parent:int, title:string, date:string, gala:string, fotos:string, competition:string}>
	 */
	protected function candidatePages(): array {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return array();
		}

		$tags = "'" . self::META_GALA . "','" . self::META_FOTOS . "','" . self::META_COMPETITION . "'";
		$meta = static fn ( string $key ): string => "( SELECT m.meta_value FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = '{$key}' LIMIT 1 )";
		$cols = $meta( self::META_GALA ) . ' AS gala, ' . $meta( self::META_FOTOS ) . ' AS fotos, ' . $meta( self::META_COMPETITION ) . ' AS competition';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- once-off CLI read of the old page tree; every interpolated part is a constant or a table name.
		$rows = $wpdb->get_results( "SELECT p.ID, p.post_parent, p.post_title, p.post_date, {$cols} FROM {$wpdb->posts} p WHERE p.post_type = 'page' AND p.post_status NOT IN ( 'auto-draft', 'inherit' ) AND ( p.post_title LIKE '%gala%' OR p.post_title LIKE 'foto%' OR p.ID IN ( SELECT t.post_id FROM {$wpdb->postmeta} t WHERE t.meta_key IN ( {$tags} ) ) )" );
		$out  = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = array(
				'id'          => (int) $row->ID,
				'parent'      => (int) $row->post_parent,
				'title'       => (string) $row->post_title,
				'date'        => (string) $row->post_date,
				'gala'        => trim( (string) $row->gala ),
				'fotos'       => trim( (string) $row->fotos ),
				'competition' => trim( (string) $row->competition ),
			);
		}

		return $out;
	}

	/**
	 * A Page's old public path, e.g. `/15-november-2025-10de-gala/fotos-15-november-2025`, or ''.
	 * Works on a Page already in the trash. Overridable seam.
	 *
	 * @param int $page_id The Page.
	 */
	protected function oldPath( int $page_id ): string {
		$uri = get_page_uri( $page_id );

		return is_string( $uri ) && '' !== $uri ? '/' . trim( str_replace( '__trashed', '', $uri ), '/' ) : '';
	}

	/**
	 * Remember the retired Pages' paths so they redirect to Kompetisies, whether or not they are
	 * in {@see StaticRedirects::EXACT}. Added to what is stored. Overridable seam.
	 *
	 * @param list<string> $paths The paths.
	 */
	protected function storeOldPaths( array $paths ): void {
		$stored = get_option( StaticRedirects::OPTION_GALA_PATHS, array() );
		$stored = is_array( $stored ) ? array_map( 'strval', $stored ) : array();

		update_option( StaticRedirects::OPTION_GALA_PATHS, array_values( array_unique( array_merge( $stored, $paths ) ) ), false );
	}

	/**
	 * An old Page's body, or ''. Overridable seam.
	 *
	 * @param int $page_id The Page.
	 */
	protected function pageBody( int $page_id ): string {
		$page = $page_id > 0 ? get_post( $page_id ) : null;

		return $page instanceof WP_Post ? (string) $page->post_content : '';
	}

	/**
	 * Get-or-create the post for a key. Overridable seam.
	 *
	 * @param string               $key     The key.
	 * @param array<string, mixed> $postarr The post arguments.
	 * @return array{id:int, created:bool}
	 */
	protected function ensurePost( string $key, array $postarr ): array {
		$ids = get_posts(
			array(
				'post_type'        => PostTypes::KOMPETISIE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- a once-off CLI lookup on the source marker.
				'meta_query'       => array(
					array(
						'key'   => self::SOURCE_KEY_META,
						'value' => $key,
					),
				),
			)
		);

		$existing = ( is_array( $ids ) && isset( $ids[0] ) ) ? Scalar::asNonNegativeInt( $ids[0] ) : 0;

		if ( $existing > 0 ) {
			$saved = wp_update_post( wp_slash( array( 'ID' => $existing ) + $postarr ), true );

			return array(
				'id'      => is_wp_error( $saved ) ? 0 : $existing,
				'created' => false,
			);
		}

		$id = wp_insert_post( wp_slash( $postarr ), true );

		return array(
			'id'      => is_wp_error( $id ) ? 0 : (int) $id,
			'created' => ! is_wp_error( $id ) && (int) $id > 0,
		);
	}

	/**
	 * Whether an id is an attachment. Overridable seam.
	 *
	 * @param int $attachment_id The id.
	 */
	protected function isAttachment( int $attachment_id ): bool {
		return 'attachment' === get_post_type( $attachment_id );
	}

	/**
	 * Persist a meta value. Overridable seam.
	 *
	 * @param int        $post_id The post.
	 * @param string     $key     The key.
	 * @param int|string $value   The value.
	 */
	protected function setMeta( int $post_id, string $key, int|string $value ): void {
		update_post_meta( $post_id, $key, $value );
	}

	/**
	 * Trash an old Page; only a Page, only when not already trashed. Overridable seam.
	 *
	 * @param int $page_id The Page.
	 * @return bool Whether it was trashed.
	 */
	protected function trashPage( int $page_id ): bool {
		$page = get_post( $page_id );

		if ( ! $page instanceof WP_Post || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
			return false;
		}

		return wp_trash_post( $page_id ) instanceof WP_Post;
	}
}
