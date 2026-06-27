<?php
/**
 * Title: Leesblad — Uitdaging
 * Slug: ink-foundation/reading-uitdaging
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n uitdaging — etiket, titel, sluitingsdatum/status, opdrag en inskrywings (Storie 12.1, FR-45).
 *
 * Presentation only (three-layer separation). The reading header is core blocks
 * resolved per-post at render time; the eyebrow label comes from the ink-core
 * terminology registry via the `ink_foundation_term()` bridge (single-source). The
 * editorial brief (opdrag, literêre middele, reëls, prys, hulpbronne) is authored as
 * the uitdaging post body and renders through core `post-content`. The sluitingsdatum,
 * Oop/Gesluit status and inskrywings list are the server-rendered
 * `ink/uitdaging-besonderhede` block (ink-core/Challenges) — all business logic stays
 * in ink-core. No WP comments UI — comments are disabled site-wide.
 *
 * @package Ink\Foundation
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'uitdaging', 'Uitdaging' )
	: 'Uitdaging';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"xs","textColor":"accent"} -->
		<p class="has-accent-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"fontSize":"3xl"} /-->

		<!-- wp:ink/uitdaging-besonderhede /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:post-content {"lock":{"move":true,"remove":true}} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
