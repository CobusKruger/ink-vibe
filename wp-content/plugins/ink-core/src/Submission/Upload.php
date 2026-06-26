<?php
/**
 * Shared `$_FILES` upload predicates for the Skryf form — Story 6.5.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for "is this `$_FILES` entry a usable upload, and what is it" (FR-20/FR-21).
 *
 * Both the optional featured image ({@see FeaturedImage}) and the optional
 * audio/video attachment ({@see MediaAttachment}) need the same two answers —
 * "was a file actually uploaded without error?" and "does its (untrusted) client
 * MIME start with an accepted prefix?". This concentrates that logic so it is
 * defined once rather than copied per media type (the same single-source
 * discipline as the {@see \Ink\Kernel\Scalar} paydown). The authoritative MIME
 * validation remains `media_handle_upload()`'s; {@see mimeStartsWith()} is only a
 * UX pre-gate so we touch the media stack only for a plausible file.
 *
 * Pure value logic, no WordPress state. Conflation-clean — no `Ink\Tiers`.
 *
 * @package Ink\Core
 */
final class Upload {

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
	 * Whether the upload's client MIME starts with any of the given prefixes.
	 *
	 * A UX pre-gate on the client-supplied (untrusted) MIME — the real validation
	 * is `media_handle_upload()`'s allowed-MIME check.
	 *
	 * @param array<string, mixed> $file     A single `$_FILES` entry.
	 * @param string               ...$prefixes Accepted MIME prefixes (e.g. `image/`).
	 * @return bool True when the client MIME starts with one of the prefixes.
	 */
	public static function mimeStartsWith( array $file, string ...$prefixes ): bool {
		$type = isset( $file['type'] ) && is_scalar( $file['type'] ) ? (string) $file['type'] : '';

		if ( '' === $type ) {
			return false;
		}

		foreach ( $prefixes as $prefix ) {
			if ( str_starts_with( $type, $prefix ) ) {
				return true;
			}
		}

		return false;
	}
}
