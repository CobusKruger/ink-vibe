/**
 * /kompetisies/ client side (`05-kompetisies` §4): the gala year chips, and search + genre
 * filters over the directory, AND-ed. Without JS: all listings, the first gala year.
 *
 * `syncAudit` keeps `data-audit-id` on visible nodes only (a hidden node keeps it as
 * `data-ink-aid`): ids stay unique and a captured state pairs what shows.
 */
( function () {
	'use strict';

	function syncAudit( root ) {
		root.querySelectorAll( '[data-audit-id],[data-ink-aid]' ).forEach( function ( el ) {
			var id = el.getAttribute( 'data-audit-id' ) || el.getAttribute( 'data-ink-aid' );

			if ( el.closest( '[hidden]' ) ) {
				el.removeAttribute( 'data-audit-id' );
				el.setAttribute( 'data-ink-aid', id );
			} else {
				el.setAttribute( 'data-audit-id', id );
				el.removeAttribute( 'data-ink-aid' );
			}
		} );
	}

	document.querySelectorAll( '[data-ink-gala]' ).forEach( function ( root ) {
		var chips = root.querySelectorAll( '[data-ink-gala-jaar]' );
		var panels = root.querySelectorAll( '[data-ink-gala-paneel]' );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var year = chip.getAttribute( 'data-ink-gala-jaar' );

				chips.forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
				} );

				panels.forEach( function ( panel ) {
					panel.hidden = panel.getAttribute( 'data-ink-gala-paneel' ) !== year;
				} );

				syncAudit( root );
			} );
		} );
	} );

	// /kompetisies/gala/<jaar>/: load the next batch from the pager's own link as the reader
	// nears the end. Without JS (or fetch / IntersectionObserver) the pager stays as it is.
	document.querySelectorAll( '[data-ink-gala-fotos]' ).forEach( function ( grid ) {
		var next = grid.parentElement.querySelector( '.ink-komp-gala-fotos__volgende' );
		var busy = false;
		var sentinel;
		var observer;

		if ( ! next || ! window.fetch || ! window.DOMParser || ! ( 'IntersectionObserver' in window ) ) {
			return;
		}

		function stop() {
			observer.disconnect();
			sentinel.remove();
		}

		function load() {
			if ( busy ) {
				return;
			}

			busy = true;
			window.fetch( next.href, { credentials: 'same-origin' } )
				.then( function ( response ) {
					return response.ok ? response.text() : Promise.reject( response.status );
				} )
				.then( function ( html ) {
					var doc = new window.DOMParser().parseFromString( html, 'text/html' );
					var more = doc.querySelector( '[data-ink-gala-fotos]' );
					var after = doc.querySelector( '.ink-komp-gala-fotos__volgende' );

					if ( more ) {
						Array.prototype.forEach.call( more.children, function ( figure ) {
							grid.appendChild( document.importNode( figure, true ) );
						} );
					}

					if ( after ) {
						next.href = after.href;
						busy = false;
						// Re-observe: if the sentinel is still in range, this fires again.
						observer.unobserve( sentinel );
						observer.observe( sentinel );
					} else {
						next.remove();
						stop();
					}
				} )
				.catch( function () {
					// Fall back to the real link.
					next.hidden = false;
					stop();
				} );
		}

		next.hidden = true;
		sentinel = document.createElement( 'div' );
		sentinel.setAttribute( 'aria-hidden', 'true' );
		grid.after( sentinel );
		observer = new window.IntersectionObserver( function ( entries ) {
			if ( entries[ 0 ].isIntersecting ) {
				load();
			}
		}, { rootMargin: '1200px 0px' } );
		observer.observe( sentinel );
	} );

	document.querySelectorAll( '[data-ink-komp-gids]' ).forEach( function ( root ) {
		var search = root.querySelector( '[data-ink-soek]' );
		var count = root.querySelector( '[data-ink-telling]' );
		var empty = root.querySelector( '[data-ink-leeg]' );
		var clear = root.querySelector( '[data-ink-vee-uit]' );
		var cards = root.querySelectorAll( '[data-soek]' );
		var chips = root.querySelectorAll( '[data-ink-filter]' );
		var grid = cards.length ? cards[ 0 ].parentElement : null;

		if ( ! grid ) {
			return;
		}

		function pressed( group ) {
			return root.querySelector( '[data-ink-filter="' + group + '"][aria-pressed="true"]' );
		}

		function apply() {
			var query = search.value.trim().toLowerCase();
			var genre = pressed( 'genre' );
			var shown = 0;
			var parts = [];

			cards.forEach( function ( card ) {
				var match = ( 'alles' === genre.getAttribute( 'data-ink-waarde' ) || card.getAttribute( 'data-genre' ) === genre.getAttribute( 'data-ink-waarde' ) )
					&& ( '' === query || -1 !== card.getAttribute( 'data-soek' ).indexOf( query ) );

				card.hidden = ! match;
				shown += match ? 1 : 0;
			} );

			parts.push( shown + ' ' + count.getAttribute( 'data-ink-telling' ) );
			if ( 'alles' !== genre.getAttribute( 'data-ink-waarde' ) ) {
				parts.push( genre.textContent );
			}
			count.textContent = parts.join( ' · ' );

			count.hidden = 0 === shown;
			grid.hidden = 0 === shown;
			empty.hidden = 0 !== shown;

			syncAudit( root );
		}

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				root.querySelectorAll( '[data-ink-filter="' + chip.getAttribute( 'data-ink-filter' ) + '"]' ).forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
				} );
				apply();
			} );
		} );

		search.addEventListener( 'input', apply );

		if ( clear ) {
			clear.addEventListener( 'click', function () {
				search.value = '';
				root.querySelectorAll( '[data-ink-filter="genre"]' ).forEach( function ( chip ) {
					chip.setAttribute( 'aria-pressed', 'alles' === chip.getAttribute( 'data-ink-waarde' ) ? 'true' : 'false' );
				} );
				apply();
			} );
		}
	} );
}() );
