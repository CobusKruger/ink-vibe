/**
 * Skryf live counter + per-type placeholder (Story 6.2) — progressive enhancement.
 *
 * A thin client mirror of the ink-core counting rules (Ink\Submission\Counters):
 *   words = UTF-8 non-whitespace tokens; lines = non-blank lines.
 * The authoritative rules live in PHP and are unit-tested there; this only gives
 * live feedback as the skrywer types. With JS off, the form still works — the body
 * keeps its server-rendered placeholder and submits normally.
 *
 * Also toggles the Plaas button's `disabled` state (mirrors Lovable's
 * `disabled={!title.trim() || !content.trim()}`, Write.tsx) — a real, previously
 * unfixed gap: the button rendered fully enabled regardless of empty fields
 * (Epic-19 third-pass re-audit finding, the same "button never disabled" bug
 * class first flagged on lees-gedig's publish control). With JS off the button
 * stays enabled and the server-side `required` attributes are still the actual
 * enforcement — this is a visual/UX affordance only, never the validation itself.
 */
( function () {
	'use strict';

	var form = document.querySelector( '.ink-skryf-form' );
	if ( ! form ) {
		return;
	}

	var title = form.querySelector( '#ink-skryf-title' );
	var body = form.querySelector( '#ink-skryf-body' );
	var counter = form.querySelector( '.ink-skryf-counter' );
	var typeInputs = form.querySelectorAll( 'input[type="radio"][data-counter-mode]' );
	var publishBtn = form.querySelector( '.ink-skryf-submit' );
	if ( ! body || ! counter || ! typeInputs.length ) {
		return;
	}

	var wordsLabel = counter.getAttribute( 'data-words-label' ) || 'woorde';
	var linesLabel = counter.getAttribute( 'data-lines-label' ) || 'reëls';

	function countWords( text ) {
		var matches = text.match( /\S+/gu );
		return matches ? matches.length : 0;
	}

	function countLines( text ) {
		var lines = text.split( /\r\n|\r|\n/ );
		var count = 0;
		for ( var i = 0; i < lines.length; i++ ) {
			if ( lines[ i ].trim() !== '' ) {
				count++;
			}
		}
		return count;
	}

	function selectedInput() {
		for ( var i = 0; i < typeInputs.length; i++ ) {
			if ( typeInputs[ i ].checked ) {
				return typeInputs[ i ];
			}
		}
		return typeInputs[ 0 ];
	}

	function render() {
		var input = selectedInput();
		var mode = input.getAttribute( 'data-counter-mode' );
		var words = countWords( body.value );
		var text = words + ' ' + wordsLabel;
		if ( mode === 'lines_words' ) {
			text = countLines( body.value ) + ' ' + linesLabel + ' · ' + text;
		}
		counter.textContent = text;
	}

	function onTypeChange() {
		var input = selectedInput();
		var placeholder = input.getAttribute( 'data-placeholder' );
		if ( placeholder !== null ) {
			body.setAttribute( 'placeholder', placeholder );
		}
		render();
	}

	function updatePublishState() {
		if ( ! publishBtn || ! title ) {
			return;
		}
		var ready = title.value.trim() !== '' && body.value.trim() !== '';
		publishBtn.disabled = ! ready;
	}

	for ( var i = 0; i < typeInputs.length; i++ ) {
		typeInputs[ i ].addEventListener( 'change', onTypeChange );
	}
	body.addEventListener( 'input', render );
	body.addEventListener( 'input', updatePublishState );
	if ( title ) {
		title.addEventListener( 'input', updatePublishState );
	}

	render();
	updatePublishState();
}() );
