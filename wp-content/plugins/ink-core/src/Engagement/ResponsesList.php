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
 * server-side (avatar + per-type badge + escaped author / relative date /
 * content), with a typed response form beneath that posts through the
 * `ink/v1/gemeenskapsreaksie` REST endpoint. The displayed count is the filtered
 * `ink_reaksie` count ({@see ResponseStore::countForPost()}), never WordPress's
 * `comment_count` (AD-5a). All controlled-vocabulary labels (Gemeenskapsreaksies,
 * Lof / Insig / Voorstel, Plaas) come from the glossary-backed {@see Terms}
 * registry — no bare literals. Presentation lives in the theme (CSS); this owns
 * the structure.
 *
 * lees-gedig fidelity pass (docs/theme-fidelity-audit-handoff.md §6, findings
 * #12/13/14/16): the response-type chips + badges each carry a per-category icon
 * (mirroring Lovable's Sparkles/Lightbulb/ThumbsUp -> Lof/Insig/Voorstel mapping);
 * the textarea placeholder + submit `disabled` state are wired for real (an empty
 * textarea now genuinely can't submit, matching Lovable — this was a real
 * functional gap, not just a styling one; the client
 * (assets/js/gemeenskapsreaksie.js) keeps the disabled state in sync as the
 * visitor types). Each response card shows the author's avatar and a relative
 * ("[N] ... gelede") timestamp instead of an absolute date, plus an upvote count
 * and a Reply action — Reply focuses the response form (a real, small piece of
 * behaviour); the upvote count is a truthful, currently-always-zero READ (no
 * per-response upvote store exists yet — that's reaction-system machinery
 * (AD-5a) deliberately deferred to the cross-page reaction-system reconciliation
 * dispatch alongside the whole-poem reaction bar and line reactions, not invented
 * here), so it is rendered as inert text, not a button implying a working toggle.
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
	 * @param int                                                                                              $post_id   The work.
	 * @param list<array{id:int, type:ResponseType, content:string, author:string, date:string, user_id?:int}> $responses The existing typed responses.
	 * @param int                                                                                              $count     The filtered response count.
	 * @return string
	 */
	public static function toHtml( int $post_id, array $responses, int $count ): string {
		$heading_label = 1 === $count ? Terms::label( 'gemeenskapsreaksie' ) : Terms::label( 'gemeenskapsreaksie_plural' );

		$html  = '<section class="ink-reaksies" aria-label="' . esc_attr( Terms::label( 'gemeenskapsreaksie_plural' ) ) . '">';
		$html .= '<h2 class="ink-reaksies__heading">' . esc_html( (string) $count . ' ' . $heading_label ) . '</h2>';

		$html .= '<ul class="ink-reaksies__list">';
		foreach ( $responses as $response ) {
			$type    = $response['type'];
			$user_id = isset( $response['user_id'] ) ? (int) $response['user_id'] : 0;

			$html .= '<li class="ink-reaksies__item ink-reaksie--' . esc_attr( $type->value ) . '">';

			if ( function_exists( 'get_avatar' ) ) {
				$html .= (string) get_avatar( $user_id, 40, '', $response['author'], array( 'class' => 'ink-reaksies__avatar' ) );
			}

			$html .= '<div class="ink-reaksies__body">'
				. '<div class="ink-reaksies__meta">'
				. '<span class="ink-reaksies__who">'
				. '<span class="ink-reaksies__badge">' . self::badgeIcon( $type ) . esc_html( Terms::label( $type->value ) ) . '</span>'
				. '<span class="ink-reaksies__author">' . esc_html( $response['author'] ) . '</span>'
				. '</span>'
				. '<span class="ink-reaksies__date">' . esc_html( self::relativeDateLabel( $response['date'] ) ) . '</span>'
				. '</div>'
				. '<p class="ink-reaksies__text">' . esc_html( $response['content'] ) . '</p>'
				. '<div class="ink-reaksies__footer">'
				. '<span class="ink-reaksies__upvote">' . esc_html( (string) 0 ) . '</span>'
				. '<button type="button" class="ink-reaksies__reply" data-ink-reply-target="ink-reaksie-teks-' . esc_attr( (string) $post_id ) . '">'
				. esc_html( Terms::label( 'antwoord' ) )
				. '</button>'
				. '</div>'
				. '</div>';

			$html .= '</li>';
		}
		$html .= '</ul>';

		$html .= self::formHtml( $post_id );
		$html .= '</section>';

		return $html;
	}

	/**
	 * The decorative per-type icon shown inside a response badge / compose chip.
	 * Purely presentational (aria-hidden) — the label text carries the meaning.
	 *
	 * @param ResponseType $type The response type.
	 * @return string
	 */
	private static function typeIcon( ResponseType $type ): string {
		return match ( $type ) {
			// Sparkles (Lof / Praise).
			ResponseType::Lof => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M9.94 15.5A2 2 0 0 0 8.5 14.06l-6.14-1.58a.5.5 0 0 1 0-.96L8.5 9.94A2 2 0 0 0 9.94 8.5l1.58-6.14a.5.5 0 0 1 .96 0L14.06 8.5A2 2 0 0 0 15.5 9.94l6.14 1.58a.5.5 0 0 1 0 .96L15.5 14.06a2 2 0 0 0-1.44 1.44l-1.58 6.14a.5.5 0 0 1-.96 0z"/></svg>',
			// Lightbulb (Insig / Insight).
			ResponseType::Insig => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-.9 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1.3.5 2.6 1.5 3.5.8.8 1.3 1.5 1.5 2.5"/><path d="M9 18h6"/><path d="M10 22h4"/></svg>',
			// Thumbs up (Voorstel / Suggestion).
			ResponseType::Voorstel => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M7 10v12"/><path d="M15 5.88 14 10h5.83a2 2 0 0 1 1.92 2.56l-2.33 8A2 2 0 0 1 17.5 22H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2h2.76a2 2 0 0 0 1.79-1.11L12 2a3.13 3.13 0 0 1 3 3.88Z"/></svg>',
		};
	}

	/**
	 * The badge's icon span (`.ink-reaksies__badge-ikoon`).
	 *
	 * @param ResponseType $type The response type.
	 * @return string
	 */
	private static function badgeIcon( ResponseType $type ): string {
		return '<span class="ink-reaksies__badge-ikoon" aria-hidden="true">' . self::typeIcon( $type ) . '</span>';
	}

	/**
	 * A relative "[N] .. gelede" timestamp label, minute/hour/day granularity
	 * (the same authored `[N] X gelede` pattern already ratified for the "[N]
	 * days ago" -> "[N] dae gelede" card timestamp, docs/ui-copy-translations.md,
	 * "Gedeelde inhoud-badges en etikette"; extended here to hour/minute
	 * granularity so a same-day response reads as "2 ure gelede" rather than the
	 * misleadingly-coarse "0 dae gelede"). Pure — no `date_i18n()`/`get_option()`
	 * dependency, matching this class's "Terms + escaping only" contract.
	 *
	 * @param string $mysqlDate The stored `comment_date` (`Y-m-d H:i:s`).
	 * @return string The relative label, or `''` if unparseable.
	 */
	private static function relativeDateLabel( string $mysqlDate ): string {
		$timestamp = strtotime( $mysqlDate );

		if ( false === $timestamp ) {
			return '';
		}

		$seconds = max( 0, time() - $timestamp );

		if ( $seconds < MINUTE_IN_SECONDS ) {
			return Terms::label( 'nou_net' );
		}

		if ( $seconds < HOUR_IN_SECONDS ) {
			$minutes = (int) floor( $seconds / MINUTE_IN_SECONDS );
			/* translators: %s: the number of minutes since the response was posted. */
			return sprintf( _n( '%s minuut gelede', '%s minute gelede', $minutes, 'ink-core' ), number_format_i18n( $minutes ) );
		}

		if ( $seconds < DAY_IN_SECONDS ) {
			$hours = (int) floor( $seconds / HOUR_IN_SECONDS );
			/* translators: %s: the number of hours since the response was posted. */
			return sprintf( _n( '%s uur gelede', '%s ure gelede', $hours, 'ink-core' ), number_format_i18n( $hours ) );
		}

		$days = (int) floor( $seconds / DAY_IN_SECONDS );
		/* translators: %s: the number of days since the response was posted. */
		return sprintf( _n( '%s dag gelede', '%s dae gelede', $days, 'ink-core' ), number_format_i18n( $days ) );
	}

	/**
	 * The typed response form — three type radios (the enum) + a textarea + submit.
	 * Posts through the REST endpoint (handled by the enqueued client).
	 *
	 * The submit button is server-rendered `disabled` (the textarea starts empty)
	 * so there's no flash of an enabled button before the client attaches; the
	 * client keeps it in sync as the visitor types (a real bug fix — the button
	 * used to stay clickable regardless of content, matching Lovable's own
	 * `disabled={!critiqueText.trim()}` behaviour).
	 *
	 * @param int $post_id The work.
	 * @return string
	 */
	private static function formHtml( int $post_id ): string {
		$html = '<form class="ink-reaksies__form" data-ink-post="' . esc_attr( (string) $post_id ) . '">';

		$html .= '<p class="ink-reaksies__intro">' . esc_html( Terms::label( 'gemeenskapsreaksie_instruksie' ) ) . '</p>';

		$html .= '<fieldset class="ink-reaksies__types">';
		foreach ( ResponseType::cases() as $type ) {
			$html .= '<label class="ink-reaksies__type ink-reaksies__type--' . esc_attr( $type->value ) . '">'
				. '<input type="radio" name="ink_reaksie_type" value="' . esc_attr( $type->value ) . '" class="ink-reaksies__type-input">'
				. '<span class="ink-reaksies__type-ikoon" aria-hidden="true">' . self::typeIcon( $type ) . '</span> '
				. esc_html( Terms::label( $type->value ) )
				. '</label>';
		}
		$html .= '</fieldset>';

		$html .= '<textarea class="ink-reaksies__input" name="ink_reaksie_content" rows="3" id="ink-reaksie-teks-'
			. esc_attr( (string) $post_id ) . '" placeholder="' . esc_attr( Terms::label( 'gemeenskapsreaksie_plekhouer' ) ) . '" aria-label="'
			. esc_attr( Terms::label( 'gemeenskapsreaksie' ) ) . '"></textarea>';

		$html .= '<button type="submit" class="ink-reaksies__submit" disabled>' . esc_html( Terms::label( 'plaas' ) ) . '</button>';

		$html .= '</form>';

		return $html;
	}
}
