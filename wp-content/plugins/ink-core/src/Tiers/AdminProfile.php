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
			'<td><input type="text" id="%1$s" name="%1$s" value="" class="regular-text" /></td>',
			esc_attr( self::FIELD_REASON )
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

		$challenge_id = isset( $_POST[ self::FIELD_CHALLENGE ] ) && is_scalar( $_POST[ self::FIELD_CHALLENGE ] )
			? absint( wp_unslash( $_POST[ self::FIELD_CHALLENGE ] ) )
			: 0;

		Api::promote( $user_id, $target, get_current_user_id(), $reason, $challenge_id );
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
