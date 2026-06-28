<?php
/**
 * InkPols by-year issue archive server block — Story 13.2 (FR-57).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/inkpols-argief` block: the InkPols issue archive, grouped
 * by year (newest year first), Story 13.2 (FR-57).
 *
 * Lists published `inkpols_uitgawe` issues, builds each into the 13.1
 * {@see Issue} read-model, then groups + sorts them by year IN PHP (never via a
 * fragile `meta_value` orderby, which would INNER JOIN and silently drop issues
 * missing the issue-date meta). Reads stay SERVER-RENDERED via `WP_Query` (AD-7),
 * mirroring the {@see \Ink\Library\Archive} house style: pure {@see queryArgs()}
 * + pure {@see groupByYear()}/{@see toHtml()} + a thin {@see render()}.
 *
 * Conflation-clean: references only `Ink\InkPols` (the read-model) + `Ink\Content`
 * (the CPT slug) + the `Terms` registry + WP core — zero `Ink\Tiers`/
 * `Ink\Entitlement`. Browsing published issues is open (never gated).
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
	 * Defensive upper bound on issues rendered — a periodical is bounded, but an
	 * unbounded `-1` query is the smell the Epic-12 review flagged (12.1 R12).
	 *
	 * @var int
	 */
	public const MAX_ISSUES = 500;

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
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
	 * Build the `WP_Query` args for the published issues. Pure.
	 *
	 * Newest-first by POST date (not a meta-join): every published issue is
	 * included, and the by-year grouping/sort runs in {@see groupByYear()}.
	 *
	 * @param int $max The defensive cap on rows.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $max ): array {
		return array(
			'post_type'           => PostTypes::INKPOLS_UITGAWE,
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, $max ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);
	}

	/**
	 * Group issues by publication year, newest year first. Pure.
	 *
	 * Issues within a year are sorted by issue date DESC (a `Y-m-d` string compare
	 * is chronological). An issue with no parseable year (`Issue::year() === ''`)
	 * is collected into a trailing undated bucket — never dropped.
	 *
	 * @param list<Issue> $issues The issue read-models.
	 * @return list<array{year:string, issues:list<Issue>}>
	 */
	public static function groupByYear( array $issues ): array {
		$buckets = array();

		foreach ( $issues as $issue ) {
			$year               = $issue->year();
			$buckets[ $year ][] = $issue;
		}

		// PHP coerces a numeric-string array key ('2026') to an int key, so
		// re-stringify to keep the declared `year:string` contract; the bucket
		// lookup below coerces back to the int key transparently.
		$years = array_map( 'strval', array_keys( $buckets ) );

		// Years descending; the undated bucket ('') sorts last regardless.
		usort(
			$years,
			static function ( string $a, string $b ): int {
				if ( '' === $a ) {
					return 1;
				}
				if ( '' === $b ) {
					return -1;
				}
				return strcmp( $b, $a );
			}
		);

		$groups = array();

		foreach ( $years as $year ) {
			$within = $buckets[ $year ];
			usort(
				$within,
				static function ( Issue $a, Issue $b ): int {
					// Date DESC, with post id DESC as a stable tiebreak — migrated issues
					// all land on a `Y-m-01` date, so same-month issues would otherwise
					// sort in an undefined order (R13 review).
					$by_date = strcmp( $b->issueDate, $a->issueDate );

					return 0 !== $by_date ? $by_date : ( $b->postId <=> $a->postId );
				}
			);

			$groups[] = array(
				'year'   => $year,
				'issues' => $within,
			);
		}

		return $groups;
	}

	/**
	 * Block render callback. Queries, builds read-models, groups, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$query = new \WP_Query( self::queryArgs( self::MAX_ISSUES ) );

		$issues = array();

		foreach ( $query->posts as $post ) {
			if ( $post instanceof \WP_Post ) {
				$issues[] = Issue::forPost( $post );
			}
		}

		return self::toHtml( self::groupByYear( $issues ) );
	}

	/**
	 * Build the archive HTML — a section per year. Pure (Terms + escaping only).
	 *
	 * @param list<array{year:string, issues:list<Issue>}> $groups The year groups.
	 * @return string
	 */
	public static function toHtml( array $groups ): string {
		$heading = '<h1 class="ink-inkpols__heading">' . esc_html( Terms::label( 'inkpols' ) ) . '</h1>';

		if ( array() === $groups ) {
			/* translators: %s: the Uitgawes (issues) plural label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'inkpols_uitgawe_plural' ) );

			return '<section class="ink-inkpols">' . $heading
				. '<p class="ink-inkpols__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-inkpols">' . $heading;

		foreach ( $groups as $group ) {
			$html .= '<section class="ink-inkpols__jaar">';

			if ( '' !== $group['year'] ) {
				$html .= '<h2 class="ink-inkpols__jaar-titel">' . esc_html( $group['year'] ) . '</h2>';
			}

			$html .= '<ul class="ink-inkpols__lys">';

			foreach ( $group['issues'] as $issue ) {
				$html .= self::card( $issue );
			}

			$html .= '</ul></section>';
		}

		return $html . '</section>';
	}

	/**
	 * One issue card — cover (when present) + title→permalink + date + volume +
	 * teaser. Pure — escaping only.
	 *
	 * @param Issue $issue The issue read-model.
	 * @return string
	 */
	public static function card( Issue $issue ): string {
		$permalink = (string) get_permalink( $issue->postId );
		$cover_url = $issue->coverUrl();

		$cover = '' !== $cover_url
			? '<a class="ink-inkpols__omslag-skakel" href="' . esc_url( $permalink ) . '">'
				. '<img class="ink-inkpols__omslag" src="' . esc_url( $cover_url ) . '" alt="' . esc_attr( $issue->title ) . '" /></a>'
			: '';

		$date = '' !== $issue->displayDate()
			? '<span class="ink-inkpols__datum">' . esc_html( $issue->displayDate() ) . '</span>'
			: '';

		$volume = '' !== $issue->volume
			? '<span class="ink-inkpols__volume">' . esc_html( $issue->volume ) . '</span>'
			: '';

		$teaser = '' !== $issue->teaser
			? '<p class="ink-inkpols__voorskou">' . esc_html( $issue->teaser ) . '</p>'
			: '';

		return '<li class="ink-inkpols__item is-style-card">'
			. $cover
			. '<a class="ink-inkpols__titel" href="' . esc_url( $permalink ) . '">' . esc_html( $issue->title ) . '</a>'
			. $date
			. $volume
			. $teaser
			. '</li>';
	}
}
