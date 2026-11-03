/**
 * The results page's category tabs (Lovable ChallengeResults.tsx). All three panels are in the
 * HTML; this swaps which one shows. Without JavaScript the first panel stays visible.
 */
( function () {
	var list = document.querySelector( '[data-audit-id="results-tabs"]' );

	if ( ! list ) {
		return;
	}

	var tabs = Array.prototype.slice.call( list.querySelectorAll( '[role="tab"]' ) );

	function select( chosen ) {
		tabs.forEach( function ( tab ) {
			var active = tab === chosen;
			var panel = document.getElementById( tab.getAttribute( 'aria-controls' ) );

			tab.setAttribute( 'aria-selected', active ? 'true' : 'false' );

			if ( panel ) {
				panel.hidden = ! active;
			}
		} );
	}

	tabs.forEach( function ( tab ) {
		tab.addEventListener( 'click', function () {
			select( tab );
		} );
	} );
}() );
