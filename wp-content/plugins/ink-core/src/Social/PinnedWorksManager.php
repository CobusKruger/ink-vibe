<?php
/**
 * Pinned-works curation block — Story 9.5 (FR-41).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/vasgespel-bestuur` curation block on the private My Profiel.
 *
 * Lists the logged-in writer's OWN published bydraes, each with a pin / unpin
 * toggle reflecting the current {@see PinnedWorks} state; the enqueued client
 * flips it through `ink/v1/vasgespel` (the leeslys server-render-then-flip
 * pattern). Shown only to the logged-in writer — a logged-out visitor sees
 * nothing.
 *
 * Conflation-clean: reads `Ink\Content\PostTypes` + `Ink\Social\PinnedWorks` +
 * `Terms` + WP core — zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class PinnedWorksManager {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/vasgespel-bestuur';

	/**
	 * Own-works listed for curation.
	 *
	 * @var int
	 */
	public const PER_PAGE = 50;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/vasgespel-bestuur` dynamic block.
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

		$user_id = get_current_user_id();

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

		$pinned = PinnedWorks::forUser( $user_id );
		$works  = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$works[] = array(
				'id'        => (int) $post->ID,
				'title'     => get_the_title( $post ),
				'is_pinned' => in_array( (int) $post->ID, $pinned, true ),
			);
		}

		return self::toHtml( $works );
	}

	/**
	 * Build the curation list HTML. Pure — Terms + escaping only.
	 *
	 * @param list<array{id:int, title:string, is_pinned:bool}> $works The writer's works.
	 * @return string
	 */
	public static function toHtml( array $works ): string {
		$heading = '<h2 class="ink-vasgespel__titel">' . esc_html__( 'Vasgespelde werke', 'ink-core' ) . '</h2>';

		if ( array() === $works ) {
			return '<section class="ink-vasgespel">' . $heading
				. '<p class="ink-vasgespel__leeg">' . esc_html__( 'Jy het nog geen gepubliseerde werk om vas te speld nie.', 'ink-core' ) . '</p></section>';
		}

		$html = '<section class="ink-vasgespel">' . $heading . '<ul class="ink-vasgespel__lys">';

		foreach ( $works as $work ) {
			$pinned  = ! empty( $work['is_pinned'] );
			$label   = $pinned ? Terms::label( 'vasgespeld' ) : Terms::label( 'vasgespel' );
			$classes = 'ink-vasgespel__knoppie' . ( $pinned ? ' is-pinned' : '' );

			$html .= '<li class="ink-vasgespel__item">'
				. '<span class="ink-vasgespel__werk">' . esc_html( (string) $work['title'] ) . '</span>'
				. '<button type="button" class="' . esc_attr( $classes ) . '"'
				. ' data-ink-post="' . esc_attr( (string) $work['id'] ) . '"'
				. ' aria-pressed="' . ( $pinned ? 'true' : 'false' ) . '">'
				. esc_html( $label )
				. '</button>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}
}
