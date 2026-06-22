<?php
/**
 * Native term images (replaces the WPCustom Category Image plugin).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use WP_Term;

defined( 'ABSPATH' ) || exit;

/**
 * Native term-image capability for the INK content taxonomies (Story 2.5).
 *
 * Stores a term image as native term meta (`ink_term_image_id`, an attachment
 * ID) on `genre`/`vaardigheid`/`uitdagingsrondte`, rendered + saved through the
 * core term add/edit form hooks — no third-party plugin, no JS build pipeline.
 * This fully replaces the WPCustom Category Image plugin's capability.
 *
 * The Epic-16 migration reassigns the 11 existing term images by writing the
 * same `ink_term_image_id` key, so every consumer reads through one surface:
 * {@see Content\Api::termImageId()}. This story builds the CAPABILITY only — it
 * scripts no migration.
 *
 * The save path is the sanctioned (never raw) `$_POST` pattern: nonce →
 * capability → `wp_unslash` + `absint` → `update_term_meta`. Render output is
 * escaped. Registered inside the `Ink\Content` module on `init`.
 *
 * @package Ink\Core
 */
final class TermImages {

	/**
	 * The native term-image meta key (single attachment ID). Single-source.
	 */
	public const META_KEY = 'ink_term_image_id';

	/**
	 * Nonce action + field name for the term-form save round-trip.
	 */
	private const NONCE_ACTION = 'ink_content_term_image_save';
	private const NONCE_NAME   = 'ink_content_term_image_nonce';

	/**
	 * The taxonomies that carry a term image (sourced from {@see Taxonomies}
	 * constants). `ster_gradering` is a rating — no image.
	 *
	 * @return list<string>
	 */
	private static function imageTaxonomies(): array {
		return array(
			Taxonomies::GENRE,
			Taxonomies::VAARDIGHEID,
			Taxonomies::UITDAGINGSRONDTE,
		);
	}

	/**
	 * Public accessor for the image-bearing taxonomies (facade/test surface).
	 *
	 * @return list<string>
	 */
	public static function imageTaxonomyList(): array {
		return self::imageTaxonomies();
	}

	/**
	 * The attachment ID of a term's image, or 0 if none. The cross-module read
	 * surface (exposed via {@see Content\Api::termImageId()}).
	 *
	 * @param int $term_id The term.
	 * @return int Attachment ID, or 0.
	 */
	public static function imageId( int $term_id ): int {
		return (int) get_term_meta( $term_id, self::META_KEY, true );
	}

	/**
	 * Register the term meta + bind the term add/edit/save hooks. Invoked on
	 * `init` from {@see Module::register()}.
	 */
	public function register(): void {
		foreach ( self::imageTaxonomies() as $tax ) {
			register_term_meta(
				$tax,
				self::META_KEY,
				array(
					'single'            => true,
					'type'              => 'integer',
					'show_in_rest'      => true,
					'default'           => 0,
					'sanitize_callback' => 'absint',
					'auth_callback'     => static fn (): bool => current_user_can( 'manage_categories' ),
				)
			);

			add_action( "{$tax}_add_form_fields", array( $this, 'renderAddField' ) );
			add_action( "{$tax}_edit_form_fields", array( $this, 'renderEditField' ) );
			add_action( "created_{$tax}", array( $this, 'save' ) );
			add_action( "edited_{$tax}", array( $this, 'save' ) );
		}
	}

	/**
	 * The Afrikaans field label (generic admin chrome).
	 */
	private static function label(): string {
		return __( 'Termbeeld (heg-ID)', 'ink-core' );
	}

	/**
	 * The Afrikaans field description (generic admin chrome).
	 */
	private static function description(): string {
		return __( 'Die heg-ID van die beeld vir hierdie term.', 'ink-core' );
	}

	/**
	 * Render the term-image field on the "add term" screen. Output escaped.
	 */
	public function renderAddField(): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<div class="form-field term-image-wrap">';
		printf(
			'<label for="ink_term_image_id"><strong>%s</strong></label>',
			esc_html( self::label() )
		);
		echo '<input type="number" name="ink_term_image_id" id="ink_term_image_id" value="" min="0" step="1" />';
		printf( '<p class="description">%s</p>', esc_html( self::description() ) );
		echo '</div>';
	}

	/**
	 * Render the term-image field on the "edit term" screen, pre-filled. Output
	 * escaped.
	 *
	 * @param WP_Term $term The term being edited.
	 */
	public function renderEditField( WP_Term $term ): void {
		$value = self::imageId( $term->term_id );
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		echo '<tr class="form-field term-image-wrap"><th scope="row">';
		printf(
			'<label for="ink_term_image_id">%s</label>',
			esc_html( self::label() )
		);
		echo '</th><td>';
		printf(
			'<input type="number" name="ink_term_image_id" id="ink_term_image_id" value="%s" min="0" step="1" />',
			esc_attr( (string) $value )
		);
		printf( '<p class="description">%s</p>', esc_html( self::description() ) );
		echo '</td></tr>';
	}

	/**
	 * Persist the term image on create/edit.
	 *
	 * The sanctioned `$_POST` path: nonce verify → `current_user_can(
	 * 'manage_categories' )` → `wp_unslash` + `absint` → `update_term_meta`.
	 * Never reads a raw superglobal.
	 *
	 * @param int $term_id The term being saved.
	 */
	public function save( int $term_id ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		if ( ! isset( $_POST['ink_term_image_id'] ) || ! is_scalar( $_POST['ink_term_image_id'] ) ) {
			return;
		}

		update_term_meta( $term_id, self::META_KEY, absint( wp_unslash( $_POST['ink_term_image_id'] ) ) );
	}
}
