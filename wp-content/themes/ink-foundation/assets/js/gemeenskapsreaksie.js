/**
 * Gemeenskapsreaksie form client (Story 7.4, FR-27).
 *
 * Submits the typed-response form rendered by the ink/gemeenskapsreaksies server
 * block through the `ink/v1/gemeenskapsreaksie` REST endpoint. Business logic
 * stays server-side: this only posts {post_id, type, content} and reloads the
 * list on success so the server stays the source of truth. The ONLY feedback
 * path — reactions-only line resonance is separate (Story 7.3).
 *
 * Config (REST root, nonce) is provided via `window.inkGemeenskapsreaksie`.
 */
( function () {
	'use strict';

	var cfg = window.inkGemeenskapsreaksie;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	function submit( form ) {
		var postId = parseInt( form.getAttribute( 'data-ink-post' ), 10 );
		var typeEl = form.querySelector( 'input[name="ink_reaksie_type"]:checked' );
		var content = form.querySelector( '[name="ink_reaksie_content"]' );

		if ( isNaN( postId ) || ! typeEl || ! content || ! content.value.trim() ) {
			return;
		}

		window.fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( {
				post_id: postId,
				type: typeEl.value,
				content: content.value
			} )
		} ).then( function ( res ) {
			if ( res.ok ) {
				window.location.reload();
			}
		} ).catch( function () {
			/* leave the form as-is on failure; the server is the source of truth */
		} );
	}

	// Real disable/enable wiring (Epic 19 lees-gedig fidelity pass, finding #14):
	// the submit button is server-rendered `disabled` (the textarea starts
	// empty); this keeps it in sync as the visitor types, so it can never be
	// clicked while the textarea is blank — matching Lovable's own
	// `disabled={!critiqueText.trim()}` behaviour. Previously the button stayed
	// clickable regardless of content, a real (not just cosmetic) bug.
	function syncSubmitState( form ) {
		var content = form.querySelector( '[name="ink_reaksie_content"]' );
		var button = form.querySelector( '.ink-reaksies__submit' );

		if ( ! content || ! button ) {
			return;
		}

		button.disabled = ! content.value.trim();
	}

	// The response-card Reply action was removed outright (Theme-Fidelity
	// fourth pass, docs/theme-fidelity-audit-handoff.md, 2026-09-05): an
	// earlier pass gave it a "focus the compose textarea" behaviour that the
	// product owner flagged as actively misleading (it looked like opening a
	// real reply thread); a follow-up pass then left it rendered but inert to
	// match Lovable's own dead `<button>Reply</button>` — but a control with
	// no effect when activated is a defect regardless of what the reference
	// does, so `Ink\Engagement\ResponsesList` no longer renders it at all.
	// Nothing here needs to reference it any more.

	function init() {
		var forms = document.querySelectorAll( '.ink-reaksies__form' );
		Array.prototype.forEach.call( forms, function ( form ) {
			var content = form.querySelector( '[name="ink_reaksie_content"]' );

			syncSubmitState( form );

			if ( content ) {
				content.addEventListener( 'input', function () {
					syncSubmitState( form );
				} );
			}

			form.addEventListener( 'submit', function ( e ) {
				e.preventDefault();
				submit( form );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
