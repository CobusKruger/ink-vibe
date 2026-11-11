<?php
/**
 * Old competitions, from the old page tree — WP5 and K6 (`05-kompetisies` §3.6, §14).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\CompetitionType;
use Ink\Kernel\Scalar;
use Ink\Migration\ShortcodeCleanup;
use Ink\Migration\StaticRedirects;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * `wp ink migrate-kompetisies`, the `kompetisies` step of `migrate-all`: one `kompetisie`
 * per year, for what existed before the cutover. **After go-live this step is finished**:
 * anything new, or any correction, is made on the site. `--force` is for rehearsals only and
 * never runs on the live site, because it would overwrite site edits to imported posts.
 *
 * **The role model (owner rulings 2026-11-11).** The owner tags the old Pages on production with
 * post meta, each holding the year (`docs/migration-tools/tag-old-competitions.sql`):
 *
 *  - {@see META_UITSLAE}: the results Page (overview of the gala evening and the announced winners).
 *    Its body is reused AS-IS, not parsed: it becomes the year's `uitslae_oud` field, shown on the
 *    results page, and the results are marked published. The Page is then trashed.
 *  - {@see META_FOTOS}: the gala photos Page. Its photos feed `gala_fotos`; then it is trashed.
 *  - {@see META_KEPT}: competition announcement, gala announcement, closure notice, gala reminder.
 *    **Never imported, trashed or redirected**: they stay live at their own addresses. This step only
 *    recognises them so the title fallback never mistakes one for a results Page.
 *
 * One `kompetisie` post per year (`gala-<jaar>`), `tipe` = `jaarliks`, built from that year's tagged
 * results and Fotos Pages. Partner competitions are not migrated. Without the tags, a top-level Page
 * with "gala" in its title and a "Foto..." child is taken as the year's results Page (warning
 * "found by title; tag it"). Nothing else is guessed.
 *
 * Photos are the ids in the Fotos Page's `vc_media_grid`, `vc_masonry_media_grid`, `vc_gallery`
 * and `vc_single_image` shortcodes, in page order, without repeats ({@see photoIds()}). A gallery
 * form the parser does not handle is reported, never skipped silently. Videos are attachments
 * too and link the same way; {@see GalaPage} plays them. Nothing the pages cannot give is
 * invented: the highlight and captions stay blank.
 *
 * **Must run before `clean-shortcodes`** (the Fotos shortcodes are read from the Pages) and cannot be
 * re-run after it. Once-off (`OPTION_DONE`), get-or-create on {@see SOURCE_KEY_META}.
 *
 * **For the redirects (story K7).** Retired Pages are trashed, so each one's old id and path are
 * stored in the option {@see StaticRedirects::OPTION_OLD_PAGES} with its year and target kind
 * (`fotos` or `uitslae`); this step builds no redirect from it. `RedirectGenerator` serves the 301s
 * from it by `?p=<id>` and by path (K7). Trash, never delete; attachments stay.
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
	 * `docs/migration-tools/tag-old-competitions.sql`: a results Page.
	 *
	 * @var string
	 */
	public const META_UITSLAE = 'ink_old_uitslae';

	/**
	 * A year's gala photos Page (value = year).
	 *
	 * @var string
	 */
	public const META_FOTOS = 'ink_old_gala_fotos';

	/**
	 * Roles whose Pages stay live at their own addresses (value = year): marked, never imported,
	 * trashed or redirected. Replaces the retired `ink_old_competition` (the 2022 announcement is
	 * now `ink_old_kompetisie_aankondiging`) and the mistaken `ink_old_gala` (those were results Pages).
	 *
	 * @var list<string>
	 */
	public const META_KEPT = array(
		'ink_old_kompetisie_aankondiging',
		'ink_old_gala_aankondiging',
		'ink_old_kompetisie_sluiting',
		'ink_old_gala_herinnering',
	);

	/**
	 * The kind of retired Page that redirects to the year's results route.
	 *
	 * @var string
	 */
	public const KIND_UITSLAE = 'uitslae';

	/**
	 * The kind of retired Page that redirects to the year's gala photos page.
	 *
	 * @var string
	 */
	public const KIND_FOTOS = 'fotos';

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
	 * One row per year from the old page tree. Pure.
	 *
	 * **Tags first.** A Page tagged {@see META_UITSLAE} is the year's results Page. Its Fotos Page is its
	 * tagged child, else the Page tagged {@see META_FOTOS} with the same year. A year with only a tagged
	 * Fotos Page still gets a row (named after that Page). Pages carrying a {@see META_KEPT} tag are
	 * never touched. Two results Pages for one year: the lower id wins, the other is reported in `notes`.
	 *
	 * **Title fallback, with a warning.** A top-level Page whose title holds "gala" and which has a
	 * child Page titled "Foto…" is a results Page even untagged, and a tagged results Page without a
	 * tagged Fotos Page takes its "Foto…" child; each is reported in `warnings` ("found by title; tag
	 * it"). The year is the first four-digit year in the title, else the date's. If that year already
	 * has a row (the 2016 duplicate, 7802) the Page is ignored, with a warning. The key is
	 * `gala-<jaar>`. Pages in any status count, so a re-run still finds Pages already in the trash.
	 *
	 * @param list<array{id:int, parent:int, title:string, date:string, uitslae?:string, fotos?:string, kept?:string}> $pages Pages that are tagged, or whose title holds "gala" or starts with "Foto".
	 * @return array{galas:list<array<string, string>>, notes:list<string>, warnings:list<string>}
	 */
	public static function discover( array $pages ): array {
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

		$rows     = array();
		$claimed  = array();
		$notes    = array();
		$warnings = array();

		// The Fotos Page of a results Page: a tagged child, else the tagged Page of the same year, else a "Foto…" child by title.
		$find_fotos = static function ( array $page, string $year ) use ( $by_id, $children, $tagged, $year_of, &$notes ): array {
			foreach ( $children[ $page['id'] ] ?? array() as $child ) {
				if ( '' !== $year_of( $by_id[ $child ], 'fotos' ) ) {
					return array( $child, false );
				}
			}

			if ( '' !== $year && isset( $tagged[ $year ] ) ) {
				return array( $tagged[ $year ][0], false );
			}

			if ( isset( $children[ $page['id'] ] ) ) {
				if ( count( $children[ $page['id'] ] ) > 1 ) {
					$notes[] = sprintf( 'Results Page %d has %d Fotos Pages; only the first (%d) is read.', $page['id'], count( $children[ $page['id'] ] ), $children[ $page['id'] ][0] );
				}

				return array( $children[ $page['id'] ][0], true );
			}

			return array( 0, false );
		};

		$make = static fn ( string $year, string $name, int $results, int $fotos ): array => array(
			'sleutel'      => 'gala-' . $year,
			'naam'         => $name,
			'jaar'         => $year,
			'bladsy'       => $results > 0 ? (string) $results : '',
			'fotos_bladsy' => $fotos > 0 ? (string) $fotos : '',
		);

		// Pass 1: tagged results Pages.
		foreach ( $by_id as $page ) {
			$year = $year_of( $page, 'uitslae' );

			if ( '' === $year ) {
				continue;
			}

			if ( isset( $rows[ $year ] ) ) {
				$notes[] = sprintf( 'Results Page %d is a second results Page for %s (Page %s is used): not imported.', $page['id'], $year, $rows[ $year ]['bladsy'] );
				continue;
			}

			list( $fotos, $by_title ) = $find_fotos( $page, $year );

			if ( $by_title ) {
				$warnings[] = sprintf( 'Fotos Page %d of results Page %d found by title; tag it (%s).', $fotos, $page['id'], self::META_FOTOS );
			}

			$claimed[ $fotos ] = true;
			$rows[ $year ]     = $make( $year, $page['title'], $page['id'], $fotos );
		}

		// Pass 2: untagged "gala" Pages with a Fotos child. Kept-role Pages and the index are never candidates.
		foreach ( $by_id as $page ) {
			if ( '' !== $year_of( $page, 'uitslae' ) || '' !== ( $page['kept'] ?? '' ) || 0 !== $page['parent'] || $is_fotos( $page ) || self::GALA_INDEX_PAGE === $page['id'] || false === mb_stripos( $page['title'], 'gala' ) ) {
				continue;
			}

			$year = 1 === preg_match( '/\b(?:19|20)\d{2}\b/u', $page['title'], $found ) ? $found[0] : substr( $page['date'], 0, 4 );

			list( $fotos, $by_title ) = $find_fotos( $page, $year );

			if ( 0 === $fotos ) {
				$notes[] = sprintf( 'Page %d "%s" looks like a gala but has no Fotos Page: not imported.', $page['id'], $page['title'] );
				continue;
			}

			if ( isset( $rows[ $year ] ) ) {
				$warnings[] = sprintf( 'Page %d "%s" looks like a gala Page but %s already has a row (results Page %s); ignored.', $page['id'], $page['title'], $year, $rows[ $year ]['bladsy'] );
				continue;
			}

			$warnings[] = sprintf( 'Results Page %d "%s" found by title; tag it (%s).', $page['id'], $page['title'], self::META_UITSLAE );

			if ( $by_title ) {
				$warnings[] = sprintf( 'Fotos Page %d of results Page %d found by title; tag it (%s).', $fotos, $page['id'], self::META_FOTOS );
			}

			$claimed[ $fotos ] = true;
			$rows[ $year ]     = $make( $year, $page['title'], $page['id'], $fotos );
		}

		// Pass 3: a tagged Fotos Page whose year has no results Page still gets its year.
		foreach ( $by_id as $page ) {
			$year = $year_of( $page, 'fotos' );

			if ( '' === $year || isset( $claimed[ $page['id'] ] ) || isset( $rows[ $year ] ) ) {
				continue;
			}

			$claimed[ $page['id'] ] = true;
			$rows[ $year ]          = $make( $year, $page['title'], 0, $page['id'] );
		}

		krsort( $rows );

		return array(
			'galas'    => array_values( $rows ),
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
	 * The results Page's body as stored: WPBakery tags stripped, nothing else changed. Pure.
	 *
	 * @param string $body The old Page's body.
	 * @return string '' when the Page has no text or image.
	 */
	public static function resultsBody( string $body ): string {
		$clean = trim( ShortcodeCleanup::stripVcShortcodes( $body ) );
		$text  = preg_replace( '/<[^>]*>|&nbsp;|\s+/u', '', $clean );

		return ( '' === (string) $text && 1 !== preg_match( '/<img\b/i', $clean ) ) ? '' : $clean;
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

		$found = self::discover( $this->candidatePages() );

		// Page id => the year and target kind it redirects to later (K7); the index Page has none.
		$retire = array( self::GALA_INDEX_PAGE => null );

		$summary['problems'] = $found['notes'];
		$summary['warnings'] = $found['warnings'];

		foreach ( $found['galas'] as $row ) {
			$key    = $row['sleutel'];
			$year   = (int) $row['jaar'];
			$result = $this->ensurePost( $key, $this->postArr( $row ) );

			if ( $result['id'] <= 0 ) {
				$summary['problems'][] = sprintf( 'Competition "%s" could not be saved.', $key );
				continue;
			}

			++$summary[ $result['created'] ? 'created' : 'updated' ];

			$this->setMeta( $result['id'], self::SOURCE_KEY_META, $key );
			$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'jaar' ), $year );
			$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'tipe' ), CompetitionType::Jaarliks->value );

			// The results Page: its body, as it is, is the results page; the results are published.
			$results = (int) $row['bladsy'];

			if ( $results > 0 ) {
				$html = self::resultsBody( $this->pageBody( $results ) );

				if ( '' === $html ) {
					$summary['problems'][] = sprintf( 'Competition "%s": results Page %d not found or empty; results not published and the Page is kept.', $key, $results );
				} else {
					$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'uitslae_oud' ), $html );
					$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'uitslae_gepubliseer' ), 'ja' );

					$retire[ $results ] = array(
						'jaar'  => $year,
						'soort' => self::KIND_UITSLAE,
					);
				}
			}

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
					$summary['problems'][] = sprintf( 'Competition "%s": no photos read from Fotos Page %d (shortcodes already cleaned?); nothing linked and the Page is kept.', $key, $fotos );
					$fotos                 = 0;
				}
			}

			$summary['photos']         += count( $photo_ids );
			$summary['per_row'][ $key ] = count( $photo_ids );

			if ( array() !== $photo_ids ) {
				$this->setMeta( $result['id'], FieldSets::kompetisieKey( 'gala_fotos' ), implode( ',', $photo_ids ) );
			}

			if ( $fotos > 0 ) {
				$retire[ $fotos ] = array(
					'jaar'  => $year,
					'soort' => self::KIND_FOTOS,
				);
			}
		}

		// Old paths first (a trashed Page's permalink is lost), so the redirects can serve them.
		$pages = array();

		foreach ( $retire as $page_id => $target ) {
			$path = $this->oldPath( $page_id );

			if ( null !== $target ) {
				$pages[] = array(
					'id'    => $page_id,
					'path'  => $path,
					'jaar'  => $target['jaar'],
					'soort' => $target['soort'],
				);
			}
		}

		$this->storeOldPages( $pages );

		foreach ( array_keys( $retire ) as $page_id ) {
			if ( $this->trashPage( $page_id ) ) {
				++$summary['retired'];
			}
		}

		$this->markDone();

		return $summary;
	}

	/**
	 * The post arguments for a row. The old body is not kept here: the results Page's body goes in
	 * the `uitslae_oud` field, the others are not imported.
	 *
	 * @param array<string, string> $row The row.
	 * @return array<string, mixed>
	 */
	private function postArr( array $row ): array {
		return array(
			'post_type'    => PostTypes::KOMPETISIE,
			'post_status'  => 'publish',
			'post_title'   => $row['naam'],
			'post_content' => '',
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
	 * Every Page that is tagged (results, Fotos, or a kept role) or whose title holds "gala" or starts with "Foto", in any status. Overridable seam.
	 *
	 * @return list<array{id:int, parent:int, title:string, date:string, uitslae:string, fotos:string, kept:string}>
	 */
	protected function candidatePages(): array {
		global $wpdb;

		if ( ! $wpdb instanceof \wpdb ) {
			return array();
		}

		$kept = "'" . implode( "','", self::META_KEPT ) . "'";
		$tags = "'" . self::META_UITSLAE . "','" . self::META_FOTOS . "'," . $kept;
		$meta = static fn ( string $key ): string => "( SELECT m.meta_value FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key = '{$key}' LIMIT 1 )";
		$role = "( SELECT m.meta_key FROM {$wpdb->postmeta} m WHERE m.post_id = p.ID AND m.meta_key IN ( {$kept} ) LIMIT 1 )";
		$cols = $meta( self::META_UITSLAE ) . ' AS uitslae, ' . $meta( self::META_FOTOS ) . ' AS fotos, ' . $role . ' AS kept';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- once-off CLI read of the old page tree; every interpolated part is a constant or a table name.
		$rows = $wpdb->get_results( "SELECT p.ID, p.post_parent, p.post_title, p.post_date, {$cols} FROM {$wpdb->posts} p WHERE p.post_type = 'page' AND p.post_status NOT IN ( 'auto-draft', 'inherit' ) AND ( p.post_title LIKE '%gala%' OR p.post_title LIKE 'foto%' OR p.ID IN ( SELECT t.post_id FROM {$wpdb->postmeta} t WHERE t.meta_key IN ( {$tags} ) ) )" );
		$out  = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			$out[] = array(
				'id'      => (int) $row->ID,
				'parent'  => (int) $row->post_parent,
				'title'   => (string) $row->post_title,
				'date'    => (string) $row->post_date,
				'uitslae' => trim( (string) $row->uitslae ),
				'fotos'   => trim( (string) $row->fotos ),
				'kept'    => trim( (string) $row->kept ),
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
	 * Remember which retired Page maps to which year and target kind, for the redirects (K7).
	 * The option is a list of `{id, path, jaar, soort}`; an entry with the same id is replaced.
	 * Overridable seam.
	 *
	 * @param list<array{id:int, path:string, jaar:int, soort:string}> $pages The retired Pages.
	 */
	protected function storeOldPages( array $pages ): void {
		$stored = get_option( StaticRedirects::OPTION_OLD_PAGES, array() );
		$merged = array();

		foreach ( is_array( $stored ) ? $stored : array() as $entry ) {
			if ( is_array( $entry ) && isset( $entry['id'] ) ) {
				$merged[ (int) $entry['id'] ] = $entry;
			}
		}

		foreach ( $pages as $entry ) {
			$merged[ $entry['id'] ] = $entry;
		}

		update_option( StaticRedirects::OPTION_OLD_PAGES, array_values( $merged ), false );
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
