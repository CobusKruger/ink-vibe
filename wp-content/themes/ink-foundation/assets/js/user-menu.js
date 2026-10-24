/**
 * Header user menu toggle (signed-in avatar dropdown).
 *
 * Progressive enhancement over server-rendered markup: the panel ships `hidden`
 * and the trigger opens/closes it. Closes on outside click, Esc (returning focus
 * to the trigger) and when focus leaves the menu; ArrowDown/ArrowUp move between
 * items while open. No business logic — pure show/hide + aria state.
 */
( function () {
	'use strict';

	var menu = document.querySelector( '[data-ink-user-menu]' );

	if ( ! menu ) {
		return;
	}

	var trigger = menu.querySelector( '[data-ink-user-menu-trigger]' );
	var panel = menu.querySelector( '[data-ink-user-menu-panel]' );

	if ( ! trigger || ! panel ) {
		return;
	}

	var items = Array.prototype.slice.call( panel.querySelectorAll( '[role="menuitem"]' ) );

	function setOpen( open, returnFocus ) {
		panel.hidden = ! open;
		trigger.setAttribute( 'aria-expanded', open ? 'true' : 'false' );

		if ( open && items.length ) {
			items[ 0 ].focus();
		} else if ( returnFocus ) {
			trigger.focus();
		}
	}

	trigger.addEventListener( 'click', function () {
		setOpen( panel.hidden, false );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! panel.hidden && ! menu.contains( event.target ) ) {
			setOpen( false, false );
		}
	} );

	menu.addEventListener( 'focusout', function ( event ) {
		if ( ! panel.hidden && event.relatedTarget && ! menu.contains( event.relatedTarget ) ) {
			setOpen( false, false );
		}
	} );

	menu.addEventListener( 'keydown', function ( event ) {
		if ( panel.hidden ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			setOpen( false, true );
		} else if ( 'ArrowDown' === event.key || 'ArrowUp' === event.key ) {
			event.preventDefault();
			var index = items.indexOf( document.activeElement );
			var step = 'ArrowDown' === event.key ? 1 : -1;
			items[ ( index + step + items.length ) % items.length ].focus();
		}
	} );
}() );
