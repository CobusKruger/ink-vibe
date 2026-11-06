<?php
/**
 * InkPols single-issue blocks — N3 (`04-inkpols`), first built in Story 13.2 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the two blocks either side of the flipbook ({@see Viewer}), from
 * `MagazineIssue.tsx`: `ink/inkpols-besonderhede` (the top bar) and `ink/inkpols-inhoud`
 * (contents and the older/newer links).
 *
 * DIVERGENCE (accepted, `inkpols.md` §2, permitted #6): no download button
 * (`magazine-issue-btn-pdf`), and the naam stands in for issue number and season.
 *
 * Conflation-clean: reading a published issue is open.
 *
 * @package Ink\Core
 */
final class SingleIssue {

	/**
	 * The top-bar block.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/inkpols-besonderhede';

	/**
	 * The contents block.
	 *
	 * @var string
	 */
	public const BLOCK_CONTENTS = 'ink/inkpols-inhoud';

	private const ICON_ARROW_LEFT  = '<path d="m12 19-7-7 7-7"/><path d="M19 12H5"/>';
	private const ICON_ARROW_RIGHT = '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>';

	/**
	 * Register the server-rendered blocks. Dispatched on `init` through {@see Module::register()}.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register both dynamic blocks.
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

		register_block_type(
			self::BLOCK_CONTENTS,
			array(
				'render_callback' => array( self::class, 'renderContents' ),
			)
		);
	}

	/**
	 * The top bar. Pure: Terms and escaping only.
	 *
	 * @param Issue $issue The issue.
	 * @return string
	 */
	public static function barHtml( Issue $issue ): string {
		return '<div class="ink-band ink-band--flush ink-band--border-b" data-audit-id="magazine-issue-bar">'
			. '<div class="ink-inkpols__balk ink-container" data-audit-id="magazine-issue-bar-inner">'
			. '<a class="ink-inkpols__terug ink-nav-link--footer" data-audit-id="magazine-issue-back" href="' . esc_url( CurrentIssue::archiveUrl() ) . '">'
			. CurrentIssue::icon( self::ICON_ARROW_LEFT, 'magazine-issue-back-icon' )
			. esc_html__( 'Terug na die argief', 'ink-core' )
			. '</a>'
			. '<div class="ink-inkpols__balk-titel" data-audit-id="magazine-issue-title-col">'
			. '<span class="ink-eyebrow ink-eyebrow--card-xs" data-audit-id="magazine-issue-eyebrow">' . esc_html( $issue->title ) . '</span>'
			. '<h1 class="ink-heading-h1-bar" data-audit-id="magazine-issue-h1">' . esc_html( $issue->titel ) . '</h1>'
			. '</div></div></div>';
	}

	/**
	 * Contents and the older/newer links. Pure: Terms and escaping only.
	 *
	 * @param Issue      $issue The issue.
	 * @param Issue|null $older The next-older published issue.
	 * @param Issue|null $newer The next-newer published issue.
	 * @return string
	 */
	public static function contentsHtml( Issue $issue, ?Issue $older, ?Issue $newer ): string {
		$items = '';

		foreach ( $issue->items() as $n => $row ) {
			$id     = "magazine-issue-item-{$n}";
			$items .= '<div class="ink-accent-block ink-accent-block--top" data-audit-id="' . esc_attr( $id ) . '">'
				. '<span class="ink-eyebrow ink-eyebrow--card-xs" data-audit-id="' . esc_attr( $id . '-kind' ) . '">' . esc_html( $row['tipe'] ) . '</span>'
				. '<p class="ink-inkpols__item-titel ink-heading-h4 ink-heading--regular" data-audit-id="' . esc_attr( $id . '-title' ) . '">' . esc_html( $row['title'] ) . '</p>'
				. '<p class="ink-inkpols__item-skrywer" data-audit-id="' . esc_attr( $id . '-author' ) . '">' . esc_html( $row['writer'] ) . '</p>'
				. '</div>';
		}

		$left = null === $older
			? '<span data-audit-id="magazine-issue-nav-spacer"></span>'
			: '<a class="ink-inkpols__nav-skakel ink-nav-link--footer" data-audit-id="magazine-issue-nav-older" href="' . esc_url( $older->permalink() ) . '">'
				. CurrentIssue::icon( self::ICON_ARROW_LEFT, 'magazine-issue-nav-older-icon' ) . esc_html( Terms::label( 'vorige' ) ) . '</a>';

		$right = null === $newer
			? ''
			: '<a class="ink-inkpols__nav-skakel ink-nav-link--footer" data-audit-id="magazine-issue-nav-newer" href="' . esc_url( $newer->permalink() ) . '">'
				. esc_html( Terms::label( 'volgende' ) ) . CurrentIssue::icon( self::ICON_ARROW_RIGHT, 'magazine-issue-nav-newer-icon', 'ink-inkpols__ikoon-na' ) . '</a>';

		return '<section class="ink-band ink-band--step" data-audit-id="magazine-issue-contents">'
			. '<div class="ink-inkpols__inhoud ink-container" data-audit-id="magazine-issue-contents-container">'
			. '<p class="ink-inkpols__inhoud-etiket ink-eyebrow ink-eyebrow--caps ink-eyebrow--muted" data-audit-id="magazine-issue-contents-label">' . esc_html( Terms::label( 'in_hierdie_uitgawe' ) ) . '</p>'
			. '<div class="ink-inkpols__inhoud-rooster" data-audit-id="magazine-issue-contents-grid">' . $items . '</div>'
			. '<nav class="ink-inkpols__nav" data-audit-id="magazine-issue-nav">' . $left . $right . '</nav>'
			. '</div></section>';
	}

	/**
	 * Find an issue's neighbours in the published list, newest first. Pure.
	 *
	 * @param list<Issue> $issues Published issues, newest `datum` first.
	 * @param int         $id     This issue's post id.
	 * @return array{older:Issue|null, newer:Issue|null}
	 */
	public static function neighbours( array $issues, int $id ): array {
		foreach ( $issues as $index => $issue ) {
			if ( $issue->postId === $id ) {
				return array(
					'older' => $issues[ $index + 1 ] ?? null,
					'newer' => $issues[ $index - 1 ] ?? null,
				);
			}
		}

		return array(
			'older' => null,
			'newer' => null,
		);
	}

	/**
	 * Top-bar render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		$issue = Api::issueFor( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );

		return null === $issue ? '' : self::barHtml( $issue );
	}

	/**
	 * Contents render callback.
	 *
	 * @return string
	 */
	public static function renderContents(): string {
		$issue = Api::issueFor( function_exists( 'get_the_ID' ) ? (int) get_the_ID() : 0 );

		if ( null === $issue ) {
			return '';
		}

		$near = self::neighbours( Api::publishedIssues(), $issue->postId );

		return self::contentsHtml( $issue, $near['older'], $near['newer'] );
	}
}
