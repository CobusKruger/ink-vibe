<?php
/**
 * InkPols archive server block — N2 (`04-inkpols`), first built in Story 13.2 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/inkpols-argief` block: the hero with the current issue, then the past
 * issues with year chips (`Magazine.tsx`). The block emits its own bands, so the root is
 * `alignfull` and the pattern adds no padding.
 *
 * Audit ids: past issues 0..4 and the first two year chips carry the ids Lovable has (the
 * chips by position, `04-inkpols` §10). Later instances are real data and carry none, so
 * the ids stay unique.
 *
 * Conflation-clean: browsing published issues is open, never gated.
 *
 * @package Ink\Core
 */
final class Archive {

	/**
	 * The block name (single source for the renderer + the theme pattern embed).
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/inkpols-argief';

	/**
	 * Defensive upper bound on issues read: a periodical is bounded, but an unbounded `-1`
	 * query is the smell the Epic-12 review flagged (12.1 R12).
	 *
	 * @var int
	 */
	public const MAX_ISSUES = 500;

	/**
	 * How many past issues carry audit ids: Lovable has five.
	 *
	 * @var int
	 */
	private const STAMPED_PAST = 5;

	/**
	 * Audit ids of the year chips, by position (`04-inkpols` §5).
	 *
	 * @var list<string>
	 */
	private const YEAR_CHIP_IDS = array( 'magazine-past-filter-2025', 'magazine-past-filter-2024' );

	/**
	 * Register the server-rendered block. Dispatched on `init` through {@see Module::register()}.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/inkpols-argief` dynamic block.
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
	 * Block render callback.
	 *
	 * @return string
	 */
	public static function render(): string {
		return self::toHtml( Api::publishedIssues() );
	}

	/**
	 * The archive. Pure: Terms and escaping only.
	 *
	 * @param list<Issue> $issues Published issues, newest `datum` first.
	 * @return string
	 */
	public static function toHtml( array $issues ): string {
		$current = $issues[0] ?? null;
		$past    = array_slice( $issues, 1 );

		if ( null === $current ) {
			/* translators: %s: the Uitgawes (issues) plural label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'inkpols_uitgawe_plural' ) );
			$body  = '<p class="ink-inkpols__leeg ink-card--empty">' . esc_html( $empty ) . '</p>';
		} else {
			$body = CurrentIssue::toHtml( $current, CurrentIssue::CONTEXT_ARCHIVE );
		}

		return '<div class="ink-inkpols alignfull">'
			. '<section class="ink-band ink-band--border-b ink-band--step" data-audit-id="magazine-archive-hero">'
			. '<div class="ink-container" data-audit-id="magazine-archive-container">'
			. '<p class="ink-eyebrow ink-eyebrow--card" data-audit-id="magazine-archive-eyebrow">' . esc_html( Terms::label( 'inkpols' ) ) . '</p>'
			. '<h1 class="ink-inkpols__argief-titel ink-heading-h1-index" data-audit-id="magazine-archive-h1">' . esc_html( Terms::label( 'inkpols_argief' ) ) . '</h1>'
			. '<p class="ink-inkpols__argief-teks" data-audit-id="magazine-archive-text">' . esc_html( Terms::label( 'inkpols_argief_teks' ) ) . '</p>'
			. $body
			. '</div></section>'
			. self::pastHtml( $past )
			. '</div>';
	}

	/**
	 * The past-issues band; '' with none (hidden with one issue, `inkpols.md`). Pure.
	 *
	 * @param list<Issue> $past The past issues, newest first.
	 * @return string
	 */
	public static function pastHtml( array $past ): string {
		if ( array() === $past ) {
			return '';
		}

		$cards = '';

		foreach ( $past as $index => $issue ) {
			$cards .= self::card( $issue, $index < self::STAMPED_PAST ? "magazine-past-{$index}" : '' );
		}

		return '<section class="ink-band ink-band--step" data-audit-id="magazine-past" data-ink-inkpols-argief>'
			. '<div class="ink-container" data-audit-id="magazine-past-container">'
			. '<div class="ink-inkpols__verlede-kop" data-audit-id="magazine-past-head">'
			. '<h2 class="ink-heading-h2-section" data-audit-id="magazine-past-h2">' . esc_html( Terms::label( 'alle_uitgawes' ) ) . '</h2>'
			. '<div class="ink-inkpols__filters" data-audit-id="magazine-past-filters">' . self::chips( $past ) . '</div>'
			. '</div>'
			. '<div class="ink-inkpols__rooster" data-audit-id="magazine-past-grid">' . $cards . '</div>'
			. '</div></section>';
	}

	/**
	 * Alles, then each year among the past issues, newest first. Pure.
	 *
	 * @param list<Issue> $past The past issues.
	 * @return string
	 */
	public static function chips( array $past ): string {
		$years = array();

		foreach ( $past as $issue ) {
			if ( '' !== $issue->year() ) {
				$years[ $issue->year() ] = true;
			}
		}

		$years = array_map( 'strval', array_keys( $years ) );
		rsort( $years, SORT_STRING );

		$html = '<button type="button" class="ink-chip ink-chip--plain" aria-pressed="true" data-jaar="alles" data-audit-id="magazine-past-filter-all">' . esc_html( Terms::label( 'alles' ) ) . '</button>';

		foreach ( $years as $position => $year ) {
			$html .= '<button type="button" class="ink-chip ink-chip--plain" aria-pressed="false" data-jaar="' . esc_attr( $year ) . '"' . CurrentIssue::aid( self::YEAR_CHIP_IDS[ $position ] ?? '' ) . '>' . esc_html( $year ) . '</button>';
		}

		return $html;
	}

	/**
	 * One past issue. Without JS every card stays visible. Pure.
	 *
	 * @param Issue  $issue The issue.
	 * @param string $id    The card's audit id, the prefix of its children's; '' stamps none.
	 * @return string
	 */
	public static function card( Issue $issue, string $id = '' ): string {
		$url = $issue->permalink();
		$aid = static fn ( string $suffix ): string => CurrentIssue::aid( '' === $id ? '' : $id . $suffix );

		return '<article class="ink-inkpols__kaart" data-jaar="' . esc_attr( $issue->year() ) . '"' . $aid( '' ) . '>'
			. '<a class="ink-inkpols__omslag-skakel"' . $aid( '-cover-link' ) . ' href="' . esc_url( $url ) . '">'
			. CurrentIssue::coverImg( $issue, '' === $id ? '' : $id . '-cover', 'ink-cover', true )
			. '</a>'
			. '<span class="ink-eyebrow ink-eyebrow--card-xs"' . $aid( '-eyebrow' ) . '>' . esc_html( $issue->title ) . '</span>'
			. '<h3 class="ink-inkpols__kaart-titel ink-heading-h3"' . $aid( '-title' ) . '>'
			. '<a class="ink-inkpols__kaart-skakel"' . $aid( '-title-link' ) . ' href="' . esc_url( $url ) . '">' . esc_html( $issue->titel ) . '</a>'
			. '</h3>'
			. '<div class="ink-inkpols__lys-hou-kompak"' . $aid( '-highlights-wrap' ) . '>' . CurrentIssue::listHtml( $issue, $id, CurrentIssue::LIST_COMPACT ) . '</div>'
			. '<div class="ink-inkpols__kaart-aksies"' . $aid( '-actions' ) . '>'
			. CurrentIssue::readButton( $url, 'ink-btn ink-btn--sm ink-btn--primary', '' === $id ? '' : $id . '-btn-read' )
			. '</div></article>';
	}
}
