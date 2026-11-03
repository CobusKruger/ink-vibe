/**
 * InkPols archive year chips (`04-inkpols` §4). Every past card is in the HTML with
 * `data-jaar`; this toggles `hidden` and `aria-pressed`. Without JS all stay visible.
 */
( function () {
	'use strict';

	document.querySelectorAll( '[data-ink-inkpols-argief]' ).forEach( function ( root ) {
		var chips = root.querySelectorAll( 'button[data-jaar]' );
		var cards = root.querySelectorAll( 'article[data-jaar]' );

		chips.forEach( function ( chip ) {
			chip.addEventListener( 'click', function () {
				var year = chip.getAttribute( 'data-jaar' );

				chips.forEach( function ( other ) {
					other.setAttribute( 'aria-pressed', other === chip ? 'true' : 'false' );
				} );

				cards.forEach( function ( card ) {
					card.hidden = 'alles' !== year && card.getAttribute( 'data-jaar' ) !== year;
				} );
			} );
		} );
	} );
}() );
