<?php
/**
 * Title: Opleiding-hub
 * Slug: ink-foundation/opleiding
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Opleiding-hulpbronhub: uitgeligte strook, soek en kaartrooster oor opleidingsartikels (Storie 11.1, FR-54).
 *
 * The Opleiding hub shell (Story 11.1). The hub itself is the server-rendered
 * `ink/opleiding-argief` block (ink-core/Training) — all business logic stays in
 * ink-core (three-layer separation). A resource hub, not an LMS. The section
 * heading reads from the ink-core terminology registry via the
 * `ink_foundation_term()` bridge (single-source, never a bare literal).
 *
 * @package Ink\Foundation
 */

$ink_opleiding_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'opleiding', 'Opleiding' )
	: 'Opleiding';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" aria-label="<?php echo esc_attr( $ink_opleiding_label ); ?>" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/opleiding-argief /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
