/**
 * My Profiel profile-picture upload (pencil on the identity-strip avatar).
 *
 * The pencil button opens a hidden file input; the chosen image is POSTed
 * (multipart, field `foto`) to `ink/v1/profiel/foto`, and every profile
 * <img> in the identity strip is swapped to the returned URL — no reload.
 * Config: `window.inkProfielFoto` (restUrl, nonce, savingText, errorText).
 */
( function () {
	'use strict';

	var cfg = window.inkProfielFoto;
	var trigger = document.querySelector( '[data-ink-profiel-foto-trigger]' );
	var input = document.querySelector( '[data-ink-profiel-foto-input]' );
	if ( ! cfg || ! trigger || ! input ) {
		return;
	}

	var status = document.querySelector( '[data-ink-profiel-foto-status]' );
	var wrap = trigger.parentNode;

	function say( text ) {
		if ( status ) {
			status.textContent = text || '';
		}
	}

	trigger.addEventListener( 'click', function () {
		input.click();
	} );

	input.addEventListener( 'change', function () {
		var file = input.files && input.files[ 0 ];
		if ( ! file ) {
			return;
		}

		var body = new window.FormData();
		body.append( 'foto', file );

		trigger.classList.add( 'is-busy' );
		trigger.disabled = true;
		say( cfg.savingText );

		window.fetch( cfg.restUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': cfg.nonce },
			body: body
		} ).then( function ( res ) {
			return res.ok ? res.json() : res.json().then( function ( err ) {
				return Promise.reject( err );
			}, function () {
				return Promise.reject( {} );
			} );
		} ).then( function ( data ) {
			var imgs = wrap.querySelectorAll( 'img' );
			Array.prototype.forEach.call( imgs, function ( img ) {
				img.src = data.url;
				img.removeAttribute( 'srcset' );
			} );
			say( '' );
		} ).catch( function ( err ) {
			say( ( err && err.message ) || cfg.errorText );
			window.alert( ( err && err.message ) || cfg.errorText );
		} ).then( function () {
			trigger.classList.remove( 'is-busy' );
			trigger.disabled = false;
			input.value = '';
		} );
	} );
}() );
