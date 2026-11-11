<?php
/**
 * One competition's results: `/kompetisies/<jaar>/uitslae/` — K5 (`05-kompetisies` §14.4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\I18n\Terms;
use Ink\Kernel\SystemPages;

defined( 'ABSPATH' ) || exit;

/**
 * The route and block `ink/kompetisies-uitslae`: the results intro and the winners under their
 * award. Mirrors {@see GalaPage}.
 *
 *  - **Route:** `^kompetisies/<jaar>/uitslae/` → the `kompetisies` Page with {@see QUERY_VAR}, so
 *    the page's sheet loads; {@see TEMPLATE} goes first in the page hierarchy, and core's canonical
 *    redirect is off for it. Flushed once per {@see VERSION}. The page is code, never `post_content`.
 *  - **404:** a year with no edition, or whose edition has not published its results.
 *  - **One edition per year:** the lookup is the same as the gala page's (the year, newest post
 *    first). A second competition in one year (the `-2` key of spec §14.4) has NO defined route and
 *    is not invented here: such a year shows its newest edition with published results.
 *
 * LAYOUT IS PROVISIONAL (K5, 2026-11-11). The Lovable design (`../verse-haven`) has no competition
 * results route or winner list, so there is no ratified build spec or audit-ids. The markup is
 * structural and minimal and reuses shared primitives only (band, container, eyebrow,
 * `ink-heading-h1-index`, `ink-heading-h2-section`, `ink-heading-h3`, static `ink-card--feature`,
 * `ink-photo`, `ink-link--ink`, back link `ink-nav-link--footer`); there is no page CSS and no
 * audit-id. The owner must ratify a design before any fidelity claim.
 *
 * @phpstan-type WinnerRow array{name:string, url:string, entry:string, note:string, photo:array{url:string, width:int, height:int}|null}
 * @phpstan-type WinnerGroup array{award:string, winners:list<WinnerRow>}
 *
 * @package Ink\Core
 */
final class UitslaePage {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/kompetisies-uitslae';

	/**
	 * The theme template, without its extension.
	 *
	 * @var string
	 */
	public const TEMPLATE = 'kompetisies-uitslae';

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
	public const VERSION_OPTION = 'ink_kompetisie_uitslae_rewrite_version';

	/**
	 * Wire the route and the block. Runs on `init`.
	 */
	public function register(): void {
		add_rewrite_rule( '^' . Module::PAGE_SLUG . '/([0-9]{4})/uitslae/?$', 'index.php?pagename=' . Module::PAGE_SLUG . '&' . self::QUERY_VAR . '=$matches[1]', 'top' );
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
	 * The requested year, or 0 when this isn't a results view.
	 *
	 * @return int
	 */
	public static function requestedYear(): int {
		$year = get_query_var( self::QUERY_VAR, '' );

		return is_scalar( $year ) && 1 === preg_match( '/^\d{4}$/', (string) $year ) ? (int) $year : 0;
	}

	/**
	 * Whether this request is a results view.
	 *
	 * @return bool
	 */
	public static function isResultsView(): bool {
		return self::requestedYear() > 0 && is_page( Module::PAGE_SLUG );
	}

	/**
	 * Put the results template first.
	 *
	 * @param list<string> $templates The hierarchy.
	 * @return list<string>
	 */
	public static function templateHierarchy( array $templates ): array {
		if ( self::isResultsView() ) {
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
	 * 404 a year with no edition, or whose results are not published.
	 */
	public static function guard(): void {
		$year = self::requestedYear();

		if ( $year <= 0 || is_404() ) {
			return;
		}

		if ( null !== Data::resultsEdition( $year ) ) {
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
		return '/' . Module::PAGE_SLUG . '/' . $year . '/uitslae/';
	}

	/**
	 * The name to show for a winner, or '' when there is none (the winner is then skipped). A
	 * linked user shows the profile name; a deleted account falls back to the typed name. Pure.
	 *
	 * @param string $accountName The linked account's display name, '' when none or deleted.
	 * @param string $typedName   The free-text name.
	 * @return string
	 */
	public static function displayName( string $accountName, string $typedName ): string {
		$account = trim( $accountName );

		return '' !== $account ? $account : trim( $typedName );
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$year    = self::requestedYear();
		$edition = $year > 0 ? Data::resultsEdition( $year ) : null;

		if ( null === $edition ) {
			return '';
		}

		$groups = array();

		foreach ( $edition->winnersByAward() as $group ) {
			$rows = array();

			foreach ( $group['winners'] as $winner ) {
				$user    = $winner->hasUser() ? get_userdata( $winner->userId ) : false;
				$account = $user instanceof \WP_User ? (string) $user->display_name : '';
				$name    = self::displayName( $account, $winner->name );

				if ( '' === $name ) {
					continue;
				}

				$photo = null;
				$src   = $winner->hasPhoto() ? wp_get_attachment_image_src( $winner->photoId, 'medium_large' ) : false;

				if ( is_array( $src ) && is_string( $src[0] ) ) {
					$photo = array(
						'url'    => $src[0],
						'width'  => (int) $src[1],
						'height' => (int) $src[2],
					);
				}

				$rows[] = array(
					'name'  => $name,
					'url'   => '' !== $account && $user instanceof \WP_User ? (string) get_author_posts_url( $user->ID ) : '',
					'entry' => $winner->entryTitle,
					'note'  => $winner->note,
					'photo' => $photo,
				);
			}

			if ( array() !== $rows ) {
				$groups[] = array(
					'award'   => $group['award'],
					'winners' => $rows,
				);
			}
		}

		$has_gala = null !== Data::galaYear( $year );

		return self::toHtml( $year, $edition->title, $edition->resultsIntro(), $groups, $has_gala ? GalaPage::urlFor( $year ) : '', home_url( '/' . Module::PAGE_SLUG . '/' ), $edition->resultsBody() );
	}

	/**
	 * The page. Pure.
	 *
	 * @param int               $year   The year.
	 * @param string            $title  The competition's name.
	 * @param list<string>      $intro  The intro paragraphs.
	 * @param list<WinnerGroup> $groups The winners under their award.
	 * @param string            $gala   The year's gala photos URL, or '' for none.
	 * @param string            $back   The `/kompetisies/` URL.
	 * @param string            $body   A migrated old results post (HTML), shown after the intro; '' for none.
	 * @return string
	 */
	public static function toHtml( int $year, string $title, array $intro, array $groups, string $gala, string $back, string $body = '' ): string {
		$heading = '' !== trim( $title ) ? $title : sprintf(
			/* translators: %d: the competition year. */
			__( 'Die wenners van die %d Ink Kompetisie', 'ink-core' ),
			$year
		);

		/* translators: %d: the gala year. */
		$gala_label = sprintf( __( "Gala-foto's %d", 'ink-core' ), $year );
		$text       = '';

		foreach ( $intro as $paragraph ) {
			$text .= '<p class="ink-komp-uitslae__teks">' . esc_html( $paragraph ) . '</p>';
		}

		$awards = '';

		foreach ( $groups as $group ) {
			$cards = '';

			foreach ( $group['winners'] as $winner ) {
				$name   = '' === $winner['url'] ? esc_html( $winner['name'] ) : '<a class="ink-link--ink" href="' . esc_url( $winner['url'] ) . '">' . esc_html( $winner['name'] ) . '</a>';
				$cards .= '<div class="ink-komp-uitslae__wenner ink-card--feature ink-card--static">'
					. ( null === $winner['photo'] ? '' : '<img class="ink-photo" src="' . esc_url( $winner['photo']['url'] ) . '" alt="' . esc_attr( $winner['name'] ) . '"'
						. ( $winner['photo']['width'] > 0 && $winner['photo']['height'] > 0 ? ' width="' . $winner['photo']['width'] . '" height="' . $winner['photo']['height'] . '"' : '' )
						. ' loading="lazy" decoding="async" />' )
					. '<h3 class="ink-heading-h3">' . $name . '</h3>'
					. ( '' === $winner['entry'] ? '' : '<p class="ink-komp-uitslae__inskrywing">' . esc_html( $winner['entry'] ) . '</p>' )
					. ( '' === $winner['note'] ? '' : '<p class="ink-komp-uitslae__aantekening">' . esc_html( $winner['note'] ) . '</p>' )
					. '</div>';
			}

			$awards .= '<div class="ink-komp-uitslae__toekenning">'
				. '<h2 class="ink-heading-h2-section">' . esc_html( $group['award'] ) . '</h2>'
				. $cards
				. '</div>';
		}

		// Provisional layout: see the class comment. No audit ids exist for this page.
		return '<section class="ink-band ink-band--step">'
			. '<div class="ink-container">'
			. '<a class="ink-komp-gala-fotos__terug ink-nav-link--footer" href="' . esc_url( $back ) . '">'
			. Markup::icon( '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>' )
			. esc_html( Terms::label( 'kompetisie_plural' ) ) . '</a>'
			. '<p class="ink-komp-gala-fotos__jaar ink-eyebrow ink-eyebrow--card">' . esc_html( (string) $year ) . '</p>'
			. '<h1 class="ink-komp-gala-fotos__titel ink-heading-h1-index">' . esc_html( $heading ) . '</h1>'
			. $text
			. ( '' === $body ? '' : '<div class="ink-komp-uitslae__oud">' . wp_kses_post( $body ) . '</div>' )
			. $awards
			. ( '' === $gala ? '' : '<a class="ink-link--ink" href="' . esc_url( $gala ) . '">'
				. esc_html(
					sprintf(
					/* translators: %d: the gala year. */
						__( 'Gala-foto\'s %d', 'ink-core' ),
						$year
					)
				)
				. Markup::icon( Markup::ARROW_RIGHT, '', 'ink-link__icon--end' )
				. '</a>' )
			. '</div></section>';
	}
}
