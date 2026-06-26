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

	function init() {
		var forms = document.querySelectorAll( '.ink-reaksies__form' );
		Array.prototype.forEach.call( forms, function ( form ) {
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
