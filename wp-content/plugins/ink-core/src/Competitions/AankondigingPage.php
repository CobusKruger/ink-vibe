<?php
/**
 * One competition's announcement, kept for good: `/kompetisies/<jaar>/` — K8 (`05-kompetisies` §14.8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\I18n\Terms;
use Ink\Kernel\SystemPages;

defined( 'ABSPATH' ) || exit;

/**
 * The route and block `ink/kompetisies-aankondiging`. `/kompetisies/` shows only the current
 * edition, so without this page a competition's announcement would vanish when the next edition
 * replaces it. Every real edition with a description keeps its own address, with the same fields
 * the main page shows: the description, dates, categories and guidelines, the gala details and
 * the tickets. Mirrors {@see UitslaePage}.
 *
 *  - **Route:** `^kompetisies/<jaar>/$` → the `kompetisies` Page with {@see QUERY_VAR}, so the
 *    page's sheet loads; {@see TEMPLATE} goes first in the page hierarchy, and core's canonical
 *    redirect is off for it. Flushed once per {@see VERSION}. The page is code, never `post_content`.
 *  - **404:** a year with no real edition, or whose edition has no description. Imported old
 *    competitions have none: their announcements stay live as the old posts and Pages (owner,
 *    2026-11-11), so nothing is duplicated and no old link breaks.
 *  - **One edition per year:** as the gala and results pages: the newest post of the year.
 *
 * LAYOUT IS PROVISIONAL (K8, 2026-11-11), as {@see UitslaePage}: the design has no such page, so
 * the markup is structural, reuses shared primitives only and carries no audit-id or page CSS.
 *
 * @package Ink\Core
 */
final class AankondigingPage {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-aankondiging';

	/**
	 * The theme template, without its extension.
	 *
	 * @var string
	 */
	public const TEMPLATE = 'kompetisies-aankondiging';

	/**
	 * The query var carrying the year. The single source is the guard-tested list in
	 * {@see SystemPages}.
	 *
	 * @var string
	 */
	public const QUERY_VAR = SystemPages::ENDPOINT_TEMPLATES[ self::TEMPLATE ];

	/**
	 * Bump when the rewrite rule changes.
	 *
	 * @var string
	 */
	public const VERSION = '1';

	/**
	 * The option holding the {@see VERSION} already flushed.
	 *
	 * @var string
	 */
	public const VERSION_OPTION = 'ink_kompetisie_aankondiging_rewrite_version';

	/**
	 * Wire the route and the block. Runs on `init`.
	 */
	public function register(): void {
		add_rewrite_rule( '^' . Module::PAGE_SLUG . '/([0-9]{4})/?$', 'index.php?pagename=' . Module::PAGE_SLUG . '&' . self::QUERY_VAR . '=$matches[1]', 'top' );
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
	 * The requested year, or 0 when this isn't an announcement view.
	 *
	 * @return int
	 */
	public static function requestedYear(): int {
		$year = get_query_var( self::QUERY_VAR, '' );

		return is_scalar( $year ) && 1 === preg_match( '/^\d{4}$/', (string) $year ) ? (int) $year : 0;
	}

	/**
	 * Whether this request is an announcement view.
	 *
	 * @return bool
	 */
	public static function isAnnouncementView(): bool {
		return self::requestedYear() > 0 && is_page( Module::PAGE_SLUG );
	}

	/**
	 * Put the announcement template first.
	 *
	 * @param list<string> $templates The hierarchy.
	 * @return list<string>
	 */
	public static function templateHierarchy( array $templates ): array {
		if ( self::isAnnouncementView() ) {
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
	 * 404 a year with no real edition that has a description.
	 */
	public static function guard(): void {
		$year = self::requestedYear();

		if ( $year <= 0 || is_404() ) {
			return;
		}

		if ( null !== Data::announcementEdition( $year ) ) {
			return;
		}

		global $wp_query;

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
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
		return '/' . Module::PAGE_SLUG . '/' . $year . '/';
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$year    = self::requestedYear();
		$edition = $year > 0 ? Data::announcementEdition( $year ) : null;

		if ( null === $edition ) {
			return '';
		}

		return self::toHtml(
			$edition,
			home_url( '/' . Module::PAGE_SLUG . '/' ),
			$edition->hasPublishedResults() ? UitslaePage::urlFor( $year ) : '',
			null !== Data::galaYear( $year ) ? GalaPage::urlFor( $year ) : ''
		);
	}

	/**
	 * The page. Pure.
	 *
	 * @param Edition $edition The edition.
	 * @param string  $back    The `/kompetisies/` URL.
	 * @param string  $results The year's results URL, or '' when not published.
	 * @param string  $gala    The year's gala photos URL, or '' for none.
	 * @return string
	 */
	public static function toHtml( Edition $edition, string $back, string $results = '', string $gala = '' ): string {
		$year  = $edition->year();
		$uit   = $edition->field( 'uitgawe' );
		$facts = '';

		foreach ( array(
			array( __( 'Open', 'ink-core' ), $edition->field( 'oopdatum' ) ),
			array( __( 'Sluit', 'ink-core' ), $edition->field( 'sluitingsdatum' ) ),
		) as $fact ) {
			if ( '' !== $fact[1] ) {
				$facts .= '<li>' . esc_html( $fact[0] . ' ' . Markup::date( $fact[1] ) ) . '</li>';
			}
		}

		if ( '' !== $edition->field( 'inskrywing_nota' ) ) {
			$facts .= '<li>' . esc_html( $edition->field( 'inskrywing_nota' ) ) . '</li>';
		}

		$categories = '';

		foreach ( $edition->categories() as $category ) {
			$lines = '';

			foreach ( $category['riglyne'] as $line ) {
				$lines .= '<li>' . esc_html( $line ) . '</li>';
			}

			$categories .= '<div class="ink-card--feature ink-card--static">'
				. '<h3 class="ink-heading-h3">' . esc_html( $category['naam'] ) . '</h3>'
				. ( '' === $category['tema'] ? '' : '<p class="ink-eyebrow ink-eyebrow--theme">' . esc_html( $category['tema'] ) . '</p>' )
				. ( '' === $category['beskrywing'] ? '' : '<p>' . esc_html( $category['beskrywing'] ) . '</p>' )
				. ( '' === $category['woordlimiet'] ? '' : '<p>' . esc_html( $category['woordlimiet'] ) . '</p>' )
				. ( '' === $lines ? '' : '<ul>' . $lines . '</ul>' )
				. '</div>';
		}

		$links = '';

		foreach ( array(
			array( $results, sprintf( /* translators: %d: the competition year. */ __( 'Uitslae %d', 'ink-core' ), $year ) ),
			array( $gala, sprintf( /* translators: %d: the gala year. */ __( "Gala-foto's %d", 'ink-core' ), $year ) ),
		) as $link ) {
			if ( '' !== $link[0] ) {
				$links .= '<p><a class="ink-link--ink" href="' . esc_url( $link[0] ) . '">' . esc_html( $link[1] )
					. Markup::icon( Markup::ARROW_RIGHT, '', 'ink-link__icon--end' ) . '</a></p>';
			}
		}

		// Provisional layout: see the class comment. No audit ids exist for this page.
		return '<section class="ink-band ink-band--step">'
			. '<div class="ink-container">'
			. '<a class="ink-komp-gala-fotos__terug ink-nav-link--footer" href="' . esc_url( $back ) . '">'
			. Markup::icon( '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>' )
			. esc_html( Terms::label( 'kompetisie_plural' ) ) . '</a>'
			. '<p class="ink-komp-gala-fotos__jaar ink-eyebrow ink-eyebrow--card">' . esc_html( trim( ( $year > 0 ? (string) $year : '' ) . ( '' === $uit ? '' : ' · ' . $uit ) ) ) . '</p>'
			. '<h1 class="ink-komp-gala-fotos__titel ink-heading-h1-index">' . esc_html( $edition->title ) . '</h1>'
			. '<p>' . esc_html( $edition->field( 'beskrywing' ) ) . '</p>'
			. ( '' === $facts ? '' : '<ul>' . $facts . '</ul>' )
			. $links
			. ( '' === $categories ? '' : '<h2 class="ink-heading-h2-section">' . esc_html__( 'Kategorieë', 'ink-core' ) . '</h2>' . $categories )
			. self::galaHtml( $edition )
			. '</div></section>';
	}

	/**
	 * The gala details and tickets, or '' when the edition describes no gala. Pure.
	 *
	 * @param Edition $edition The edition.
	 * @return string
	 */
	private static function galaHtml( Edition $edition ): string {
		if ( ! $edition->hasGala() ) {
			return '';
		}

		$rows = '';

		foreach ( array(
			array( __( 'Datum', 'ink-core' ), '' === $edition->field( 'gala_datum' ) ? '' : Markup::date( $edition->field( 'gala_datum' ) ) ),
			array( __( 'Deure open', 'ink-core' ), $edition->field( 'gala_deure' ) ),
			array( __( 'Plek', 'ink-core' ), $edition->field( 'gala_plek' ) ),
			array( __( 'Drag', 'ink-core' ), $edition->field( 'gala_drag' ) ),
		) as $row ) {
			if ( '' !== $row[1] ) {
				$rows .= '<li>' . esc_html( $row[0] . ': ' . $row[1] ) . '</li>';
			}
		}

		$programme = '';

		foreach ( $edition->programme() as $line ) {
			$programme .= '<li>' . esc_html( $line ) . '</li>';
		}

		$tickets = '';

		foreach ( $edition->tickets() as $ticket ) {
			$tickets .= '<li>' . esc_html( trim( $ticket['label'] . ' ' . $ticket['price'] ) ) . '</li>';
		}

		return '<h2 class="ink-heading-h2-section">' . esc_html( $edition->field( 'gala_naam' ) ) . '</h2>'
			. ( '' === $edition->field( 'gala_beskrywing' ) ? '' : '<p>' . esc_html( $edition->field( 'gala_beskrywing' ) ) . '</p>' )
			. ( '' === $rows ? '' : '<ul>' . $rows . '</ul>' )
			. ( '' === $programme ? '' : '<h3 class="ink-heading-h3">' . esc_html__( 'Program', 'ink-core' ) . '</h3><ol>' . $programme . '</ol>' )
			. ( '' === $tickets ? '' : '<h3 class="ink-heading-h3">' . esc_html__( 'Kaartjies', 'ink-core' ) . '</h3><ul>' . $tickets . '</ul>' );
	}
}
