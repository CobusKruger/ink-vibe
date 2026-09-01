/**
 * Skrywerprofiel "Deel" (share) button — copies the profile URL to the
 * clipboard and briefly swaps the button's label to confirm the copy.
 *
 * Thin client only; the button itself is server-rendered by
 * `Ink\Social\SkrywerProfiel::toHtml()` with its labels already carried on
 * `data-ink-deel-*` attributes (both ratified Afrikaans strings — no copy
 * lives in this file). No REST call, no business logic.
 */
( function () {
	'use strict';

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ink-skrywerprofiel__deel' );

		if ( ! button ) {
			return;
		}

		var url = button.getAttribute( 'data-ink-deel-url' ) || window.location.href;
		var confirmLabel = button.getAttribute( 'data-ink-deel-gekopieer' );
		var originalLabel = button.getAttribute( 'data-ink-deel-label' ) || button.textContent;

		var confirmCopy = function () {
			if ( ! confirmLabel || button.dataset.inkDeelBesig === '1' ) {
				return;
			}

			button.dataset.inkDeelBesig = '1';
			button.textContent = confirmLabel;

			window.setTimeout( function () {
				button.textContent = originalLabel;
				button.dataset.inkDeelBesig = '0';
			}, 2000 );
		};

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).then( confirmCopy, function () {
				/* Clipboard write failed (e.g. permissions) — leave the label as-is. */
			} );
		}
	} );
} )();
