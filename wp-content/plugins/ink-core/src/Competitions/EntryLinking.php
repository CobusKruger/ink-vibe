<?php
/**
 * Competition entries at submission — K2 (`05-kompetisies` §14.3).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\PostTypes;
use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a writer enter an open competition from Skryf.
 *
 * Modelled on `Ink\Submission\ChallengeLinking`, but a competition is not a challenge (owner,
 * 2026-11-09): this class has its OWN taxonomy ({@see self::TAXONOMY}) and term meta, and uses
 * nothing from `CadenceType`, `ChallengeRound` or `WinRecords`. Every challenge count (Gradering,
 * challenge pages, profiles) reads `uitdagingsrondte` by name, so an entry filed here is never
 * one of them.
 *
 * Skryf (module Submission) does not depend on Competitions, so the two meet through two hooks:
 * the filter {@see self::OFFER_FILTER} supplies the choices for the form, and the action
 * {@see self::LINK_ACTION} hands over the ids the writer ticked once the bydrae is saved.
 *
 * Which competitions are offered, and whether one still accepts an entry, is decided by
 * {@see Data::stateOf()} and nothing else: state `open`. The form is never trusted; {@see self::link()}
 * re-checks the window on every submission. A closed competition is not offered, and an entry
 * already linked stays linked (nothing here unlinks).
 *
 * Not `final`: `editions`, `now`, `entryCountFor`, `resolveEntryTerm` and `assign` are overridable
 * seams, as in `ChallengeLinking`, so the orchestration is unit-testable without WordPress.
 *
 * @package Ink\Core
 */
class EntryLinking {

	/**
	 * The Skryf checkbox-array field name for ticked competition ids.
	 *
	 * Skryf reads the same literal (it may not depend on this module); a test pins that they match.
	 *
	 * @var string
	 */
	public const FIELD = 'ink_submission_kompetisies';

	/**
	 * The taxonomy holding one term per competition. Not `uitdagingsrondte`.
	 *
	 * @var string
	 */
	public const TAXONOMY = 'kompetisie_inskrywing';

	/**
	 * The term meta holding the `kompetisie` post id the term belongs to.
	 *
	 * @var string
	 */
	public const TERM_META = 'ink_kompetisie_post';

	/**
	 * The term-slug prefix; the `kompetisie` post id follows.
	 *
	 * @var string
	 */
	public const SLUG_PREFIX = 'kompetisie-';

	/**
	 * Filter: the open competitions Skryf offers, `list<array{id:int, title:string}>`.
	 *
	 * @var string
	 */
	public const OFFER_FILTER = 'ink/skryf_open_competitions';

	/**
	 * Action: `( int $post_id, list<int> $kompetisie_ids )`, fired by Skryf after a bydrae is saved.
	 *
	 * @var string
	 */
	public const LINK_ACTION = 'ink/skryf_link_competitions';

	/**
	 * The most entries of a given content type, per author, per competition. The spec (§14.3) says
	 * to follow the challenge rule (FR-48: 3 per type), so this mirrors
	 * `ChallengeLinking::MAX_ENTRIES_PER_TYPE` without referencing it. The owner may set a
	 * different number for competitions; it is one constant.
	 *
	 * @var int
	 */
	public const MAX_ENTRIES_PER_TYPE = 3;

	/**
	 * Register the taxonomy and the two hooks.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerTaxonomy' ), 20 );
		add_filter( self::OFFER_FILTER, array( $this, 'offer' ) );
		add_action( self::LINK_ACTION, array( $this, 'onLink' ), 10, 2 );
	}

	/**
	 * Register the entry taxonomy: a code-managed join key, so no UI, no URLs, no REST.
	 */
	public function registerTaxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			PostTypes::bydraeTypes(),
			array(
				'labels'            => array( 'name' => __( 'Kompetisie', 'ink-core' ) ),
				'public'            => false,
				'hierarchical'      => false,
				'show_ui'           => false,
				'show_in_rest'      => false,
				'show_admin_column' => false,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}

	/**
	 * The term slug for a competition. Pure.
	 *
	 * @param int $kompetisie_id The `kompetisie` post id.
	 * @return string
	 */
	public static function slugFor( int $kompetisie_id ): string {
		return self::SLUG_PREFIX . $kompetisie_id;
	}

	/**
	 * Whether another entry of a type is allowed given the author's existing count of that type
	 * in this competition. Pure.
	 *
	 * @param int $existing The author's existing entries of this type in this competition.
	 * @return bool
	 */
	public static function withinCap( int $existing ): bool {
		return $existing < self::MAX_ENTRIES_PER_TYPE;
	}

	/**
	 * The competitions to offer among these editions. Pure.
	 *
	 * Every published real edition whose derived state is `open`, partner competitions included;
	 * the default announcement is never offered (its state is `closed`, and it is skipped
	 * explicitly as well). Newest year first.
	 *
	 * @param list<Edition>      $editions Every published edition.
	 * @param \DateTimeImmutable $now      Now, in the site's timezone.
	 * @return list<array{id:int, title:string}>
	 */
	public static function offerable( array $editions, \DateTimeImmutable $now ): array {
		$open = array();

		foreach ( $editions as $edition ) {
			if ( $edition->postId > 0 && ! $edition->isDefault() && EditionState::Open === Data::stateOf( $edition, $editions, $now ) ) {
				$open[] = $edition;
			}
		}

		usort( $open, static fn ( Edition $a, Edition $b ): int => $b->year() <=> $a->year() );

		return array_map(
			static fn ( Edition $edition ): array => array(
				'id'    => $edition->postId,
				'title' => $edition->title,
			),
			$open
		);
	}

	/**
	 * The ids among the ticked ones that may be linked now, in the order ticked. Pure.
	 *
	 * Drops anything that is not a positive id, duplicates, and anything not offerable (closed,
	 * announced, archive, the default, not a competition at all).
	 *
	 * @param list<int>                         $ticked   The ids from the form.
	 * @param list<array{id:int, title:string}> $offerable {@see self::offerable()}.
	 * @return list<int>
	 */
	public static function acceptable( array $ticked, array $offerable ): array {
		$allowed = array_column( $offerable, 'id' );
		$out     = array();

		foreach ( $ticked as $raw ) {
			$id = (int) $raw;

			if ( $id > 0 && in_array( $id, $allowed, true ) && ! in_array( $id, $out, true ) ) {
				$out[] = $id;
			}
		}

		return $out;
	}

	/**
	 * Filter callback: the open competitions for the Skryf form.
	 *
	 * @param mixed $current The value so far; ignored (this module is the only source).
	 * @return list<array{id:int, title:string}>
	 */
	public function offer( mixed $current = array() ): array {
		unset( $current );

		return self::offerable( $this->editions(), $this->now() );
	}

	/**
	 * Action callback for {@see self::LINK_ACTION}.
	 *
	 * @param int          $post_id The saved bydrae.
	 * @param array<mixed> $ids     The ticked ids.
	 */
	public function onLink( int $post_id, array $ids ): void {
		$this->link( $post_id, array_map( 'intval', array_values( $ids ) ) );
	}

	/**
	 * Link a bydrae to each ticked competition that is open right now, creating the competition's
	 * term at the first entry. The window is re-read here, not taken from the form.
	 *
	 * @param int                     $post_id The bydrae id.
	 * @param list<int>               $ids     The ticked competition ids.
	 * @param \DateTimeImmutable|null $now     Pinned "now" (tests).
	 * @return list<int> The competition ids actually linked.
	 */
	public function link( int $post_id, array $ids, ?\DateTimeImmutable $now = null ): array {
		if ( $post_id <= 0 || array() === $ids ) {
			return array();
		}

		$accepted = self::acceptable( $ids, self::offerable( $this->editions(), $now ?? $this->now() ) );
		$linked   = array();

		foreach ( $accepted as $kompetisie_id ) {
			// A 4th entry of the type is not linked; the bydrae itself still saves.
			if ( ! self::withinCap( $this->entryCountFor( $post_id, $kompetisie_id ) ) ) {
				continue;
			}

			$term_id = $this->resolveEntryTerm( $kompetisie_id );

			if ( $term_id > 0 ) {
				$this->assign( $post_id, $term_id );
				$linked[] = $kompetisie_id;
			}
		}

		return $linked;
	}

	/**
	 * Every published edition. Overridable seam for tests.
	 *
	 * @return list<Edition>
	 */
	protected function editions(): array {
		return Data::editions();
	}

	/**
	 * Now in the site's timezone. Overridable seam for tests.
	 *
	 * @return \DateTimeImmutable
	 */
	protected function now(): \DateTimeImmutable {
		return Sast::now();
	}

	/**
	 * The author's EXISTING entries of the same content type already in this competition
	 * (excludes the post being linked now). Overridable seam for tests.
	 *
	 * @param int $post_id       The bydrae being linked.
	 * @param int $kompetisie_id The competition.
	 * @return int
	 */
	protected function entryCountFor( int $post_id, int $kompetisie_id ): int {
		$type   = (string) get_post_type( $post_id );
		$author = (int) get_post_field( 'post_author', $post_id );

		if ( '' === $type || $author <= 0 ) {
			return 0;
		}

		$existing = get_posts(
			array(
				'post_type'        => $type,
				'author'           => $author,
				'post_status'      => array( 'publish', 'pending', 'draft', 'future' ),
				'fields'           => 'ids',
				'numberposts'      => self::MAX_ENTRIES_PER_TYPE + 1,
				'post__not_in'     => array( $post_id ),
				'suppress_filters' => false,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- a single bounded competition-slug facet to enforce the per-type entry cap; not a listing query.
				'tax_query'        => array(
					array(
						'taxonomy' => self::TAXONOMY,
						'field'    => 'slug',
						'terms'    => self::slugFor( $kompetisie_id ),
					),
				),
			)
		);

		return is_array( $existing ) ? count( $existing ) : 0;
	}

	/**
	 * Get-or-create the entry term for a competition, keyed to the `kompetisie` post by its slug
	 * and by term meta. Called at the first submission, never on publish.
	 *
	 * @param int $kompetisie_id The competition.
	 * @return int The term id, or 0 on failure.
	 */
	protected function resolveEntryTerm( int $kompetisie_id ): int {
		$slug     = self::slugFor( $kompetisie_id );
		$existing = get_term_by( 'slug', $slug, self::TAXONOMY );

		if ( $existing instanceof \WP_Term ) {
			return (int) $existing->term_id;
		}

		$created = wp_insert_term( (string) get_the_title( $kompetisie_id ), self::TAXONOMY, array( 'slug' => $slug ) );

		if ( is_wp_error( $created ) ) {
			// Two first entries at once: the other request made it, so use that one.
			$raced = $created->get_error_data( 'term_exists' );

			return is_numeric( $raced ) ? (int) $raced : 0;
		}

		if ( ! isset( $created['term_id'] ) ) {
			return 0;
		}

		add_term_meta( (int) $created['term_id'], self::TERM_META, $kompetisie_id, true );

		return (int) $created['term_id'];
	}

	/**
	 * Append the entry term to the bydrae (never clobbers its other terms).
	 *
	 * @param int $post_id The bydrae id.
	 * @param int $term_id The entry term id.
	 */
	protected function assign( int $post_id, int $term_id ): void {
		wp_set_object_terms( $post_id, array( $term_id ), self::TAXONOMY, true );
	}
}
