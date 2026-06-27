<?php
/**
 * Graderingsgeskiedenis audit-trail display (with challenge links) — Story 12.7.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\Capabilities;
use Ink\Tiers\Api as TiersApi;
use Ink\Tiers\PromotionLogEntry;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a writer's graderingsgeskiedenis (tier-history) with the optional
 * challenge link surfaced — the redakteur audit trail (FR-51, UJ-5).
 *
 * The WRITE side already exists: {@see \Ink\Tiers\AdminProfile} (Story 5.2) records a
 * promotion with an optional linked uitdaging, persisted as `challenge_id` by
 * {@see TiersApi::promote()} / {@see \Ink\Tiers\PromotionLog} (Story 5.3). This is the
 * READ side: a read-only section on the writer's user-edit screen showing each
 * promotion (from→to, reason, actor) and — when linked — the producing uitdaging as a
 * link, so "the audit trail connects results to advancement".
 *
 * The challenge resolution lives HERE (not in Tiers): `Ink\Tiers` may not depend on
 * `Ink\Content` (deptrac `Tiers: [Kernel, Notifications]`), but `Ink\Challenges` may
 * read both — it consumes the history via the {@see TiersApi::historyFor()} facade and
 * resolves the uitdaging via `Ink\Content`. Conflation-clean: zero `Ink\Entitlement`.
 *
 * Not `final`: {@see self::resolveChallenge()} is an overridable seam (the
 * {@see \Ink\Tiers\AdminProfile}/{@see ChallengeLinking} precedent) for unit testing.
 *
 * @package Ink\Core
 */
class PromotionHistory {

	/**
	 * Bind the read-only render to the user-edit screen. Invoked from the Module.
	 *
	 * `edit_user_profile` fires when editing ANOTHER user — a redakteur viewing a
	 * writer's audit trail (mirrors {@see \Ink\Tiers\AdminProfile}).
	 */
	public function register(): void {
		add_action( 'edit_user_profile', array( $this, 'renderField' ) );
	}

	/**
	 * Render the graderingsgeskiedenis section. Output only for a `MANAGE_TIERS`
	 * holder; every value is escaped at output.
	 *
	 * @param WP_User $user The user being edited.
	 */
	public function renderField( WP_User $user ): void {
		if ( ! current_user_can( Capabilities::MANAGE_TIERS ) ) {
			return;
		}

		$rows = array();

		foreach ( TiersApi::historyFor( (int) $user->ID ) as $entry ) {
			if ( $entry instanceof PromotionLogEntry ) {
				$challenge = $entry->isChallengeLinked() ? $this->resolveChallenge( $entry->challengeId ) : null;
				$rows[]    = self::rowView( $entry, $challenge );
			}
		}

		// renderField intentionally echoes pre-escaped markup from the pure builder.
		echo self::toHtml( $rows ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- toHtml escapes every interpolated value at source.
	}

	/**
	 * Resolve a linked uitdaging to its display data. Overridable seam for tests.
	 *
	 * @param int $challenge_id The linked uitdaging id.
	 * @return array{title:string, permalink:string}|null Null when not a valid uitdaging.
	 */
	protected function resolveChallenge( int $challenge_id ): ?array {
		if ( $challenge_id <= 0 || PostTypes::UITDAGING !== get_post_type( $challenge_id ) ) {
			return null;
		}

		return array(
			'title'     => (string) get_the_title( $challenge_id ),
			'permalink' => (string) get_permalink( $challenge_id ),
		);
	}

	/**
	 * Build a pure view row from an audit entry + its (optional) resolved challenge.
	 *
	 * @param PromotionLogEntry                          $entry     The audit record.
	 * @param array{title:string, permalink:string}|null $challenge The resolved link, or null.
	 * @return array{from:string, to:string, reason:string, is_system:bool, challenge:array{title:string, permalink:string}|null, created_at:string}
	 */
	public static function rowView( PromotionLogEntry $entry, ?array $challenge ): array {
		return array(
			'from'       => Terms::label( $entry->from->value ),
			'to'         => Terms::label( $entry->to->value ),
			'reason'     => $entry->reason,
			'is_system'  => $entry->isSystem(),
			'challenge'  => $challenge,
			'created_at' => $entry->createdAt,
		);
	}

	/**
	 * Render the history table. Pure — Terms + escaping only.
	 *
	 * @param list<array{from:string, to:string, reason:string, is_system:bool, challenge:array{title:string, permalink:string}|null, created_at:string}> $rows The view rows.
	 * @return string
	 */
	public static function toHtml( array $rows ): string {
		$heading = '<h2>' . esc_html( Terms::label( 'graderingsgeskiedenis' ) ) . '</h2>';

		if ( array() === $rows ) {
			return '<div class="ink-graderingsgeskiedenis">' . $heading
				. '<p class="ink-graderingsgeskiedenis__leeg">' . esc_html__( 'Geen graderingsveranderinge nie.', 'ink-core' ) . '</p></div>';
		}

		$html = '<div class="ink-graderingsgeskiedenis">' . $heading
			. '<table class="widefat striped"><thead><tr>'
			. '<th>' . esc_html__( 'Datum', 'ink-core' ) . '</th>'
			. '<th>' . esc_html__( 'Verandering', 'ink-core' ) . '</th>'
			. '<th>' . esc_html__( 'Rede', 'ink-core' ) . '</th>'
			. '<th>' . esc_html__( 'Bron', 'ink-core' ) . '</th>'
			. '<th>' . esc_html( Terms::label( 'uitdaging' ) ) . '</th>'
			. '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$source = ! empty( $row['is_system'] )
				? esc_html__( 'Stelsel', 'ink-core' )
				: esc_html__( 'Redakteur', 'ink-core' );

			$challenge = '—';

			if ( is_array( $row['challenge'] ?? null ) ) {
				$challenge = '<a href="' . esc_url( $row['challenge']['permalink'] ) . '">'
					. esc_html( $row['challenge']['title'] ) . '</a>';
			}

			$html .= '<tr>'
				. '<td>' . esc_html( (string) ( $row['created_at'] ?? '' ) ) . '</td>'
				. '<td>' . esc_html( (string) ( $row['from'] ?? '' ) ) . ' → ' . esc_html( (string) ( $row['to'] ?? '' ) ) . '</td>'
				. '<td>' . esc_html( (string) ( $row['reason'] ?? '' ) ) . '</td>'
				. '<td>' . $source . '</td>'
				. '<td>' . $challenge . '</td>'
				. '</tr>';
		}

		return $html . '</tbody></table></div>';
	}
}
