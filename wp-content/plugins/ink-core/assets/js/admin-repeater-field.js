/**
 * Add, remove and reorder the rows of a FieldSets `repeater` input (K4: the winner records).
 *
 * Progressive enhancement: the server renders every row plus one blank row, and the rows save in
 * page order without this script. Here, each row gets Op / Af / Verwyder buttons and the add
 * button clones the hidden template. After every change the rows are renumbered, so the posted
 * `key[n][column]` order is the order on screen.
 */
( function () {
	'use strict';

	function renumber( root ) {
		var key = root.dataset.inkRepeater;
		var rows = root.querySelectorAll( ':scope > .ink-repeater__rye > .ink-repeater__ry' );

		rows.forEach( function ( row, index ) {
			row.querySelectorAll( '[name^="' + key + '["]' ).forEach( function ( field ) {
				// `key[old][column]` becomes `key[index][column]`.
				field.name = key + '[' + index + ']' + field.name.slice( field.name.indexOf( ']', key.length ) + 1 );
			} );
		} );
	}

	function button( label, action ) {
		var element = document.createElement( 'button' );
		element.type = 'button';
		element.className = 'button button-small';
		element.textContent = label;
		element.dataset.inkRepeaterAksie = action;
		element.style.marginRight = '4px';

		return element;
	}

	function decorate( root, row ) {
		if ( row.querySelector( '[data-ink-repeater-aksie]' ) ) {
			return;
		}

		var bar = document.createElement( 'p' );
		bar.style.margin = '0';
		bar.appendChild( button( '↑ ' + root.dataset.inkOp, 'op' ) );
		bar.appendChild( button( '↓ ' + root.dataset.inkAf, 'af' ) );
		bar.appendChild( button( root.dataset.inkVerwyder, 'verwyder' ) );
		row.appendChild( bar );
	}

	function enhance( root ) {
		var list = root.querySelector( ':scope > .ink-repeater__rye' );
		var template = root.querySelector( ':scope > template' );

		list.querySelectorAll( ':scope > .ink-repeater__ry' ).forEach( function ( row ) {
			decorate( root, row );
		} );

		root.addEventListener( 'click', function ( event ) {
			var target = event.target;

			if ( ! ( target instanceof HTMLElement ) ) {
				return;
			}

			if ( target.hasAttribute( 'data-ink-repeater-by' ) ) {
				var fresh = template.content.firstElementChild.cloneNode( true );
				list.appendChild( fresh );
				decorate( root, fresh );
				renumber( root );
				return;
			}

			var action = target.dataset.inkRepeaterAksie;
			var row = target.closest( '.ink-repeater__ry' );

			if ( ! action || ! row ) {
				return;
			}

			if ( 'op' === action && row.previousElementSibling ) {
				list.insertBefore( row, row.previousElementSibling );
			} else if ( 'af' === action && row.nextElementSibling ) {
				list.insertBefore( row.nextElementSibling, row );
			} else if ( 'verwyder' === action ) {
				row.remove();
			}

			renumber( root );
		} );

		// Number the rows now, so the saved order is the screen order even if nothing is clicked.
		renumber( root );
	}

	function start() {
		document.querySelectorAll( '[data-ink-repeater]' ).forEach( enhance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
