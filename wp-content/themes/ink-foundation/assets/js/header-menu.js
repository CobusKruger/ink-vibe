/**
 * Header mobile menu toggle (build spec 06-chrome §4).
 *
 * Progressive enhancement over server-rendered markup: the inline panel ships
 * `hidden` and the toggle opens and closes it. The icon swap follows
 * `aria-expanded` in CSS. Esc closes it and returns focus to the toggle when focus
 * was in the menu. As in the design, it stays open on an outside click: it pushes
 * the page down and covers nothing. No business logic.
 */
( function () {
	'use strict';

	var toggle = document.querySelector( '[data-ink-mobile-menu-toggle]' );
	var panel = document.querySelector( '[data-ink-mobile-menu]' );

	if ( ! toggle || ! panel ) {
		return;
	}

	function setOpen( open ) {
		panel.hidden = ! open;
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	}

	toggle.addEventListener( 'click', function () {
		setOpen( panel.hidden );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' !== event.key || panel.hidden ) {
			return;
		}

		var active = document.activeElement;
		var focusInMenu = ! active || active === document.body || active === toggle || panel.contains( active );

		setOpen( false );

		if ( focusInMenu ) {
			toggle.focus();
		}
	} );

	// From 1024 the toggle and the panel are gone: don't leave them open for the way
	// back. Accepted divergence: the design's breakpoint is 768 (06-chrome §3).
	var desktop = window.matchMedia( '(min-width: 1024px)' );

	desktop.addEventListener( 'change', function ( event ) {
		if ( event.matches ) {
			setOpen( false );
		}
	} );

	// Back from a page the menu linked to, out of the browser's page cache.
	window.addEventListener( 'pageshow', function ( event ) {
		if ( event.persisted ) {
			setOpen( false );
		}
	} );
}() );
