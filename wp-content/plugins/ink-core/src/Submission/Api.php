<?php
/**
 * Submission module public facade (reserved).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Submission;

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
	 * @return array{post_action:string, nonce_action:string, nonce_name:string, field_type:string, field_title:string, field_body:string, types:list<array{slug:string, label:string, counter_mode:string}>}
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
			'post_action'  => SubmissionForm::postAction(),
			'nonce_action' => SubmissionForm::nonceAction(),
			'nonce_name'   => SubmissionForm::nonceName(),
			'field_type'   => SubmissionForm::FIELD_TYPE,
			'field_title'  => SubmissionForm::FIELD_TITLE,
			'field_body'   => SubmissionForm::FIELD_BODY,
			'types'        => $types,
		);
	}
}
