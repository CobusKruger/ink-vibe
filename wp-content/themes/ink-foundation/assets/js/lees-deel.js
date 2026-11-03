/**
 * The reading page's share button (Lovable ReadStory.tsx `reading-share-btn`): opens the
 * device share sheet where there is one, else copies the page link. No copy of its own:
 * the button's label is "Deel" (docs/ui-copy-translations.md, Share).
 */
( function () {
	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest ? event.target.closest( '[data-ink-deel]' ) : null;

		if ( ! button ) {
			return;
		}

		var data = { title: document.title, url: window.location.href };

		if ( navigator.share ) {
			navigator.share( data ).catch( function () {} );
			return;
		}

		if ( navigator.clipboard && navigator.clipboard.writeText ) {
			navigator.clipboard.writeText( data.url ).catch( function () {} );
		}
	} );
}() );
