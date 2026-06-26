/**
 * Leeslys save toggle (Story 7.7, FR-29).
 *
 * Toggles the current work in/out of the member's leeslys through the
 * `ink/v1/leeslys` REST endpoint and shows the authored confirmation toast.
 * Server-renders the initial saved state (the `is-saved` class on the button),
 * so this only reflects state — business logic stays server-side.
 *
 * Config (REST root, nonce, the two authored toast strings) is provided via
 * `window.inkLeeslys`.
 */
( function () {
	'use strict';

	var cfg = window.inkLeeslys;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	function toast( message ) {
		if ( ! message ) {
			return;
		}
		var el = document.createElement( 'div' );
		el.className = 'ink-toast';
		el.setAttribute( 'role', 'status' );
		el.textContent = message;
		document.body.appendChild( el );
		window.setTimeout( function () {
			el.remove();
		}, 3000 );
	}

	function toggle( button ) {
		var postId = parseInt( button.getAttribute( 'data-ink-post' ), 10 );
		if ( isNaN( postId ) ) {
			return;
		}

		var saved = button.classList.contains( 'is-saved' );
		var method = saved ? 'DELETE' : 'POST';

		window.fetch( cfg.restUrl, {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( { post_id: postId } )
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} ).then( function ( data ) {
			var nowSaved = !! ( data && data.saved );
			button.classList.toggle( 'is-saved', nowSaved );
			button.setAttribute( 'aria-pressed', nowSaved ? 'true' : 'false' );
			toast( nowSaved ? cfg.savedText : cfg.removedText );
		} ).catch( function () {
			/* leave state unchanged on failure; the server is the source of truth */
		} );
	}

	function init() {
		var buttons = document.querySelectorAll( '.ink-leeslys-knoppie[data-ink-post]' );
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.addEventListener( 'click', function () {
				toggle( button );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
