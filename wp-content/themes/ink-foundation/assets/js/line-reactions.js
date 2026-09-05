/**
 * Line resonance (Story 7.3, FR-26).
 *
 * Attaches a single heart-toggle control to each content line of a poem (the
 * `[data-ink-line]` anchors emitted by the ink/gedig-body server block, Story 7.2)
 * and writes through the `ink/v1/reaksie` REST endpoint. This is a boolean
 * per-line "resonance" toggle — Lovable's `PoetryReader.tsx` design has exactly
 * one reaction affordance per line (a heart), not a toolbar of reaction types —
 * so every write always sends `reaction: 'hartjie'`, the one enum case
 * `Ink\Kernel\Reaction` and `Ink\Engagement\ReactionController` treat as valid
 * here (see `ink_foundation_enqueue_line_reactions()` in functions.php, which no
 * longer localises `duim_op`/`wow` — this control never offered a type choice in
 * the design it now matches).
 *
 * Business logic stays server-side: this client only reflects state — it posts
 * the fixed reaction and toggles the active class on the success response. No
 * free-form commentary is possible here (reactions only — "encouragement, not
 * critique"); structured feedback is the Gemeenskapsreaksie (Story 7.4).
 *
 * Config (REST root, nonce, post id, the aggregate `reactedLines` list so the
 * persisted tint/fill renders for every visitor on page load, not just an
 * ephemeral click — same pattern as `text-highlight-reactions.js`'s
 * `reactedParagraphs`) is provided by the theme via `window.inkLineReactions`
 * (localised in functions.php).
 */
( function () {
	'use strict';

	var cfg = window.inkLineReactions;
	if ( ! cfg || ! cfg.restUrl || ! cfg.postId ) {
		return;
	}

	var REACTION = 'hartjie';

	// Lucide `Heart` path — the same outline already used elsewhere in this theme
	// via ink_foundation_icon() (ReactionTotals.php, footer-main.php,
	// gemeenskap.php, reading-gedig.php's hint pill) — not hand-drawn here.
	var HEART_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>';

	function request( method, line ) {
		var body = { post_id: cfg.postId, line: line, reaction: REACTION };

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

	function setResonant( lineEl, btn, resonant ) {
		btn.classList.toggle( 'is-active', resonant );
		btn.setAttribute( 'aria-pressed', resonant ? 'true' : 'false' );
		lineEl.classList.toggle( 'has-reaksie', resonant );
	}

	function buildHeart( lineEl ) {
		var line = parseInt( lineEl.getAttribute( 'data-ink-line' ), 10 );
		if ( isNaN( line ) ) {
			return;
		}

		// The heart is positioned off the inner `.ink-gedig__line-text` span
		// (shrink-to-fit), not the full-width line `<p>` — it sits right after
		// each line's own text, the way Lovable's `relative inline-block` text
		// wrapper does, not pinned to a fixed column unrelated to line length.
		var textEl = lineEl.querySelector( '.ink-gedig__line-text' ) || lineEl;

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'ink-gedig__react';
		btn.setAttribute( 'aria-label', cfg.label || 'Merk hierdie reël' );
		btn.setAttribute( 'aria-pressed', 'false' );
		btn.innerHTML = HEART_SVG;

		var reacted = cfg.reactedLines || [];
		if ( -1 !== reacted.indexOf( line ) ) {
			setResonant( lineEl, btn, true );
		}

		btn.addEventListener( 'click', function () {
			var wasActive = btn.classList.contains( 'is-active' );
			var method    = wasActive ? 'DELETE' : 'POST';

			request( method, line ).then( function ( data ) {
				var resonant = !! ( data && ! data.removed && data.reaction );
				setResonant( lineEl, btn, resonant );

				// A mouse click leaves the button holding real DOM focus, which
				// keeps `:focus-within`/`:hover`-adjacent reveal rules engaged
				// until focus moves elsewhere. Release it so a persisted (active)
				// heart's visibility comes only from `.is-active`, never from a
				// lingering focus/hover state — keyboard users tabbing through
				// are unaffected, focus simply moves to the next control as
				// normal, it just doesn't linger here after a click.
				btn.blur();
			} ).catch( function () {
				/* leave state unchanged on failure; the server is the source of truth */
			} );
		} );

		textEl.appendChild( btn );
	}

	function init() {
		var lines = document.querySelectorAll( '.ink-gedig__line[data-ink-line]' );
		Array.prototype.forEach.call( lines, buildHeart );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
