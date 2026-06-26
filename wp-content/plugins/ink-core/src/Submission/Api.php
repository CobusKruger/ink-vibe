<?php
/**
 * Submission module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

use Ink\Entitlement\Api as EntitlementApi;
use Ink\Entitlement\MembershipStatus;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Submission module facade — the sole public cross-module surface (AD-1).
 *
 * Other code (notably the theme's `ink_foundation_*` bridges) reaches the Skryf
 * workflow ONLY through this facade — never into the handler internals. It hands
 * the theme a flat, escapable view-model so no submission logic lives in a
 * template (three-layer separation).
 *
 * @package Ink\Core
 */
final class Api {

	/**
	 * The Skryf form view-model for the page pattern (Story 6.1).
	 *
	 * Supplies only what is DYNAMIC: the submittable bydrae types (slug + the
	 * Afrikaans noun from the {@see Terms} registry) and the form wiring (the
	 * `admin-post` action, nonce action/name, field names). Presentation copy
	 * (headings, button labels, placeholders) stays in the theme pattern as
	 * `ink-foundation` strings — the model carries no markup and no logic.
	 *
	 * Each type carries its `counter_mode` (Story 6.2) so the browser counter can
	 * show lines+words for a gedig and words-only for prose without re-defining the
	 * rule — {@see Counters} / {@see ContentType} remain the single source.
	 *
	 * @return array{post_action:string, nonce_action:string, nonce_name:string, field_type:string, field_title:string, field_body:string, field_image:string, field_media:string, field_challenges:string, open_challenges:list<array{id:int, title:string}>, intent_field:string, intent_draft:string, intent_publish:string, types:list<array{slug:string, label:string, counter_mode:string}>}
	 */
	public static function formModel(): array {
		$types = array();

		foreach ( SubmissionForm::submittableTypes() as $slug ) {
			$types[] = array(
				'slug'         => $slug,
				'label'        => Terms::label( $slug ),
				'counter_mode' => ContentType::counterMode( $slug ),
			);
		}

		return array(
			'post_action'      => SubmissionForm::postAction(),
			'nonce_action'     => SubmissionForm::nonceAction(),
			'nonce_name'       => SubmissionForm::nonceName(),
			'field_type'       => SubmissionForm::FIELD_TYPE,
			'field_title'      => SubmissionForm::FIELD_TITLE,
			'field_body'       => SubmissionForm::FIELD_BODY,
			'field_image'      => FeaturedImage::FIELD,
			'field_media'      => MediaAttachment::FIELD,
			'field_challenges' => ChallengeLinking::FIELD,
			'open_challenges'  => ( new ChallengeLinking() )->openChallenges(),
			'intent_field'     => SubmissionForm::INTENT_FIELD,
			'intent_draft'     => SubmissionForm::INTENT_DRAFT,
			'intent_publish'   => SubmissionForm::INTENT_PUBLISH,
			'types'            => $types,
		);
	}

	/**
	 * The success-screen view-model for a freshly published bydrae (Story 6.7).
	 *
	 * Returns the title + Afrikaans type label + permalink for a PUBLISHED bydrae,
	 * so the Skryf pattern can render "Jou [gedig/storie/artikel] is gepubliseer"
	 * with read-and-respond prompts. Returns null for a non-bydrae, a non-published
	 * post, or an invalid id (the pattern then just shows the form).
	 *
	 * @param int $post_id The published bydrae id (from the post-plaas redirect).
	 * @return array{title:string, type_label:string, permalink:string}|null
	 */
	public static function successModel( int $post_id ): ?array {
		if ( $post_id <= 0 ) {
			return null;
		}

		$type = (string) get_post_type( $post_id );

		if ( ! in_array( $type, SubmissionForm::submittableTypes(), true ) ) {
			return null;
		}

		if ( 'publish' !== get_post_status( $post_id ) ) {
			return null;
		}

		return array(
			'title'      => (string) get_the_title( $post_id ),
			'type_label' => Terms::label( $type ),
			'permalink'  => (string) get_permalink( $post_id ),
		);
	}

	/**
	 * The Afrikaans publish-denial message (Story 6.8, FR-19).
	 *
	 * Sourced from the Entitlement facade's 4.7 `status_access_denied` message
	 * ("Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies.") — a single
	 * source, never re-authored here. Shown with a link to the lidmaatskap plans
	 * when a non-entitled plaas is denied (the bydrae is preserved as a konsep).
	 *
	 * @return string The access-denied message.
	 */
	public static function denialMessage(): string {
		return EntitlementApi::statusMessage( MembershipStatus::AccessDenied );
	}
}
