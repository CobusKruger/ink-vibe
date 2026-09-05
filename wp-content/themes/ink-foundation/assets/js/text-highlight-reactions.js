/**
 * Text-highlight reactions for storie (post-Epic-19 storie fidelity pass, FR-24/26).
 *
 * The storie/artikel equivalent of `line-reactions.js`: a lid selects arbitrary
 * text inside the prose body, a floating bar appears near the selection (the
 * Lovable "select text → tooltip" interaction, matched via
 * `getSelection().getRangeAt(0).getBoundingClientRect()`), and attaches one of
 * the SAME hartjie/duim_op/wow reactions gedig uses — reactions only, no
 * free-form annotation.
 *
 * Anchor granularity is the PARAGRAPH, not the exact character range (mirrors
 * gedig's per-line, not per-character, precedent): the closest `<p>` ancestor
 * of the selection becomes the anchor (`data-ink-para`, 0-based, assigned here
 * client-side in DOM order — the same order the server's `ProseBody::tokenize()`
 * produces for the stored body, see `Ink\Engagement\ReactionController`). A
 * confirmed reaction tints the WHOLE paragraph (`has-reaksie`), not just the
 * selected substring.
 *
 * Business logic stays server-side: this client only reflects state — it posts
 * the chosen reaction and toggles the tint class on the success response. Config
 * (REST root, nonce, post id, reaction glyphs/labels, the paragraph indexes that
 * already carry a reaction from ANY reader) is provided by the theme via
 * `window.inkTextHighlightReactions` (localised in functions.php) so the tint
 * renders for every visitor on page load, not just the ephemeral selection.
 */
( function () {
	'use strict';

	var cfg = window.inkTextHighlightReactions;
	if ( ! cfg || ! cfg.restUrl || ! cfg.postId ) {
		return;
	}

	var REACTIONS = cfg.reactions || [
		{ key: 'hartjie', label: 'Hartjie', glyph: '♥' },
		{ key: 'duim_op', label: 'Duim op', glyph: '👍' },
		{ key: 'wow', label: 'Wow', glyph: '✨' }
	];

	var container = document.querySelector( cfg.containerSelector || '.ink-lees-storie__prose' );
	if ( ! container ) {
		return;
	}

	var bar = null;

	function request( reaction, paragraph ) {
		return window.fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( { post_id: cfg.postId, line: paragraph, reaction: reaction } )
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} );
	}

	/**
	 * Number every paragraph in the prose body, in DOM order, 0-based — the
	 * same order `ProseBody::tokenize()` produces server-side for the stored
	 * (light-editor, blank-line-delimited) body.
	 */
	function numberParagraphs() {
		var paragraphs = container.querySelectorAll( 'p' );
		Array.prototype.forEach.call( paragraphs, function ( p, index ) {
			p.setAttribute( 'data-ink-para', String( index ) );
			p.classList.add( 'ink-storie__para' );
		} );
		return paragraphs;
	}

	/**
	 * Pre-mark paragraphs that already carry a reaction from ANY reader, so the
	 * tint renders for every visitor on page load (not just the ephemeral
	 * selection that just reacted).
	 */
	function applyPersistedTint( paragraphs ) {
		var reacted = cfg.reactedParagraphs || [];
		if ( ! reacted.length ) {
			return;
		}
		Array.prototype.forEach.call( paragraphs, function ( p ) {
			var index = parseInt( p.getAttribute( 'data-ink-para' ), 10 );
			if ( reacted.indexOf( index ) !== -1 ) {
				p.classList.add( 'has-reaksie' );
			}
		} );
	}

	function removeBar() {
		if ( bar && bar.parentNode ) {
			bar.parentNode.removeChild( bar );
		}
		bar = null;
	}

	function closestParagraph( node ) {
		var el = node.nodeType === 1 ? node : node.parentElement;
		while ( el && el !== container ) {
			if ( 'P' === el.tagName && el.hasAttribute( 'data-ink-para' ) ) {
				return el;
			}
			el = el.parentElement;
		}
		return null;
	}

	function showBar( range, paragraphEl ) {
		removeBar();

		var paragraph = parseInt( paragraphEl.getAttribute( 'data-ink-para' ), 10 );
		if ( isNaN( paragraph ) ) {
			return;
		}

		var rect = range.getBoundingClientRect();

		bar = document.createElement( 'div' );
		bar.className = 'ink-storie-highlight-bar';
		bar.setAttribute( 'data-audit-id', 'storie-highlight' );
		bar.style.position = 'fixed';
		bar.style.left = ( rect.left + rect.width / 2 ) + 'px';
		bar.style.top = ( rect.top - 10 ) + 'px';

		REACTIONS.forEach( function ( r ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.className = 'ink-storie-highlight-bar__react';
			btn.setAttribute( 'data-reaction', r.key );
			btn.setAttribute( 'aria-label', r.label );
			btn.textContent = r.glyph;

			btn.addEventListener( 'click', function () {
				request( r.key, paragraph ).then( function ( data ) {
					// `has-reaksie` reflects an AGGREGATE fact (does ANY reader have a
					// reaction on this paragraph, {@see reactedParagraphs} above) — but
					// the member who JUST toggled their own reaction off is the one
					// case this client can answer optimistically without a re-fetch:
					// reflect what THEY just did (mirrors line-reactions.js's own
					// session-local toggle). A reload re-syncs to the true aggregate.
					if ( data && data.removed ) {
						paragraphEl.classList.remove( 'has-reaksie' );
					} else if ( data ) {
						paragraphEl.classList.add( 'has-reaksie' );
					}
					window.getSelection().removeAllRanges();
					removeBar();
				} ).catch( function () {
					/* leave state unchanged on failure; the server is the source of truth */
					removeBar();
				} );
			} );

			bar.appendChild( btn );
		} );

		document.body.appendChild( bar );
	}

	function handleSelectionChange() {
		var selection = window.getSelection();
		if ( ! selection || selection.isCollapsed || 0 === selection.rangeCount ) {
			removeBar();
			return;
		}

		var text = selection.toString().trim();
		if ( ! text ) {
			removeBar();
			return;
		}

		var range = selection.getRangeAt( 0 );
		var paragraphEl = closestParagraph( range.startContainer );
		if ( ! paragraphEl || ! container.contains( paragraphEl ) ) {
			removeBar();
			return;
		}

		showBar( range, paragraphEl );
	}

	function handleClickOutside( event ) {
		if ( bar && ! bar.contains( event.target ) ) {
			removeBar();
		}
	}

	function init() {
		var paragraphs = numberParagraphs();
		applyPersistedTint( paragraphs );

		container.addEventListener( 'mouseup', handleSelectionChange );
		document.addEventListener( 'mousedown', handleClickOutside );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
