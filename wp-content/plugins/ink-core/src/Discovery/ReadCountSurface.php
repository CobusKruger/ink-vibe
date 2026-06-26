<?php
/**
 * Private read-count surface on My Profiel — Story 9.12 (FR-44b, R8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/leesgetalle` block: the logged-in writer's PRIVATE per-bydrae
 * read counts on My Profiel (R8).
 *
 * Lists the current user's own published bydraes with each work's
 * `_ink_read_count` ({@see ReadCount::READ_COUNT_META}, bumped on every
 * published-bydrae view, Story 8.x), verb-less and plural-correct. PRIVATE — it
 * is embedded only in the My Profiel pattern and renders only the current user's
 * own works; it never appears on the public Skrywerprofiel (the FR-40
 * separation). Server-rendered (`WP_Query`, no REST — AD-7), mirroring the
 * {@see WorksArchive} house style.
 *
 * Lives in Discovery because Discovery OWNS the read count (the `_ink_read_count`
 * meta + {@see ReadCount}); reading its own meta needs no new cross-module edge
 * (Discovery→Content is already allowed). Conflation-clean: zero
 * `Ink\Tiers`/`Ink\Entitlement` (a writer seeing their own reach is private, not
 * a gate).
 *
 * @package Ink\Core
 */
final class ReadCountSurface {

	/**
	 * The block name (single source for the renderer + the theme embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/leesgetalle';

	/**
	 * Own works listed.
	 *
	 * @var int
	 */
	public const PER_PAGE = 100;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/leesgetalle` dynamic block.
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

		$query = new \WP_Query(
			array(
				'post_type'           => PostTypes::readableTypes(),
				'post_status'         => 'publish',
				'author'              => get_current_user_id(),
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

			$rows[] = array(
				'title' => get_the_title( $post ),
				'count' => (int) get_post_meta( (int) $post->ID, ReadCount::READ_COUNT_META, true ),
			);
		}

		return self::toHtml( $rows );
	}

	/**
	 * The verb-less, plural-correct read-count label.
	 *
	 * A noun phrase ("12 lesings"), not the verb form ("12 keer gelees") — the
	 * Story 7.8 verb-less house style. (`lesing`/`lesings` — copy-debt to ratify.)
	 *
	 * @param int $n The read count (n=0 → plural form).
	 * @return string
	 */
	public static function countLabel( int $n ): string {
		/* translators: %s: the number of reads (lesings) of a work. */
		$format = _n( '%s lesing', '%s lesings', $n, 'ink-core' );

		return sprintf( $format, number_format_i18n( $n ) );
	}

	/**
	 * Build the read-count list HTML. Pure — escaping only.
	 *
	 * @param list<array{title:string, count:int}> $rows The writer's works + counts.
	 * @return string
	 */
	public static function toHtml( array $rows ): string {
		$heading = '<h2 class="ink-leesgetalle__titel">' . esc_html__( 'Leesgetalle', 'ink-core' ) . '</h2>';

		if ( array() === $rows ) {
			return '<section class="ink-leesgetalle">' . $heading
				. '<p class="ink-leesgetalle__leeg">' . esc_html__( 'Jy het nog geen gepubliseerde werk nie.', 'ink-core' ) . '</p></section>';
		}

		$html = '<section class="ink-leesgetalle">' . $heading . '<ul class="ink-leesgetalle__lys">';

		foreach ( $rows as $row ) {
			$html .= '<li class="ink-leesgetalle__item">'
				. '<span class="ink-leesgetalle__werk">' . esc_html( (string) $row['title'] ) . '</span>'
				. '<span class="ink-leesgetalle__telling">' . esc_html( self::countLabel( (int) $row['count'] ) ) . '</span>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
