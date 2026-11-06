<?php
/**
 * A writer's recorded wins on the public profile's Prestasies rail.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the writer's recorded challenge wins (imported and live alike, from {@see WinRecords}) to the
 * profile's accomplishments. Social owns the rail and may not depend on Challenges, so this hooks
 * its filter (`Ink\Social\SkrywerProfiel::ACCOMPLISHMENTS_FILTER`, repeated here as a literal).
 *
 * Each win reads "Nde wen" (the approved winner-card label, {@see FeaturedWinners::winLabel()}) with
 * the round's title; no copy is invented.
 *
 * @package Ink\Core
 */
class ProfileAccomplishments {

	/**
	 * The filter Social applies.
	 *
	 * @var string
	 */
	public const FILTER = 'ink_skrywerprofiel_accomplishments';

	/**
	 * Bind the filter. Invoked from the Module.
	 */
	public function register(): void {
		add_filter( self::FILTER, array( $this, 'add' ), 10, 2 );
	}

	/**
	 * Add the writer's latest wins.
	 *
	 * @param mixed $items     The accomplishments so far.
	 * @param mixed $author_id The skrywer.
	 * @return mixed
	 */
	public function add( $items, $author_id ) {
		if ( ! is_array( $items ) ) {
			return $items;
		}

		foreach ( WinRecords::forUser( (int) $author_id, 3 ) as $win ) {
			$round_id = (int) ( $win['round_id'] ?? 0 );
			$item     = self::item( $win, WinRecords::countThroughRound( (int) $author_id, $round_id ), 'publish' === get_post_status( $round_id ) ? (string) get_the_title( $round_id ) : '' );

			if ( null !== $item ) {
				$items[] = $item;
			}
		}

		return $items;
	}

	/**
	 * One accomplishment from a stored win. Pure.
	 *
	 * @param array<string, mixed> $win         The {@see WinRecords::forUser()} row.
	 * @param int                  $count       The writer's win count as of that round.
	 * @param string               $round_title The round's title, or ''.
	 * @return array{at:int, label:string, detail:string}|null Null when there is no label to show.
	 */
	public static function item( array $win, int $count, string $round_title ): ?array {
		$label = FeaturedWinners::winLabel( $count );

		if ( '' === $label ) {
			return null;
		}

		$timestamp = strtotime( (string) ( $win['round_date'] ?? '' ) . ' UTC' );

		return array(
			'at'     => false !== $timestamp ? $timestamp : 0,
			'label'  => $label,
			'detail' => $round_title,
		);
	}
}
