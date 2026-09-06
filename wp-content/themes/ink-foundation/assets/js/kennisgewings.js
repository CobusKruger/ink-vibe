/**
 * Kennisgewings "Merk alles as gelees" client — My Profiel rebuild §5.8.
 *
 * `KennisgewingsSurface::toHtml()` server-renders the kennisgewings list, and
 * this mirrors `vasgespel.js`/`volg.js`'s server-render-then-flip pattern
 * against the new `ink/v1/kennisgewings` POST endpoint — no full page reload.
 *
 * DOM contract assumed (written now so the later tab-shell markup step can
 * match it precisely; `KennisgewingsSurface::toHtml()` already renders it):
 * - `.ink-kennisgewings` — the section wrapping the whole tab/card.
 * - `.ink-kennisgewings__merk-alles[data-ink-kennisgewings-merk-alles]` — the
 *   "Merk alles as gelees" button, inside that section.
 * - `.ink-kennisgewings__item[data-ink-kennisgewing-status]` — each row; the
 *   attribute is `"unread"` or `"gelees"`, and unread rows also carry the
 *   `is-unread` class.
 * - `[data-ink-kennisgewings-badge]` (OPTIONAL, zero or more, anywhere on the
 *   page — e.g. a future tab-label pill or the Oorsig "Ongelees" stat): its
 *   text content is zeroed out on a successful mark-all-read, since every
 *   kennisgewing on the page is now read.
 *
 * Config (REST root, nonce) is provided via `window.inkKennisgewings`.
 */
( function () {
	'use strict';

	var cfg = window.inkKennisgewings;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	function markAllRead( button ) {
		button.disabled = true;

		window.fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			}
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} ).then( function () {
			var section = button.closest( '.ink-kennisgewings' );

			if ( section ) {
				Array.prototype.forEach.call(
					section.querySelectorAll( '.ink-kennisgewings__item[data-ink-kennisgewing-status="unread"]' ),
					function ( item ) {
						item.classList.remove( 'is-unread' );
						item.setAttribute( 'data-ink-kennisgewing-status', 'gelees' );
					}
				);
			}

			Array.prototype.forEach.call(
				document.querySelectorAll( '[data-ink-kennisgewings-badge]' ),
				function ( badge ) {
					badge.textContent = '0';
				}
			);
		} ).catch( function () {
			/* leave state unchanged on failure; the server is the source of truth */
		} ).then( function () {
			button.disabled = false;
		} );
	}

	function init() {
		var buttons = document.querySelectorAll( '.ink-kennisgewings__merk-alles[data-ink-kennisgewings-merk-alles]' );
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.addEventListener( 'click', function () {
				markAllRead( button );
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
