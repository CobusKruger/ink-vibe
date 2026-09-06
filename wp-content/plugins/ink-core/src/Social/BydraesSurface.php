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
	 * Build the Bydraes-tab list HTML. Pure — escaping only.
	 *
	 * @param list<array{id:int, title:string, type:string, type_label:string, date:string, read_count:int, read_label:string, is_pinned:bool, edit_url:string, view_url:string}> $rows The writer's own works.
	 * @return string
	 */
	public static function toHtml( array $rows ): string {
		// "Jou bydraes" H2 + a second "Nuwe bydrae" button matching the identity
		// strip's — Lovable shows one per H2-row context; same ratified copy/link
		// (`ui-copy-translations.md` "Bydraes-blad" / "Identiteitsstrook"), no new
		// REST/JS mechanism (§5.4).
		$html = '<section class="ink-bydraes">'
			. '<div class="ink-bydraes__kop">'
			. '<h2 class="ink-bydraes__titel">' . esc_html__( 'Jou bydraes', 'ink-core' ) . '</h2>'
			. '<a class="ink-bydraes__nuwe wp-element-button" href="' . esc_url( home_url( '/skryf/' ) ) . '">' . esc_html__( 'Nuwe bydrae', 'ink-core' ) . '</a>'
			. '</div>';

		if ( array() === $rows ) {
			// No ratified copy exists yet for this empty state — flagged per the
			// standard [[afrikaans-copy-debt-process]] rather than invented (see
			// docs/afrikaans-translation-sheet.md BYDRAES-LEEG /
			// docs/afrikaans-copy-worklist.md).
			$empty = __( '[NEEDS HUMAN AFRIKAANS] — Bydraes-tab empty-state copy not yet authored in ui-copy-translations.md.', 'ink-core' );

			return $html . '<p class="ink-bydraes__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html .= '<ul class="ink-bydraes__lys">';

		foreach ( $rows as $row ) {
			$edit_url = (string) $row['edit_url'];

			$html .= '<li class="ink-bydraes__item is-style-card">'
				. '<div class="ink-bydraes__meta">'
				. '<span class="ink-bydraes__tipe">' . esc_html( (string) $row['type_label'] ) . '</span>'
				// Always "Gepubliseer" — never "Konsep" (§7 decision 3, no draft
				// concept exists in the submission flow to render one).
				. '<span class="ink-bydraes__status">' . esc_html__( 'Gepubliseer', 'ink-core' ) . '</span>';

			if ( '' !== (string) $row['date'] ) {
				$html .= '<span class="ink-bydraes__datum">' . esc_html( (string) $row['date'] ) . '</span>';
			}

			$html .= '</div>'
				. '<a class="ink-bydraes__werk-titel" href="' . esc_url( (string) $row['view_url'] ) . '">' . esc_html( (string) $row['title'] ) . '</a>'
				. '<div class="ink-bydraes__voet">'
				. '<span class="ink-bydraes__lesers">' . esc_html( (string) $row['read_label'] ) . '</span>'
				// The EXACT vasgespel.js-wired toggle markup — see the class docblock.
				. PinnedWorksManager::toggleHtml( (int) $row['id'], ! empty( $row['is_pinned'] ) )
				. '<a class="ink-bydraes__sien" href="' . esc_url( (string) $row['view_url'] ) . '">' . esc_html__( 'Sien', 'ink-core' ) . '</a>';

			if ( '' !== $edit_url ) {
				// Graceful degrade: get_edit_post_link() returns '' when the current
				// user lacks edit capability on the post — never a broken/empty href.
				$html .= '<a class="ink-bydraes__wysig" href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Wysig', 'ink-core' ) . '</a>';
			}

			$html .= '</div>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
