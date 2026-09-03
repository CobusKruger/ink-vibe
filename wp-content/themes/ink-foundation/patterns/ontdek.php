<?php
/**
 * Title: Ontdek-skanderaal
 * Slug: ink-foundation/ontdek
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Ontdek-skanderaal: konteks-inleiding, soek, ontdek-vlakke, Bydraes/Skrywers-oortjies en die werke-/skrywers-argief.
 *
 * The hub shell (Story 8.1). The tab labels read from the ink-core terminology
 * registry via the `ink_foundation_term()` bridge (single-source, never a bare
 * literal). The works/skrywers archives are the server-rendered `ink/ontdek-werke`
 * / `ink/ontdek-skrywers` blocks (ink-core/Discovery) — all business logic stays
 * in ink-core (three-layer). The "Bydraes"/"Skrywers" nav is a real toggling
 * tab (Theme-Fidelity re-audit, page 11): `ontdek-tabs.js` shows one panel and
 * hides the other, matching Lovable's `Browse.tsx` `TabButton` behaviour; both
 * panels are server-rendered up-front (no REST/AJAX, AD-7) so the `#bydraes`/
 * `#skrywers` anchors still work — and both sections still render — with JS off.
 *
 * @package Ink\Foundation
 */

$ink_bydraes_label  = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'bydrae_plural', 'Bydraes' )
	: 'Bydraes';
$ink_skrywers_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'skrywer_plural', 'Skrywers' )
	: 'Skrywers';
?>
<!-- wp:pattern {"slug":"ink-foundation/archive-intro"} /-->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-soek /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-vlakke /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<nav class="ink-ontdek-tabs" aria-label="<?php esc_attr_e( 'Ontdek-oortjies', 'ink-foundation' ); ?>">
			<button type="button" class="ink-ontdek-tabs__knoppie is-active" data-ink-ontdek-tab="bydraes" aria-selected="true">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon" aria-hidden="true" focusable="false"><path d="M8 21h12a2 2 0 0 0 2-2v-2H10v2a2 2 0 1 1-4 0V5a2 2 0 1 0-4 0v3h4"/><path d="M19 17V5a2 2 0 0 0-2-2H4"/><path d="M15 8h-5"/><path d="M15 12h-5"/></svg>
				<span><?php echo esc_html( $ink_bydraes_label ); ?></span>
			</button>
			<button type="button" class="ink-ontdek-tabs__knoppie" data-ink-ontdek-tab="skrywers" aria-selected="false">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon" aria-hidden="true" focusable="false"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
				<span><?php echo esc_html( $ink_skrywers_label ); ?></span>
			</button>
		</nav>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" id="bydraes" data-ink-ontdek-panel="bydraes" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-werke /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" id="skrywers" data-ink-ontdek-panel="skrywers" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-skrywers /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
