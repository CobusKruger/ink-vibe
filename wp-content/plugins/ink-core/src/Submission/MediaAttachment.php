<?php
/**
 * Optional audio/video attachment for the Skryf form — Story 6.5.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * The optional audio/video attachment a skrywer may add to a bydrae (FR-21).
 *
 * Like the featured image (6.4) the attachment is OPTIONAL — omitting it never
 * blocks submission. Unlike the image it is not a thumbnail: the uploaded media's
 * attachment id is stored on the bydrae as the registered `ink_media_attachment`
 * post meta (so the Epic-7 reading layer can surface a recording). The "usable
 * upload?" / "client MIME?" checks are shared via {@see Upload}; the authoritative
 * MIME validation is `media_handle_upload()`'s.
 *
 * Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class MediaAttachment {

	/**
	 * The Skryf audio/video file-input name (single source for handler + theme).
	 */
	public const FIELD = 'ink_submission_media';

	/**
	 * The bydrae post-meta key storing the uploaded media's attachment id.
	 */
	public const META_KEY = 'ink_media_attachment';

	/**
	 * Register the media-attachment meta on the submittable bydrae CPTs.
	 *
	 * Invoked from {@see Module::register()} (dispatched on `init`). Single integer
	 * attachment id, `absint`-sanitised, `show_in_rest` so the reading layer can
	 * read it; written only by a logged-in author (the Skryf handler).
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'registerMeta' ) );
	}

	/**
	 * Register the `ink_media_attachment` post meta on each submittable type.
	 */
	public function registerMeta(): void {
		foreach ( SubmissionForm::submittableTypes() as $type ) {
			register_post_meta(
				$type,
				self::META_KEY,
				array(
					'single'            => true,
					'type'              => 'integer',
					'show_in_rest'      => true,
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'auth_callback'     => static function (): bool {
						return is_user_logged_in();
					},
				)
			);
		}
	}

	/**
	 * Whether the upload's client MIME looks like audio or video (UX pre-gate only).
	 *
	 * @param array<string, mixed> $file A single `$_FILES` entry.
	 * @return bool True when the client MIME starts with `audio/` or `video/`.
	 */
	public static function isAudioVideo( array $file ): bool {
		return Upload::mimeStartsWith( $file, 'audio/', 'video/' );
	}
}
