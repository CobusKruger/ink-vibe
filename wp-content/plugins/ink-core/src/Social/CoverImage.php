<?php
/**
 * Public Skrywerprofiel cover-image user meta + admin field.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Owns the writer's optional profile cover-image attachment id.
 *
 * The public Skrywerprofiel (Story 9.4) shows a wide banner behind the avatar,
 * mirroring the Lovable reference (`Writer.tsx`'s `cover` field) — a feature the
 * pre-existing block had no data source for at all. Stored as a single
 * `ink_skrywer_omslag_id` user-meta (a Media Library attachment id), following
 * the codebase's existing JS-free "attachment id number field" convention (see
 * {@see \Ink\Content\FieldSets}'s `INKPOLS_COVER_ID` field): an admin pastes the
 * numeric id from the Media Library rather than a dedicated uploader widget.
 *
 * Self-contained within `Ink\Social` — register_meta('user', …) + the two
 * WordPress user-profile-edit hooks are core APIs, so this needs no new
 * cross-module deptrac edge.
 *
 * @package Ink\Core
 */
final class CoverImage {

	/**
	 * User-meta key: the cover-image attachment id (0 = none set).
	 *
	 * @var string
	 */
	public const META = 'ink_skrywer_omslag_id';

	/**
	 * The nonce action + field name for the profile-edit-screen save round-trip.
	 */
	private const NONCE_ACTION = 'ink_social_cover_image_save';
	private const NONCE_NAME   = 'ink_social_cover_image_nonce';

	/**
	 * Register the meta field + the wp-admin profile-edit-screen field.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so this runs directly, no nested `add_action( 'init', … )`.
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

		add_action( 'show_user_profile', array( self::class, 'renderField' ) );
		add_action( 'edit_user_profile', array( self::class, 'renderField' ) );
		add_action( 'personal_options_update', array( self::class, 'save' ) );
		add_action( 'edit_user_profile_update', array( self::class, 'save' ) );
	}

	/**
	 * The resolved cover-image URL for a skrywer at a given registered size.
	 *
	 * @param int    $user_id The skrywer.
	 * @param string $size    A registered image size.
	 * @return string The URL, or '' when no cover is set / the attachment is gone.
	 */
	public static function urlFor( int $user_id, string $size = 'large' ): string {
		$attachment_id = (int) get_user_meta( $user_id, self::META, true );

		if ( $attachment_id <= 0 ) {
			return '';
		}

		$url = wp_get_attachment_image_url( $attachment_id, $size );

		return is_string( $url ) ? $url : '';
	}

	/**
	 * Render the admin field on the user-profile-edit screen.
	 *
	 * @param \WP_User $user The profile being edited.
	 */
	public static function renderField( \WP_User $user ): void {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}

		$current = (int) get_user_meta( $user->ID, self::META, true );

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		?>
		<h2><?php esc_html_e( 'Skrywerprofiel', 'ink-core' ); ?></h2>
		<table class="form-table" role="presentation">
			<tr>
				<th><label for="<?php echo esc_attr( self::META ); ?>"><?php esc_html_e( 'Profile cover image (Media Library attachment ID)', 'ink-core' ); ?></label></th>
				<td>
					<input type="number" min="0" step="1" name="<?php echo esc_attr( self::META ); ?>" id="<?php echo esc_attr( self::META ); ?>" value="<?php echo esc_attr( (string) $current ); ?>" class="regular-text" />
					<p class="description"><?php esc_html_e( 'Paste the numeric attachment ID of an image from the Media Library. Shown as the wide banner behind the avatar on the public Skrywerprofiel page. Leave 0 for none.', 'ink-core' ); ?></p>
					<?php if ( $current > 0 ) : ?>
						<?php $preview = wp_get_attachment_image_url( $current, 'medium' ); ?>
						<?php if ( is_string( $preview ) && '' !== $preview ) : ?>
							<p><img src="<?php echo esc_url( $preview ); ?>" alt="" style="max-width:320px;height:auto;" /></p>
						<?php endif; ?>
					<?php endif; ?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Save the field from the profile-edit-screen POST.
	 *
	 * @param int $user_id The profile being saved.
	 */
	public static function save( int $user_id ): void {
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::META ] ) ) {
			return;
		}

		$attachment_id = absint( wp_unslash( $_POST[ self::META ] ) );

		update_user_meta( $user_id, self::META, $attachment_id );
	}
}
