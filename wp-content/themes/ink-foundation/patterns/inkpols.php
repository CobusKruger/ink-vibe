<?php
/**
 * Title: InkPols-argief
 * Slug: ink-foundation/inkpols
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die InkPols-argief: uitgawes gegroepeer per jaar (Storie 13.2, FR-57).
 *
 * The InkPols archive shell (Story 13.2). The archive itself is the
 * server-rendered `ink/inkpols-argief` block (ink-core/InkPols) — all business
 * logic stays in ink-core (three-layer separation). The section heading reads
 * from the ink-core terminology registry via the `ink_foundation_term()` bridge
 * (single-source, never a bare literal).
 *
 * @package Ink\Foundation
 */

$ink_inkpols_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'inkpols', 'InkPols' )
	: 'InkPols';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" aria-label="<?php echo esc_attr( $ink_inkpols_label ); ?>" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/inkpols-argief /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
