/**
 * Line reactions (Story 7.3, FR-26).
 *
 * Attaches a small reaction control (hartjie / duim op / wow) to each content
 * line of a poem (the `[data-ink-line]` anchors emitted by the ink/gedig-body
 * server block, Story 7.2) and writes through the `ink/v1/reaksie` REST endpoint.
 *
 * Business logic stays server-side: this client only reflects state — it posts
 * the chosen reaction and toggles the active class on the success response. No
 * free-form commentary is possible here (reactions only — "encouragement, not
 * critique"); structured feedback is the Gemeenskapsreaksie (Story 7.4).
 *
 * Config (REST root, nonce, post id, Afrikaans labels) is provided by the theme
 * via `window.inkLineReactions` (localised in functions.php).
 */
( function () {
	'use strict';

	var cfg = window.inkLineReactions;
	if ( ! cfg || ! cfg.restUrl || ! cfg.postId ) {
		return;
	}

	var REACTIONS = cfg.reactions || [
		{ key: 'hartjie', label: 'Hartjie', glyph: '♥' },
		{ key: 'duim_op', label: 'Duim op', glyph: '👍' },
		{ key: 'wow', label: 'Wow', glyph: '✨' }
	];

	function request( method, line, reaction ) {
		var body = { post_id: cfg.postId, line: line };
		if ( reaction ) {
			body.reaction = reaction;
		}

		return window.fetch( cfg.restUrl, {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( body )
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} );
	}

	function buildToolbar( lineEl ) {
		var line = parseInt( lineEl.getAttribute( 'data-ink-line' ), 10 );
		if ( isNaN( line ) ) {
			return;
		}

		var bar = document.createElement( 'span' );
		bar.className = 'ink-gedig__reacts';

		REACTIONS.forEach( function ( r ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'ink-gedig__react';
			btn.setAttribute( 'data-reaction', r.key );
			btn.setAttribute( 'aria-label', r.label );
			btn.textContent = r.glyph;

			btn.addEventListener( 'click', function () {
				request( 'POST', line, r.key ).then( function ( data ) {
					bar.querySelectorAll( '.ink-gedig__react' ).forEach( function ( b ) {
						b.classList.remove( 'is-active' );
					} );
					if ( data && ! data.removed && data.reaction ) {
						btn.classList.add( 'is-active' );
					}
				} ).catch( function () {
					/* leave state unchanged on failure; the server is the source of truth */
				} );
			} );

			bar.appendChild( btn );
		} );

		lineEl.appendChild( bar );
	}

	function init() {
		var lines = document.querySelectorAll( '.ink-gedig__line[data-ink-line]' );
		Array.prototype.forEach.call( lines, buildToolbar );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
