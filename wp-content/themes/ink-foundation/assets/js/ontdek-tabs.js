/**
 * Ontdek tab toggle (Theme-Fidelity re-audit, page 11).
 *
 * Progressive enhancement over the `#bydraes`/`#skrywers` anchor-jump nav: both
 * panels are server-rendered up-front (AD-7, no REST for discovery), so with
 * this script disabled the two sections simply stack and the nav still jumps to
 * them. With it enabled, only the active panel shows and the nav behaves like
 * Lovable's `Browse.tsx` `TabButton` (one visible section, an `aria-selected`
 * tab, `#hash` still updates so the state is bookmarkable/shareable).
 *
 * No business logic here — pure show/hide + a class toggle; the theme performs
 * no computation (three-layer separation).
 */
( function () {
	'use strict';

	var nav = document.querySelector( '.ink-ontdek-tabs' );

	if ( ! nav ) {
		return;
	}

	var tabs = Array.prototype.slice.call( nav.querySelectorAll( '[data-ink-ontdek-tab]' ) );
	var panels = Array.prototype.slice.call( document.querySelectorAll( '[data-ink-ontdek-panel]' ) );

	if ( 0 === tabs.length || 0 === panels.length ) {
		return;
	}

	function activate( key, focusTab ) {
		tabs.forEach( function ( tab ) {
			var isActive = tab.getAttribute( 'data-ink-ontdek-tab' ) === key;

			tab.classList.toggle( 'is-active', isActive );
			tab.setAttribute( 'aria-selected', isActive ? 'true' : 'false' );

			if ( isActive && focusTab ) {
				tab.focus();
			}
		} );

		panels.forEach( function ( panel ) {
			panel.hidden = panel.getAttribute( 'data-ink-ontdek-panel' ) !== key;
		} );
	}

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			var key = tab.getAttribute( 'data-ink-ontdek-tab' );

			activate( key, false );

			if ( key && window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', '#' + key );
			}
		} );
	} );

	// A filter/sort control inside a panel is a real GET link (server-rendered,
	// AD-7 — no client-side re-fetch), so clicking one reloads the page. Its URL
	// carries the panel's own query-var prefix (`skrywer_*` for the Skrywers
	// panel, `werke_*` for Bydraes) but no `#hash` — falling back to the `#hash`
	// alone would silently snap back to the Bydraes tab and hide the very
	// results the reload just produced. Query-string prefix wins when present;
	// `#hash` is the fallback (e.g. a direct link to `#skrywers`); Bydraes is the
	// default default.
	function initialTabKey() {
		var hash = ( window.location.hash || '' ).replace( '#', '' );
		var search = window.location.search || '';

		if ( /(^|[?&])skrywer_/.test( search ) ) {
			return 'skrywers';
		}

		if ( /(^|[?&])werke_/.test( search ) ) {
			return 'bydraes';
		}

		return tabs.some( function ( tab ) {
			return tab.getAttribute( 'data-ink-ontdek-tab' ) === hash;
		} )
			? hash
			: tabs[ 0 ].getAttribute( 'data-ink-ontdek-tab' );
	}

	activate( initialTabKey(), false );
} )();
