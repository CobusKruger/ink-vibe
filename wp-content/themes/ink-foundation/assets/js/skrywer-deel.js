/**
 * Skrywerprofiel "Deel" (share) button — copies the profile URL to the
 * clipboard and briefly swaps the button's label to confirm the copy.
 *
 * Thin client only; the button itself is server-rendered by
 * `Ink\Social\SkrywerProfiel::toHtml()` with its labels already carried on
 * `data-ink-deel-*` attributes (both ratified Afrikaans strings — no copy
 * lives in this file). No REST call, no business logic.
 *
 * The label swap is UNCONDITIONAL — it always confirms on click, the same
 * way the Lovable `Writer.tsx` reference's `onShare()` always fires its toast
 * without even checking a copy result. Gating the confirmation behind
 * `navigator.clipboard.writeText()` resolving was a real bug: on any
 * environment where the Clipboard API is unavailable, denied, or slow (a
 * non-focused tab, a restrictive Permissions Policy, an older browser), the
 * click did nothing visible at all — the exact silent-no-op class of bug
 * flagged for this button, not just a missing style. The real copy is still
 * attempted as a best-effort side effect; a `document.execCommand( 'copy' )`
 * fallback covers browsers with no Clipboard API at all.
 */
( function () {
	'use strict';

	function fallbackCopy( text ) {
		var input = document.createElement( 'textarea' );
		input.value = text;
		input.setAttribute( 'readonly', '' );
		input.style.position = 'fixed';
		input.style.opacity = '0';
		document.body.appendChild( input );
		input.select();

		try {
			document.execCommand( 'copy' );
		} catch ( e ) {
			/* best-effort only; the visible confirmation does not depend on this. */
		}

		document.body.removeChild( input );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ink-skrywerprofiel__deel' );

		if ( ! button ) {
			return;
		}

		var url = button.getAttribute( 'data-ink-deel-url' ) || window.location.href;
		var confirmLabel = button.getAttribute( 'data-ink-deel-gekopieer' );
		var originalLabel = button.getAttribute( 'data-ink-deel-label' ) || button.textContent;

		if ( confirmLabel && button.dataset.inkDeelBesig !== '1' ) {
			button.dataset.inkDeelBesig = '1';
			button.textContent = confirmLabel;

			window.setTimeout( function () {
				button.textContent = originalLabel;
				button.dataset.inkDeelBesig = '0';
			}, 2000 );
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( url ).catch( function () {
				fallbackCopy( url );
			} );
		} else {
			fallbackCopy( url );
		}
	} );
} )();
