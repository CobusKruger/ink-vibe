<?php
/**
 * Winners-announcement post generation — Story 12A.4 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\FieldSets;
use Ink\Kernel\Scalar;
use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Generates the wenneraankondiging (winners-announcement) post on a results commit, and
 * feeds the home featured slot (Story 12A.4, R2).
 *
 * Fills the {@see Ingestion::commitWinnersPost()} reserved seam: on commit it composes a
 * standard `post` (an editorial announcement, not a bydrae CPT) whose body FRAME comes
 * from a Story-1.12 form-letter {@see Template} (`ink_wenneraankondiging`, read via
 * {@see Notifications::templateBody()}) with the ordered winning-entry links appended,
 * links it to its round via {@see self::UITDAGING_META}, and publishes it. Idempotent: an
 * existing announcement for the round is returned, never duplicated.
 *
 * It also hooks {@see FeaturedWinners::FEATURED_FILTER} (15.6) to supply the home slot's
 * `{title, url, winners}` payload from the latest announcement + the round's placements —
 * the slot collapsed to empty until now. Ordering (algehele wenner first) is
 * {@see FeaturedWinners::order} (12A.7's rule).
 *
 * Conflation-clean: reads placements + post data only — zero `Ink\Entitlement`/`Ink\Tiers`.
 *
 * @package Ink\Core
 */
class WinnersPost {

	/**
	 * The Story-1.12 form-letter template key for the announcement body frame.
	 *
	 * @var string
	 */
	public const TEMPLATE_KEY = 'ink_wenneraankondiging';

	/**
	 * Post meta linking an announcement to its producing uitdaging (the FEATURED_FILTER
	 * lookup + the idempotency key).
	 *
	 * @var string
	 */
	public const UITDAGING_META = 'ink_wenneraankondiging_uitdaging';

	/**
	 * Register the form-letter template + the home featured-slot filter.
	 */
	public function register(): void {
		$this->registerTemplate();
		add_filter( FeaturedWinners::FEATURED_FILTER, array( $this, 'featured' ) );
	}

	/**
	 * Register the Afrikaans-source announcement-body template (Story 1.12).
	 */
	public function registerTemplate(): void {
		Notifications::registerTemplate(
			new Template(
				self::TEMPLATE_KEY,
				__( 'Wenners aangekondig', 'ink-core' ),
				__( 'Baie geluk aan al ons wenners! Hier is die uitslae van die uitdaging.', 'ink-core' ),
				false
			)
		);
	}

	/**
	 * The announcement title for a round. Pure.
	 *
	 * Carries the round's cadence period (Story 12B.1) when supplied: a monthly round
	 * reads "Wenners: Desember 2026 — {tema}" and an annual round "Wenners: 2026 —
	 * {tema}", so the published announcement is the production surface where the
	 * cadence (monthly vs annual) becomes observable. An empty period keeps the prior
	 * "Wenners: {tema}" form (a round with no deadline set yet).
	 *
	 * @param string $uitdaging_title The producing uitdaging's title.
	 * @param string $period          The cadence period label (e.g. "Desember 2026" / "2026"); '' to omit.
	 * @return string
	 */
	public static function composeTitle( string $uitdaging_title, string $period = '' ): string {
		$title  = trim( $uitdaging_title );
		$period = trim( $period );

		if ( '' === $title ) {
			return __( 'Wenneraankondiging', 'ink-core' );
		}

		if ( '' === $period ) {
			/* translators: %s: the uitdaging title. */
			return sprintf( __( 'Wenners: %s', 'ink-core' ), $title );
		}

		/* translators: 1: the cadence period (e.g. "Desember 2026" / "2026"); 2: the uitdaging title. */
		return sprintf( __( 'Wenners: %1$s — %2$s', 'ink-core' ), $period, $title );
	}

	/**
	 * Compose the announcement body: the form-letter frame, then the ordered winner
	 * links. Pure (escaping only).
	 *
	 * @param string                                              $frame   The form-letter body frame.
	 * @param list<array{label:string, title:string, url:string}> $entries The ordered winners.
	 * @return string HTML body.
	 */
	public static function composeBody( string $frame, array $entries ): string {
		$html  = '';
		$frame = trim( $frame );

		if ( '' !== $frame ) {
			$html .= '<p>' . esc_html( $frame ) . '</p>';
		}

		if ( array() === $entries ) {
			return $html;
		}

		$html .= '<ul class="ink-wenneraankondiging__lys">';

		foreach ( $entries as $entry ) {
			$label = trim( (string) ( $entry['label'] ?? '' ) );
			$title = trim( (string) ( $entry['title'] ?? '' ) );
			$url   = (string) ( $entry['url'] ?? '' );

			$prefix = '' !== $label ? esc_html( $label . ': ' ) : '';
			$name   = '' !== $url
				? '<a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a>'
				: esc_html( $title );

			$html .= '<li>' . $prefix . $name . '</li>';
		}

		return $html . '</ul>';
	}

	/**
	 * Build the FEATURED_FILTER payload. Pure.
	 *
	 * @param string                                                  $title   The announcement title.
	 * @param string                                                  $url     The announcement permalink.
	 * @param list<array{id:int, rank:int, title:string, url:string}> $winners The winner rows.
	 * @return array{title:string, url:string, winners:list<array{id:int, rank:int, title:string, url:string}>}
	 */
	public static function featuredPayload( string $title, string $url, array $winners ): array {
		return array(
			'title'   => $title,
			'url'     => $url,
			'winners' => $winners,
		);
	}

	/**
	 * Generate (or return the existing) wenneraankondiging post for a round. Idempotent.
	 *
	 * @param int                                               $uitdaging_id The round.
	 * @param list<array{post_id:int, rank:int, author_id:int}> $winners      The committed winners.
	 * @return int The announcement post id (0 on failure).
	 */
	public function generate( int $uitdaging_id, array $winners ): int {
		if ( $uitdaging_id <= 0 ) {
			return 0;
		}

		// Idempotent (defence in depth over Ingestion's commit guard).
		$existing = $this->existingPostFor( $uitdaging_id );

		if ( $existing > 0 ) {
			return $existing;
		}

		$entries = array();

		foreach ( $winners as $winner ) {
			$post_id = (int) ( $winner['post_id'] ?? 0 );
			$rank    = (int) ( $winner['rank'] ?? 0 );

			if ( $post_id <= 0 || ! Placements::isValidRank( $rank ) ) {
				continue;
			}

			$view      = $this->entryView( $post_id );
			$entries[] = array(
				'label' => Placements::placementLabel( $rank ),
				'title' => $view['title'],
				'url'   => $view['url'],
			);
		}

		$title   = self::composeTitle( $this->uitdagingTitle( $uitdaging_id ), $this->roundPeriod( $uitdaging_id ) );
		$body    = self::composeBody( $this->bodyFrame(), $entries );
		$post_id = $this->insertPost( $title, $body );

		if ( $post_id > 0 ) {
			update_post_meta( $post_id, self::UITDAGING_META, $uitdaging_id );
		}

		return $post_id;
	}

	/**
	 * Supply the home featured-slot payload (the FEATURED_FILTER callback).
	 *
	 * Yields the latest announcement + its round's placed entries; leaves a payload set
	 * by a higher-priority filter untouched. Returns the incoming value (collapsing the
	 * slot) when there is no announcement yet.
	 *
	 * @param mixed $value The incoming filter value.
	 * @return mixed
	 */
	public function featured( $value ) {
		if ( is_array( $value ) && '' !== (string) ( $value['title'] ?? '' ) ) {
			return $value;
		}

		$post_id = $this->latestAnnouncement();

		if ( $post_id <= 0 ) {
			return $value;
		}

		$uitdaging_id = (int) get_post_meta( $post_id, self::UITDAGING_META, true );
		$winners      = array();

		foreach ( $this->placedEntries( $uitdaging_id ) as $entry ) {
			$view      = $this->entryView( $entry['id'] );
			$winners[] = array(
				'id'    => $entry['id'],
				'rank'  => $entry['rank'],
				'title' => $view['title'],
				'url'   => $view['url'],
			);
		}

		return self::featuredPayload(
			(string) get_the_title( $post_id ),
			(string) get_permalink( $post_id ),
			$winners
		);
	}

	// --- Overridable seams (testability) -----------------------------------------------

	/**
	 * The form-letter body frame (Story 1.12), read via the Notifications facade.
	 * Overridable seam (test isolation over the Notifications static facade).
	 *
	 * @return string
	 */
	protected function bodyFrame(): string {
		return Notifications::templateBody( self::TEMPLATE_KEY );
	}

	/**
	 * Insert the announcement post. Overridable seam.
	 *
	 * @param string $title The post title.
	 * @param string $body  The post body (HTML).
	 * @return int The new post id (0 on failure).
	 */
	protected function insertPost( string $title, string $body ): int {
		$id = wp_insert_post(
			array(
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_content' => $body,
			),
			true
		);

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * The existing announcement post id for a round (0 if none). Overridable seam.
	 *
	 * @param int $uitdaging_id The round.
	 * @return int
	 */
	protected function existingPostFor( int $uitdaging_id ): int {
		$ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'meta_key'         => self::UITDAGING_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- low-volume editorial lookup (one announcement per round).
				'meta_value'       => (string) $uitdaging_id, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'suppress_filters' => false,
			)
		);

		return is_array( $ids ) && isset( $ids[0] ) ? (int) $ids[0] : 0;
	}

	/**
	 * The most-recent announcement post id (0 if none). Overridable seam.
	 *
	 * @return int
	 */
	protected function latestAnnouncement(): int {
		$ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => 1,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'meta_key'         => self::UITDAGING_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- low-volume home-slot lookup.
				'suppress_filters' => false,
			)
		);

		return is_array( $ids ) && isset( $ids[0] ) ? (int) $ids[0] : 0;
	}

	/**
	 * The placed entries (rank 1–3) for a round, flattened across every pool. Overridable
	 * seam.
	 *
	 * {@see Placements::forRound()} buckets per (Gradering × category) since the D1
	 * read-collapse fix, so flattening here surfaces EVERY category's podium (a Goud-Gedig
	 * algehele wenner AND a Goud-Storie algehele wenner both appear) — the home featured
	 * feed ({@see FeaturedWinners::orderFeed()}) then lists them all.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{id:int, rank:int}>
	 */
	protected function placedEntries( int $uitdaging_id ): array {
		$out = array();

		foreach ( Placements::forRound( $uitdaging_id ) as $rows ) {
			foreach ( $rows as $row ) {
				$out[] = array(
					'id'   => (int) $row['id'],
					'rank' => (int) $row['rank'],
				);
			}
		}

		return $out;
	}

	/**
	 * A post's title + permalink. Overridable seam.
	 *
	 * @param int $post_id The post.
	 * @return array{title:string, url:string}
	 */
	protected function entryView( int $post_id ): array {
		return array(
			'title' => (string) get_the_title( $post_id ),
			'url'   => (string) get_permalink( $post_id ),
		);
	}

	/**
	 * A round's title. Overridable seam.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	protected function uitdagingTitle( int $uitdaging_id ): string {
		return (string) get_the_title( $uitdaging_id );
	}

	/**
	 * A round's cadence period label (Story 12B.1) — resolves the round's deadline +
	 * its cadence (monthly/annual) into the period the announcement title carries.
	 * '' when no deadline is set yet (the title then omits the period). Overridable
	 * seam (isolates the deadline/cadence meta reads).
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	protected function roundPeriod( int $uitdaging_id ): string {
		$deadline = Deadline::parse(
			Scalar::asString( get_post_meta( $uitdaging_id, FieldSets::UITDAGING_DEADLINE, true ) )
		);

		return null === $deadline ? '' : Cadence::periodLabelFor( $uitdaging_id, $deadline );
	}
}
