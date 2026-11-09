<?php
/**
 * Bydraes-tab unified per-post render — My Profiel rebuild §5.4/§3.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use Ink\Discovery\Api as DiscoveryApi;
use Ink\I18n\Terms;
use Ink\Kernel\QaFixture;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/bydraes` block: the My Profiel "Bydraes" tab's unified
 * per-post render (title/type/date/read-count/pin-toggle/edit-view actions in
 * one row), replacing the tab's previous two separately-rendered blocks
 * (`ink/leesgetalle` + `ink/vasgespel-bestuur`) with ONE own-author query.
 *
 * Runs the same own-author `WP_Query` shape {@see PinnedWorksManager} already
 * uses (published, {@see PostTypes::readableTypes()}, fixture-excluded) and
 * attaches, per post: the read count + "leser"/"lesers" label via
 * {@see \Ink\Discovery\Api} (§2 item 2's rename), the pin state via
 * {@see PinnedWorks::isPinned()}, and Edit/View links — ONE query rather than
 * the three separate own-author queries the flat page previously ran across
 * this tab (the decision recorded in `docs/my-profiel-rebuild-strategy.md`
 * §5.4).
 *
 * The pin/unpin control reuses {@see PinnedWorksManager::toggleHtml()}
 * VERBATIM — the exact markup `vasgespel.js`'s existing REST wiring already
 * targets — rather than forking a second pin-toggle shape or a second REST
 * endpoint (§3). The status badge is ALWAYS "Gepubliseer": no "Konsep" (draft)
 * concept exists in the submission flow today, a deliberate, product-owner-
 * ratified omission (§7 decision 3), not an oversight.
 *
 * Does NOT touch {@see PinnedWorks}/{@see PinnedWorksManager}'s query/REST
 * logic, nor {@see \Ink\Discovery\ReadCount}'s counting logic — this class
 * reads the SAME underlying data sources for a new, unified presentation
 * layer (§3's explicit boundary).
 *
 * Conflation-clean: reads `Ink\Content\PostTypes` (own-slug registry) +
 * `Ink\Discovery\Api` (display-only read-count facade, `deptrac.yaml`
 * Social→Discovery edge) + `Ink\Kernel\QaFixture` + WP core — zero
 * `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class BydraesSurface {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/bydraes';

	/**
	 * Own-works listed.
	 *
	 * @var int
	 */
	public const PER_PAGE = 50;

	/**
	 * Register the server-rendered block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/bydraes` dynamic block.
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
	 * Block render callback (the logged-in writer's own works only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		return self::toHtml( self::rows( get_current_user_id() ) );
	}

	/**
	 * The current user's own published works, with the read count, pin state,
	 * and Edit/View links attached — one query, shared by {@see render()} and
	 * the Oorsig "Bydraes" stat count (`patterns/my-profiel.php`).
	 *
	 * @param int $user_id The writer.
	 * @return list<array{id:int, title:string, type:string, type_label:string, date:string, read_count:int, read_label:string, is_pinned:bool, edit_url:string, view_url:string}>
	 */
	public static function rows( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		$query = new \WP_Query(
			array(
				'post_type'           => PostTypes::readableTypes(),
				'post_status'         => 'publish',
				'author'              => $user_id,
				'posts_per_page'      => self::PER_PAGE,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			)
		);

		$rows = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$title = get_the_title( $post );

			// Same fixture-leak bug class fixed on every other page this rework —
			// the writer's own bydraes list must never show seeded QA content.
			if ( QaFixture::isFixtureTitle( $title ) ) {
				continue;
			}

			$post_id    = (int) $post->ID;
			$type       = (string) get_post_type( $post );
			$read_count = class_exists( DiscoveryApi::class ) ? DiscoveryApi::readCountFor( $post_id ) : 0;
			$timestamp  = get_post_time( 'U', true, $post );

			$rows[] = array(
				'id'         => $post_id,
				'title'      => $title,
				'type'       => $type,
				'type_label' => Terms::label( $type ),
				'date'       => is_int( $timestamp ) ? date_i18n( get_option( 'date_format' ), $timestamp ) : '',
				'read_count' => $read_count,
				'read_label' => class_exists( DiscoveryApi::class ) ? DiscoveryApi::readerLabel( $read_count ) : '',
				'is_pinned'  => PinnedWorks::isPinned( $user_id, $post_id ),
				// 'raw' (unescaped by WP) so toHtml() escapes it exactly once, the
				// same raw-then-esc_url-at-render convention get_permalink() below
				// already follows across this codebase.
				'edit_url'   => (string) get_edit_post_link( $post_id, 'raw' ),
				'view_url'   => (string) get_permalink( $post_id ),
			);
		}

		return $rows;
	}

	/**
	 * Whether the user has at least one published, non-fixture work of a type.
	 *
	 * Drives the My Profiel "writer" gates (owner, 2026-11-09): a member with no
	 * bydrae is a reader; the Gradering pill + wins-needed line need a Gedig.
	 *
	 * @param int    $user_id The member.
	 * @param string $type    A {@see PostTypes} slug.
	 */
	public static function hasPublishedType( int $user_id, string $type ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		$query = new \WP_Query(
			array(
				'post_type'           => $type,
				'post_status'         => 'publish',
				'author'              => $user_id,
				'posts_per_page'      => self::PER_PAGE,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
			)
		);

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post && ! QaFixture::isFixtureTitle( get_the_title( $post ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * The reader state of the Bydraes tab: a member with no bydrae yet is invited
	 * to start writing. Copy supplied by the owner (2026-11-09).
	 *
	 * @return string
	 */
	private static function readerHtml(): string {
		$buttons = array(
			array( 'begin-skryf', __( 'Begin skryf', 'ink-core' ), '/skryf/', 'ink-btn--primary' ),
			array( 'uitdagings', __( 'Uitdagings', 'ink-core' ), '/uitdaging/', 'ink-btn--outline' ),
			array( 'opleiding', __( 'Opleiding', 'ink-core' ), '/opleiding/', 'ink-btn--outline' ),
		);

		$html = '<section class="ink-bydraes ink-bydraes--leser">'
			. '<div class="ink-card ink-card--empty ink-bydraes__leeg" data-audit-id="profiel-posts-empty">'
			. '<p class="ink-bydraes__leeg-teks">'
			. esc_html__( 'Wil jy graag van jou eie skryfwerk op Ink publiseer? Begin hier, of kyk na ons maandelikse uitdagings. En as jy nog nie gereed is nie, besoek gerus ons opleiding-afdeling.', 'ink-core' )
			. '</p>'
			. '<div class="ink-bydraes__leeg-aksies">';

		foreach ( $buttons as $button ) {
			$html .= '<a class="ink-btn ' . esc_attr( $button[3] ) . '" data-audit-id="profiel-posts-empty-' . esc_attr( $button[0] ) . '" href="' . esc_url( home_url( $button[2] ) ) . '">' . esc_html( $button[1] ) . '</a>';
		}

		return $html . '</div></div></section>';
	}

	/**
	 * Build the Bydraes-tab list HTML. Pure — escaping only.
	 *
	 * @param list<array{id:int, title:string, type:string, type_label:string, date:string, read_count:int, read_label:string, is_pinned:bool, edit_url:string, view_url:string}> $rows The writer's own works.
	 * @return string
	 */
	public static function toHtml( array $rows ): string {
		if ( array() === $rows ) {
			return self::readerHtml();
		}

		// "Jou bydraes" H2 + a second "Nuwe bydrae" button matching the identity
		// strip's — Lovable shows one per H2-row context; same ratified copy/link
		// (`ui-copy-translations.md` "Bydraes-blad" / "Identiteitsstrook"), no new
		// REST/JS mechanism (§5.4).
		$html = '<section class="ink-bydraes">'
			. '<div class="ink-bydraes__kop" data-audit-id="profiel-posts-head">'
			. '<h2 class="ink-heading-h2-block ink-bydraes__titel" data-audit-id="profiel-posts-title">' . esc_html__( 'Jou bydraes', 'ink-core' ) . '</h2>'
			. '<a class="ink-btn ink-btn--primary ink-bydraes__nuwe" data-audit-id="profiel-posts-new-btn" href="' . esc_url( home_url( '/skryf/' ) ) . '">' . esc_html__( 'Nuwe bydrae', 'ink-core' ) . '</a>'
			. '</div>';

		$html .= '<ul class="ink-bydraes__lys" data-audit-id="profiel-posts-list">';

		foreach ( array_values( $rows ) as $i => $row ) {
			$edit_url = (string) $row['edit_url'];
			// Fidelity join keys on the FIRST row only (maps/my-profiel.csv) — ids are unique per page.
			$audit = static fn( string $suffix ): string => 0 === $i ? ' data-audit-id="profiel-posts-' . $suffix . '"' : '';
			$pin   = PinnedWorksManager::toggleHtml( (int) $row['id'], ! empty( $row['is_pinned'] ) );

			if ( 0 === $i ) {
				$pin = (string) preg_replace( '/^<button\b/', '<button data-audit-id="profiel-posts-pin"', $pin, 1 );
			}

			$html .= '<li class="ink-card ink-bydraes__item"' . $audit( 'card' ) . '>'
				. '<div class="ink-bydraes__meta"' . $audit( 'card-meta' ) . '>'
				. '<span class="ink-pill ink-pill--outline ink-bydraes__tipe"' . $audit( 'type' ) . '>' . esc_html( (string) $row['type_label'] ) . '</span>'
				// Always "Gepubliseer" — never "Konsep" (§7 decision 3, no draft
				// concept exists in the submission flow to render one).
				. '<span class="ink-pill ink-pill--outline ink-tone-accent ink-bydraes__status"' . $audit( 'status' ) . '>' . esc_html__( 'Gepubliseer', 'ink-core' ) . '</span>';

			if ( '' !== (string) $row['date'] ) {
				$html .= '<span class="ink-meta ink-bydraes__datum"' . $audit( 'date' ) . '>' . esc_html( (string) $row['date'] ) . '</span>';
			}

			$html .= '</div>'
				. '<a class="ink-heading-h4 ink-bydraes__werk-titel"' . $audit( 'card-title' ) . ' href="' . esc_url( (string) $row['view_url'] ) . '">' . esc_html( (string) $row['title'] ) . '</a>'
				. '<div class="ink-bydraes__voet"' . $audit( 'actions' ) . '>'
				. '<span class="ink-meta ink-bydraes__lesers"' . $audit( 'readers' ) . '>' . esc_html( (string) $row['read_label'] ) . '</span>'
				// The EXACT vasgespel.js-wired toggle markup — see the class docblock.
				. $pin
				. '<a class="ink-btn ink-btn--sm ink-btn--primary ink-bydraes__sien"' . $audit( 'view-btn' ) . ' href="' . esc_url( (string) $row['view_url'] ) . '">' . esc_html__( 'Sien', 'ink-core' ) . '</a>';

			if ( '' !== $edit_url ) {
				// Graceful degrade: get_edit_post_link() returns '' when the current
				// user lacks edit capability on the post — never a broken/empty href.
				$html .= '<a class="ink-btn ink-btn--sm ink-btn--subtle ink-bydraes__wysig"' . $audit( 'edit-btn' ) . ' href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Wysig', 'ink-core' ) . '</a>';
			}

			$html .= '</div>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
