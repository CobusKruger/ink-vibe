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
use Ink\Kernel\CompetitionType;
use Ink\I18n\Terms;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the editorial meta + admin meta boxes for the InkPols, challenge, sponsor
 * and competition CPTs (Story 2.4).
 *
 * Native: each field is `register_post_meta` (typed, REST-aware,
 * sanitised, capability-gated) AND a classic `add_meta_box` field rendered
 * server-side. The `media` and `gallery` inputs and the `repeater` (an ordered list of rows stored as one
 * JSON string, see {@see Repeater}) are the progressive enhancements:
 * `admin-media-field.js` adds the media-library buttons, and without it their real
 * input stays visible and still saves. Gutenberg renders the classic box and posts it back to
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
	public const INKPOLS_COVER_ID   = 'ink_inkpols_cover_id';
	public const INKPOLS_PDF_ID     = 'ink_inkpols_pdf_id';
	public const INKPOLS_TEASER     = 'ink_inkpols_teaser';

	// InkPols issue heading + the three featured items (WP4, `inkpols.md` §2): plain text
	// describing something in the PDF, never linked. The post title holds the naam. Since the
	// owner ruling of 2026-11-03 the items are glans-1 to glans-3 (a variety of items), each with
	// a titel, a skrywer and a free-text tipe (what the item is) in place of the hardcoded
	// Gedig/Storie/Artikel.
	public const INKPOLS_TITEL           = 'ink_inkpols_titel';
	public const INKPOLS_GLANS_1_TITEL   = 'ink_inkpols_glans_1_titel';
	public const INKPOLS_GLANS_1_SKRYWER = 'ink_inkpols_glans_1_skrywer';
	public const INKPOLS_GLANS_1_TIPE    = 'ink_inkpols_glans_1_tipe';
	public const INKPOLS_GLANS_2_TITEL   = 'ink_inkpols_glans_2_titel';
	public const INKPOLS_GLANS_2_SKRYWER = 'ink_inkpols_glans_2_skrywer';
	public const INKPOLS_GLANS_2_TIPE    = 'ink_inkpols_glans_2_tipe';
	public const INKPOLS_GLANS_3_TITEL   = 'ink_inkpols_glans_3_titel';
	public const INKPOLS_GLANS_3_SKRYWER = 'ink_inkpols_glans_3_skrywer';
	public const INKPOLS_GLANS_3_TIPE    = 'ink_inkpols_glans_3_tipe';

	/**
	 * The longest a glans `tipe` may be (characters). It is a short label ("Gedig", "Onderhoud
	 * met die redakteur"), shown in a small caps eyebrow.
	 */
	public const GLANS_TIPE_MAX = 40;

	/**
	 * The meta keys superseded by the glans fields (before 2026-11-03: exactly one Gedig, one
	 * Storie and one Artikel). Nothing reads them; `migrate-inkpols --force` deletes them.
	 *
	 * @var list<string>
	 */
	public const INKPOLS_RETIRED_KEYS = array(
		'ink_inkpols_gedig_titel',
		'ink_inkpols_gedig_skrywer',
		'ink_inkpols_storie_titel',
		'ink_inkpols_storie_skrywer',
		'ink_inkpols_artikel_titel',
		'ink_inkpols_artikel_skrywer',
	);

	// Challenge meta keys.
	public const UITDAGING_THEME    = 'ink_uitdaging_theme';
	public const UITDAGING_DEADLINE = 'ink_uitdaging_deadline';
	public const UITDAGING_CADENCE  = 'ink_uitdaging_cadence';

	// A round's results (D8): the judges' letter (blank line = new paragraph) and the
	// honourable mentions (one per line, `Titel | Skrywer`), read and corrected in the box.
	public const UITDAGING_BRIEF       = 'ink_uitdaging_brief';
	public const UITDAGING_VERMELDINGS = 'ink_uitdaging_vermeldings';

	// Up to two Opleiding entries an editor picks as "Nuttig vir hierdie uitdaging" (owner,
	// 2026-11-08). Post ids of `opleiding_artikel`; 0 = none chosen, and with none chosen the
	// section does not show.
	public const UITDAGING_OPLEIDING_1 = 'ink_uitdaging_opleiding_1';
	public const UITDAGING_OPLEIDING_2 = 'ink_uitdaging_opleiding_2';

	// A gedig's optional dedication (owner-approved 2026-11-02, spec 02 section 8). One line of
	// plain text, shown as "— text —" above the poem; empty means none, so old poems need
	// no migration. Writers enter it on /skryf/ (SubmissionForm), editors in this box.
	public const GEDIG_TOEWYDING            = 'ink_gedig_toewyding';
	public const GEDIG_TOEWYDING_MAX_LENGTH = 200;

	// Competition meta-key prefixes, used through the two key helpers below.
	public const KOMPETISIE_PREFIX = 'ink_kompetisie_';
	public const EKSTERNE_PREFIX   = 'ink_eksterne_';

	// The closed choices of the competition select fields.
	public const KOMPETISIE_IKONE = array( 'feather', 'book', 'pen' );
	public const EKSTERNE_GENRES  = array( 'gedigte', 'fiksie', 'niefiksie', 'gemeng' );

	// The category and ticket slots on a `kompetisie`.
	public const KOMPETISIE_SLOTS = 3;

	// How many photos a winner record's pick-list offers, and the transient that carries the
	// "records left out" count from the save to the next screen (per user).
	public const PHOTO_CHOICES_MAX  = 500;
	public const REJECTED_TRANSIENT = 'ink_repeater_verwerp_';

	// The longest a winner record's text cells may be (characters).
	public const WINNER_AWARD_MAX = 200;
	public const WINNER_NAME_MAX  = 120;
	public const WINNER_NOTE_MAX  = 1000;

	// The feature-photo slots on a `kompetisie`'s gala: one wide, four small (owner, 2026-11-08).
	public const GALA_FOTO_SLOTS = 5;

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
				// A field may advertise a constrained REST schema (e.g. an `enum`) so the
				// REST surface matches what the sanitiser actually persists; absent that,
				// `show_in_rest` stays the plain boolean (the unconstrained-string default).
				$show_in_rest = isset( $field['rest_schema'] )
					? array( 'schema' => $field['rest_schema'] )
					: true;

				// A field may declare an explicit 'default' (required whenever a
				// `rest_schema.enum` is present, since '' / 0 is not necessarily a member
				// of that closed value set — WordPress's own register_meta() schema
				// validation rejects a default outside the enum). Absent that, fall back to
				// the plain per-type default, which is always valid for an unconstrained
				// 'integer' or 'string' field.
				$default = $field['default'] ?? ( 'integer' === $field['type'] ? 0 : '' );

				register_post_meta(
					$cpt,
					$field['key'],
					array(
						'single'            => true,
						'type'              => $field['type'],
						'show_in_rest'      => $show_in_rest,
						'default'           => $default,
						'sanitize_callback' => $field['sanitize'],
						'auth_callback'     => static fn (): bool => current_user_can( $cap ),
					)
				);
			}
		}

		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxes' ) );
		add_action( 'save_post', array( $this, 'save' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueueMediaField' ) );
		add_action( 'admin_notices', array( $this, 'rejectedNotice' ) );
	}

	/**
	 * A `kompetisie` meta key from its suffix (`05-kompetisies` §3.2). Pure.
	 *
	 * @param string $suffix The key suffix, e.g. `gala_fotos`.
	 * @return string
	 */
	public static function kompetisieKey( string $suffix ): string {
		return self::KOMPETISIE_PREFIX . $suffix;
	}

	/**
	 * An `eksterne_kompetisie` meta key from its suffix (`05-kompetisies` §3.3). Pure.
	 *
	 * @param string $suffix The key suffix, e.g. `fooi`.
	 * @return string
	 */
	public static function eksterneKey( string $suffix ): string {
		return self::EKSTERNE_PREFIX . $suffix;
	}

	/**
	 * A field's admin label, or the key itself when it is not a field. Pure.
	 *
	 * @param string $key The meta key.
	 * @return string
	 */
	public static function labelFor( string $key ): string {
		foreach ( self::definitions() as $def ) {
			foreach ( $def['fields'] as $field ) {
				if ( $key === $field['key'] ) {
					return $field['label'];
				}
			}
		}

		return $key;
	}

	/**
	 * Whether a post type's box uses the media-library inputs. Pure.
	 *
	 * @param string $cpt The post type.
	 * @return bool
	 */
	public static function usesMediaInputs( string $cpt ): bool {
		foreach ( self::definitions()[ $cpt ]['fields'] ?? array() as $field ) {
			if ( in_array( $field['input'], array( 'media', 'gallery' ), true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether a post type's box uses a repeater input. Pure.
	 *
	 * @param string $cpt The post type.
	 * @return bool
	 */
	public static function usesRepeaterInputs( string $cpt ): bool {
		foreach ( self::definitions()[ $cpt ]['fields'] ?? array() as $field ) {
			if ( 'repeater' === $field['input'] ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Load the media library and the field script on the edit screens that need them.
	 *
	 * @param string $hook The admin page hook.
	 */
	public function enqueueMediaField( string $hook ): void {
		if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}

		$screen = get_current_screen();

		if ( null === $screen ) {
			return;
		}

		if ( self::usesRepeaterInputs( (string) $screen->post_type ) ) {
			wp_enqueue_script( 'ink-admin-repeater-field', plugins_url( 'assets/js/admin-repeater-field.js', INK_CORE_FILE ), array(), INK_CORE_VERSION, true );
		}

		if ( ! self::usesMediaInputs( (string) $screen->post_type ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'ink-admin-media-field', plugins_url( 'assets/js/admin-media-field.js', INK_CORE_FILE ), array(), INK_CORE_VERSION, true );
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

			if ( 'repeater' === $field['input'] ) {
				printf( '<p><strong>%s</strong></p>', esc_html( $field['label'] ) );
				$this->renderRepeater( $post, $key, (array) ( $field['columns'] ?? array() ), (string) $value );
				continue;
			}

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
			} elseif ( 'opleiding' === $field['input'] ) {
				// A pick-list of published Opleiding entries, so editors choose rather than type an id.
				$chosen = (int) $value;
				$items  = get_posts(
					array(
						'post_type'      => PostTypes::OPLEIDING_ARTIKEL,
						'post_status'    => 'publish',
						'posts_per_page' => -1,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'no_found_rows'  => true,
					)
				);

				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $key ) );
				printf( '<option value="0">%s</option>', esc_html__( '— Geen —', 'ink-core' ) );

				foreach ( is_array( $items ) ? $items : array() as $item ) {
					if ( $item instanceof WP_Post ) {
						printf(
							'<option value="%1$d"%2$s>%3$s</option>',
							(int) $item->ID,
							selected( $chosen, (int) $item->ID, false ),
							esc_html( get_the_title( $item ) )
						);
					}
				}

				echo '</select>';
			} elseif ( 'media' === $field['input'] || 'gallery' === $field['input'] ) {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" data-ink-media="%5$s" data-ink-media-kies="%6$s" data-ink-media-verwyder="%7$s" />',
					'media' === $field['input'] ? 'number' : 'text',
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value ),
					esc_attr( $field['input'] ),
					esc_attr__( 'Kies', 'ink-core' ),
					esc_attr__( 'Verwyder', 'ink-core' )
				);
			} else {
				printf(
					'<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="regular-text" />',
					esc_attr( $field['input'] ),
					esc_attr( $id ),
					esc_attr( $key ),
					esc_attr( (string) $value )
				);
			}

			if ( PostTypes::KOMPETISIE === $cpt && self::kompetisieKey( 'gala_fotos' ) === $key && $post->ID > 0 ) {
				$attached = get_posts(
					array(
						'post_type'      => 'attachment',
						'post_status'    => 'inherit',
						'post_parent'    => $post->ID,
						'post_mime_type' => 'image,video',
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'no_found_rows'  => true,
					)
				);

				printf(
					'<br /><em>%s</em>',
					esc_html(
						sprintf(
							/* translators: %d: how many photos were uploaded to this post. */
							__( '%d foto\'s en video\'s is op hierdie bladsy opgelaai. Sonder \'n lys hierbo word almal gewys, volgens lêernaam.', 'ink-core' ),
							is_array( $attached ) ? count( $attached ) : 0
						)
					)
				);
			}

			echo '</p>';
		}
	}

	/**
	 * Render a repeater: its rows in order, a blank row to fill, and a hidden template the script
	 * clones. Works without JS (the rows save in page order); `admin-repeater-field.js` adds the
	 * add, remove and up/down buttons. Cells are posted as `key[n][column]`.
	 *
	 * @param WP_Post                                                      $post    The post.
	 * @param string                                                       $key     The meta key.
	 * @param array<int, array{key: string, label: string, input: string}> $columns The columns.
	 * @param string                                                       $stored  The stored JSON.
	 */
	private function renderRepeater( WP_Post $post, string $key, array $columns, string $stored ): void {
		$rows    = Repeater::decode( $stored );
		$rows[]  = array();
		$choices = $this->photoChoices( $post );

		printf(
			'<div class="ink-repeater" data-ink-repeater="%1$s" data-ink-op="%2$s" data-ink-af="%3$s" data-ink-verwyder="%4$s"><input type="hidden" name="%1$s[%5$s]" value="1" /><div class="ink-repeater__rye">',
			esc_attr( $key ),
			esc_attr__( 'Op', 'ink-core' ),
			esc_attr__( 'Af', 'ink-core' ),
			esc_attr__( 'Verwyder', 'ink-core' ),
			esc_attr( Repeater::PRESENT )
		);

		foreach ( $rows as $n => $row ) {
			$this->renderRepeaterRow( $key, $columns, (string) $n, $row, $choices );
		}

		echo '</div><template class="ink-repeater__sjabloon">';
		$this->renderRepeaterRow( $key, $columns, '__i__', array(), $choices );
		echo '</template>';
		printf( '<p><button type="button" class="button" data-ink-repeater-by>%s</button></p></div>', esc_html__( 'Voeg \'n ry by', 'ink-core' ) );
	}

	/**
	 * One repeater row.
	 *
	 * @param string                                                       $key     The meta key.
	 * @param array<int, array{key: string, label: string, input: string}> $columns The columns.
	 * @param string                                                       $index   The row index in the posted name.
	 * @param array<string, scalar>                                        $row     The row values.
	 * @param array<int, string>                                           $choices Photo id to label.
	 */
	private function renderRepeaterRow( string $key, array $columns, string $index, array $row, array $choices ): void {
		echo '<fieldset class="ink-repeater__ry" style="border:1px solid #c3c4c7;padding:8px;margin:0 0 8px;">';

		foreach ( $columns as $column ) {
			$name  = sprintf( '%1$s[%2$s][%3$s]', $key, $index, $column['key'] );
			$value = isset( $row[ $column['key'] ] ) ? (string) $row[ $column['key'] ] : '';

			printf( '<p style="margin:0 0 6px;"><label><strong>%s</strong><br />', esc_html( $column['label'] ) );

			if ( 'textarea' === $column['input'] ) {
				printf( '<textarea name="%1$s" rows="2" class="large-text">%2$s</textarea>', esc_attr( $name ), esc_textarea( $value ) );
			} elseif ( 'photo' === $column['input'] ) {
				$options = $choices;

				if ( (int) $value > 0 && ! isset( $options[ (int) $value ] ) ) {
					$options[ (int) $value ] = '#' . (int) $value;
				}

				printf( '<select name="%s">', esc_attr( $name ) );
				printf( '<option value="0">%s</option>', esc_html__( '— Geen foto —', 'ink-core' ) );

				foreach ( $options as $id => $label ) {
					printf( '<option value="%1$d"%2$s>%3$s</option>', (int) $id, selected( (int) $value, (int) $id, false ), esc_html( $label ) );
				}

				echo '</select>';
			} else {
				printf( '<input type="%1$s" name="%2$s" value="%3$s" class="regular-text"%4$s />', esc_attr( $column['input'] ), esc_attr( $name ), esc_attr( $value ), 'number' === $column['input'] ? ' min="0"' : '' );
			}

			echo '</label></p>';
		}

		echo '</fieldset>';
	}

	/**
	 * The photos a winner record may pick: the edition's gala gallery, its five feature photos,
	 * and the images uploaded to the post. Labelled by the attachment title.
	 *
	 * @param WP_Post $post The post.
	 * @return array<int, string>
	 */
	private function photoChoices( WP_Post $post ): array {
		$ids = array_map( 'intval', explode( ',', self::sanitizeIdList( get_post_meta( $post->ID, self::kompetisieKey( 'gala_fotos' ), true ) ) ) );

		for ( $n = 1; $n <= self::GALA_FOTO_SLOTS; $n++ ) {
			$ids[] = (int) get_post_meta( $post->ID, self::kompetisieKey( "gala_foto{$n}" ), true );
		}

		if ( $post->ID > 0 ) {
			$attached = get_posts(
				array(
					'post_type'      => 'attachment',
					'post_status'    => 'inherit',
					'post_parent'    => $post->ID,
					'post_mime_type' => 'image',
					'posts_per_page' => self::PHOTO_CHOICES_MAX,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'fields'         => 'ids',
					'no_found_rows'  => true,
				)
			);
			$ids      = array_merge( $ids, is_array( $attached ) ? array_map( 'intval', $attached ) : array() );
		}

		$out = array();

		foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, self::PHOTO_CHOICES_MAX ) as $id ) {
			$title      = (string) get_the_title( $id );
			$out[ $id ] = '' !== $title ? $title : '#' . $id;
		}

		return $out;
	}

	/**
	 * Tell the editor that records were left out on the last save (once).
	 */
	public function rejectedNotice(): void {
		$key  = self::REJECTED_TRANSIENT . get_current_user_id();
		$left = (int) get_transient( $key );

		if ( $left < 1 ) {
			return;
		}

		delete_transient( $key );
		printf(
			'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %d: how many winner records were left out. */
					_n( '%d wenner is nie gestoor nie: elke wenner het \'n toekenning en \'n gebruiker of \'n naam nodig.', '%d wenners is nie gestoor nie: elke wenner het \'n toekenning en \'n gebruiker of \'n naam nodig.', $left, 'ink-core' ),
					$left
				)
			)
		);
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

			if ( 'repeater' === $field['input'] ) {
				$this->saveRepeater( $post_id, $field );
				continue;
			}

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
	 * Persist one repeater: the posted rows through the field's sanitiser, stored as JSON. Runs
	 * inside {@see self::save()}, after its nonce and capability checks. Only when the repeater
	 * was on the screen (its marker was posted), so a save from elsewhere never clears it.
	 *
	 * @param int                  $post_id The post.
	 * @param array<string, mixed> $field   The field definition.
	 */
	private function saveRepeater( int $post_id, array $field ): void {
		$key = (string) $field['key'];

		if ( ! isset( $_POST[ $key ] ) || ! is_array( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in save().
			return;
		}

		$raw  = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in save(); sanitised on the next line via $field['sanitize'].
		$json = (string) call_user_func( $field['sanitize'], $raw );
		$left = count( Repeater::rows( $raw ) ) - count( Repeater::decode( $json ) );

		if ( $left > 0 ) {
			set_transient( self::REJECTED_TRANSIENT . get_current_user_id(), $left, MINUTE_IN_SECONDS );
		}

		if ( '' === $json ) {
			delete_post_meta( $post_id, $key );

			return;
		}

		// update_post_meta() strips one level of slashes, which would eat the JSON's own.
		update_post_meta( $post_id, $key, wp_slash( $json ) );
	}

	/**
	 * The declarative per-CPT field-set map: CPT slug → editorial capability,
	 * Terms key for the box title, and the field list. Keyed by {@see PostTypes}
	 * slug constants (never re-typed literals).
	 *
	 * @return array<string, array{cap: string, term: string, fields: list<array{key: string, label: string, type: string, input: string, sanitize: callable, options?: array<string, string>, rest_schema?: array<string, mixed>, default?: mixed, columns?: list<array{key: string, label: string, input: string, sanitize: callable}>}>}>
	 */
	private static function definitions(): array {
		return array(
			PostTypes::INKPOLS_UITGAWE     => array(
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
						'key'      => self::INKPOLS_COVER_ID,
						'label'    => __( 'Omslagbeeld (heg-ID)', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'media',
						'sanitize' => 'absint',
					),
					array(
						'key'      => self::INKPOLS_PDF_ID,
						'label'    => __( 'PDF (heg-ID)', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'media',
						'sanitize' => 'absint',
					),
					array(
						'key'      => self::INKPOLS_TEASER,
						'label'    => __( 'Voorskou-teks', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'textarea',
						'sanitize' => 'sanitize_textarea_field',
					),
					array(
						'key'      => self::INKPOLS_TITEL,
						'label'    => __( 'Titel', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => 'sanitize_text_field',
					),
					...self::glansFields(),
				),
			),
			PostTypes::UITDAGING           => array(
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
						'key'         => self::UITDAGING_CADENCE,
						'label'       => __( 'Kadens', 'ink-core' ),
						'type'        => 'string',
						'input'       => 'select',
						'options'     => array(
							CadenceType::Maandeliks->value => __( 'Maandeliks', 'ink-core' ),
							CadenceType::Jaarliks->value   => __( 'Jaarliks', 'ink-core' ),
						),
						'sanitize'    => array( self::class, 'sanitizeCadence' ),
						// REST advertises the same closed value set the sanitiser enforces
						// (maandeliks/jaarliks) — the CadenceType backing values, the single
						// source — so a REST writer is constrained, not just silently coerced.
						'rest_schema' => array(
							'type' => 'string',
							'enum' => CadenceType::values(),
						),
						// Explicit — an unconstrained '' default is not a member of the
						// `rest_schema.enum` above, and register_meta() rejects that
						// mismatch. CadenceType::default() (monthly) is the single source,
						// never an inline 'maandeliks' literal; mirrors CadenceType::fromMeta().
						'default'     => CadenceType::default()->value,
					),
					array(
						'key'      => self::UITDAGING_BRIEF,
						'label'    => __( 'Skei paragrawe met \'n blanko reël.', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'textarea',
						'sanitize' => 'sanitize_textarea_field',
					),
					array(
						'key'      => self::UITDAGING_VERMELDINGS,
						'label'    => __( 'Eervolle vermelding', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'textarea',
						'sanitize' => 'sanitize_textarea_field',
					),
					array(
						'key'      => self::UITDAGING_OPLEIDING_1,
						'label'    => __( 'Nuttige opleiding 1', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'opleiding',
						'sanitize' => 'absint',
					),
					array(
						'key'      => self::UITDAGING_OPLEIDING_2,
						'label'    => __( 'Nuttige opleiding 2', 'ink-core' ),
						'type'     => 'integer',
						'input'    => 'opleiding',
						'sanitize' => 'absint',
					),
				),
			),
			PostTypes::BORG                => array(
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
			PostTypes::GEDIG               => array(
				'cap'    => 'edit_' . PostTypes::CAPABILITY_TYPE_PLURAL,
				'term'   => 'gedig',
				'fields' => array(
					array(
						'key'      => self::GEDIG_TOEWYDING,
						'label'    => __( 'Opgedra aan', 'ink-core' ),
						'type'     => 'string',
						'input'    => 'text',
						'sanitize' => array( self::class, 'sanitizeToewyding' ),
					),
				),
			),
			PostTypes::KOMPETISIE          => array(
				'cap'    => 'edit_' . PostTypes::CAPABILITY_TYPE_PLURAL,
				'term'   => 'kompetisie',
				'fields' => self::kompetisieFields(),
			),
			PostTypes::EKSTERNE_KOMPETISIE => array(
				'cap'    => 'edit_' . PostTypes::CAPABILITY_TYPE_PLURAL,
				'term'   => 'eksterne_kompetisie',
				'fields' => self::eksterneFields(),
			),
		);
	}

	/**
	 * The three featured items' meta keys: item number (1 to 3) → titel / skrywer / tipe. Pure.
	 *
	 * @return array<int, array{titel:string, skrywer:string, tipe:string}>
	 */
	public static function glansKeys(): array {
		return array(
			1 => array(
				'titel'   => self::INKPOLS_GLANS_1_TITEL,
				'skrywer' => self::INKPOLS_GLANS_1_SKRYWER,
				'tipe'    => self::INKPOLS_GLANS_1_TIPE,
			),
			2 => array(
				'titel'   => self::INKPOLS_GLANS_2_TITEL,
				'skrywer' => self::INKPOLS_GLANS_2_SKRYWER,
				'tipe'    => self::INKPOLS_GLANS_2_TIPE,
			),
			3 => array(
				'titel'   => self::INKPOLS_GLANS_3_TITEL,
				'skrywer' => self::INKPOLS_GLANS_3_SKRYWER,
				'tipe'    => self::INKPOLS_GLANS_3_TIPE,
			),
		);
	}

	/**
	 * The nine glans fields for the issue box, in display order. `tipe` is capped to one line.
	 *
	 * @return list<array{key: string, label: string, type: string, input: string, sanitize: callable}>
	 */
	private static function glansFields(): array {
		$fields = array();

		foreach ( self::glansKeys() as $n => $keys ) {
			/* translators: %d: the featured item's number (1 to 3). */
			$titel = sprintf( __( 'Glans %d: titel', 'ink-core' ), $n );
			/* translators: %d: the featured item's number (1 to 3). */
			$skrywer = sprintf( __( 'Glans %d: skrywer', 'ink-core' ), $n );
			/* translators: %d: the featured item's number (1 to 3). */
			$tipe = sprintf( __( 'Glans %d: tipe', 'ink-core' ), $n );

			$fields[] = self::textField( $keys['titel'], $titel );
			$fields[] = self::textField( $keys['skrywer'], $skrywer );
			$fields[] = self::textField( $keys['tipe'], $tipe, 'text', array( self::class, 'sanitizeTipe' ) );
		}

		return $fields;
	}

	/**
	 * A glans `tipe`: one line of plain text, at most {@see GLANS_TIPE_MAX} characters. Pure.
	 *
	 * @param mixed $value The raw value.
	 */
	public static function sanitizeTipe( $value ): string {
		$text = sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );

		return trim( mb_substr( $text, 0, self::GLANS_TIPE_MAX ) );
	}

	/**
	 * One text-shaped field. Pure.
	 *
	 * @param string $key      The meta key.
	 * @param string $label    The admin label.
	 * @param string $input    The input type (`text`, `textarea`, `date`, `url`).
	 * @param mixed  $sanitize The sanitise callback; null picks the input's own.
	 * @return array{key: string, label: string, type: string, input: string, sanitize: callable}
	 */
	private static function textField( string $key, string $label, string $input = 'text', $sanitize = null ): array {
		$defaults = array(
			'textarea' => 'sanitize_textarea_field',
			'date'     => array( self::class, 'sanitizeDate' ),
			'url'      => 'esc_url_raw',
		);

		return array(
			'key'      => $key,
			'label'    => $label,
			'type'     => 'string',
			'input'    => $input,
			'sanitize' => $sanitize ?? ( $defaults[ $input ] ?? 'sanitize_text_field' ),
		);
	}

	/**
	 * The `kompetisie` fields, in `05-kompetisies` §3.2's order. Labels are copy-debt
	 * markers until the owner has the Afrikaans. The post title is the naam.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function kompetisieFields(): array {
		$category = __( 'Kategorie', 'ink-core' );
		$slot     = static fn ( string $noun, int $n, string $what ): string => sprintf( '%1$s %2$d: %3$s', $noun, $n, $what );

		$fields = array(
			array(
				'key'      => self::kompetisieKey( 'standaard' ),
				'label'    => __( 'Verstek-aankondiging (wys dit tot daar \'n kompetisie oop is; geen inskrywing nie)', 'ink-core' ),
				'type'     => 'string',
				'input'    => 'select',
				'options'  => array(
					'nee' => __( 'Nee, dis \'n gewone kompetisie', 'ink-core' ),
					'ja'  => __( 'Ja, dis die verstek-aankondiging', 'ink-core' ),
				),
				'sanitize' => array( self::class, 'sanitizeDefaultFlag' ),
				'default'  => 'nee',
			),
			array(
				// Copy-debt: admin labels for tipe, vennoot_naam and oopdatum await the owner's Afrikaans.
				'key'      => self::kompetisieKey( 'tipe' ),
				'label'    => __( 'Tipe kompetisie', 'ink-core' ),
				'type'     => 'string',
				'input'    => 'select',
				'options'  => array(
					CompetitionType::Jaarliks->value => __( 'Jaarliks (met gala)', 'ink-core' ),
					CompetitionType::Vennoot->value  => __( 'Saam met \'n vennoot (geen gala)', 'ink-core' ),
				),
				'sanitize' => array( self::class, 'sanitizeCompetitionType' ),
				'default'  => CompetitionType::default()->value,
			),
			self::textField( self::kompetisieKey( 'vennoot_naam' ), __( 'Vennoot-organisasie', 'ink-core' ) ),
			array(
				'key'      => self::kompetisieKey( 'jaar' ),
				'label'    => __( 'jaar', 'ink-core' ),
				'type'     => 'integer',
				'input'    => 'number',
				'sanitize' => 'absint',
			),
			self::textField( self::kompetisieKey( 'uitgawe' ), __( 'Edisie', 'ink-core' ) ),
			self::textField( self::kompetisieKey( 'beskrywing' ), __( 'Beskrywing', 'ink-core' ), 'textarea' ),
			self::textField( self::kompetisieKey( 'oopdatum' ), __( 'Oopdatum', 'ink-core' ), 'date' ),
			self::textField( self::kompetisieKey( 'sluitingsdatum' ), __( 'Sluitingsdatum', 'ink-core' ), 'date' ),
			self::textField( self::kompetisieKey( 'inskrywing_nota' ), __( 'Inskrywing', 'ink-core' ) ),
			self::textField( self::kompetisieKey( 'naaswenner_nota' ), __( 'Naaswenner', 'ink-core' ) ),
			self::textField( self::kompetisieKey( 'kat_titel' ), __( 'Kategorieë: opskrif (leeg = die standaardopskrif)', 'ink-core' ) ),
			self::textField( self::kompetisieKey( 'kat_inleiding' ), __( 'Kategorieë: inleiding (leeg = die standaardteks)', 'ink-core' ), 'textarea' ),
		);

		for ( $n = 1; $n <= self::KOMPETISIE_SLOTS; $n++ ) {
			$fields[] = self::textField( self::kompetisieKey( "kat{$n}_naam" ), $slot( $category, $n, __( 'naam', 'ink-core' ) ) );
			$fields[] = array(
				'key'      => self::kompetisieKey( "kat{$n}_ikoon" ),
				'label'    => $slot( $category, $n, __( 'Ikoon', 'ink-core' ) ),
				'type'     => 'string',
				'input'    => 'select',
				'options'  => array(
					'feather' => __( 'Veertjie-ikoon', 'ink-core' ),
					'book'    => __( 'Boek-ikoon', 'ink-core' ),
					'pen'     => __( 'Pen-ikoon', 'ink-core' ),
				),
				'sanitize' => array( self::class, 'sanitizeIcon' ),
				'default'  => self::KOMPETISIE_IKONE[0],
			);
			$fields[] = self::textField( self::kompetisieKey( "kat{$n}_tema" ), $slot( $category, $n, __( 'Tema', 'ink-core' ) ) );
			$fields[] = self::textField( self::kompetisieKey( "kat{$n}_beskrywing" ), $slot( $category, $n, __( 'Beskrywing', 'ink-core' ) ), 'textarea' );
			$fields[] = self::textField( self::kompetisieKey( "kat{$n}_woordlimiet" ), $slot( $category, $n, __( 'Woordbeperking', 'ink-core' ) ) );
			$fields[] = self::textField( self::kompetisieKey( "kat{$n}_riglyne" ), $slot( $category, $n, __( 'Riglyne', 'ink-core' ) ), 'textarea' );
		}

		$fields[] = self::textField( self::kompetisieKey( 'gala_naam' ), __( 'Naam', 'ink-core' ) );
		$fields[] = self::textField( self::kompetisieKey( 'gala_beskrywing' ), __( 'Beskrywing', 'ink-core' ), 'textarea' );
		$fields[] = self::textField( self::kompetisieKey( 'gala_datum' ), __( 'Datum', 'ink-core' ), 'date' );
		$fields[] = self::textField( self::kompetisieKey( 'gala_deure' ), __( 'Deure open', 'ink-core' ) );
		$fields[] = self::textField( self::kompetisieKey( 'gala_plek' ), __( 'Venue', 'ink-core' ) );
		$fields[] = self::textField( self::kompetisieKey( 'gala_drag' ), __( 'Drag', 'ink-core' ) );
		$fields[] = self::textField( self::kompetisieKey( 'gala_program' ), __( 'Program', 'ink-core' ), 'textarea' );

		for ( $n = 1; $n <= self::KOMPETISIE_SLOTS; $n++ ) {
			$fields[] = self::textField( self::kompetisieKey( "kaartjie{$n}_etiket" ), $slot( __( 'Kaartjie', 'ink-core' ), $n, __( 'naam', 'ink-core' ) ) );
			// D4: the price is fictional until payments exist. This label is where that is said.
			$fields[] = self::textField( self::kompetisieKey( "kaartjie{$n}_prys" ), $slot( __( 'Kaartjie', 'ink-core' ), $n, __( 'Kaartjieprys', 'ink-core' ) ) );
		}

		$fields[] = self::textField( self::kompetisieKey( 'gala_hoogtepunt' ), __( 'Hoogtepunt', 'ink-core' ) );

		// The five photos the page shows, picked and captioned here. Slot 1 is the wide one.
		for ( $n = 1; $n <= self::GALA_FOTO_SLOTS; $n++ ) {
			$fields[] = array(
				'key'      => self::kompetisieKey( "gala_foto{$n}" ),
				'label'    => sprintf( '%1$s %2$d: %3$s', __( 'Uitgesoekte foto', 'ink-core' ), $n, 1 === $n ? __( 'die breë foto bo-aan', 'ink-core' ) : __( 'foto', 'ink-core' ) ),
				'type'     => 'integer',
				'input'    => 'media',
				'sanitize' => 'absint',
			);
			$fields[] = self::textField( self::kompetisieKey( "gala_foto{$n}_onderskrif" ), sprintf( '%1$s %2$d: %3$s', __( 'Uitgesoekte foto', 'ink-core' ), $n, __( 'onderskrif', 'ink-core' ) ) );
		}

		// Every photo of the gala, for "Sien alles". The imported galas have theirs from the old Fotos Pages.
		$fields[] = array(
			'key'      => self::kompetisieKey( 'gala_fotos' ),
			'label'    => __( 'Alle foto\'s van die gala: laat leeg en laai die foto\'s op hierdie bladsy op (Voeg media by), of kies uit die biblioteek', 'ink-core' ),
			'type'     => 'string',
			'input'    => 'gallery',
			'sanitize' => array( self::class, 'sanitizeIdList' ),
		);

		// Results (K4, `05-kompetisies` §14.4). Copy-debt: these admin labels await the owner's Afrikaans.
		// Publishing is one flag, like `standaard`: `ja` makes the edition's state `results` and the
		// results route exist. The records below it are an ordered list, one award per record.
		$fields[] = array(
			'key'      => self::kompetisieKey( 'uitslae_gepubliseer' ),
			'label'    => __( 'Uitslae', 'ink-core' ),
			'type'     => 'string',
			'input'    => 'select',
			'options'  => array(
				'nee' => __( 'Nog nie gepubliseer nie', 'ink-core' ),
				'ja'  => __( 'Gepubliseer: wys die uitslaebladsy', 'ink-core' ),
			),
			'sanitize' => array( self::class, 'sanitizeResultsFlag' ),
			'default'  => 'nee',
		);
		$fields[] = self::textField( self::kompetisieKey( 'uitslae_inleiding' ), __( 'Uitslae: inleiding (blanko reël tussen paragrawe)', 'ink-core' ), 'textarea' );
		// A migrated old results post, reused as it is (not parsed): shown on the results page after the intro.
		$fields[] = self::textField( self::kompetisieKey( 'uitslae_oud' ), __( 'Uitslae: ou berig (HTML van die ou webwerf; kan gewysig word)', 'ink-core' ), 'textarea', 'wp_kses_post' );
		$fields[] = array(
			'key'      => self::kompetisieKey( 'wenners' ),
			'label'    => __( 'Wenners (in die volgorde waarin hulle gewys word)', 'ink-core' ),
			'type'     => 'string',
			'input'    => 'repeater',
			'sanitize' => array( self::class, 'sanitizeWinners' ),
			'columns'  => self::winnerColumns(),
		);

		return $fields;
	}

	/**
	 * The columns of one winner record, in form order (K4). `photo` is a pick-list of the
	 * edition's gala photos. Copy-debt labels.
	 *
	 * @return list<array{key: string, label: string, input: string, sanitize: callable}>
	 */
	private static function winnerColumns(): array {
		return array(
			array(
				'key'      => 'toekenning',
				'label'    => __( 'Toekenning (vereis)', 'ink-core' ),
				'input'    => 'text',
				'sanitize' => static fn ( $value ): string => self::clipLine( $value, self::WINNER_AWARD_MAX ),
			),
			array(
				'key'      => 'wenner_gebruiker',
				'label'    => __( 'Wenner se gebruiker-ID (opsioneel)', 'ink-core' ),
				'input'    => 'number',
				'sanitize' => 'absint',
			),
			array(
				'key'      => 'wenner_naam',
				'label'    => __( 'Wenner se naam (vereis sonder gebruiker)', 'ink-core' ),
				'input'    => 'text',
				'sanitize' => static fn ( $value ): string => self::clipLine( $value, self::WINNER_NAME_MAX ),
			),
			array(
				'key'      => 'inskrywing',
				'label'    => __( 'Inskrywing of werk (opsioneel)', 'ink-core' ),
				'input'    => 'text',
				'sanitize' => static fn ( $value ): string => self::clipLine( $value, self::WINNER_AWARD_MAX ),
			),
			array(
				'key'      => 'foto',
				'label'    => __( 'Foto (opsioneel)', 'ink-core' ),
				'input'    => 'photo',
				'sanitize' => 'absint',
			),
			array(
				'key'      => 'aantekening',
				'label'    => __( 'Aantekening (opsioneel)', 'ink-core' ),
				'input'    => 'textarea',
				'sanitize' => static fn ( $value ): string => self::clipText( $value, self::WINNER_NOTE_MAX ),
			),
		);
	}

	/**
	 * Sanitise the winner records to their stored JSON (K4): each column through its own
	 * callback, a linked user that no longer exists dropped to none, and a record kept only with
	 * an award and a user or a name ({@see \Ink\Competitions\Winner::fromRow()}). Takes the posted
	 * array or a JSON string (REST). Order is kept.
	 *
	 * @param mixed $value Incoming value.
	 * @return string JSON, or '' for no winners.
	 */
	public static function sanitizeWinners( $value ): string {
		$columns = array();

		foreach ( self::winnerColumns() as $column ) {
			$columns[ $column['key'] ] = $column['sanitize'];
		}

		$rows = array();

		foreach ( Repeater::clean( $value, $columns, static fn (): bool => true ) as $row ) {
			// A user that does not exist must not satisfy "user or name".
			if ( (int) $row['wenner_gebruiker'] > 0 && ! get_userdata( (int) $row['wenner_gebruiker'] ) ) {
				$row['wenner_gebruiker'] = 0;
			}

			if ( self::isWinnerRecord( $row ) ) {
				$rows[] = $row;
			}
		}

		return Repeater::encode( $rows );
	}

	/**
	 * Whether a winner record is complete: an award, and a linked user or a name. Pure; the one
	 * rule, used on save here and on read by {@see \Ink\Competitions\Winner::fromRow()}.
	 *
	 * @param array<string, mixed> $row A record with the {@see self::winnerColumns()} keys.
	 * @return bool
	 */
	public static function isWinnerRecord( array $row ): bool {
		$text = static fn ( string $column ): string => isset( $row[ $column ] ) && is_scalar( $row[ $column ] ) ? trim( (string) $row[ $column ] ) : '';

		return '' !== $text( 'toekenning' ) && ( (int) $text( 'wenner_gebruiker' ) > 0 || '' !== $text( 'wenner_naam' ) );
	}

	/**
	 * One line of plain text, cut to a length. Pure.
	 *
	 * @param mixed $value Incoming value.
	 * @param int   $max   The most characters.
	 * @return string
	 */
	public static function clipLine( $value, int $max ): string {
		return trim( mb_substr( trim( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) ), 0, $max ) );
	}

	/**
	 * Plain text that may span lines, cut to a length. Pure.
	 *
	 * @param mixed $value Incoming value.
	 * @param int   $max   The most characters.
	 * @return string
	 */
	public static function clipText( $value, int $max ): string {
		return trim( mb_substr( trim( sanitize_textarea_field( is_scalar( $value ) ? (string) $value : '' ) ), 0, $max ) );
	}

	/**
	 * Sanitise the results-published flag: `ja`, or anything else is `nee`.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeResultsFlag( $value ): string {
		return 'ja' === $value ? 'ja' : 'nee';
	}

	/**
	 * The `eksterne_kompetisie` fields (`05-kompetisies` §3.3). The post title is the name.
	 *
	 * @return list<array<string, mixed>>
	 */
	private static function eksterneFields(): array {
		return array(
			self::textField( self::eksterneKey( 'organiseerder' ), __( 'Organiseerder', 'ink-core' ) ),
			self::textField( self::eksterneKey( 'streek' ), __( 'Streek', 'ink-core' ) ),
			// Plain text, because a recurring closing date carries no year.
			self::textField( self::eksterneKey( 'sluitingsdatum' ), __( 'Sluitingsdatum', 'ink-core' ) ),
			self::textField( self::eksterneKey( 'prys' ), __( 'Prys', 'ink-core' ) ),
			self::textField( self::eksterneKey( 'fooi' ), __( 'Inskrywingsfooi', 'ink-core' ) ),
			array(
				'key'      => self::eksterneKey( 'genre' ),
				'label'    => __( 'Genre', 'ink-core' ),
				'type'     => 'string',
				'input'    => 'select',
				'options'  => array(
					'gedigte'   => __( 'Poësie', 'ink-core' ),
					'fiksie'    => __( 'Fiksie', 'ink-core' ),
					'niefiksie' => __( 'Nie-fiksie', 'ink-core' ),
					'gemeng'    => __( 'Gemeng', 'ink-core' ),
				),
				'sanitize' => array( self::class, 'sanitizeGenre' ),
				'default'  => self::EKSTERNE_GENRES[0],
			),
			self::textField( self::eksterneKey( 'url' ), __( 'Skakel', 'ink-core' ), 'url' ),
			self::textField( self::eksterneKey( 'nota' ), __( 'Nota', 'ink-core' ), 'textarea' ),
		);
	}

	/**
	 * Sanitise a gallery to a comma-separated attachment-id list, keeping the media-modal
	 * order and dropping zeros and repeats.
	 *
	 * @param mixed $value Incoming value.
	 * @return string `12,7,33`, or ''.
	 */
	public static function sanitizeIdList( $value ): string {
		$ids = array_filter( array_map( 'absint', explode( ',', is_scalar( $value ) ? (string) $value : '' ) ) );

		return implode( ',', array_unique( $ids ) );
	}

	/**
	 * Sanitise the default-announcement flag: `ja`, or anything else is `nee`.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeDefaultFlag( $value ): string {
		return 'ja' === $value ? 'ja' : 'nee';
	}

	/**
	 * Sanitise the competition type to the closed set; blank or junk is annual.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeCompetitionType( $value ): string {
		return CompetitionType::fromMeta( $value )->value;
	}

	/**
	 * Sanitise a category icon to the closed set, folding junk to the first.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeIcon( $value ): string {
		return in_array( $value, self::KOMPETISIE_IKONE, true ) ? (string) $value : self::KOMPETISIE_IKONE[0];
	}

	/**
	 * Sanitise a listing's genre to the closed set, folding junk to the first.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeGenre( $value ): string {
		return in_array( $value, self::EKSTERNE_GENRES, true ) ? (string) $value : self::EKSTERNE_GENRES[0];
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
	 * Sanitize a gedig dedication: one line of plain text, at most
	 * {@see self::GEDIG_TOEWYDING_MAX_LENGTH} characters. Anything else drops to ''.
	 *
	 * @param mixed $value Incoming value.
	 * @return string
	 */
	public static function sanitizeToewyding( $value ): string {
		$text = trim( sanitize_text_field( is_scalar( $value ) ? (string) $value : '' ) );

		return function_exists( 'mb_substr' )
			? mb_substr( $text, 0, self::GEDIG_TOEWYDING_MAX_LENGTH )
			: substr( $text, 0, self::GEDIG_TOEWYDING_MAX_LENGTH );
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
