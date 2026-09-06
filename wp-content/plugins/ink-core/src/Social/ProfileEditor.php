<?php
/**
 * My Profiel identity-strip edit-profile modal — My Profiel rebuild (§5.1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Social;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/profiel-redigeer` block: the real inline "Wysig profiel"
 * modal (name / tagline / bio + a read-only avatar), matching Lovable's
 * `Profile.tsx` interaction shape — hidden by default, opened via a trigger,
 * an inline overlay/dialog (never a page navigation) — with a REAL persistence
 * layer behind it ({@see ProfileController}'s `ink/v1/profiel` route), unlike
 * Lovable's demo-only modal.
 *
 * NOT YET embedded in `my-profiel.php`'s live template (that is the later
 * identity-strip build step) — this is a self-contained, independently
 * reviewable/testable component, following the exact same precedent as
 * {@see FollowingList} (`ink/volg-lys`, registered + tested before its own
 * consumer existed). Registered as a real dynamic block now so it renders
 * correctly (and is fully covered by tests) the moment the identity-strip step
 * places `<!-- wp:ink/profiel-redigeer /-->` on the page.
 *
 * TRIGGER CONTRACT for the identity-strip step (see `profiel-edit.js`):
 * - Any element anywhere on the page carrying `data-ink-profiel-redigeer-trigger`
 *   opens this modal on click (e.g. the "Wysig profiel" button).
 * - After a successful save, the client updates any element carrying
 *   `data-ink-profiel-veld="naam"` / `="leuse"` / `="bio"` in place (textContent)
 *   with the newly-saved value — the identity strip's own name/tagline/bio
 *   markup should carry these attributes to get the live, no-reload update;
 *   their absence is safely a no-op (this modal does not require them to exist).
 *
 * AVATAR SCOPE (deliberately read-only here): this codebase has NO existing
 * avatar-assignment mechanism to reuse (every avatar read is Gravatar via
 * `get_avatar()`/`get_avatar_url()`) — unlike {@see CoverImage}, which is a
 * single-purpose banner image nothing else on the site reads. Building a
 * `pre_get_avatar_data` override would change avatar resolution SITE-WIDE
 * (comments, admin, notifications, every card that calls `get_avatar()`), plus
 * raise fresh questions (fallback behaviour, size variants, cache invalidation,
 * moderation of writer-uploaded images) that belong to their own reviewed pass,
 * not a name/tagline/bio persistence change. The modal shows the current
 * Gravatar-resolved avatar for visual completeness (matching Lovable's shape)
 * with no edit control — name/tagline/bio ship fully real and working; avatar
 * upload is an explicit, separate follow-up.
 *
 * @package Ink\Core
 */
final class ProfileEditor {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/profiel-redigeer';

	/**
	 * Register the server-rendered block.
	 *
	 * Invoked from {@see Module::register()}, which the Kernel already dispatches
	 * on `init` — so `registerBlock()` is called DIRECTLY here rather than nesting
	 * a second `add_action( 'init', … )` from within the running `init` hook.
	 */
	public function register(): void {
		self::registerBlock();
	}

	/**
	 * Register the `ink/profiel-redigeer` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Block render callback (the logged-in viewer's own profile only).
	 *
	 * @return string
	 */
	public static function render(): string {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$user_id = get_current_user_id();

		$profile = array(
			'name'    => (string) get_the_author_meta( 'display_name', $user_id ),
			// Same bio source SkrywerProfiel/FollowingList already read.
			'bio'     => (string) get_the_author_meta( 'description', $user_id ),
			'tagline' => class_exists( Tagline::class ) ? Tagline::get( $user_id ) : '',
			'avatar'  => function_exists( 'get_avatar' ) ? (string) get_avatar( $user_id, 96 ) : '',
		);

		return self::toHtml( $profile );
	}

	/**
	 * Build the modal HTML. Pure — escaping only. Hidden by default
	 * (`is-hidden` + `aria-hidden="true"`); `profiel-edit.js` toggles both when
	 * a `[data-ink-profiel-redigeer-trigger]` is clicked anywhere on the page.
	 *
	 * @param array{name:string, bio:string, tagline:string, avatar:string} $profile The current user's editable fields.
	 * @return string
	 */
	public static function toHtml( array $profile ): string {
		$name    = isset( $profile['name'] ) ? (string) $profile['name'] : '';
		$bio     = isset( $profile['bio'] ) ? (string) $profile['bio'] : '';
		$tagline = isset( $profile['tagline'] ) ? (string) $profile['tagline'] : '';
		$avatar  = isset( $profile['avatar'] ) ? (string) $profile['avatar'] : '';

		$html = '<div class="ink-profiel-redigeer is-hidden" id="ink-profiel-redigeer" data-ink-profiel-redigeer-modal aria-hidden="true">'
			. '<div class="ink-profiel-redigeer__agtergrond" data-ink-profiel-redigeer-sluit></div>'
			. '<div class="ink-profiel-redigeer__venster" role="dialog" aria-modal="true" aria-labelledby="ink-profiel-redigeer-titel">'
			// "Wysig profiel" is the already-ratified button copy
			// (`ui-copy-translations.md` "My Profiel-bladsy" > "Identiteitsstrook")
			// reused verbatim as the modal's own heading — not new copy.
			. '<h2 id="ink-profiel-redigeer-titel" class="ink-profiel-redigeer__titel">' . esc_html__( 'Wysig profiel', 'ink-core' ) . '</h2>';

		if ( '' !== $avatar ) {
			// Read-only — see the class docblock "AVATAR SCOPE" note. No upload
			// control; shown only so the modal isn't missing the Lovable element
			// entirely.
			$html .= '<div class="ink-profiel-redigeer__avatar">' . $avatar . '</div>';
		}

		$html .= '<form class="ink-profiel-redigeer__vorm" data-ink-profiel-redigeer-vorm>'
			. '<p class="ink-profiel-redigeer__veld">'
				. '<label for="ink-profiel-redigeer-naam">' . esc_html__( 'Naam', 'ink-core' ) . '</label>'
				. '<input type="text" id="ink-profiel-redigeer-naam" name="display_name" value="' . esc_attr( $name ) . '" /></p>'
			. '<p class="ink-profiel-redigeer__veld">'
				// Copy-debt: "tagline" is a brand-new field with no ratified
				// Afrikaans label yet — flagged per the standard
				// afrikaans-copy-debt-process (see docs/afrikaans-translation-sheet.md
				// PROFIEL-REDIGEER-LEUSE-LABEL / docs/afrikaans-copy-worklist.md).
				. '<label for="ink-profiel-redigeer-leuse">' . esc_html__( '[NEEDS HUMAN AFRIKAANS] — tagline field label not yet authored in ui-copy-translations.md.', 'ink-core' ) . '</label>'
				. '<input type="text" id="ink-profiel-redigeer-leuse" name="tagline" maxlength="' . esc_attr( (string) Tagline::MAX_LENGTH ) . '" value="' . esc_attr( $tagline ) . '" /></p>'
			. '<p class="ink-profiel-redigeer__veld">'
				// "Oor my" — the already-ratified Oorsig-tab card heading for this
				// exact bio content, reused verbatim as the field label.
				. '<label for="ink-profiel-redigeer-bio">' . esc_html__( 'Oor my', 'ink-core' ) . '</label>'
				. '<textarea id="ink-profiel-redigeer-bio" name="bio" rows="4">' . esc_textarea( $bio ) . '</textarea></p>'
			. '<div class="ink-profiel-redigeer__aksies">'
				// Copy-debt: Save/Cancel have no ratified Afrikaans yet either —
				// flagged the same way (PROFIEL-REDIGEER-KANSELLEER / -STOOR).
				. '<button type="button" class="ink-profiel-redigeer__kanselleer" data-ink-profiel-redigeer-sluit>' . esc_html__( '[NEEDS HUMAN AFRIKAANS] — cancel button label not yet authored in ui-copy-translations.md.', 'ink-core' ) . '</button>'
				. '<button type="submit" class="ink-profiel-redigeer__stoor">' . esc_html__( '[NEEDS HUMAN AFRIKAANS] — save button label not yet authored in ui-copy-translations.md.', 'ink-core' ) . '</button>'
			. '</div>'
			. '<p class="ink-profiel-redigeer__status" role="status" data-ink-profiel-redigeer-status></p>'
			. '</form>';

		$html .= '</div></div>';

		return $html;
	}
}
