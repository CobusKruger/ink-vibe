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
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'gedig', 'Gedig' )
	: 'Gedig';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"xs","textColor":"accent"} -->
		<p class="has-accent-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase"><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"fontSize":"3xl"} /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<div class="wp-block-group">
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
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gedig-body /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"margin":{"top":"var:preset|spacing|s-48"}}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--s-48)">
		<!-- wp:ink/leesprompte /-->

		<!-- wp:ink/gemeenskapsreaksies /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
