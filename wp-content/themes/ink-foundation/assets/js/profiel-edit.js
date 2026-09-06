/**
 * My Profiel identity-strip edit-profile modal (My Profiel rebuild §5.1).
 *
 * `ProfileEditor` server-renders the hidden `#ink-profiel-redigeer` modal +
 * its form; this client wires the open/close interaction and the REST save,
 * mirroring `vasgespel.js`/`volg.js`'s server-render-then-flip pattern against
 * the `ink/v1/profiel` endpoint.
 *
 * TRIGGER CONTRACT (for the later identity-strip build step): any element
 * anywhere on the page carrying `data-ink-profiel-redigeer-trigger` opens the
 * modal on click. After a successful save, any element on the page carrying
 * `data-ink-profiel-veld="naam"` / `="leuse"` / `="bio"` is updated in place
 * (textContent) with the newly-saved value — the identity strip's own
 * name/tagline/bio markup should carry those attributes to get the live,
 * no-reload update. Neither the trigger nor the display elements exist on the
 * live template yet; their absence is a safe no-op (`querySelectorAll` simply
 * returns nothing).
 *
 * Config (REST root, nonce, the save/cancel/error copy) is provided via
 * `window.inkProfielEdit`.
 */
( function () {
	'use strict';

	var cfg = window.inkProfielEdit;
	if ( ! cfg || ! cfg.restUrl ) {
		return;
	}

	var modal = document.getElementById( 'ink-profiel-redigeer' );
	if ( ! modal ) {
		return;
	}

	var form = modal.querySelector( '[data-ink-profiel-redigeer-vorm]' );
	var statusEl = modal.querySelector( '[data-ink-profiel-redigeer-status]' );

	/**
	 * Any element elsewhere on the page displaying a given profile field, so a
	 * successful save can update it in place without a full reload.
	 *
	 * @param {string} field "naam" | "leuse" | "bio"
	 * @return {Element[]}
	 */
	function displaysFor( field ) {
		return Array.prototype.slice.call(
			document.querySelectorAll( '[data-ink-profiel-veld="' + field + '"]' )
		);
	}

	function openModal() {
		modal.classList.remove( 'is-hidden' );
		modal.setAttribute( 'aria-hidden', 'false' );

		var firstField = form && form.querySelector( 'input, textarea' );
		if ( firstField ) {
			firstField.focus();
		}
	}

	function closeModal() {
		modal.classList.add( 'is-hidden' );
		modal.setAttribute( 'aria-hidden', 'true' );

		if ( statusEl ) {
			statusEl.textContent = '';
		}
	}

	function setStatus( text ) {
		if ( statusEl ) {
			statusEl.textContent = text || '';
		}
	}

	function onSubmit( event ) {
		event.preventDefault();

		if ( ! form ) {
			return;
		}

		var payload = {};
		var nameField = form.elements.namedItem( 'display_name' );
		var taglineField = form.elements.namedItem( 'tagline' );
		var bioField = form.elements.namedItem( 'bio' );

		if ( nameField ) {
			payload.display_name = nameField.value;
		}
		if ( taglineField ) {
			payload.tagline = taglineField.value;
		}
		if ( bioField ) {
			payload.bio = bioField.value;
		}

		var submitButton = form.querySelector( 'button[type="submit"]' );
		if ( submitButton ) {
			submitButton.disabled = true;
		}
		setStatus( cfg.savingText || '' );

		window.fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': cfg.nonce
			},
			body: JSON.stringify( payload )
		} ).then( function ( res ) {
			return res.ok ? res.json() : Promise.reject( res );
		} ).then( function ( data ) {
			if ( data && 'undefined' !== typeof data.display_name ) {
				displaysFor( 'naam' ).forEach( function ( el ) {
					el.textContent = data.display_name;
				} );
			}
			if ( data && 'undefined' !== typeof data.tagline ) {
				displaysFor( 'leuse' ).forEach( function ( el ) {
					el.textContent = data.tagline;

					// The quote-mark wrapper always renders (Tier-2 fix): toggle its
					// empty/non-empty visibility here rather than touching its
					// textContent, which would also wipe the permanent quote marks
					// that sit OUTSIDE this inner span.
					var wrap = el.closest( '[data-ink-profiel-leuse-wrap]' );
					if ( wrap ) {
						wrap.classList.toggle( 'is-empty', '' === data.tagline );
					}
				} );
			}
			if ( data && 'undefined' !== typeof data.bio ) {
				displaysFor( 'bio' ).forEach( function ( el ) {
					el.textContent = data.bio;
				} );
			}

			closeModal();
		} ).catch( function () {
			setStatus( cfg.errorText || '' );
		} ).then( function () {
			if ( submitButton ) {
				submitButton.disabled = false;
			}
		} );
	}

	function init() {
		var triggers = document.querySelectorAll( '[data-ink-profiel-redigeer-trigger]' );
		Array.prototype.forEach.call( triggers, function ( trigger ) {
			trigger.addEventListener( 'click', openModal );
		} );

		var closers = modal.querySelectorAll( '[data-ink-profiel-redigeer-sluit]' );
		Array.prototype.forEach.call( closers, function ( closer ) {
			closer.addEventListener( 'click', closeModal );
		} );

		document.addEventListener( 'keydown', function ( event ) {
			if ( 'Escape' === event.key && ! modal.classList.contains( 'is-hidden' ) ) {
				closeModal();
			}
		} );

		if ( form ) {
			form.addEventListener( 'submit', onSubmit );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
