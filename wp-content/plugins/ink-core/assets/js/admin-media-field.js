/**
 * Media-library buttons for the `media` and `gallery` inputs in the FieldSets box.
 *
 * Progressive enhancement: the real input stays in the form and is what saves, so
 * without this script an editor can still type an attachment id or a comma-separated
 * list. `media` holds one attachment id; `gallery` holds ids in the modal's order.
 * Captions are edited in the modal, on the attachment.
 */
( function () {
	'use strict';

	function ids( input ) {
		return input.value
			.split( ',' )
			.map( function ( id ) {
				return parseInt( id, 10 );
			} )
			.filter( function ( id ) {
				return id > 0;
			} );
	}

	function drawPreview( preview, input ) {
		preview.textContent = '';

		ids( input ).forEach( function ( id ) {
			var attachment = window.wp.media.attachment( id );

			attachment.fetch().then( function () {
				var sizes = attachment.get( 'sizes' );
				var url = sizes && sizes.thumbnail ? sizes.thumbnail.url : attachment.get( 'url' );

				if ( 'image' === attachment.get( 'type' ) && url ) {
					var image = document.createElement( 'img' );
					image.src = url;
					image.alt = '';
					image.width = 60;
					image.style.marginRight = '4px';
					preview.appendChild( image );
				} else {
					var name = document.createElement( 'span' );
					name.textContent = attachment.get( 'filename' ) || String( id );
					name.style.marginRight = '4px';
					preview.appendChild( name );
				}
			} );
		} );
	}

	function openModal( input, isGallery, preview ) {
		var frame = window.wp.media( {
			title: input.dataset.inkMediaKies,
			multiple: isGallery ? 'add' : false,
			button: { text: input.dataset.inkMediaKies },
		} );

		frame.on( 'open', function () {
			var selection = frame.state().get( 'selection' );

			ids( input ).forEach( function ( id ) {
				var attachment = window.wp.media.attachment( id );
				attachment.fetch();
				selection.add( attachment );
			} );
		} );

		frame.on( 'select', function () {
			input.value = frame
				.state()
				.get( 'selection' )
				.map( function ( attachment ) {
					return attachment.id;
				} )
				.join( ',' );
			drawPreview( preview, input );
		} );

		frame.open();
	}

	function button( label ) {
		var element = document.createElement( 'button' );
		element.type = 'button';
		element.className = 'button';
		element.textContent = label;
		element.style.marginLeft = '4px';

		return element;
	}

	function enhance( input ) {
		var isGallery = 'gallery' === input.dataset.inkMedia;
		var choose = button( input.dataset.inkMediaKies );
		var remove = button( input.dataset.inkMediaVerwyder );
		var preview = document.createElement( 'div' );

		preview.style.marginTop = '4px';

		choose.addEventListener( 'click', function () {
			openModal( input, isGallery, preview );
		} );

		remove.addEventListener( 'click', function () {
			input.value = '';
			drawPreview( preview, input );
		} );

		input.after( choose, remove, preview );
		drawPreview( preview, input );
	}

	function start() {
		if ( ! window.wp || ! window.wp.media ) {
			return;
		}

		document.querySelectorAll( 'input[data-ink-media]' ).forEach( enhance );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', start );
	} else {
		start();
	}
} )();
