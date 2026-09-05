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
 * No backend persistence, no login gate — matches Lovable exactly:
 * `ReadStory.tsx` holds `highlights` in plain `useState`, never sent to a
 * server anywhere in the component, and neither `HighlightableText.tsx` nor
 * `HighlightsPanel.tsx` reference auth/login state at all. So this client is
 * pure DOM manipulation + in-memory state, lost on reload, visible to guests —
 * there is nothing to POST and nothing to gate.
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

	var highlights = []; // { id, mark } — mark is the live <mark> element.
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

	function addHighlight( mark ) {
		var id = 'ink-highlight-' + Date.now() + '-' + highlights.length;
		mark.addEventListener( 'click', function () {
			removeHighlight( id );
		} );
		highlights.push( { id: id, mark: mark } );
		updateToggleCount();
		renderPanelList();
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
			var mark = wrapRange( range );
			window.getSelection().removeAllRanges();
			removeTooltip();
			addHighlight( mark );
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
		toggle.className = 'ink-highlights-toggle';
		toggle.innerHTML = HIGHLIGHTER_SVG;
		toggle.setAttribute( 'aria-label', labelPanelTitle );

		panel = document.createElement( 'div' );
		panel.className = 'ink-highlights-panel';
		panel.innerHTML =
			'<div class="ink-highlights-panel__header">' +
				'<div class="ink-highlights-panel__title">' + HIGHLIGHTER_SVG + '<span>' + labelPanelTitle + '</span>' +
					'<span class="ink-highlights-panel__count">0</span>' +
				'</div>' +
				'<button type="button" class="ink-highlights-panel__close" aria-label="' + labelPanelTitle + '">' + CLOSE_SVG + '</button>' +
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
		container.addEventListener( 'mouseup', handleMouseUp );
		document.addEventListener( 'mousedown', handleClickOutside );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
