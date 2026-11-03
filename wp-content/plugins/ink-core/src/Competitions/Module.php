<?php
/**
 * Competitions module bootstrap — WP5 (N4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

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
	 * Register this module's hooks.
	 */
	public function register(): void {
		( new Migration() )->register();
		( new GalaPage() )->register();

		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( Hero::BLOCK, array( 'render_callback' => array( Hero::class, 'render' ) ) );
			register_block_type( Categories::BLOCK, array( 'render_callback' => array( Categories::class, 'render' ) ) );
			register_block_type( Gala::BLOCK, array( 'render_callback' => array( Gala::class, 'render' ) ) );
			register_block_type( Directory::BLOCK, array( 'render_callback' => array( Directory::class, 'render' ) ) );
		}

		add_action( 'save_post_' . PostTypes::KOMPETISIE, array( self::class, 'purgePage' ) );
		add_action( 'save_post_' . PostTypes::EKSTERNE_KOMPETISIE, array( self::class, 'purgePage' ) );
	}

	/**
	 * Purge the cached Page after an edit: neither type is public, so nothing else would.
	 * LiteSpeed's own action, inert when the plugin is absent.
	 */
	public static function purgePage(): void {
		$page = get_page_by_path( self::PAGE_SLUG, OBJECT, 'page' );

		if ( $page instanceof \WP_Post ) {
			do_action( 'litespeed_purge_post', (int) $page->ID );
		}
	}
}
