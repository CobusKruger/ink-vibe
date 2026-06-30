<?php
/**
 * Moderator-feedback comment type + writer display toggle — Story 12A.5 (FR-50-R2, C5).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Kernel\Capabilities;

defined( 'ABSPATH' ) || exit;

/**
 * Stores judges'/moderator feedback privately as a custom comment type and shows it on a
 * work ONLY when the writer opts in (Story 12A.5, C5).
 *
 * Fills the 12A.3 {@see Ingestion::commitModeratorFeedback()} seam: on commit it writes
 * one `ink_moderator_terugvoer` ("Terugvoer van die moderator") comment per entry via
 * `wp_insert_comment` — a PROGRAMMATIC custom type that bypasses the site-wide
 * `comments_open` disable (Story 1.8), so this is NOT a re-enabled WP comment and needs
 * no Engagement edge. It is a sanctioned exception to the "Gemeenskapsreaksies are the
 * only feedback path" rule, distinct from the `ink_reaksie` type.
 *
 * The privacy control (C5): the feedback is stored on commit, but {@see feedbackFor()}
 * surfaces it only when the WORK'S AUTHOR has enabled {@see DISPLAY_META} (default OFF) —
 * the writer owns whether critique appears on their work. The 9.4 My Profiel surface reads
 * the same meta; this story registers the meta + a self-service profile control.
 *
 * Conflation-clean: comments + a display flag only — zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
class ModeratorFeedback {

	/**
	 * The custom comment type (NOT a re-enabled WP comment; distinct from `ink_reaksie`).
	 *
	 * @var string
	 */
	public const COMMENT_TYPE = 'ink_moderator_terugvoer';

	/**
	 * The writer's display-toggle user meta (default OFF — feedback stays private).
	 *
	 * @var string
	 */
	public const DISPLAY_META = 'ink_wys_moderator_terugvoer';

	/**
	 * Nonce action for the self-service profile toggle.
	 */
	private const NONCE = 'ink_moderator_terugvoer_toggle';

	/**
	 * Register the display-toggle meta + the self-service profile control.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerMeta' ) );
		add_action( 'show_user_profile', array( $this, 'renderField' ) );
		add_action( 'edit_user_profile', array( $this, 'renderField' ) );
		add_action( 'personal_options_update', array( $this, 'save' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save' ) );
	}

	/**
	 * Register the boolean display-toggle user meta (self-managed by the writer).
	 */
	public function registerMeta(): void {
		register_meta(
			'user',
			self::DISPLAY_META,
			array(
				'single'            => true,
				'type'              => 'boolean',
				'show_in_rest'      => true,
				'default'           => false,
				'sanitize_callback' => array( self::class, 'sanitizeFlag' ),
				'auth_callback'     => array( self::class, 'authToggle' ),
			)
		);
	}

	/**
	 * Coerce any incoming value to a boolean flag (the shared non-scalar guard).
	 *
	 * @param mixed $value Incoming meta value.
	 * @return bool
	 */
	public static function sanitizeFlag( $value ): bool {
		if ( ! is_scalar( $value ) ) {
			return false;
		}

		return (bool) rest_sanitize_boolean( $value );
	}

	/**
	 * Authorise a write to the display toggle: the user themselves, or a moderator.
	 *
	 * @param bool   $allowed   The incoming decision.
	 * @param string $meta_key The meta key.
	 * @param int    $object_id  The target user id.
	 * @return bool
	 */
	public static function authToggle( $allowed, $meta_key, $object_id ): bool {
		unset( $allowed, $meta_key );

		return get_current_user_id() === (int) $object_id || current_user_can( Capabilities::MODERATE );
	}

	/**
	 * Write the moderator feedback for a round's entries (the 12A.3 commit seam).
	 *
	 * One `ink_moderator_terugvoer` comment per entry; an empty text or an entry that
	 * already carries feedback is skipped (idempotent defence over Ingestion's commit
	 * guard). Returns the number of comments written.
	 *
	 * @param int                                                 $uitdaging_id The round (unused — kept for the seam contract).
	 * @param list<array{post_id:int, title:string, text:string}> $commentary   The resolved per-entry commentary.
	 * @return int
	 */
	public function recordForRound( int $uitdaging_id, array $commentary ): int {
		unset( $uitdaging_id );

		$written = 0;

		foreach ( $commentary as $block ) {
			$post_id = (int) ( $block['post_id'] ?? 0 );
			$text    = trim( (string) ( $block['text'] ?? '' ) );

			if ( $post_id <= 0 || '' === $text || $this->hasFeedback( $post_id ) ) {
				continue;
			}

			if ( $this->insertComment( $post_id, $text ) > 0 ) {
				++$written;
			}
		}

		return $written;
	}

	/**
	 * Whether the writer has enabled the display of moderator feedback on their work.
	 *
	 * @param int $user_id The writer.
	 * @return bool
	 */
	public function isDisplayEnabled( int $user_id ): bool {
		if ( $user_id <= 0 ) {
			return false;
		}

		return self::sanitizeFlag( get_user_meta( $user_id, self::DISPLAY_META, true ) );
	}

	/**
	 * The moderator-feedback texts to SHOW on a work — gated by the author's opt-in (C5).
	 *
	 * Returns the stored feedback only when the work's author has the display toggle ON;
	 * otherwise an empty list (the feedback stays private). This is the privacy control.
	 *
	 * @param int $post_id The work.
	 * @return list<string>
	 */
	public function feedbackFor( int $post_id ): array {
		if ( $post_id <= 0 ) {
			return array();
		}

		if ( ! $this->isDisplayEnabled( $this->authorOf( $post_id ) ) ) {
			return array();
		}

		return $this->commentsFor( $post_id );
	}

	/**
	 * Render the self-service display toggle on the user's profile.
	 *
	 * @param \WP_User $user The profile user.
	 */
	public function renderField( $user ): void {
		if ( ! ( $user instanceof \WP_User ) ) {
			return;
		}

		$enabled = $this->isDisplayEnabled( (int) $user->ID );

		echo '<h2>' . esc_html__( 'Moderator-terugvoer', 'ink-core' ) . '</h2>';
		echo '<table class="form-table"><tr>';
		echo '<th><label for="' . esc_attr( self::DISPLAY_META ) . '">' . esc_html__( 'Wys moderator-terugvoer op my werk', 'ink-core' ) . '</label></th>';
		echo '<td>';
		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<input type="checkbox" id="' . esc_attr( self::DISPLAY_META ) . '" name="' . esc_attr( self::DISPLAY_META ) . '" value="1"' . checked( $enabled, true, false ) . ' /> ';
		echo esc_html__( 'Terugvoer van die moderator verskyn op jou werk slegs as dit aangeskakel is.', 'ink-core' );
		echo '</td></tr></table>';
	}

	/**
	 * Save the self-service display toggle (the sanctioned $_POST path).
	 *
	 * @param int $user_id The profile user being saved.
	 */
	public function save( int $user_id ): void {
		if ( ! self::authToggle( false, self::DISPLAY_META, $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE ] ) || ! is_scalar( $_POST[ self::NONCE ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			return;
		}

		// An unchecked checkbox posts nothing → OFF; checked → '1' (nonce verified above).
		$enabled = isset( $_POST[ self::DISPLAY_META ] ) && is_scalar( $_POST[ self::DISPLAY_META ] )
			&& '1' === sanitize_key( wp_unslash( $_POST[ self::DISPLAY_META ] ) );

		update_user_meta( $user_id, self::DISPLAY_META, $enabled );
	}

	// --- Overridable seams (testability) -----------------------------------------------

	/**
	 * Insert one moderator-feedback comment. Overridable seam.
	 *
	 * @param int    $post_id The work.
	 * @param string $text    The feedback.
	 * @return int The new comment id (0 on failure).
	 */
	protected function insertComment( int $post_id, string $text ): int {
		$id = wp_insert_comment(
			array(
				'comment_post_ID'  => $post_id,
				'comment_content'  => $text,
				'comment_type'     => self::COMMENT_TYPE,
				'comment_approved' => 1,
			)
		);

		// wp_insert_comment returns int|false; guard the false case, never is_int (the
		// success id can arrive as a numeric string on some installs) — R12A review.
		return false === $id ? 0 : (int) $id;
	}

	/**
	 * Whether the work already carries moderator feedback. Overridable seam.
	 *
	 * @param int $post_id The work.
	 * @return bool
	 */
	protected function hasFeedback( int $post_id ): bool {
		return array() !== $this->commentsFor( $post_id );
	}

	/**
	 * The stored moderator-feedback texts for a work (ungated). Overridable seam.
	 *
	 * @param int $post_id The work.
	 * @return list<string>
	 */
	protected function commentsFor( int $post_id ): array {
		$comments = get_comments(
			array(
				'post_id' => $post_id,
				'type'    => self::COMMENT_TYPE,
				'status'  => 'approve',
			)
		);

		$texts = array();

		foreach ( is_array( $comments ) ? $comments : array() as $comment ) {
			if ( $comment instanceof \WP_Comment ) {
				$texts[] = (string) $comment->comment_content;
			}
		}

		return $texts;
	}

	/**
	 * The author id of a work. Overridable seam.
	 *
	 * @param int $post_id The work.
	 * @return int
	 */
	protected function authorOf( int $post_id ): int {
		return (int) get_post_field( 'post_author', $post_id );
	}
}
