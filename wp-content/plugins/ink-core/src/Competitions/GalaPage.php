<?php
/**
 * All of one year's gala photos: `/kompetisies/gala/<jaar>/` (owner ruling 2026-11-02).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\I18n\Terms;
use Ink\Kernel\ArchiveRender;
use Ink\Kernel\SystemPages;

defined( 'ABSPATH' ) || exit;

/**
 * The route and block `ink/kompetisies-gala-fotos` behind each year's "Sien alles" link.
 *
 * APPROVED ADDITION (owner, 2026-11-02, `owner-rulings.md` "Gala photos"): the design has no
 * such page or link. It is built only from ratified recipes, with no new primitive: the
 * standard band, container, eyebrow and `ink-heading-h1-index`, the back link
 * `ink-nav-link--footer`, `ink-photo` (RG-KO-15) and the shared `ink-pager`. Layout lives in
 * `kompetisies.css`.
 *
 *  - **Route:** `^kompetisies/gala/<jaar>/` → the `kompetisies` Page with {@see QUERY_VAR}, so
 *    the page's sheet and script load; {@see TEMPLATE} goes first in the page hierarchy, and
 *    core's canonical redirect is off for it. Flushed once per {@see VERSION}.
 *  - **404:** a year with no published gala photos, or a page beyond the last.
 *  - **Batches:** {@see PER_PAGE} photos per `?bladsy=N`. Without JavaScript the pager's real
 *    links reach every photo; `kompetisies.js` loads the next batch as the reader nears the
 *    end, from the same URLs. Images are lazy, and `ink-photo`'s fixed 3 / 2 box means a
 *    loading image moves nothing.
 *
 * @package Ink\Core
 */
final class GalaPage {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-gala-fotos';

	/**
	 * The theme template, without its extension.
	 *
	 * @var string
	 */
	public const TEMPLATE = 'kompetisies-gala';

	/**
	 * The query var carrying the year. The single source is the guard-tested list in
	 * {@see SystemPages}.
	 *
	 * @var string
	 */
	public const QUERY_VAR = SystemPages::ENDPOINT_TEMPLATES[ self::TEMPLATE ];

	/**
	 * The page-number query var (`?bladsy=2`).
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'bladsy';

	/**
	 * Photos per batch.
	 *
	 * @var int
	 */
	public const PER_PAGE = 24;

	/**
	 * Bump when the rewrite rule changes.
	 *
	 * @var string
	 */
	public const VERSION = '2';

	/**
	 * The option holding the {@see VERSION} already flushed.
	 *
	 * @var string
	 */
	public const VERSION_OPTION = 'ink_gala_fotos_rewrite_version';

	/**
	 * Wire the route and the block. Runs on `init`.
	 */
	public function register(): void {
		add_rewrite_rule( '^' . Module::PAGE_SLUG . '/gala/([0-9]{4})/?$', 'index.php?pagename=' . Module::PAGE_SLUG . '&' . self::QUERY_VAR . '=$matches[1]', 'top' );
		add_filter( 'query_vars', array( self::class, 'queryVars' ) );
		add_action( 'init', array( self::class, 'flushIfStale' ), 99 );
		add_filter( 'page_template_hierarchy', array( self::class, 'templateHierarchy' ) );
		add_filter( 'redirect_canonical', array( self::class, 'canonical' ) );
		add_action( 'template_redirect', array( self::class, 'guard' ), 1 );

		if ( function_exists( 'register_block_type' ) ) {
			register_block_type( self::BLOCK, array( 'render_callback' => array( self::class, 'render' ) ) );
		}
	}

	/**
	 * Add the year var.
	 *
	 * @param list<string> $vars The public query vars.
	 * @return list<string>
	 */
	public static function queryVars( array $vars ): array {
		$vars[] = self::QUERY_VAR;

		return $vars;
	}

	/**
	 * Flush the rewrite rules once per {@see VERSION}.
	 */
	public static function flushIfStale(): void {
		if ( self::VERSION === get_option( self::VERSION_OPTION ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, self::VERSION );
	}

	/**
	 * The requested year, or 0 when this isn't a gala view.
	 *
	 * @return int
	 */
	public static function requestedYear(): int {
		$year = get_query_var( self::QUERY_VAR, '' );

		return is_scalar( $year ) && 1 === preg_match( '/^\d{4}$/', (string) $year ) ? (int) $year : 0;
	}

	/**
	 * Whether this request is a gala view.
	 *
	 * @return bool
	 */
	public static function isGalaView(): bool {
		return self::requestedYear() > 0 && is_page( Module::PAGE_SLUG );
	}

	/**
	 * Put the gala template first.
	 *
	 * @param list<string> $templates The hierarchy.
	 * @return list<string>
	 */
	public static function templateHierarchy( array $templates ): array {
		if ( self::isGalaView() ) {
			array_unshift( $templates, self::TEMPLATE . '.php' );
		}

		return $templates;
	}

	/**
	 * No canonical redirect to the Page itself.
	 *
	 * @param string|false $redirect The redirect core chose.
	 * @return string|false
	 */
	public static function canonical( $redirect ) {
		return self::requestedYear() > 0 ? false : $redirect;
	}

	/**
	 * 404 a year without photos, or a page past the last.
	 */
	public static function guard(): void {
		$year = self::requestedYear();

		if ( $year <= 0 || is_404() ) {
			return;
		}

		$gala = Data::galaYear( $year );

		if ( null !== $gala && self::requestedPage() <= self::pageCount( count( $gala->photoIds ) ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * The requested page, at least 1.
	 *
	 * @return int
	 */
	public static function requestedPage(): int {
		return max( 1, ArchiveRender::requestInt( self::PAGED_VAR, 1 ) );
	}

	/**
	 * How many pages a count of photos fills. Pure.
	 *
	 * @param int $photos The photo count.
	 * @return int
	 */
	public static function pageCount( int $photos ): int {
		return max( 1, (int) ceil( $photos / self::PER_PAGE ) );
	}

	/**
	 * The photos on one page, in gallery order. Pure.
	 *
	 * @param list<int> $ids   Every photo.
	 * @param int       $paged The page.
	 * @return list<int>
	 */
	public static function pageIds( array $ids, int $paged ): array {
		return array_values( array_slice( $ids, ( max( 1, $paged ) - 1 ) * self::PER_PAGE, self::PER_PAGE ) );
	}

	/**
	 * A year's URL.
	 *
	 * @param int $year The year.
	 * @return string
	 */
	public static function urlFor( int $year ): string {
		return home_url( self::path( $year ) );
	}

	/**
	 * A year's path. Pure.
	 *
	 * @param int $year The year.
	 * @return string
	 */
	public static function path( int $year ): string {
		return '/' . Module::PAGE_SLUG . '/gala/' . $year . '/';
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$year = self::requestedYear();
		$gala = $year > 0 ? Data::galaYear( $year ) : null;

		if ( null === $gala ) {
			return '';
		}

		$paged  = self::requestedPage();
		$photos = array();

		foreach ( self::pageIds( $gala->photoIds, $paged ) as $id ) {
			$caption = (string) wp_get_attachment_caption( $id );
			$src     = wp_get_attachment_image_src( $id, 'medium_large' );

			if ( is_array( $src ) && is_string( $src[0] ) ) {
				$photos[] = array(
					'url'     => $src[0],
					'width'   => (int) $src[1],
					'height'  => (int) $src[2],
					'caption' => $caption,
					'video'   => false,
				);
				continue;
			}

			// The old Fotos Pages also hold a few clips (2025: two); they stay reachable.
			$url = wp_get_attachment_url( $id );

			if ( is_string( $url ) && str_starts_with( (string) get_post_mime_type( $id ), 'video/' ) ) {
				$photos[] = array(
					'url'     => $url,
					'width'   => 0,
					'height'  => 0,
					'caption' => $caption,
					'video'   => true,
				);
			}
		}

		return self::toHtml(
			$year,
			$photos,
			ArchiveRender::pagination( $paged, self::pageCount( count( $gala->photoIds ) ), 'ink-komp-gala-fotos', self::PAGED_VAR ),
			home_url( '/' . Module::PAGE_SLUG . '/' ),
			null !== $gala->edition && $gala->edition->hasPublishedResults() ? UitslaePage::urlFor( $year ) : ''
		);
	}

	/**
	 * The page. Pure.
	 *
	 * @param int                                                                         $year   The year.
	 * @param list<array{url:string, width:int, height:int, caption:string, video?:bool}> $photos This page's photos and clips.
	 * @param string                                                                      $pager  The pager links, or ''.
	 * @param string                                                                      $back   The `/kompetisies/` URL.
	 * @param string                                                                      $results The year's results URL (K5), or '' when not published. PROVISIONAL placement, see {@see UitslaePage}.
	 * @return string
	 */
	public static function toHtml( int $year, array $photos, string $pager, string $back, string $results = '' ): string {
		/* translators: %d: the competition year. */
		$results_label = sprintf( __( 'Uitslae %d', 'ink-core' ), $year );
		$figs          = '';

		foreach ( $photos as $photo ) {
			$size  = $photo['width'] > 0 && $photo['height'] > 0 ? ' width="' . $photo['width'] . '" height="' . $photo['height'] . '"' : '';
			$media = ! empty( $photo['video'] )
				? '<video class="ink-photo" src="' . esc_url( $photo['url'] ) . '" controls preload="none" playsinline></video>'
				: '<img class="ink-photo" src="' . esc_url( $photo['url'] ) . '" alt="' . esc_attr( $photo['caption'] ) . '"' . $size . ' loading="lazy" decoding="async" />';
			$figs .= '<figure class="ink-komp-gala-fotos__foto">' . $media
				. ( '' === $photo['caption'] ? '' : '<figcaption class="ink-komp-gala__onderskrif">' . esc_html( $photo['caption'] ) . '</figcaption>' )
				. '</figure>';
		}

		return '<section class="ink-band ink-band--step" data-audit-id="gala-fotos">'
			. '<div class="ink-container">'
			. '<a class="ink-komp-gala-fotos__terug ink-nav-link--footer" href="' . esc_url( $back ) . '">'
			. Markup::icon( '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>' )
			. esc_html( Terms::label( 'kompetisie_plural' ) ) . '</a>'
			. '<p class="ink-komp-gala-fotos__jaar ink-eyebrow ink-eyebrow--card">' . esc_html( (string) $year ) . '</p>'
			. '<h1 class="ink-komp-gala-fotos__titel ink-heading-h1-index">' . esc_html( sprintf(
				/* translators: %d: the gala year. */
				__( 'Ink se Presige Gala-aand %d', 'ink-core' ),
				$year
			) ) . '</h1>'
			. ( '' === $results ? '' : '<p><a class="ink-link--ink" href="' . esc_url( $results ) . '">'
				. esc_html( $results_label )
				. Markup::icon( Markup::ARROW_RIGHT, '', 'ink-link__icon--end' )
				. '</a></p>' )
			. '<div class="ink-komp-gala-fotos__rooster" data-ink-gala-fotos>' . $figs . '</div>'
			. $pager
			. '</div></section>';
	}
}
