/**
 * Vasgespelde-werke pin/unpin toggle (Story 9.5, FR-41).
 *
 * Root-cause fix (Phase-2 fidelity pass, my-profiel): `PinnedWorksManager`
 * server-renders `.ink-vasgespel__knoppie[data-ink-post]` buttons, but no
 * client ever wired a click handler to them — clicking fired no REST request
 * at all. This mirrors `leeslys.js`'s server-render-then-flip pattern against
 * the `ink/v1/vasgespel` endpoint (Story 9.5, AD-6).
 *
 * Config (REST root, nonce, the two state labels) is provided via
 * `window.inkVasgespel`.
 */
( function () {
	'use strict';

	var cfg = window.inkVasgespel;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	function toggle( button ) {
		var postId = parseInt( button.getAttribute( 'data-ink-post' ), 10 );
		if ( isNaN( postId ) ) {
			return;
		}

		var pinned = button.classList.contains( 'is-pinned' );
		var method = pinned ? 'DELETE' : 'POST';

		button.disabled = true;

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
			var nowPinned = !! ( data && data.pinned );
			button.classList.toggle( 'is-pinned', nowPinned );
			button.setAttribute( 'aria-pressed', nowPinned ? 'true' : 'false' );
			button.textContent = nowPinned ? cfg.pinnedText : cfg.unpinnedText;
		} ).catch( function () {
			/* leave state unchanged on failure; the server is the source of truth */
		} ).then( function () {
			button.disabled = false;
		} );
	}

	function init() {
		var buttons = document.querySelectorAll( '.ink-vasgespel__knoppie[data-ink-post]' );
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
