<?php
/**
 * Per-CPT admin field sets (editorial meta + native meta boxes).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Content;

use Ink\Kernel\CadenceType;
use Ink\Kernel\Capabilities;
use Ink\I18n\Terms;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the editorial meta + admin meta boxes for the InkPols, challenge and
 * sponsor CPTs (Story 2.4).
 *
 * Native and JS-free: each field is `register_post_meta` (typed, REST-aware,
 * sanitised, capability-gated) AND a classic `add_meta_box` field rendered
 * server-side. Gutenberg renders the classic box and posts it back to
 * `post.php`, so {@see FieldSets::save()} is the required save path — the ONLY
 * `$_POST` site in the codebase, and it is the sanctioned (never raw) WordPress
 * pattern: nonce → capability → `wp_unslash` + `sanitize_*` → `update_post_meta`.
 *
 * One declarative {@see FieldSets::definitions()} map (CPT → cap + fields) drives
 * registration, rendering and saving. Meta keys are `ink_`-prefixed class
 * constants (single-source); CPT slugs come from {@see PostTypes} constants.
 * `borg` tier/placement are sanitised text here — the controlled `SponsorTier`
 * value set and scheduling are Epic 14.
 *
 * @package Ink\Core
 */
final class FieldSets {

	/**
	 * Nonce action + field name for the meta-box save round-trip.
	 */
	private const NONCE_ACTION = 'ink_content_fieldsets_save';
	private const NONCE_NAME   = 'ink_content_fieldsets_nonce';

	// InkPols issue meta keys.
	public const INKPOLS_ISSUE_DATE = 'ink_inkpols_issue_date';
	public const INKPOLS_VOLUME     = 'ink_inkpols_volume';
	public const INKPOLS_COVER_ID   = 'ink_inkpols_cover_id';
	public const INKPOLS_PDF_ID     = 'ink_inkpols_pdf_id';
	public const INKPOLS_TEASER     = 'ink_inkpols_teaser';

	// Challenge meta keys.
	public const UITDAGING_THEME    = 'ink_uitdaging_theme';
	public const UITDAGING_DEADLINE = 'ink_uitdaging_deadline';
	public const UITDAGING_CADENCE  = 'ink_uitdaging_cadence';

	// Sponsor meta keys.
	public const BORG_LINK       = 'ink_borg_link';
	public const BORG_TIER       = 'ink_borg_tier';
	public const BORG_START_DATE = 'ink_borg_start_date';
	public const BORG_END_DATE   = 'ink_borg_end_date';
	public const BORG_PLACEMENT  = 'ink_borg_placement';

	/**
	 * Every field meta key across all CPTs (the facade surface).
	 *
	 * @return list<string>
	 */
	public static function metaKeys(): array {
		$keys = array();

		foreach ( self::definitions() as $def ) {
			foreach ( $def['fields'] as $field ) {
				$keys[] = $field['key'];
			}
		}

		return $keys;
	}

	/**
	 * Register the meta + bind the meta-box render/save hooks. Invoked on `init`
	 * from {@see Module::register()}.
	 */
	public function register(): void {
		foreach ( self::definitions() as $cpt => $def ) {
			$cap = $def['cap'];

			foreach ( $def['fields'] as $field ) {
				register_post_meta(
					$cpt,
					$field['key'],
					array(
						'single'            => true,
						'type'              => $field['type'],
						'show_in_rest'      => true,
						'default'           => 'integer' === $field['type'] ? 0 : '',
						'sanitize_callback' => $field['sanitize'],
						'auth_callback'     => static fn (): bool => current_user_can( $cap ),
					)
				);
			}
		}

		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
	}

	/**
	 * Register one meta box per CPT. The box title composes the CPT noun from the
	 * {@see Terms} registry; field labels are generic admin chrome (AC-3).
	 */
	public function addMetaBoxes(): void {
		foreach ( self::definitions() as $cpt => $def ) {
			add_meta_box(
				"ink_{$cpt}_besonderhede",
				/* translators: %s: the singular content-type label (e.g. Borg). */
				sprintf( __( '%s — besonderhede', 'ink-core' ), Terms::label( $def['term'] ) ),
				array( $this, 'renderBox' ),
				$cpt,
				'normal',
				'high',
				array( 'cpt' => $cpt )
			);
		}
	}

	/**
	 * Render a CPT's field set. Every value is escaped at output.
	 *
	 * @param WP_Post              $post The post being edited.
	 * @param array<string, mixed> $box  The `add_meta_box` args (carries `cpt`).
	 */
	public function renderBox( WP_Post $post, array $box ): void {
		$cpt         = (string) ( $box['args']['cpt'] ?? '' );
		$definitions = self::definitions();

		if ( ! isset( $definitions[ $cpt ] ) ) {
			return;
		}

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );

		foreach ( $definitions[ $cpt ]['fields'] as $field ) {
			$key   = $field['key'];
			$value = get_post_meta( $post->ID, $key, true );
			$id    = 'field_' . $key;

			echo '<p>';
			printf(
				'<label for="%1$s"><strong>%2$s</strong></label><br />',
				esc_attr( $id ),
				esc_html( $field['label'] )
			);

			if ( 'textarea' === $field['input'] ) {
				printf(
					'<textarea id="%1$s" name="%2$s" rows="4" class="large-text">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_textarea( (string) $value )
				);
			} elseif ( 'select' === $field['input'] ) {
				$options  = (array) ( $field['options'] ?? array() );
				$selected = (string) $value;

				// A stored value outside the option set (legacy/junk written before the
				// field existed) falls back to the first option, so the rendered selection
				// matches the effective (sanitiser-coerced) value rather than showing
				// nothing selected.
				if ( array() !== $options && ! array_key_exists( $selected, $options ) ) {
					$selected = (string) array_key_first( $options );
				}

				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $key ) );

				foreach ( $options as $opt_value => $opt_label ) {
					printf(
						'<option value="%1$s"%2$s>%3$s</option>',
						esc_attr( (string) $opt_value ),
						selected( $selected, (string) $opt_value, false ),
						esc_html( (string) $opt_label )
					);
				}

				echo '</select>';
			} else {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />',
					esc_attr( $field['input'] ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			echo '</p>';
		}
	}

	/**
	 * Persist a CPT's field set on save.
	 *
	 * The sanctioned `$_POST` path: nonce verify → autosave/revision guard →
	 * `current_user_can( 'edit_post' )` → per-field `wp_unslash` + `sanitize_*` →
	 * `update_post_meta`. Never reads a raw superglobal.
	 *
	 * @param int     $post_id The post being saved.
	 * @param WP_Post $post    The post object.
	 */
	public function save( int $post_id, WP_Post $post ): void {
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$definitions = self::definitions();

		if ( ! isset( $definitions[ $post->post_type ] ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Capability reconciliation (Story 12.3, deferred from Epic 2 review): the
		// REST write path gates the meta on the per-CPT editorial capability via the
		// register_post_meta auth_callback (current_user_can($cap)); enforce the SAME
		// capability here so the classic/meta-box save path cannot bypass the editorial
		// gate with only edit_post. The cap is granted to admin+editor at activation
		// (Story 3.3 Capabilities::grantToEditor), so editorial roles keep write access.
		if ( ! current_user_can( $definitions[ $post->post_type ]['cap'] ) ) {
			return;
		}

		foreach ( $definitions[ $post->post_type ]['fields'] as $field ) {
			$key = $field['key'];

			if ( ! isset( $_POST[ $key ] ) || ! is_scalar( $_POST[ $key ] ) ) {
				continue;
			}

			// Unslashed here, then sanitized on the next line by the field's own
			// declared sanitize callback ($field['sanitize'], e.g. absint /
			// sanitize_text_field). WPCS cannot trace the dynamic callable, so the
			// access is annotated; nonce + capability are verified above.
			$raw       = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitized on the next line via $field['sanitize'].
			$sanitized = call_user_func( $field['sanitize'], $raw );

			update_post_meta( $post_id, $key, $sanitized );
		}
	}

	/**
	 * The declarative per-CPT field-set map: CPT slug → editorial capability,
	 * Terms key for the box title, and the field list. Keyed by {@see PostTypes}
	 * slug constants (never re-typed literals).
	 *
	 * @return array<string, array{cap: string, term: string, fields: list<array{key: string, label: string, type: string, input: string, sanitize: callable, options?: array<string, string>}>}>
	 */
	private static function definitions(): array {
		return array(
			PostTypes::INKPOLS_UITGAWE => array(
				'cap'    => 'edit_posts', // No dedicated InkPols capability; editorial.
				'term'   => 'inkpols_uitgawe',
				'fields' => array(
					array(
						'key'      => self::INKPOLS_ISSUE_DATE,
						'label'    => __( 'Uitgawedatum', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'date',
						'sanitize' => array( self::class, 'sanitizeDate' ),
					),
					array(
						'key'      => self::INKPOLS_VOLUME,
						'label'    => __( 'Volume / jaargang', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					array(
						'key'      => self::INKPOLS_COVER_ID,
						'label'    => __( 'Omslagbeeld (heg-ID)', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'number',
						'sanitize' => 'absint',
					),
					array(
						'key'      => self::INKPOLS_PDF_ID,
						'label'    => __( 'PDF (heg-ID)', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'number',
						'sanitize' => 'absint',
					),
					array(
						'key'      => self::INKPOLS_TEASER,
						'label'    => __( 'Voorskou-teks', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'textarea',
						'sanitize' => 'sanitize_textarea_field',
					),
				),
			),
			PostTypes::UITDAGING       => array(
				'cap'    => Capabilities::MANAGE_CHALLENGES,
				'term'   => 'uitdaging',
				'fields' => array(
					array(
						'key'      => self::UITDAGING_THEME,
						'label'    => __( 'Tema', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					array(
						'key'      => self::UITDAGING_DEADLINE,
						'label'    => __( 'Sluitingsdatum', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'date',
						'sanitize' => array( self::class, 'sanitizeDate' ),
					),
					array(
						'key'      => self::UITDAGING_CADENCE,
						'label'    => __( 'Kadens', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'select',
						'options'  => array(
							CadenceType::Maandeliks->value => __( 'Maandeliks', 'ink-core' ),
							CadenceType::Jaarliks->value   => __( 'Jaarliks', 'ink-core' ),
						),
						'sanitize' => array( self::class, 'sanitizeCadence' ),
					),
				),
			),
			PostTypes::BORG            => array(
				'cap'    => Capabilities::MANAGE_SPONSORS,
				'term'   => 'borg',
				'fields' => array(
					array(
						'key'      => self::BORG_LINK,
						'label'    => __( 'Skakel', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'url',
						'sanitize' => 'esc_url_raw',
					),
					array(
						'key'      => self::BORG_TIER,
						'label'    => __( 'Borgvlak', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					array(
						'key'      => self::BORG_START_DATE,
						'label'    => __( 'Begindatum', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'date',
						'sanitize' => array( self::class, 'sanitizeDate' ),
					),
					array(
						'key'      => self::BORG_END_DATE,
						'label'    => __( 'Einddatum', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'date',
						'sanitize' => array( self::class, 'sanitizeDate' ),
					),
					array(
						'key'      => self::BORG_PLACEMENT,
						'label'    => __( 'Plasing', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => 'sanitize_text_field',
					),
				),
			),
		);
	}

	/**
	 * Sanitise a date string to a date-only `Y-m-d` value.
	 *
	 * INK dates — including the uitdaging deadline — are DATE ONLY: the time-of-day
	 * never matters because the SAST end-of-day boundary supplies it ({@see
	 * \Ink\Kernel\Sast::endOfDay()}; a deadline is valid through 23:59:59 SAST on its
	 * calendar day). A bare `Y-m-d` is kept; a legacy/submitted datetime shape
	 * (`Y-m-d\TH:i` or `Y-m-d H:i(:s)`) is gracefully TRUNCATED to its date portion so
	 * a value carried over from the old datetime-local input never persists a spurious
	 * time. Anything else drops to an empty string.
	 *
	 * @param mixed $value Incoming value.
	 * @return string A valid `Y-m-d` date string, or ''.
	 */
	public static function sanitizeDate( $value ): string {
		$value = sanitize_text_field( (string) $value );

		if ( 1 === preg_match( '/^(\d{4}-\d{2}-\d{2})([ T]\d{2}:\d{2}(:\d{2})?)?$/', $value, $matches ) ) {
			return $matches[1];
		}

		return '';
	}

	/**
	 * Sanitize a `uitdaging` cadence to a valid {@see CadenceType} backing value.
	 *
	 * Coerces any input through {@see CadenceType::fromMeta()} so only `maandeliks`
	 * or `jaarliks` is ever persisted; junk/empty folds to the monthly default —
	 * the single source for the value set, never an inline literal here (Story 12B.1).
	 *
	 * @param mixed $value Incoming value.
	 * @return string A valid cadence backing value (`maandeliks`/`jaarliks`).
	 */
	public static function sanitizeCadence( $value ): string {
		return CadenceType::fromMeta( is_scalar( $value ) ? (string) $value : null )->value;
	}
}
