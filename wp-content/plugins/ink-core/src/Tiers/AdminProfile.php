<?php
/**
 * Staff set/adjust Gradering — user-profile admin UI.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Tiers;

use Ink\I18n\Terms;
use Ink\Kernel\Capabilities;
use Ink\Kernel\Tier;
use WP_User;

defined( 'ABSPATH' ) || exit;

/**
 * The redakteur-facing "Gradering" section on a writer's user-edit screen
 * (Story 5.2, FR-12 / UJ-5).
 *
 * Lets a {@see Capabilities::MANAGE_TIERS} holder set any writer's Gradering in
 * any direction — including the manual-only {@see Tier::Meester} — with a reason
 * and an optional linked uitdaging, writing an audit entry through the sole
 * {@see Api::promote()} write path. Capability is granted to editor + admin at
 * activation (Story 3.3); a non-holder sees nothing. A `MANAGE_TIERS` holder may
 * set ANY writer's grade — the editorial model (redakteurs manage all writers),
 * so the capability, not per-author ownership, is the authorization boundary.
 *
 * Uses the sanctioned `$_POST` admin pattern (the {@see \Ink\Content\FieldSets}
 * precedent): nonce → capability → `wp_unslash` + `sanitize_*` → write. Never a
 * raw superglobal. THE conflation rule (AD-1): references only the Kernel
 * `Tier`/`Capabilities`, this module's `Api`, and the `Terms` registry — zero
 * `Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class AdminProfile {

	/**
	 * Nonce action + field name for the profile save round-trip.
	 */
	private const NONCE_ACTION = 'ink_set_gradering';
	private const NONCE_NAME   = 'ink_set_gradering_nonce';

	// Single-source POST field names.
	private const FIELD_TIER      = 'ink_gradering';
	private const FIELD_REASON    = 'ink_gradering_reason';
	private const FIELD_CHALLENGE = 'ink_gradering_uitdaging';

	/**
	 * How many recent uitdagings to offer in the optional challenge select.
	 */
	private const CHALLENGE_CHOICES = 50;

	/**
	 * Maximum stored length for the change reason. The audit `reason` column is
	 * `text`; the cap keeps a stray paste from truncating mid-write or failing
	 * the insert under MySQL strict mode.
	 */
	private const REASON_MAX_LENGTH = 500;

	/**
	 * Bind the render + save hooks. Invoked from {@see Module::register()}.
	 *
	 * `edit_user_profile` (+ `_update`) fire when editing ANOTHER user — staff
	 * setting a writer's grade; a writer never sets their own.
	 */
	public function register(): void {
		add_action( 'edit_user_profile', array( $this, 'renderField' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	/**
	 * Render the Gradering section. Output only for a `MANAGE_TIERS` holder; every
	 * value is escaped at output.
	 *
	 * @param WP_User $user The user being edited.
	 */
	public function renderField( WP_User $user ): void {
		if ( ! current_user_can( Capabilities::MANAGE_TIERS ) ) {
			return;
		}

		$current = Api::forUser( (int) $user->ID );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		printf( '<h2>%s</h2>', esc_html( Terms::label( 'gradering' ) ) );
		echo '<table class="form-table" role="presentation"><tbody>';

		// Grade select.
		echo '<tr>';
		printf(
			'<th><label for="%1$s">%2$s</label></th>',
			esc_attr( self::FIELD_TIER ),
			esc_html( Terms::label( 'gradering' ) )
		);
		echo '<td>';
		printf( '<select id="%1$s" name="%1$s">', esc_attr( self::FIELD_TIER ) );
		foreach ( Tier::cases() as $tier ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $tier->value ),
				selected( $tier, $current, false ),
				esc_html( Terms::label( $tier->value ) )
			);
		}
		echo '</select>';
		echo '</td></tr>';

		// Reason.
		echo '<tr>';
		printf(
			'<th><label for="%1$s">%2$s</label></th>',
			esc_attr( self::FIELD_REASON ),
			esc_html__( 'Rede vir verandering', 'ink-core' )
		);
		printf(
			'<td><input type="text" id="%1$s" name="%1$s" value="" maxlength="%2$d" class="regular-text" /></td>',
			esc_attr( self::FIELD_REASON ),
			(int) self::REASON_MAX_LENGTH
		);
		echo '</tr>';

		// Optional linked uitdaging.
		echo '<tr>';
		printf(
			'<th><label for="%1$s">%2$s</label></th>',
			esc_attr( self::FIELD_CHALLENGE ),
			esc_html__( 'Gekoppelde uitdaging (opsioneel)', 'ink-core' )
		);
		echo '<td>';
		printf( '<select id="%1$s" name="%1$s">', esc_attr( self::FIELD_CHALLENGE ) );
		printf( '<option value="0">%s</option>', esc_html__( 'Geen', 'ink-core' ) );
		foreach ( $this->challengeChoices() as $id => $title ) {
			printf(
				'<option value="%1$d">%2$s</option>',
				(int) $id,
				esc_html( $title )
			);
		}
		echo '</select>';
		echo '</td></tr>';

		echo '</tbody></table>';
	}

	/**
	 * Persist a Gradering change on profile save.
	 *
	 * The sanctioned `$_POST` path: nonce verify → `current_user_can( MANAGE_TIERS )`
	 * → `wp_unslash` + sanitise → {@see Api::promote()} (the sole write path),
	 * actor = the acting staff user. Never reads a raw superglobal.
	 *
	 * The reason is length-capped, the optional challenge link is re-validated as
	 * a published `uitdaging` (a tampered select value is dropped to "no link",
	 * never logged verbatim), and a manual write is refused when no acting user
	 * resolves — `actor_id = 0` is the automatic-engine sentinel, so recording a
	 * manual change as actor 0 would falsify the audit trail.
	 *
	 * @param int $user_id The user being saved.
	 */
	public function save( int $user_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( Capabilities::MANAGE_TIERS ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::FIELD_TIER ] ) || ! is_scalar( $_POST[ self::FIELD_TIER ] ) ) {
			return;
		}

		$target = Tier::tryFrom( sanitize_text_field( wp_unslash( $_POST[ self::FIELD_TIER ] ) ) );

		if ( null === $target ) {
			return;
		}

		$reason = isset( $_POST[ self::FIELD_REASON ] ) && is_scalar( $_POST[ self::FIELD_REASON ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_REASON ] ) )
			: '';

		if ( '' !== $reason ) {
			$reason = function_exists( 'mb_substr' )
				? mb_substr( $reason, 0, self::REASON_MAX_LENGTH )
				: substr( $reason, 0, self::REASON_MAX_LENGTH );
		}

		$challenge_id = isset( $_POST[ self::FIELD_CHALLENGE ] ) && is_scalar( $_POST[ self::FIELD_CHALLENGE ] )
			? absint( wp_unslash( $_POST[ self::FIELD_CHALLENGE ] ) )
			: 0;

		// Select-tampering guard: only a published uitdaging may be linked. An
		// unknown id or a wrong-type post is dropped to 0 (no link) rather than
		// written verbatim into the audit log + the ink/tier_promoted payload.
		if ( 0 !== $challenge_id && ! $this->isLinkableChallenge( $challenge_id ) ) {
			$challenge_id = 0;
		}

		$actor_id = get_current_user_id();

		// A manual change must be attributable: actor 0 is the sentinel for the
		// automatic engine (Story 5.8), so refuse a manual write that cannot
		// identify its actor rather than mis-log it as a system promotion.
		if ( 0 === $actor_id ) {
			return;
		}

		Api::promote( $user_id, $target, $actor_id, $reason, $challenge_id );
	}

	/**
	 * Whether a post id is a published uitdaging that may be linked to a change.
	 *
	 * @param int $challenge_id The posted challenge id.
	 */
	private function isLinkableChallenge( int $challenge_id ): bool {
		return 'uitdaging' === get_post_type( $challenge_id )
			&& 'publish' === get_post_status( $challenge_id );
	}

	/**
	 * Recent uitdagings for the optional challenge select, id => title.
	 *
	 * A plain `get_posts()` of the CPT — NOT a Challenges-module dependency
	 * (Epic 12); the link is stored as the post id.
	 *
	 * @return array<int, string>
	 */
	private function challengeChoices(): array {
		$choices = array();

		$posts = get_posts(
			array(
				'post_type'        => 'uitdaging',
				'post_status'      => 'publish',
				'numberposts'      => self::CHALLENGE_CHOICES,
				'orderby'          => 'date',
				'order'            => 'DESC',
				'suppress_filters' => false,
			)
		);

		foreach ( $posts as $post ) {
			$choices[ (int) $post->ID ] = (string) $post->post_title;
		}

		return $choices;
	}
}
