<?php
/**
 * Title: Biblioteek-argief
 * Slug: ink-foundation/biblioteek
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Biblioteek-argief: uitgeligte strook, genre-filter, soek en kaartrooster oor biblioteekitems (Storie 10.1, FR-52).
 *
 * The Biblioteek archive shell (Story 10.1). The archive itself is the
 * server-rendered `ink/biblioteek-argief` block (ink-core/Library) — all business
 * logic stays in ink-core (three-layer separation). The section heading reads from
 * the ink-core terminology registry via the `ink_foundation_term()` bridge
 * (single-source, never a bare literal).
 *
 * @package Ink\Foundation
 */

$ink_biblioteek_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'biblioteek', 'Biblioteek' )
	: 'Biblioteek';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" aria-label="<?php echo esc_attr( $ink_biblioteek_label ); ?>" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/biblioteek-argief /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
