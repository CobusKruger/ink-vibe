<?php
/**
 * Optional featured-image upload policy for the Skryf form — Story 6.4.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a Skryf `$_FILES` entry is an optional featured image to attach (FR-20).
 *
 * The featured image is OPTIONAL — omitting it never blocks submission. These pure
 * predicates answer "is there a usable image upload here?" so the handler can decide
 * whether to invoke the WordPress media stack at all. The authoritative MIME / type
 * validation is `media_handle_upload()` itself (it checks `get_allowed_mime_types()`);
 * {@see isImage()} is only a UX pre-gate on the client-supplied (untrusted) MIME, so
 * a non-image is rejected before we touch the media stack.
 *
 * Pure value logic, no WordPress state. Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class FeaturedImage {

	/**
	 * The Skryf featured-image file-input name (single source for handler + theme).
	 */
	public const FIELD = 'ink_submission_featured_image';

	/**
	 * The accepted client-MIME prefix (the UX pre-gate).
	 */
	public const ALLOWED_MIME_PREFIX = 'image/';

	/**
	 * Whether a `$_FILES` entry is a present, error-free, non-empty upload.
	 *
	 * @param array<string, mixed> $file A single `$_FILES` entry.
	 * @return bool True when a file was actually uploaded without error.
	 */
	public static function isPresent( array $file ): bool {
		$error = isset( $file['error'] ) && is_scalar( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE;
		$size  = isset( $file['size'] ) && is_scalar( $file['size'] ) ? (int) $file['size'] : 0;
		$name  = isset( $file['name'] ) && is_scalar( $file['name'] ) ? (string) $file['name'] : '';

		return UPLOAD_ERR_OK === $error && $size > 0 && '' !== $name;
	}

	/**
	 * Whether the upload's client MIME looks like an image (UX pre-gate only).
	 *
	 * @param array<string, mixed> $file A single `$_FILES` entry.
	 * @return bool True when the client MIME starts with `image/`.
	 */
	public static function isImage( array $file ): bool {
		$type = isset( $file['type'] ) && is_scalar( $file['type'] ) ? (string) $file['type'] : '';

		return str_starts_with( $type, self::ALLOWED_MIME_PREFIX );
	}
}
