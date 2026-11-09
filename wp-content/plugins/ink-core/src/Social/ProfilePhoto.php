<?php
/**
 * Member profile picture: upload endpoint + site-wide avatar override.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * Lets a logged-in member upload their own profile picture from My Profiel.
 *
 * The picture is a Media Library attachment whose id lives in the
 * `ink_profielfoto_id` user meta. {@see self::overrideAvatar()} hooks
 * `pre_get_avatar_data`, so every `get_avatar()` / `get_avatar_url()` call on
 * the site (cards, comments, header menu, Skrywerprofiel) shows the uploaded
 * picture; members with no upload keep the Gravatar/default resolution.
 *
 * The route has no target-user parameter: it always writes to
 * `get_current_user_id()`, like {@see ProfileController}. Members do not hold
 * `upload_files`, so the file goes through `wp_handle_upload()` directly after
 * our own type and size checks.
 *
 * @package Ink\Core
 */
final class ProfilePhoto {

	public const META = 'ink_profielfoto_id';

	private const NAMESPACE = 'ink/v1';
	private const ROUTE     = '/profiel/foto';
	private const MAX_BYTES = 5242880; // 5 MB.

	/**
	 * Register the meta, the avatar override and the REST route.
	 */
	public function register(): void {
		register_meta(
			'user',
			self::META,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => 'absint',
				'show_in_rest'      => false,
			)
		);

		add_filter( 'pre_get_avatar_data', array( self::class, 'overrideAvatar' ), 10, 2 );
		add_action( 'rest_api_init', array( $this, 'registerRoutes' ) );
	}

	/**
	 * Register `POST` on `ink/v1/profiel/foto`.
	 */
	public function registerRoutes(): void {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'handleUpload' ),
					'permission_callback' => static fn (): bool => is_user_logged_in(),
				),
			)
		);
	}

	/**
	 * `pre_get_avatar_data`: serve the uploaded picture when the member has one.
	 *
	 * @param array<string, mixed> $args        Avatar args.
	 * @param mixed                $id_or_email User id, email, WP_User, WP_Post or WP_Comment.
	 * @return array<string, mixed>
	 */
	public static function overrideAvatar( $args, $id_or_email ) {
		$user_id = self::userIdFrom( $id_or_email );

		if ( $user_id <= 0 ) {
			return $args;
		}

		$url = self::urlFor( $user_id, (int) ( $args['size'] ?? 96 ) );

		if ( '' === $url ) {
			return $args;
		}

		$args['url']          = $url;
		$args['found_avatar'] = true;

		return $args;
	}

	/**
	 * The uploaded picture's URL at (at least) `$size` px square, or ''.
	 *
	 * @param int $user_id The member.
	 * @param int $size    Requested edge in px.
	 * @return string
	 */
	public static function urlFor( int $user_id, int $size = 96 ): string {
		$attachment_id = (int) get_user_meta( $user_id, self::META, true );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		// 2x for retina; WordPress picks the closest generated size.
		$url = wp_get_attachment_image_url( $attachment_id, array( $size * 2, $size * 2 ) );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Resolve the avatar subject to a user id (0 when it is not a known member).
	 *
	 * @param mixed $id_or_email As passed to `get_avatar_data()`.
	 */
	private static function userIdFrom( $id_or_email ): int {
		if ( is_numeric( $id_or_email ) ) {
			return (int) $id_or_email;
		}

		if ( $id_or_email instanceof \WP_User ) {
			return (int) $id_or_email->ID;
		}

		if ( $id_or_email instanceof \WP_Post ) {
			return (int) $id_or_email->post_author;
		}

		if ( $id_or_email instanceof \WP_Comment ) {
			return (int) $id_or_email->user_id;
		}

		if ( is_string( $id_or_email ) && false !== strpos( $id_or_email, '@' ) ) {
			$user = get_user_by( 'email', $id_or_email );

			return $user instanceof \WP_User ? (int) $user->ID : 0;
		}

		return 0;
	}

	/**
	 * POST: store the uploaded `foto` file as the current member's picture.
	 *
	 * @param WP_REST_Request $request The REST request (multipart, field `foto`).
	 * @return WP_REST_Response|WP_Error
	 */
	public function handleUpload( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$files   = $request->get_file_params();
		$file    = $files['foto'] ?? null;

		if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! empty( $file['error'] ) ) {
			return new WP_Error( 'ink_foto_leeg', 'Kies asseblief \'n prent.', array( 'status' => 400 ) );
		}

		if ( (int) $file['size'] > self::MAX_BYTES ) {
			return new WP_Error( 'ink_foto_groot', 'Die prent is te groot (maks. 5 MB).', array( 'status' => 413 ) );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		$allowed = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'png'          => 'image/png',
			'gif'          => 'image/gif',
			'webp'         => 'image/webp',
		);

		$moved = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $allowed,
			)
		);

		if ( ! is_array( $moved ) || isset( $moved['error'] ) ) {
			return new WP_Error( 'ink_foto_tipe', 'Slegs JPG-, PNG-, GIF- of WebP-prente word aanvaar.', array( 'status' => 415 ) );
		}

		$attachment_id = wp_insert_attachment(
			array(
				'post_mime_type' => (string) $moved['type'],
				'post_title'     => 'Profielfoto ' . $user_id,
				'post_status'    => 'inherit',
				'post_author'    => $user_id,
			),
			(string) $moved['file']
		);

		if ( is_wp_error( $attachment_id ) || $attachment_id <= 0 ) {
			return new WP_Error( 'ink_foto_stoor', 'Dit het nie gewerk nie. Probeer weer.', array( 'status' => 500 ) );
		}

		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, (string) $moved['file'] ) );

		$previous = (int) get_user_meta( $user_id, self::META, true );
		update_user_meta( $user_id, self::META, $attachment_id );

		// Drop the member's replaced picture (ours only, never an arbitrary id).
		if ( $previous > 0 && $previous !== $attachment_id && (int) get_post_field( 'post_author', $previous ) === $user_id ) {
			wp_delete_attachment( $previous, true );
		}

		return new WP_REST_Response( array( 'url' => self::urlFor( $user_id, 112 ) ) );
	}
}
