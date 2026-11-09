<?php
/**
 * Winners-announcement post generation — Story 12A.4 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
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
 * It also hooks {@see FeaturedWinners::FEATURED_FILTER} (15.6) to supply the home section's
 * four winner slots from the latest announcement's round ({@see self::slotsFrom()}, D10).
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
				false,
				group: __( 'Uitdagings', 'ink-core' ),
				label: __( 'Wenneraankondiging (blogplasing, nie e-pos nie)', 'ink-core' )
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
	 * @param string                              $month The round's month name, or ''.
	 * @param string                              $theme The round's theme.
	 * @param array<string, array<string, mixed>> $slots The slots: key ⇒ card view.
	 * @return array{month:string, theme:string, slots:array<string, array<string, mixed>>}
	 */
	public static function featuredPayload( string $month, string $theme, array $slots ): array {
		return array(
			'month' => $month,
			'theme' => $theme,
			'slots' => $slots,
		);
	}

	/**
	 * The five home winner slots from a round's rows, in display order. Pure.
	 *
	 * DIVERGENCE (accepted, D10, handoff permitted #1): five cards, not Lovable's two: Goud
	 * Gedig + Storie, Silwer Gedig + Artikel, then Brons Gedig (owner, 2026-11-06). Each slot takes position 1, ties by lowest
	 * post id as the home feed always has. Meester counts as Goud (D11). A slot with no winner
	 * is left out. Storie and Artikel ignore the tier, so the Gradering fix changes nothing here.
	 *
	 * @param list<array<string, mixed>> $rows The rows, from {@see RoundResults::forRound()}.
	 * @return array<string, array<string, mixed>> Slot key ⇒ winning row.
	 */
	public static function slotsFrom( array $rows ): array {
		$wanted = array(
			'goud-gedig'   => array( PostTypes::GEDIG, 'goud' ),
			'storie'       => array( PostTypes::STORIE, '' ),
			'silwer-gedig' => array( PostTypes::GEDIG, 'silwer' ),
			'artikel'      => array( PostTypes::ARTIKEL, '' ),
			'brons-gedig'  => array( PostTypes::GEDIG, 'brons' ),
		);
		$slots  = array();

		foreach ( $wanted as $key => $rule ) {
			$matches = array_filter(
				$rows,
				static function ( array $row ) use ( $rule ): bool {
					$tier     = 'meester' === ( $row['tier'] ?? '' ) ? 'goud' : (string) ( $row['tier'] ?? '' );
					$category = (string) ( $row['category'] ?? '' );

					return $category === $rule[0]
						&& Placements::RANK_FIRST === (int) ( $row['position'] ?? 0 )
						&& ( '' === $rule[1] || $tier === $rule[1] );
				}
			);

			if ( array() === $matches ) {
				continue;
			}

			usort( $matches, static fn ( array $a, array $b ): int => (int) ( $a['post_id'] ?? 0 ) <=> (int) ( $b['post_id'] ?? 0 ) );

			$slots[ $key ] = $matches[0];
		}

		return $slots;
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
	 * Supply the home featured-slot payload (the FEATURED_FILTER callback): the four winner
	 * slots of the latest announcement's round. Leaves a payload set by a higher-priority
	 * filter untouched, and returns the incoming value with no announcement.
	 *
	 * @param mixed $value The incoming filter value.
	 * @return mixed
	 */
	public function featured( $value ) {
		if ( is_array( $value ) && array() !== ( $value['slots'] ?? array() ) ) {
			return $value;
		}

		$post_id = $this->latestAnnouncement();

		if ( $post_id <= 0 ) {
			return $value;
		}

		$uitdaging_id = (int) get_post_meta( $post_id, self::UITDAGING_META, true );
		$slots        = array();

		foreach ( self::slotsFrom( $this->roundRows( $uitdaging_id ) ) as $key => $row ) {
			$slots[ $key ] = $this->slotView( $row );
		}

		return self::featuredPayload( RoundResults::monthFor( $uitdaging_id ), RoundResults::themeFor( $uitdaging_id ), $slots );
	}

	/**
	 * What a card needs from a winning row. Overridable seam.
	 *
	 * @param array<string, mixed> $row The winning row.
	 * @return array<string, mixed>
	 */
	protected function slotView( array $row ): array {
		$post_id = (int) ( $row['post_id'] ?? 0 );
		$user_id = (int) ( $row['user_id'] ?? 0 );

		return array(
			'title'      => (string) ( $row['title'] ?? '' ),
			'url'        => $post_id > 0 ? (string) get_permalink( $post_id ) : '',
			'author'     => (string) ( $row['author'] ?? '' ),
			'quote'      => (string) ( $row['excerpt'] ?? '' ),
			'avatar_url' => (string) get_avatar_url( $user_id, array( 'size' => 96 ) ),
			'win_label'  => FeaturedWinners::winLabel(
				WinRecords::countThroughRound(
					$user_id,
					(int) ( $row['round_id'] ?? 0 ) > 0 ? (int) $row['round_id'] : WinRecords::roundOfWin( $post_id, $user_id )
				)
			),
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
	 * The announcement post id of the latest round (0 if none): the round that closed last, not the
	 * announcement written last, so publishing an older round's results later (August after
	 * September) never replaces the newer round on the home page. The public read behind the
	 * results banner and the home winners. Overridable seam.
	 *
	 * @return int
	 */
	public function latestAnnouncement(): int {
		$ids = get_posts(
			array(
				'post_type'        => 'post',
				'post_status'      => 'publish',
				'numberposts'      => 60,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'fields'           => 'ids',
				'meta_key'         => self::UITDAGING_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- low-volume home-slot lookup.
				'suppress_filters' => false,
			)
		);

		$best       = 0;
		$best_close = '';

		// Newest announcement first, so a round with no close date (or a tie) keeps the newest.
		foreach ( is_array( $ids ) ? $ids : array() as $id ) {
			$close = $this->closeOf( (int) get_post_meta( (int) $id, self::UITDAGING_META, true ) );

			if ( 0 === $best || strcmp( $close, $best_close ) > 0 ) {
				$best       = (int) $id;
				$best_close = $close;
			}
		}

		return $best;
	}

	/**
	 * When a round closes, `Y-m-d`, or '' with no close date. Overridable seam.
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	protected function closeOf( int $uitdaging_id ): string {
		$close = $uitdaging_id > 0 ? Cadence::closeFor( $uitdaging_id ) : null;

		return null === $close ? '' : $close->format( 'Y-m-d' );
	}

	/**
	 * A round's result rows. Overridable seam.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array<string, mixed>>
	 */
	protected function roundRows( int $uitdaging_id ): array {
		return RoundResults::forRound( $uitdaging_id );
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
		$deadline = Cadence::closeFor( $uitdaging_id );

		return null === $deadline ? '' : Cadence::periodLabelFor( $uitdaging_id, $deadline );
	}
}
