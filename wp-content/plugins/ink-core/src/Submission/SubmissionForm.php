<?php
/**
 * The custom front-end submission form handler (the Skryf flow) — Story 6.1.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Content\PostTypes;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the custom Skryf submission write path (FR-16), replacing the legacy
 * Youzify front-end editor.
 *
 * A logged-in skrywer picks a bydrae type (gedig / storie / artikel), enters a
 * title and body, and submits; this collaborator validates the input and creates
 * the bydrae of the chosen CPT, authored by that user. Per the Epic-6 build-order
 * the bydrae is saved as a **konsep (draft)** here — saving a draft is never
 * entitlement-gated (FR-23); the *plaas* (publish) path and the lidmaatskap
 * entitlement gate land in Stories 6.7 and 6.8.
 *
 * Authorisation is INK's own, not WordPress's editor caps: the `ink_content`
 * capability family is granted only to administrator / editor (Story 3.3), so a
 * skrywer (gratis or paid lid) holds none of it — front-end submission is
 * authorised by "logged in + nonce" for a draft, and (from 6.8) by active
 * lidmaatskap for a publish. The handler therefore creates the post directly via
 * {@see wp_insert_post()} after its OWN checks, rather than relying on
 * `current_user_can( 'create_ink_contents' )`.
 *
 * THE conflation rule (AD-1): this carries ZERO reference to `Ink\Tiers` — the
 * writer Gradering (`ink_writer_tier`) never gates submission. The write seam is
 * the sanctioned logged-in `admin-post` path, mirroring {@see \Ink\Accounts\Onboarding}:
 * nonce-verified, raw `$_POST` guarded inline with `is_scalar()` before any
 * sanitiser, then a safe redirect.
 *
 * @package Ink\Core
 */
class SubmissionForm {

	/**
	 * Nonce action + field name for the Skryf write round-trip.
	 */
	public const NONCE_ACTION = 'ink_submission_plaas';
	public const NONCE_NAME   = 'ink_submission_nonce';

	/**
	 * The logged-in `admin-post` action the Skryf form posts to.
	 */
	public const POST_ACTION = 'ink_submission_plaas';

	/**
	 * Form field names (single source for handler + theme bridge).
	 */
	public const FIELD_TYPE  = 'ink_submission_type';
	public const FIELD_TITLE = 'ink_submission_title';
	public const FIELD_BODY  = 'ink_submission_body';

	/**
	 * The intent field + its two values: save a konsep vs plaas (publish).
	 */
	public const INTENT_FIELD   = 'ink_submission_intent';
	public const INTENT_DRAFT   = 'konsep';
	public const INTENT_PUBLISH = 'plaas';

	/**
	 * Register the Skryf hooks. Invoked once from {@see Module::register()}.
	 *
	 * Logged-in `admin-post` only (no `nopriv`) — an anonymous visitor cannot
	 * reach the write path (AD-7 sanctions `admin-post` for a write of this size).
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::POST_ACTION, array( $this, 'handlePost' ) );
	}

	/**
	 * The bydrae CPTs a skrywer may submit through the Skryf form.
	 *
	 * The three user-facing bydrae types. `skryfwerk` is deliberately EXCLUDED —
	 * it is the migration holding bucket (afrikaans-terms.md / project-context §3),
	 * not a type a member chooses; a tampered `skryfwerk` (or any other) value is
	 * rejected by {@see buildPost()}.
	 *
	 * @return list<string>
	 */
	public static function submittableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * The nonce action (test/template surface).
	 */
	public static function nonceAction(): string {
		return self::NONCE_ACTION;
	}

	/**
	 * The nonce field name (test/template surface).
	 */
	public static function nonceName(): string {
		return self::NONCE_NAME;
	}

	/**
	 * The `admin-post` action the Skryf form targets (test/template surface).
	 */
	public static function postAction(): string {
		return self::POST_ACTION;
	}

	/**
	 * Map a submit intent to a post status (Story 6.7).
	 *
	 * `plaas` publishes; anything else (incl. the missing/`konsep` case) is the
	 * fail-safe ungated draft.
	 *
	 * @param string $intent The submitted intent value.
	 * @return string `publish` or `draft`.
	 */
	public static function statusForIntent( string $intent ): string {
		return self::INTENT_PUBLISH === $intent ? 'publish' : 'draft';
	}

	/**
	 * Validate the submitted fields and build the bydrae `wp_insert_post` array.
	 *
	 * Pure, fail-safe, type-aware validation (no WordPress state): the type must be
	 * one of {@see submittableTypes()} (an unknown / tampered type — including the
	 * non-submittable `skryfwerk` bucket — is rejected), and both title and body
	 * must be non-empty after trimming. On any failure a {@see WP_Error} is
	 * returned and NO post is created (the caller returns the skrywer to the form
	 * rather than silently dropping their work).
	 *
	 * @param string $type      The chosen bydrae CPT slug (already sanitised).
	 * @param string $title     The bydrae title (already sanitised).
	 * @param string $body      The bydrae body (already sanitised).
	 * @param int    $author_id The submitting skrywer's user id.
	 * @param string $status    The post status (`draft` for a konsep, `publish` for plaas).
	 * @return array<string, mixed>|WP_Error The `wp_insert_post` args, or an error.
	 */
	public function buildPost( string $type, string $title, string $body, int $author_id, string $status = 'draft' ) {
		if ( ! in_array( $type, self::submittableTypes(), true ) ) {
			return new WP_Error( 'ink_submission_invalid_type', 'Onbekende bydrae-tipe.' );
		}

		if ( '' === trim( $title ) ) {
			return new WP_Error( 'ink_submission_missing_title', 'Titel ontbreek.' );
		}

		if ( '' === trim( $body ) ) {
			return new WP_Error( 'ink_submission_missing_body', 'Inhoud ontbreek.' );
		}

		return array(
			'post_type'    => $type,
			'post_title'   => $title,
			'post_content' => $body,
			'post_status'  => $status,
			'post_author'  => $author_id,
		);
	}

	/**
	 * Handle the nonce-protected Skryf POST (logged-in only).
	 *
	 * Sanctioned path: logged-in guard → nonce verify → inline `is_scalar` guards +
	 * `wp_unslash` + sanitise → {@see buildDraft()} → {@see wp_insert_post()} →
	 * safe redirect. Every failure degrades gracefully (returns the skrywer to the
	 * form); no raw superglobal reaches a sanitiser un-guarded.
	 */
	public function handlePost(): void {
		$user_id = get_current_user_id();

		if ( 0 === $user_id ) {
			$this->redirect( $this->formUrl() );
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! is_scalar( $_POST[ self::NONCE_NAME ] ) ) {
			$this->redirect( $this->formUrl() );
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->redirect( $this->formUrl() );
			return;
		}

		$type = isset( $_POST[ self::FIELD_TYPE ] ) && is_scalar( $_POST[ self::FIELD_TYPE ] )
			? sanitize_key( wp_unslash( $_POST[ self::FIELD_TYPE ] ) )
			: '';

		$title = isset( $_POST[ self::FIELD_TITLE ] ) && is_scalar( $_POST[ self::FIELD_TITLE ] )
			? sanitize_text_field( wp_unslash( $_POST[ self::FIELD_TITLE ] ) )
			: '';

		$body = '';

		if ( isset( $_POST[ self::FIELD_BODY ] ) && is_scalar( $_POST[ self::FIELD_BODY ] ) ) {
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ProseSanitizer::sanitize() wraps wp_kses() (Story 6.3 strict allowlist); the sniff cannot see through the wrapper.
			$body = ProseSanitizer::sanitize( (string) wp_unslash( $_POST[ self::FIELD_BODY ] ) );
		}

		$intent = isset( $_POST[ self::INTENT_FIELD ] ) && is_scalar( $_POST[ self::INTENT_FIELD ] )
			? sanitize_key( wp_unslash( $_POST[ self::INTENT_FIELD ] ) )
			: self::INTENT_DRAFT;

		$status = self::statusForIntent( $intent );

		$postarr = $this->buildPost( $type, $title, $body, $user_id, $status );

		if ( is_wp_error( $postarr ) ) {
			$this->redirect( $this->formUrl( 'fout' ) );
			return;
		}

		$post_id = wp_insert_post( $postarr, true );

		if ( is_wp_error( $post_id ) || 0 === (int) $post_id ) {
			$this->redirect( $this->formUrl( 'fout' ) );
			return;
		}

		$this->attachFeaturedImage( (int) $post_id );
		$this->attachMedia( (int) $post_id );
		$this->linkChallenges( (int) $post_id );

		if ( 'publish' === $status ) {
			$this->redirect( $this->successUrl( (int) $post_id ) );
			return;
		}

		$this->redirect( $this->formUrl( 'konsep-gestoor' ) );
	}

	/**
	 * The Skryf success-screen URL for a freshly published bydrae (Story 6.7).
	 *
	 * @param int $post_id The published bydrae id.
	 * @return string The local success URL.
	 */
	protected function successUrl( int $post_id ): string {
		return add_query_arg(
			array(
				'ink_skryf' => 'geplaas',
				'id'        => $post_id,
			),
			home_url( '/skryf/' )
		);
	}

	/**
	 * Link the new bydrae to the OPEN uitdagings the skrywer ticked (Story 6.6).
	 *
	 * Reads the `ink_submission_uitdagings[]` checkbox array, coerces each entry to
	 * a positive id, and hands them to {@see ChallengeLinking::link()} (which links
	 * only the open ones). Nonce already verified by {@see handlePost()}.
	 *
	 * @param int $post_id The freshly created bydrae id.
	 */
	protected function linkChallenges( int $post_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handlePost() before this runs.
		if ( ! isset( $_POST[ ChallengeLinking::FIELD ] ) || ! is_array( $_POST[ ChallengeLinking::FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- each ticked id is absint()-sanitised below; nonce verified in handlePost().
		$raw = wp_unslash( $_POST[ ChallengeLinking::FIELD ] );

		$ids = array();

		foreach ( (array) $raw as $value ) {
			$ids[] = absint( $value );
		}

		( new ChallengeLinking() )->link( $post_id, $ids );
	}

	/**
	 * Attach an OPTIONAL featured image to the new bydrae (Story 6.4).
	 *
	 * Bails silently when no usable image was uploaded. Any failure (non-image,
	 * upload error, media-stack error) is non-fatal — the bydrae keeps its text,
	 * just without a thumbnail. The real MIME validation is `media_handle_upload`'s;
	 * the {@see FeaturedImage} checks are a UX pre-gate so we only invoke the media
	 * stack for a plausible image. Nonce is already verified by {@see handlePost()}.
	 *
	 * @param int $post_id The freshly created bydrae id.
	 */
	protected function attachFeaturedImage( int $post_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handlePost() before this runs.
		if ( ! isset( $_FILES[ FeaturedImage::FIELD ] ) || ! is_array( $_FILES[ FeaturedImage::FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- $_FILES metadata is read through FeaturedImage's is_scalar guards; media_handle_upload performs the authoritative MIME validation.
		$file = wp_unslash( $_FILES[ FeaturedImage::FIELD ] );

		if ( ! FeaturedImage::isPresent( $file ) || ! FeaturedImage::isImage( $file ) ) {
			return;
		}

		$this->ensureMediaStack();

		$attachment_id = $this->mediaHandleUpload( FeaturedImage::FIELD, $post_id );

		if ( is_wp_error( $attachment_id ) || 0 === (int) $attachment_id ) {
			return;
		}

		set_post_thumbnail( $post_id, (int) $attachment_id );
	}

	/**
	 * Attach an OPTIONAL audio/video file to the new bydrae (Story 6.5).
	 *
	 * Mirrors {@see attachFeaturedImage()}: bails silently when no usable
	 * audio/video file was uploaded; any failure is non-fatal. On success the
	 * uploaded media's attachment id is stored on the bydrae as
	 * {@see MediaAttachment::META_KEY} (not a thumbnail). Nonce already verified.
	 *
	 * @param int $post_id The freshly created bydrae id.
	 */
	protected function attachMedia( int $post_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handlePost() before this runs.
		if ( ! isset( $_FILES[ MediaAttachment::FIELD ] ) || ! is_array( $_FILES[ MediaAttachment::FIELD ] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- $_FILES metadata is read through Upload's is_scalar guards; media_handle_upload performs the authoritative MIME validation.
		$file = wp_unslash( $_FILES[ MediaAttachment::FIELD ] );

		if ( ! Upload::isPresent( $file ) || ! MediaAttachment::isAudioVideo( $file ) ) {
			return;
		}

		$this->ensureMediaStack();

		$attachment_id = $this->mediaHandleUpload( MediaAttachment::FIELD, $post_id );

		if ( is_wp_error( $attachment_id ) || 0 === (int) $attachment_id ) {
			return;
		}

		update_post_meta( $post_id, MediaAttachment::META_KEY, (int) $attachment_id );
	}

	/**
	 * Load the WordPress media-handling includes. Overridable seam for tests.
	 */
	protected function ensureMediaStack(): void {
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}

	/**
	 * Hand the uploaded file to WordPress media handling. Overridable seam for tests.
	 *
	 * @param string $field   The `$_FILES` field name.
	 * @param int    $post_id The post to attach to.
	 * @return int|\WP_Error The attachment id, or an error.
	 */
	protected function mediaHandleUpload( string $field, int $post_id ) {
		return media_handle_upload( $field, $post_id );
	}

	/**
	 * The Skryf page URL, with an optional notice marker.
	 *
	 * @param string $notice Optional `ink_skryf` notice slug.
	 * @return string The local Skryf URL.
	 */
	protected function formUrl( string $notice = '' ): string {
		$url = home_url( '/skryf/' );

		return '' === $notice ? $url : add_query_arg( 'ink_skryf', $notice, $url );
	}

	/**
	 * Redirect to a local URL and end the request.
	 *
	 * Wrapped as a seam: tests override {@see halt()} to avoid terminating the
	 * test process while still asserting the redirect target.
	 *
	 * @param string $url Target URL.
	 */
	protected function redirect( string $url ): void {
		wp_safe_redirect( $url );
		$this->halt();
	}

	/**
	 * End the request after a redirect. Overridable seam for tests.
	 */
	protected function halt(): void {
		exit;
	}
}
