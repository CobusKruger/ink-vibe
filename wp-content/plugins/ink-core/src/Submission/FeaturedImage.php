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
 * The featured image is OPTIONAL — omitting it never blocks submission. These thin
 * predicates delegate the shared "usable upload?" / "client MIME?" logic to
 * {@see Upload} (the single source reused by {@see MediaAttachment}) and only pin
 * the image-specific MIME prefix here. The authoritative MIME validation remains
 * `media_handle_upload()`'s; {@see isImage()} is a UX pre-gate.
 *
 * Conflation-clean — no `Ink\Tiers`.
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
		return Upload::isPresent( $file );
	}

	/**
	 * Whether the upload's client MIME looks like an image (UX pre-gate only).
	 *
	 * @param array<string, mixed> $file A single `$_FILES` entry.
	 * @return bool True when the client MIME starts with `image/`.
	 */
	public static function isImage( array $file ): bool {
		return Upload::mimeStartsWith( $file, self::ALLOWED_MIME_PREFIX );
	}
}
