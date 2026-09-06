/**
 * Leeslys save toggle (Story 7.7, FR-29).
 *
 * Toggles the current work in/out of the member's leeslys through the
 * `ink/v1/leeslys` REST endpoint and shows the authored confirmation toast.
 * Server-renders the initial saved state (the `is-saved` class on the button),
 * so this only reflects state — business logic stays server-side.
 *
 * The toggle is OPTIMISTIC — the button (and its toast) update immediately on
 * click and only roll back if the write actually fails, never waiting on the
 * round-trip first (product-owner report, Theme-Fidelity fourth pass,
 * 2026-09-06: the same multi-second "looks broken" delay reported for the
 * line-reaction hearts, see `line-reactions.js`). The server's response still
 * reconciles the guess afterwards; the server remains the source of truth on
 * both success and failure.
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
		if ( button.dataset.inkGuest ) {
			// Guests see the same icon (post-Epic-19 product-owner decision) but
			// saving requires an account — send them to sign in rather than
			// posting to the REST endpoint (which would 403 anyway) or doing
			// nothing. `redirect_to` reuses the login form's own existing hidden
			// field (patterns/auth-login.php already reads `$_GET['redirect_to']`
			// into it) so a successful sign-in lands the visitor back on the
			// work they were reading, not a fresh /meld-aan/.
			if ( cfg.loginUrl ) {
				window.location.href = cfg.loginUrl + '?redirect_to=' + encodeURIComponent( window.location.href );
			}
			return;
		}

		var postId = parseInt( button.getAttribute( 'data-ink-post' ), 10 );
		if ( isNaN( postId ) ) {
			return;
		}

		var saved = button.classList.contains( 'is-saved' );
		var method = saved ? 'DELETE' : 'POST';

		// Optimistic: flip the button and show the confirmation toast right
		// away, don't wait on the write first.
		button.classList.toggle( 'is-saved', ! saved );
		button.setAttribute( 'aria-pressed', saved ? 'false' : 'true' );
		toast( saved ? cfg.removedText : cfg.savedText );

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
			// Reconcile with the server's actual state — normally a no-op
			// against the optimistic guess above, but keeps the UI honest if
			// it ever disagrees.
			var nowSaved = !! ( data && data.saved );
			button.classList.toggle( 'is-saved', nowSaved );
			button.setAttribute( 'aria-pressed', nowSaved ? 'true' : 'false' );
		} ).catch( function () {
			// The write failed — undo the optimistic guess and let the member
			// know, rather than leaving the button lying about the real state.
			button.classList.toggle( 'is-saved', saved );
			button.setAttribute( 'aria-pressed', saved ? 'true' : 'false' );
			toast( cfg.errorText );
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
