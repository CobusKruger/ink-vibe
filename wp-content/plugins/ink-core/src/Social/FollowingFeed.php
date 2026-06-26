<?php
/**
 * Following-feed server block — Story 9.3 (FR-39).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/volg-voer` block: the member's following-feed.
 *
 * New publications by the skrywers the current member follows, newest-first —
 * the profile "Aktiwiteit" tab (Story 9.4 places it on My Profiel). Reads the
 * followed ids through {@see Api::followeeIdsFor()} (the 9.2 facade), never
 * {@see FollowStore} directly. Server-rendered via `WP_Query` (AD-7 — no REST
 * for listings), mirroring the {@see \Ink\Discovery\WorksArchive} house style:
 * pure {@see self::queryArgs()} + pure {@see self::toHtml()} + a thin
 * {@see self::render()}.
 *
 * The decisive correctness concern: a member who follows nobody must see the
 * empty state, NOT everyone's work. An empty `author__in` is silently ignored by
 * `WP_Query` (it would return all posts), so the render gate skips the query when
 * there are no followees and `queryArgs()` defensively yields `author__in =>
 * [0]` (matches nothing) for an empty list.
 *
 * Conflation-clean: references `Ink\Social\Api` (own module) + `Ink\Content`
 * (the readable-types single source) + `Terms` + WP core — zero
 * `Ink\Tiers`/`Ink\Entitlement` (seeing your feed is open to any lid, never
 * entitlement-gated).
 *
 * @package Ink\Core
 */
final class FollowingFeed {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/volg-voer';

	/**
	 * Recent publications shown in the feed.
	 *
	 * @var int
	 */
	public const PER_PAGE = 20;

	/**
	 * The "follows nobody yet" render state.
	 *
	 * @var string
	 */
	public const STATE_NO_FOLLOWS = 'geen-volg';

	/**
	 * The populated / "nothing published yet" render state.
	 *
	 * @var string
	 */
	public const STATE_FEED = 'voer';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/volg-voer` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Build the `WP_Query` args for the following-feed. Pure.
	 *
	 * Constrained to the followed skrywer ids via `author__in`. An empty id list
	 * yields `author__in => [0]` so the query matches NOTHING rather than every
	 * post (an empty `author__in` is silently dropped by `WP_Query`). Callers
	 * should still gate the no-followees case before querying (the empty state).
	 *
	 * @param list<int> $author_ids The followed skrywer ids.
	 * @param int       $per_page   Posts to show.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( array $author_ids, int $per_page ): array {
		$author_ids = array_values( array_filter( array_map( 'intval', $author_ids ) ) );

		return array(
			'post_type'           => PostTypes::readableTypes(),
			'post_status'         => 'publish',
			'author__in'          => array() === $author_ids ? array( 0 ) : $author_ids,
			'posts_per_page'      => $per_page,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * Block render callback (logged-in members only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$author_ids = Api::followeeIdsFor( get_current_user_id() );

		if ( array() === $author_ids ) {
			return self::toHtml( array(), self::STATE_NO_FOLLOWS );
		}

		$query = new \WP_Query( self::queryArgs( $author_ids, self::PER_PAGE ) );

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$cards[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
				'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			);
		}

		return self::toHtml( $cards, self::STATE_FEED );
	}

	/**
	 * Build the feed HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{title:string, permalink:string, type:string, author:string}> $cards The works.
	 * @param string                                                                  $state One of STATE_NO_FOLLOWS / STATE_FEED.
	 * @return string
	 */
	public static function toHtml( array $cards, string $state ): string {
		// Heading + empty-state copy are human-authored, approved Afrikaans from
		// ui-copy-translations.md (My Profiel — Volg/Aktiwiteit, lines 751/753/755).
		// Zero AI Afrikaans.
		$heading = '<h2 class="ink-volg-voer__heading">' . esc_html__( 'Aktiwiteit van wie jy volg', 'ink-core' ) . '</h2>';

		if ( self::STATE_NO_FOLLOWS === $state ) {
			$msg = __( "Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien.", 'ink-core' );

			return '<section class="ink-volg-voer">' . $heading
				. '<p class="ink-volg-voer__leeg">' . esc_html( $msg ) . '</p></section>';
		}

		if ( array() === $cards ) {
			$msg = __( 'Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer.', 'ink-core' );

			return '<section class="ink-volg-voer">' . $heading
				. '<p class="ink-volg-voer__leeg">' . esc_html( $msg ) . '</p></section>';
		}

		$html = '<section class="ink-volg-voer">' . $heading . '<ul class="ink-volg-voer__list">';

		foreach ( $cards as $card ) {
			$html .= '<li class="ink-volg-voer__item is-style-card">'
				. '<span class="ink-volg-voer__type">' . esc_html( Terms::label( $card['type'] ) ) . '</span>'
				. '<a class="ink-volg-voer__title" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
				. '<span class="ink-volg-voer__author">' . esc_html( $card['author'] ) . '</span>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
