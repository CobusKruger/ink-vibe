<?php
/**
 * Title: Leesblad — Gedig
 * Slug: ink-foundation/reading-gedig
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n gedig — tipe-etiket, titel, byskrif en die strofe-bewuste digbundel-uitleg (Storie 7.2, FR-25). Reëls, leë reëls/strofes en inkeping word verbatim bewaar; Romeinse strofemerkers word gestileer.
 *
 * Presentation only (three-layer separation). The reading header is core blocks
 * resolved per-post at render time; the eyebrow type label comes from the ink-core
 * terminology registry via the `ink_foundation_term()` bridge (single-source). The
 * poem body renders through the `ink/gedig-body` server block (ink-core, AD-7),
 * which bypasses wpautop so the 6.3-stored verbatim line/stanza structure survives.
 * No WP comments UI — comments are disabled site-wide (Ink\Engagement\Comments).
 *
 * Poetry-vs-prose fidelity (Post-Epic-19 lees-gedig pass): Lovable's reader gives
 * poetry its own genre colour (accent/"sage" green, not the primary/"terracotta"
 * orange the storie reader uses) plus a decorative feather glyph on the type pill,
 * an italic serif title (vs. storie's upright title), and a narrower body column —
 * the `.ink-gedig` line/stanza typography itself lives in theme.json's global CSS
 * (page-scoped by the block's own `ink-gedig` output class, not a new class here).
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'gedig', 'Gedig' )
	: 'Gedig';

$ink_gedig_feather_svg = '<span aria-hidden="true" style="display:inline-flex;vertical-align:-2px;margin-right:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"ink-lees-tipe","textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"sm","textColor":"accent"} -->
		<p class="ink-lees-tipe has-text-align-center has-accent-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:500"><?php echo $ink_gedig_feather_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","fontSize":"4xl","style":{"typography":{"fontStyle":"italic","fontWeight":"600"}}} /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":40,"style":{"border":{"radius":"9999px"}}} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( 'deur', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:post-author-name {"fontSize":"sm","textColor":"muted-text"} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size">·</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"sm","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:ink/reaksie-tellers /-->

		<!-- wp:ink/leeslys-knoppie /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"576px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gedig-body /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"margin":{"top":"var:preset|spacing|s-48"}}},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--s-48)">
		<!-- wp:ink/leesprompte /-->

		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
