/**
 * Text-selection highlighting for storie/artikel (Theme-Fidelity fourth pass,
 * docs/theme-fidelity-audit-handoff.md, 2026-09-05, item 9).
 *
 * REPLACES `text-highlight-reactions.js` (deleted) — that client was
 * architecturally the wrong interaction: it reused gedig's whole-paragraph,
 * three-reaction (hartjie/duim_op/wow), REST-persisted "resonance" mechanism.
 * Lovable's ACTUAL storie/artikel interaction, read directly from
 * `src/components/reading/HighlightableText.tsx` + `HighlightsPanel.tsx`, is a
 * completely different mechanism: select arbitrary SUBSTRING text with the
 * browser's normal selection → a "Highlight" tooltip appears near the
 * selection → confirming it wraps the exact selected text in a `<mark>` and
 * adds it to a highlight list/counter shown in a slide-out side panel. This is
 * the poetry-only hover-heart mechanism's opposite number for prose; it must
 * NOT leak onto gedig (gated to storie/artikel only, see the enqueue function
 * in functions.php).
 *
 * Saved for signed-in members (owner decision after the first migration
 * rehearsal: Lovable kept highlights in React state only, which made them
 * pointless): the script is only enqueued for a signed-in member, each new
 * highlight is saved to `ink/v1/hooglig` (paragraph index + character range +
 * the quoted text), and the member's saved highlights arrive in `cfg.saved` and
 * are re-applied on load. A saved range is skipped when the paragraph text no
 * longer matches its quote (the piece was edited). Guests get no highlight tool.
 *
 * Granularity: exact character range (via `Range.extractContents()` +
 * `insertNode()`, which — unlike `Range.surroundContents()` — tolerates a
 * selection that crosses inline-mark boundaries, e.g. spans part of an
 * `<em>`), matching Lovable's own startOffset/endOffset substring model
 * (gedig's per-LINE granularity is a deliberate, different, already-ratified
 * precedent — not reused here).
 */
( function () {
	'use strict';

	var cfg = window.inkHighlightableText || {};
	var container = document.querySelector( cfg.containerSelector || '.ink-lees-storie__prose' );
	if ( ! container ) {
		return;
	}

	var labelHighlight = cfg.highlightLabel || 'Merk uit';
	var labelPanelTitle = cfg.panelTitleLabel || 'Jou uitgeligte gedeeltes';
	var labelEmpty = cfg.emptyLabel || 'Kies enige teks in die stuk om onvergeetlike gedeeltes uit te lig.';
	var labelRemove = cfg.removeLabel || 'Verwyder hooglig';
	var restUrl = cfg.restUrl || '';
	var restNonce = cfg.nonce || '';
	var postId = cfg.postId || 0;
	var QUOTE_MAX = 500;

	var highlights = []; // { id, mark, serverId } — mark is the live <mark> element.
	var tooltip = null;
	var panel = null;
	var toggle = null;
	var backdrop = null;
	var panelOpen = false;

	function svg( paths ) {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false">' + paths + '</svg>';
	}

	var HIGHLIGHTER_SVG = svg( '<path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/>' );
	var CLOSE_SVG = svg( '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>' );

	function removeTooltip() {
		if ( tooltip && tooltip.parentNode ) {
			tooltip.parentNode.removeChild( tooltip );
		}
		tooltip = null;
	}

	function closestParagraph( node ) {
		var el = node.nodeType === 1 ? node : node.parentElement;
		while ( el && el !== container ) {
			if ( 'P' === el.tagName ) {
				return el;
			}
			el = el.parentElement;
		}
		return null;
	}

	function paragraphs() {
		return Array.prototype.slice.call( container.querySelectorAll( 'p' ) );
	}

	/**
	 * Where a selection sits: the paragraph's index and the character offsets of the
	 * selection inside that paragraph's text (marks never change the text, so the
	 * offsets stay valid after highlighting).
	 */
	function locate( range, para ) {
		var pre = document.createRange();
		pre.selectNodeContents( para );
		pre.setEnd( range.startContainer, range.startOffset );
		var start = pre.toString().length;
		return {
			para: paragraphs().indexOf( para ),
			start: start,
			end: start + range.toString().length
		};
	}

	/**
	 * A DOM range over [start, end) of a paragraph's text, or null when it does not fit.
	 */
	function rangeFor( para, start, end ) {
		var walker = document.createTreeWalker( para, NodeFilter.SHOW_TEXT, null );
		var pos = 0;
		var range = document.createRange();
		var started = false;
		var node;
		while ( ( node = walker.nextNode() ) ) {
			var len = node.nodeValue.length;
			if ( ! started && start < pos + len ) {
				range.setStart( node, start - pos );
				started = true;
			}
			if ( started && end <= pos + len ) {
				range.setEnd( node, end - pos );
				return range;
			}
			pos += len;
		}
		return null;
	}

	function request( method, url, body ) {
		return window.fetch( url, {
			method: method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': restNonce },
			body: body ? JSON.stringify( body ) : undefined
		} ).then( function ( res ) {
			if ( ! res.ok ) {
				throw new Error( 'hooglig ' + res.status );
			}
			return res.json();
		} );
	}

	/**
	 * Re-apply the member's saved highlights. A highlight whose paragraph text no
	 * longer matches its quote (the piece was edited) is skipped, never mis-placed.
	 */
	function restoreSaved() {
		var paras = paragraphs();
		( cfg.saved || [] ).forEach( function ( item ) {
			var para = paras[ item.para ];
			if ( ! para ) {
				return;
			}
			var now = para.textContent.substring( item.start, item.end ).trim().slice( 0, QUOTE_MAX );
			if ( now !== String( item.quote ).trim() ) {
				return;
			}
			var range = rangeFor( para, item.start, item.end );
			if ( range ) {
				addHighlight( wrapRange( range ), item.id );
			}
		} );
	}

	/**
	 * Wrap the given range's contents in a `<mark class="ink-highlight">`.
	 * `extractContents()`/`insertNode()` (not `surroundContents()`) so a
	 * selection that partially overlaps an existing inline element (`<em>`,
	 * `<a>`, an earlier `<mark>`…) never throws.
	 */
	function wrapRange( range ) {
		var mark = document.createElement( 'mark' );
		mark.className = 'ink-highlight';
		mark.setAttribute( 'data-audit-id', 'storie-highlight' );
		mark.setAttribute( 'title', 'Klik om die hooglig te verwyder' );
		mark.appendChild( range.extractContents() );
		range.insertNode( mark );
		return mark;
	}

	function updateToggleCount() {
		if ( ! toggle ) {
			return;
		}
		var countEl = toggle.querySelector( '.ink-highlights-toggle__count' );
		if ( highlights.length > 0 ) {
			if ( ! countEl ) {
				countEl = document.createElement( 'span' );
				countEl.className = 'ink-highlights-toggle__count';
				toggle.appendChild( countEl );
			}
			countEl.textContent = String( highlights.length );
		} else if ( countEl ) {
			countEl.parentNode.removeChild( countEl );
		}
	}

	function renderPanelList() {
		var body = panel.querySelector( '.ink-highlights-panel__body' );
		body.innerHTML = '';

		var panelCount = panel.querySelector( '.ink-highlights-panel__count' );
		panelCount.textContent = String( highlights.length );

		if ( 0 === highlights.length ) {
			var empty = document.createElement( 'div' );
			empty.className = 'ink-highlights-panel__empty';
			empty.innerHTML = '<span class="ink-highlights-panel__empty-ikoon" aria-hidden="true">' + HIGHLIGHTER_SVG + '</span><p>' + labelEmpty + '</p>';
			body.appendChild( empty );
			return;
		}

		var list = document.createElement( 'ul' );
		list.className = 'ink-highlights-panel__list';

		highlights.forEach( function ( entry ) {
			var item = document.createElement( 'li' );
			item.className = 'ink-highlights-panel__item';

			var text = document.createElement( 'p' );
			text.className = 'ink-highlights-panel__item-text';
			text.textContent = '“' + entry.mark.textContent + '”';
			item.appendChild( text );

			var remove = document.createElement( 'button' );
			remove.type = 'button';
			remove.className = 'ink-highlights-panel__remove';
			remove.setAttribute( 'aria-label', labelRemove );
			remove.innerHTML = CLOSE_SVG;
			remove.addEventListener( 'click', function () {
				removeHighlight( entry.id );
			} );
			item.appendChild( remove );

			list.appendChild( item );
		} );

		body.appendChild( list );
	}

	function removeHighlight( id ) {
		var index = -1;
		highlights.forEach( function ( entry, i ) {
			if ( entry.id === id ) {
				index = i;
			}
		} );
		if ( -1 === index ) {
			return;
		}

		var mark = highlights[ index ].mark;
		var serverId = highlights[ index ].serverId;
		if ( serverId && restUrl ) {
			request( 'DELETE', restUrl + '/' + serverId ).catch( function () {} );
		}
		if ( mark && mark.parentNode ) {
			// Unwrap: replace the <mark> with its own text, preserving position.
			var parent = mark.parentNode;
			while ( mark.firstChild ) {
				parent.insertBefore( mark.firstChild, mark );
			}
			parent.removeChild( mark );
			parent.normalize();
		}

		highlights.splice( index, 1 );
		updateToggleCount();
		renderPanelList();
	}

	function addHighlight( mark, serverId ) {
		var id = 'ink-highlight-' + Date.now() + '-' + highlights.length;
		mark.addEventListener( 'click', function () {
			removeHighlight( id );
		} );
		var entry = { id: id, mark: mark, serverId: serverId || 0 };
		highlights.push( entry );
		updateToggleCount();
		renderPanelList();
		return entry;
	}

	function showTooltip( range ) {
		removeTooltip();

		var rect = range.getBoundingClientRect();

		tooltip = document.createElement( 'div' );
		tooltip.className = 'ink-highlight-tooltip';
		tooltip.style.left = ( rect.left + rect.width / 2 ) + 'px';
		tooltip.style.top = ( rect.top - 10 ) + 'px';
		tooltip.style.transform = 'translate(-50%, -100%)';

		var btn = document.createElement( 'button' );
		btn.type = 'button';
		btn.className = 'ink-highlight-tooltip__btn';
		btn.innerHTML = HIGHLIGHTER_SVG + '<span>' + labelHighlight + '</span>';

		btn.addEventListener( 'click', function () {
			// Re-read the CURRENT selection's range at click time — the selection
			// object itself may have changed by now, but the Range instance we
			// captured on mouseup is still the one we want to act on.
			var para = closestParagraph( range.startContainer );
			var where = para ? locate( range, para ) : null;
			var quote = range.toString().trim().slice( 0, QUOTE_MAX );
			var mark = wrapRange( range );
			window.getSelection().removeAllRanges();
			removeTooltip();
			var entry = addHighlight( mark );

			if ( ! where || ! restUrl ) {
				return;
			}
			request( 'POST', restUrl, { post_id: postId, para: where.para, start: where.start, end: where.end, quote: quote } )
				.then( function ( data ) {
					entry.serverId = data.id;
					// Removed while the save was in flight: take the saved row back too.
					if ( -1 === highlights.indexOf( entry ) ) {
						request( 'DELETE', restUrl + '/' + data.id ).catch( function () {} );
					}
				} )
				.catch( function () {
					// Not saved: take the highlight back rather than pretend it will survive a reload.
					removeHighlight( entry.id );
				} );
		} );

		var arrow = document.createElement( 'div' );
		arrow.className = 'ink-highlight-tooltip__arrow';

		tooltip.appendChild( btn );
		tooltip.appendChild( arrow );
		document.body.appendChild( tooltip );
	}

	function handleMouseUp() {
		var selection = window.getSelection();
		if ( ! selection || selection.isCollapsed || 0 === selection.rangeCount ) {
			removeTooltip();
			return;
		}

		var text = selection.toString().trim();
		if ( ! text ) {
			removeTooltip();
			return;
		}

		var range = selection.getRangeAt( 0 );
		var startPara = closestParagraph( range.startContainer );
		var endPara = closestParagraph( range.endContainer );

		if ( ! startPara || ! endPara || startPara !== endPara ) {
			// Cross-paragraph or outside-the-body selections are not a case
			// Lovable's own HighlightableText.tsx handles cleanly either — skip
			// quietly rather than producing a garbled highlight.
			removeTooltip();
			return;
		}

		showTooltip( range.cloneRange() );
	}

	function handleClickOutside( event ) {
		if ( tooltip && ! tooltip.contains( event.target ) ) {
			removeTooltip();
		}
	}

	function buildPanelChrome() {
		toggle = document.createElement( 'button' );
		toggle.type = 'button';
		toggle.className = 'ink-highlights-toggle ink-icon-btn ink-icon-btn--highlight-tab';
		toggle.innerHTML = HIGHLIGHTER_SVG;
		toggle.setAttribute( 'aria-label', labelPanelTitle );
		toggle.setAttribute( 'data-audit-id', 'storie-highlights-toggle' );

		panel = document.createElement( 'div' );
		panel.className = 'ink-highlights-panel ink-sheet';
		panel.setAttribute( 'data-audit-id', 'storie-highlights-panel' );
		panel.innerHTML =
			'<div class="ink-highlights-panel__header">' +
				'<div class="ink-highlights-panel__title">' + HIGHLIGHTER_SVG + '<span>' + labelPanelTitle + '</span>' +
					'<span class="ink-highlights-panel__count ink-pill ink-pill--highlight">0</span>' +
				'</div>' +
				'<button type="button" class="ink-highlights-panel__close ink-icon-btn ink-icon-btn--panel-close" aria-label="' + labelPanelTitle + '">' + CLOSE_SVG + '</button>' +
			'</div>' +
			'<div class="ink-highlights-panel__body"></div>';

		backdrop = document.createElement( 'div' );
		backdrop.className = 'ink-highlights-backdrop';
		backdrop.style.display = 'none';

		function setOpen( open ) {
			panelOpen = open;
			toggle.classList.toggle( 'is-open', open );
			panel.classList.toggle( 'is-open', open );
			backdrop.style.display = open ? 'block' : 'none';
		}

		toggle.addEventListener( 'click', function () {
			setOpen( ! panelOpen );
		} );
		panel.querySelector( '.ink-highlights-panel__close' ).addEventListener( 'click', function () {
			setOpen( false );
		} );
		backdrop.addEventListener( 'click', function () {
			setOpen( false );
		} );

		document.body.appendChild( toggle );
		document.body.appendChild( backdrop );
		document.body.appendChild( panel );

		renderPanelList();
	}

	function init() {
		buildPanelChrome();
		restoreSaved();
		container.addEventListener( 'mouseup', handleMouseUp );
		document.addEventListener( 'mousedown', handleClickOutside );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
