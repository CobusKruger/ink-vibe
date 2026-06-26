<?php
/**
 * Ontdek search server block — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\I18n\Terms;
use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-soek` block: diacritic-insensitive search of works + skrywers.
 *
 * The query term is folded ({@see Diacritics::fold()}) and matched `LIKE` against
 * the folded indexes maintained by {@see SearchIndex} — so accents never matter
 * (AD-7). Works = published bydraes (title/theme); skrywers = writers (name/bio/
 * genre). Server-rendered (`WP_Query`/`WP_User_Query`, no REST). Not gated.
 *
 * Conflation-clean: reads the Tiers Api for the skrywer Gradering label (display
 * only); zero `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class Search {

	public const BLOCK = 'ink/ontdek-soek';

	public const QUERY_VAR = 'soek';

	private const LIMIT = 12;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/ontdek-soek` dynamic block.
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
	 * The readable bydrae types searched (skryfwerk bucket excluded).
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( \Ink\Content\PostTypes::GEDIG, \Ink\Content\PostTypes::STORIE, \Ink\Content\PostTypes::ARTIKEL );
	}

	/**
	 * `WP_Query` args matching works whose folded index contains the folded term. Pure.
	 *
	 * @param string $folded The folded query term.
	 * @param int    $limit  Max results.
	 * @return array<string, mixed>
	 */
	public static function worksQueryArgs( string $folded, int $limit ): array {
		return array(
			'post_type'           => self::readableTypes(),
			'post_status'         => 'publish',
			'posts_per_page'      => $limit,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded, folded-index LIKE; the AD-7 search substrate, no search plugin.
			'meta_query'          => array(
				array(
					'key'     => SearchIndex::WORKS_META,
					'value'   => '%' . $folded . '%',
					'compare' => 'LIKE',
				),
			),
		);
	}

	/**
	 * `WP_User_Query` args matching writers whose folded index contains the term. Pure.
	 *
	 * @param string $folded The folded query term.
	 * @param int    $limit  Max results.
	 * @return array<string, mixed>
	 */
	public static function skrywersQueryArgs( string $folded, int $limit ): array {
		return array(
			'number'     => $limit,
			'fields'     => 'ID',
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded, folded-index LIKE; the AD-7 search substrate, no search plugin.
			'meta_query' => array(
				array(
					'key'     => SearchIndex::SKRYWER_META,
					'value'   => '%' . $folded . '%',
					'compare' => 'LIKE',
				),
			),
		);
	}

	/**
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$raw = self::requestQuery();

		if ( '' === $raw ) {
			return self::toHtml( '', array(), array() );
		}

		$folded = Diacritics::fold( $raw );

		$works = array();
		$query = new \WP_Query( self::worksQueryArgs( $folded, self::LIMIT ) );

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$works[] = array(
				'title'     => get_the_title( $post ),
				'permalink' => (string) get_permalink( $post ),
				'type'      => $post->post_type,
				'author'    => (string) get_the_author_meta( 'display_name', (int) $post->post_author ),
			);
		}

		$skrywers   = array();
		$user_query = new \WP_User_Query( self::skrywersQueryArgs( $folded, self::LIMIT ) );

		foreach ( array_map( 'intval', (array) $user_query->get_results() ) as $uid ) {
			if ( $uid <= 0 ) {
				continue;
			}

			$skrywers[] = array(
				'name'        => (string) get_the_author_meta( 'display_name', $uid ),
				'profile_url' => (string) get_author_posts_url( $uid ),
				'gradering'   => Terms::label( TiersApi::forUser( $uid )->value ),
			);
		}

		return self::toHtml( $raw, $works, $skrywers );
	}

	/**
	 * Read the (sanitised) raw search query from the query var / GET.
	 *
	 * @return string
	 */
	private static function requestQuery(): string {
		$value = get_query_var( self::QUERY_VAR, '' );

		if ( '' === $value || null === $value ) {
			$value = filter_input( INPUT_GET, self::QUERY_VAR );
		}

		return ( is_string( $value ) && '' !== $value ) ? sanitize_text_field( $value ) : '';
	}

	/**
	 * Build the search form + result groups. Pure — escaping + Terms only.
	 *
	 * @param string                                                                  $raw_query The raw (unfolded) query, for the input value + heading.
	 * @param list<array{title:string, permalink:string, type:string, author:string}> $works     Work results.
	 * @param list<array{name:string, profile_url:string, gradering:string}>          $skrywers  Skrywer results.
	 * @return string
	 */
	public static function toHtml( string $raw_query, array $works, array $skrywers ): string {
		$html = '<section class="ink-ontdek-soek"><form class="ink-ontdek-soek__form" role="search" method="get">'
			. '<input type="search" class="ink-ontdek-soek__veld" name="' . esc_attr( self::QUERY_VAR ) . '"'
			. ' value="' . esc_attr( $raw_query ) . '"'
			. ' placeholder="' . esc_attr__( 'Vind stories, gedigte of skrywers...', 'ink-core' ) . '"'
			. ' aria-label="' . esc_attr__( 'Vind stories, gedigte of skrywers...', 'ink-core' ) . '" />'
			. '<button type="submit" class="ink-ontdek-soek__knoppie">' . esc_html__( 'Soek', 'ink-core' ) . '</button>'
			. '</form>';

		if ( '' === $raw_query ) {
			return $html . '</section>';
		}

		if ( array() === $works && array() === $skrywers ) {
			$html .= '<p class="ink-ontdek-soek__leeg">' . esc_html__( "Probeer 'n ander soekterm of blaai deur alle artikels.", 'ink-core' ) . '</p>';

			return $html . '</section>';
		}

		if ( array() !== $works ) {
			$html .= '<h2 class="ink-ontdek-soek__groep">' . esc_html( Terms::label( 'bydrae_plural' ) ) . '</h2><ul class="ink-ontdek-soek__werke">';

			foreach ( $works as $work ) {
				$html .= '<li class="ink-ontdek-soek__werk is-style-card">'
					. '<span class="ink-ontdek-soek__tipe">' . esc_html( Terms::label( $work['type'] ) ) . '</span>'
					. '<a class="ink-ontdek-soek__titel" href="' . esc_url( $work['permalink'] ) . '">' . esc_html( $work['title'] ) . '</a>'
					. '<span class="ink-ontdek-soek__outeur">' . esc_html( $work['author'] ) . '</span>'
					. '</li>';
			}

			$html .= '</ul>';
		}

		if ( array() !== $skrywers ) {
			$html .= '<h2 class="ink-ontdek-soek__groep">' . esc_html( Terms::label( 'skrywer_plural' ) ) . '</h2><ul class="ink-ontdek-soek__skrywers">';

			foreach ( $skrywers as $skrywer ) {
				$html .= '<li class="ink-ontdek-soek__skrywer is-style-card">'
					. '<a class="ink-ontdek-soek__naam" href="' . esc_url( $skrywer['profile_url'] ) . '">' . esc_html( $skrywer['name'] ) . '</a>'
					. '<span class="ink-ontdek-soek__gradering">' . esc_html( $skrywer['gradering'] ) . '</span>'
					. '</li>';
			}

			$html .= '</ul>';
		}

		return $html . '</section>';
	}
}
