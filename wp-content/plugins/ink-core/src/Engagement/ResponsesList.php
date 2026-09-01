<?php
/**
 * Gemeenskapsreaksie list + form server block — Story 7.4 (FR-27, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\I18n\Terms;
use Ink\Kernel\ResponseType;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/gemeenskapsreaksies` server block on a reading surface.
 *
 * Reads stay server-rendered (AD-7): the existing typed responses are listed
 * server-side (type badge + escaped author / date / content), with a typed
 * response form beneath that posts through the `ink/v1/gemeenskapsreaksie` REST
 * endpoint. The displayed count is the filtered `ink_reaksie` count
 * ({@see ResponseStore::countForPost()}), never WordPress's `comment_count`
 * (AD-5a). All controlled-vocabulary labels (Gemeenskapsreaksies, Lof / Insig /
 * Voorstel, Plaas) come from the glossary-backed {@see Terms} registry — no bare
 * literals. Presentation lives in the theme (CSS); this owns the structure.
 *
 * `toHtml()` is pure (Terms + escaping only) and unit-tested; `render()` is the
 * thin block callback that pulls the post's data.
 *
 * @package Ink\Core
 */
final class ResponsesList {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/gemeenskapsreaksies';

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
	 * Register the `ink/gemeenskapsreaksies` dynamic block.
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
	 * Block render callback: the list + form for the current work.
	 *
	 * @return string
	 */
	public static function render(): string {
		$post_id = function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0;

		if ( $post_id <= 0 ) {
			return '';
		}

		return self::toHtml( $post_id, ResponseStore::forPost( $post_id ), ResponseStore::countForPost( $post_id ) );
	}

	/**
	 * Build the Gemeenskapsreaksies section HTML. Pure — Terms + escaping only.
	 *
	 * @param int                                                                                $post_id   The work.
	 * @param list<array{id:int, type:ResponseType, content:string, author:string, date:string}> $responses The existing typed responses.
	 * @param int                                                                                $count     The filtered response count.
	 * @return string
	 */
	public static function toHtml( int $post_id, array $responses, int $count ): string {
		$heading_label = 1 === $count ? Terms::label( 'gemeenskapsreaksie' ) : Terms::label( 'gemeenskapsreaksie_plural' );

		$html  = '<section class="ink-reaksies" aria-label="' . esc_attr( Terms::label( 'gemeenskapsreaksie_plural' ) ) . '">';
		$html .= '<h2 class="ink-reaksies__heading">' . esc_html( (string) $count . ' ' . $heading_label ) . '</h2>';

		$html .= '<ul class="ink-reaksies__list">';
		foreach ( $responses as $response ) {
			$type = $response['type'];

			$html .= '<li class="ink-reaksies__item ink-reaksie--' . esc_attr( $type->value ) . '">'
				. '<div class="ink-reaksies__meta">'
				. '<span class="ink-reaksies__who">'
				. '<span class="ink-reaksies__badge">' . esc_html( Terms::label( $type->value ) ) . '</span>'
				. '<span class="ink-reaksies__author">' . esc_html( $response['author'] ) . '</span>'
				. '</span>'
				. '<span class="ink-reaksies__date">' . esc_html( self::formatDate( $response['date'] ) ) . '</span>'
				. '</div>'
				. '<p class="ink-reaksies__text">' . esc_html( $response['content'] ) . '</p>'
				. '</li>';
		}
		$html .= '</ul>';

		$html .= self::formHtml( $post_id );
		$html .= '</section>';

		return $html;
	}

	/**
	 * Format a stored `comment_date` for display in the response list.
	 *
	 * Deliberately locale-free (numeric `d/m/Y`, PHP built-ins only) so this stays
	 * a pure function — no `date_i18n()`/`get_option()` WP dependency to mock in
	 * tests, matching this class's "Terms + escaping only" contract.
	 *
	 * @param string $mysqlDate The `comment_date` (`Y-m-d H:i:s`).
	 * @return string The formatted date, or `''` if unparseable.
	 */
	private static function formatDate( string $mysqlDate ): string {
		$timestamp = strtotime( $mysqlDate );

		return false === $timestamp ? '' : gmdate( 'j/n/Y', $timestamp );
	}

	/**
	 * The typed response form — three type radios (the enum) + a textarea + submit.
	 * Posts through the REST endpoint (handled by the enqueued client).
	 *
	 * @param int $post_id The work.
	 * @return string
	 */
	private static function formHtml( int $post_id ): string {
		$html = '<form class="ink-reaksies__form" data-ink-post="' . esc_attr( (string) $post_id ) . '">';

		$html .= '<fieldset class="ink-reaksies__types">';
		foreach ( ResponseType::cases() as $type ) {
			$html .= '<label class="ink-reaksies__type"><input type="radio" name="ink_reaksie_type" value="'
				. esc_attr( $type->value ) . '"> ' . esc_html( Terms::label( $type->value ) ) . '</label>';
		}
		$html .= '</fieldset>';

		$html .= '<textarea class="ink-reaksies__input" name="ink_reaksie_content" rows="3" aria-label="'
			. esc_attr( Terms::label( 'gemeenskapsreaksie' ) ) . '"></textarea>';

		$html .= '<button type="submit" class="ink-reaksies__submit">' . esc_html( Terms::label( 'plaas' ) ) . '</button>';

		$html .= '</form>';

		return $html;
	}
}
