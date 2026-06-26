<?php
/**
 * Personalised discovery surfaces — Story 8.5 (FR-36, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\I18n\Terms;
use Ink\Tiers\Api as TiersApi;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/ontdek-vlakke` block: INK's OWN discovery surfaces (never a
 * default BuddyPress directory screen).
 *
 * Surfaces (all server-rendered `WP_User_Query`, AD-7): New voices (first-publish
 * recency), Recently active (last-publish recency), Skrywers soos jy (writers
 * sharing a published FORM with the logged-in writer — shared-taxonomy
 * relatedness, Principle 8, no follow graph), and Skrywers in jou Gradering
 * (same-`Tier` peers — a discovery CONVENIENCE clustering, never an entitlement
 * gate). "Unread-by-you" is deferred (no per-user read history exists yet —
 * see deferred-work.md); the component slots it in when that lands.
 *
 * Conflation-clean: reads `Tiers\Api` for grouping/labels (display), never gates
 * discovery on a tier; zero `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class DiscoverySurfaces {

	public const BLOCK = 'ink/ontdek-vlakke';

	/**
	 * Writers shown per surface row.
	 *
	 * @var int
	 */
	private const LIMIT = 6;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/ontdek-vlakke` dynamic block.
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
	 * `WP_User_Query` args for "New voices" (newest first publication). Pure.
	 *
	 * @param int $limit Max writers.
	 * @return array<string, mixed>
	 */
	public static function newVoicesArgs( int $limit ): array {
		return self::byMetaDesc( SkrywerIndex::FIRST_PUBLISH_META, $limit );
	}

	/**
	 * `WP_User_Query` args for "Recently active" (newest last publication). Pure.
	 *
	 * @param int $limit Max writers.
	 * @return array<string, mixed>
	 */
	public static function recentlyActiveArgs( int $limit ): array {
		return self::byMetaDesc( SkrywerIndex::LAST_PUBLISH_META, $limit );
	}

	/**
	 * Writers-by-NUMERIC-meta-DESC args (writer-scoped). Pure.
	 *
	 * @param string $meta_key The NUMERIC user-meta to order by.
	 * @param int    $limit    Max writers.
	 * @return array<string, mixed>
	 */
	private static function byMetaDesc( string $meta_key, int $limit ): array {
		return array(
			'number'     => $limit,
			'fields'     => 'ID',
			'orderby'    => array( 'sorteer' => 'DESC' ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- discovery-scoped, bounded WP_User_Query on indexed denorm meta (AD-7).
			'meta_query' => array(
				'sorteer' => array(
					'key'     => $meta_key,
					'type'    => 'NUMERIC',
					'compare' => 'EXISTS',
				),
			),
		);
	}

	/**
	 * `WP_User_Query` args for "writers like" a reference writer (shared form). Pure.
	 *
	 * An OR over the reference writer's published-form flags, excluding the
	 * reference. Empty forms (not a writer) → a match-nothing sentinel.
	 *
	 * @param list<string> $forms      The reference writer's published forms.
	 * @param int          $exclude_id The reference writer (excluded).
	 * @param int          $limit      Max writers.
	 * @return array<string, mixed>
	 */
	public static function writersLikeArgs( array $forms, int $exclude_id, int $limit ): array {
		if ( array() === $forms ) {
			// No forms ⇒ surface nothing (include id 0 matches no user).
			return array(
				'number'  => $limit,
				'fields'  => 'ID',
				'include' => array( 0 ),
			);
		}

		$meta = array( 'relation' => 'OR' );

		foreach ( $forms as $form ) {
			$meta[] = array(
				'key'   => SkrywerIndex::formFlagKey( $form ),
				'value' => '1',
			);
		}

		return array(
			'number'     => $limit,
			'fields'     => 'ID',
			'exclude'    => array( $exclude_id ),
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- discovery-scoped, bounded form-flag OR query (AD-7).
			'meta_query' => $meta,
		);
	}

	/**
	 * Remove an id from a list (e.g. the viewer themselves). Pure.
	 *
	 * @param list<int> $ids The ids.
	 * @param int       $id  The id to drop.
	 * @return list<int>
	 */
	public static function excludeId( array $ids, int $id ): array {
		return array_values( array_filter( $ids, static fn ( int $candidate ): bool => $candidate !== $id ) );
	}

	/**
	 * The published forms a writer has a flag for.
	 *
	 * @param int $user_id The writer.
	 * @return list<string>
	 */
	public static function formsFor( int $user_id ): array {
		$forms = array();

		foreach ( SkrywerIndex::readableTypes() as $type ) {
			if ( '1' === (string) get_user_meta( $user_id, SkrywerIndex::formFlagKey( $type ), true ) ) {
				$forms[] = $type;
			}
		}

		return $forms;
	}

	/**
	 * Other writers in the member's Gradering (same Tier), excluding self.
	 *
	 * @param int $user_id The member.
	 * @param int $limit   Max writers.
	 * @return list<int>
	 */
	public static function inGraderingIds( int $user_id, int $limit ): array {
		$ids = TiersApi::usersByGrade(
			TiersApi::forUser( $user_id ),
			array( 'number' => $limit + 1 )
		);

		return array_slice( self::excludeId( $ids, $user_id ), 0, $limit );
	}

	/**
	 * Block render callback. Assembles the surfaces for the current viewer.
	 *
	 * @return string
	 */
	public static function render(): string {
		$surfaces = array(
			'nuwe_stemme'    => self::cardsFromUserQuery( self::newVoicesArgs( self::LIMIT ) ),
			'onlangs_aktief' => self::cardsFromUserQuery( self::recentlyActiveArgs( self::LIMIT ) ),
		);

		if ( is_user_logged_in() ) {
			$uid   = get_current_user_id();
			$forms = self::formsFor( $uid );

			if ( array() !== $forms ) {
				$surfaces['soos_jy'] = self::cardsFromUserQuery( self::writersLikeArgs( $forms, $uid, self::LIMIT ) );
			}

			$surfaces['jou_gradering'] = self::cardsFromIds( self::inGraderingIds( $uid, self::LIMIT ) );
		}

		return self::toHtml( $surfaces );
	}

	/**
	 * Run a `WP_User_Query` and map its ids to skrywer cards.
	 *
	 * @param array<string, mixed> $args The query args.
	 * @return list<array{name:string, profile_url:string, gradering:string}>
	 */
	private static function cardsFromUserQuery( array $args ): array {
		$query = new \WP_User_Query( $args );

		return self::cardsFromIds( array_map( 'intval', (array) $query->get_results() ) );
	}

	/**
	 * Map writer ids to skrywer cards.
	 *
	 * @param list<int> $ids The writer ids.
	 * @return list<array{name:string, profile_url:string, gradering:string}>
	 */
	private static function cardsFromIds( array $ids ): array {
		$cards = array();

		foreach ( $ids as $uid ) {
			$uid = (int) $uid;

			if ( $uid <= 0 ) {
				continue;
			}

			$cards[] = array(
				'name'        => (string) get_the_author_meta( 'display_name', $uid ),
				'profile_url' => (string) get_author_posts_url( $uid ),
				'gradering'   => Terms::label( TiersApi::forUser( $uid )->value ),
			);
		}

		return $cards;
	}

	/**
	 * Build the surfaces HTML — a titled row per NON-EMPTY surface. Pure.
	 *
	 * @param array<string, list<array{name:string, profile_url:string, gradering:string}>> $surfaces Surface key → cards.
	 * @return string
	 */
	public static function toHtml( array $surfaces ): string {
		$html = '';

		foreach ( $surfaces as $key => $cards ) {
			if ( array() === $cards ) {
				continue; // No empty rows.
			}

			$html .= '<section class="ink-ontdek-vlak ink-ontdek-vlak--' . esc_attr( (string) $key ) . '">'
				. '<h2 class="ink-ontdek-vlak__titel">' . esc_html( self::surfaceLabel( (string) $key ) ) . '</h2>'
				. '<ul class="ink-ontdek-vlak__lys">';

			foreach ( $cards as $card ) {
				$html .= '<li class="ink-ontdek-vlak__item is-style-card">'
					. '<a class="ink-ontdek-vlak__naam" href="' . esc_url( $card['profile_url'] ) . '">' . esc_html( $card['name'] ) . '</a>'
					. '<span class="ink-ontdek-vlak__gradering">' . esc_html( $card['gradering'] ) . '</span>'
					. '</li>';
			}

			$html .= '</ul></section>';
		}

		if ( '' === $html ) {
			return '';
		}

		return '<div class="ink-ontdek-vlakke">' . $html . '</div>';
	}

	/**
	 * The Afrikaans heading for a surface (authored source copy — copy-debt to ratify).
	 *
	 * @param string $key The surface key.
	 * @return string
	 */
	private static function surfaceLabel( string $key ): string {
		switch ( $key ) {
			case 'onlangs_aktief':
				return __( 'Onlangs aktief', 'ink-core' );
			case 'soos_jy':
				return __( 'Skrywers soos jy', 'ink-core' );
			case 'jou_gradering':
				return __( 'Skrywers in jou Gradering', 'ink-core' );
			case 'nuwe_stemme':
			default:
				return __( 'Nuwe stemme', 'ink-core' );
		}
	}
}
