/**
 * INK Foundation — Afrikaans required-field message (Stage 2 ruling NR-2).
 *
 * The browser's native required-field bubble ("Please fill out this field.")
 * follows the browser's language, not the page's. On every `required` field that
 * is submitted empty this sets the custom validity message to the localised
 * "Vul hierdie veld in" (passed in via `inkRequiredValidity.message`), and clears
 * it again as soon as the visitor types or changes the value, so the field can
 * validate normally. Only the value-missing case is overridden; other native
 * messages (wrong e-mail format, too short, …) are left alone.
 *
 * Delegated at the document in the capture phase (`invalid` does not bubble), so
 * it covers every form on the page, including ones rendered after load.
 */
( function () {
	'use strict';

	var config = window.inkRequiredValidity || {};
	var message = config.message || 'Vul hierdie veld in';

	function isField( el ) {
		return !! el && typeof el.setCustomValidity === 'function';
	}

	document.addEventListener(
		'invalid',
		function ( event ) {
			var el = event.target;
			if ( ! isField( el ) || ! el.required ) {
				return;
			}
			// Re-check without our own message so a stale custom error never masks a filled field.
			el.setCustomValidity( '' );
			if ( el.validity && el.validity.valueMissing ) {
				el.setCustomValidity( message );
			}
		},
		true
	);

	function clear( event ) {
		var el = event.target;
		if ( isField( el ) && el.required ) {
			el.setCustomValidity( '' );
		}
	}

	document.addEventListener( 'input', clear, true );
	document.addEventListener( 'change', clear, true );
} )();
