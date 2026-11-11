<?php
/**
 * Competitions module bootstrap — WP5 (N4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Module as ModuleContract;

defined( 'ABSPATH' ) || exit;

/**
 * Competitions module: the data behind `/kompetisies/` (`05-kompetisies`).
 *
 * Competitions are not challenges (owner): nothing here reads `uitdaging` or
 * `CadenceType`. Everything on the page is added and edited on the site, in the
 * `kompetisie` and `eksterne_kompetisie` admin screens. The one CSV is read once, by
 * {@see Migration}, for what existed before the cutover.
 *
 * @package Ink\Core
 */
final class Module implements ModuleContract {

	/**
	 * The Page these post types show on.
	 *
	 * @var string
	 */
	public const PAGE_SLUG = 'kompetisies';

	/**
	 * The option that remembers the default announcement was created, so an editor who
	 * deletes it is not fought: the page then falls back to {@see Edition::builtIn()}.
	 *
	 * @var string
	 */
	public const OPTION_DEFAULT_MADE = 'ink_kompetisie_standaard_gemaak';

	/**
	 * Register this module's hooks.
	 */
	public function register(): void {
		( new Migration() )->register();
		( new GalaPage() )->register();
		( new UitslaePage() )->register();
		( new AankondigingPage() )->register();
		( new EntryLinking() )->register();

		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( Hero::BLOCK, array( 'render_callback' => array( Hero::class, 'render' ) ) );
			register_block_type( Categories::BLOCK, array( 'render_callback' => array( Categories::class, 'render' ) ) );
			register_block_type( Gala::BLOCK, array( 'render_callback' => array( Gala::class, 'render' ) ) );
			register_block_type( Directory::BLOCK, array( 'render_callback' => array( Directory::class, 'render' ) ) );
		}

		add_action( 'init', array( self::class, 'ensureDefault' ), 99 );
		add_action( 'save_post_' . PostTypes::KOMPETISIE, array( self::class, 'purgePage' ) );
		add_action( 'save_post_' . PostTypes::EKSTERNE_KOMPETISIE, array( self::class, 'purgePage' ) );
	}

	/**
	 * Create the default announcement post once, so the editor has it to change from the first
	 * day (owner, 2026-11-08). Real site code, not an import: it is edited in the admin like
	 * any competition, and a competition with a description replaces it on the page.
	 */
	public static function ensureDefault(): void {
		if ( get_option( self::OPTION_DEFAULT_MADE, false ) ) {
			return;
		}

		$existing = get_posts(
			array(
				'post_type'        => PostTypes::KOMPETISIE,
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- runs once, until the option is set.
				'meta_query'       => array(
					array(
						'key'   => FieldSets::kompetisieKey( 'standaard' ),
						'value' => Edition::DEFAULT_YES,
					),
				),
			)
		);

		if ( array() === $existing ) {
			$built = Edition::builtIn();
			$id    = wp_insert_post(
				wp_slash(
					array(
						'post_type'   => PostTypes::KOMPETISIE,
						'post_status' => 'publish',
						'post_title'  => $built->title,
					)
				),
				true
			);

			if ( is_wp_error( $id ) || (int) $id <= 0 ) {
				return;
			}

			foreach ( $built->values() as $suffix => $value ) {
				update_post_meta( (int) $id, FieldSets::kompetisieKey( $suffix ), $value );
			}
		}

		update_option( self::OPTION_DEFAULT_MADE, true, false );
	}

	/**
	 * Purge the cached Page after an edit: neither type is public, so nothing else would.
	 * LiteSpeed's own action, inert when the plugin is absent.
	 */
	public static function purgePage( int $post_id = 0 ): void {
		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $page instanceof \WP_Post ) {
			do_action( 'litespeed_purge_post', (int) $page->ID );
		}

		// The year's results and gala photos are the same Page at other URLs (K5): purge by URL.
		$year = $post_id > 0 ? (int) get_post_meta( $post_id, FieldSets::kompetisieKey( 'jaar' ), true ) : 0;

		foreach ( self::yearPaths( $year ) as $path ) {
			do_action( 'litespeed_purge_url', home_url( $path ) );
		}
	}

	/**
	 * The paths of a year's own pages. Pure.
	 *
	 * @param int $year The edition's year, 0 for none.
	 * @return list<string>
	 */
	public static function yearPaths( int $year ): array {
		return $year > 0 ? array( UitslaePage::path( $year ), GalaPage::path( $year ), AankondigingPage::path( $year ) ) : array();
	}
}
