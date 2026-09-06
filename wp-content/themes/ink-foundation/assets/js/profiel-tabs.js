/**
 * My Profiel tab toggle (My Profiel rebuild §5.2).
 *
 * Progressive enhancement over the 7 stacked `<section data-ink-profiel-panel>`
 * sections, closely mirroring `ontdek-tabs.js`'s exact mechanics (AD-7, no
 * REST/AJAX): all 7 panels are server-rendered up-front, so with this script
 * disabled they simply stack top to bottom in ratified order. With it enabled,
 * only the active panel shows and the nav behaves like a real tablist — one
 * visible section, an `aria-selected` tab, `#hash` updates so the state is
 * bookmarkable/shareable and survives a reload.
 *
 * Unlike Ontdek's tabs, no panel here has its own query-string-prefixed
 * filter/sort controls (each tab is a single self-contained view, not a
 * paginated/filterable list with its own GET params) — so the `#hash` alone
 * decides the initial tab, with the first tab (Oorsig) as the default when no
 * hash matches, exactly mirroring Ontdek's own fallback rule.
 *
 * No business logic here — pure show/hide + a class toggle; the theme performs
 * no computation (three-layer separation).
 */
( function () {
	'use strict';

	var nav = document.querySelector( '.ink-profiel-tabs' );

	if ( ! nav ) {
		return;
	}

	var tabs = Array.prototype.slice.call( nav.querySelectorAll( '[data-ink-profiel-tab]' ) );
	var panels = Array.prototype.slice.call( document.querySelectorAll( '[data-ink-profiel-panel]' ) );

	if ( 0 === tabs.length || 0 === panels.length ) {
		return;
	}

	function activate( key, focusTab ) {
		tabs.forEach( function ( tab ) {
			var isActive = tab.getAttribute( 'data-ink-profiel-tab' ) === key;

			tab.classList.toggle( 'is-active', isActive );
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );

			if ( isActive && focusTab ) {
				tab.focus();
			}
		} );

		panels.forEach( function ( panel ) {
			panel.hidden = panel.getAttribute( 'data-ink-profiel-panel' ) !== key;
		} );
	}

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			var key = tab.getAttribute( 'data-ink-profiel-tab' );

			activate( key, false );

			if ( key && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + key );
			}
		} );
	} );

	function initialTabKey() {
		var hash = ( window.location.hash || '' ).replace( '#', '' );

		return tabs.some( function ( tab ) {
			return tab.getAttribute( 'data-ink-profiel-tab' ) === hash;
		} )
			? hash
			: tabs[ 0 ].getAttribute( 'data-ink-profiel-tab' );
	}

	activate( initialTabKey(), false );
} )();
