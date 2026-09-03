/**
 * Volg / Volg tans follow toggle (Story 9.2, FR-38).
 *
 * `FollowToggle` server-renders `.ink-volg-knoppie[data-ink-skrywer]` buttons
 * (Skrywerprofiel + the My Profiel "Wie ek volg" list), but — like the
 * vasgespel pin toggle — no client ever wired a click handler to them. Mirrors
 * `leeslys.js`'s server-render-then-flip pattern against the `ink/v1/volg`
 * endpoint (Story 9.2, AD-6).
 *
 * On the "Wie ek volg" list an unfollow removes the writer from view entirely
 * (matching Lovable's `unfollow()` behaviour, which drops the row from the
 * list rather than leaving a dangling "Volg"-state row in a following-only
 * list) via the optional `data-ink-remove-on-unfollow` container hook.
 *
 * Config (REST root, nonce, the two state labels) is provided via
 * `window.inkVolg`.
 */
( function () {
	'use strict';

	var cfg = window.inkVolg;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	function toggle( button ) {
		var skrywerId = parseInt( button.getAttribute( 'data-ink-skrywer' ), 10 );
		if ( isNaN( skrywerId ) ) {
			return;
		}

		var following = button.classList.contains( 'is-following' );
		var method = following ? 'DELETE' : 'POST';

		button.disabled = true;

		window.fetch( cfg.restUrl, {
			method: method,
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( { followee_id: skrywerId } )
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} ).then( function ( data ) {
			var nowFollowing = !! ( data && data.following );
			var row = button.closest( '[data-ink-remove-on-unfollow]' );

			if ( row && ! nowFollowing ) {
				row.remove();
				return;
			}

			button.classList.toggle( 'is-following', nowFollowing );
			button.setAttribute( 'aria-pressed', nowFollowing ? 'true' : 'false' );
			button.textContent = nowFollowing ? cfg.followingText : cfg.followText;
			button.disabled = false;
		} ).catch( function () {
			/* leave state unchanged on failure; the server is the source of truth */
			button.disabled = false;
		} );
	}

	function init() {
		var buttons = document.querySelectorAll( '.ink-volg-knoppie[data-ink-skrywer]' );
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
