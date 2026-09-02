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
 *
 * lees-gedig fidelity pass (17-item audit, docs/theme-fidelity-audit-handoff.md §6):
 * the genre pill's 15%-tint background is a poetry-only deviation from the shared
 * `.ink-lees-tipe` class (storie/artikel stay at the shared 10% — verified against
 * Lovable's own source, which tints poetry differently from prose), so it is an
 * inline-style override on this pill only, not a change to the shared class. The
 * title's font-family is pinned to literal Georgia (not the theme's Lora/Georgia
 * heading stack) per an explicit product-owner decision: Lovable's own preview
 * never loads Lora and silently renders Georgia for every visitor, so this
 * replicates that rendered reality rather than "fixing" it. The hint pill's copy
 * ("Merk hierdie reël") reuses the one ratified INK phrase for line-reaction
 * guidance (afrikaans-terms.md's "hooglignering" row) — no new Afrikaans invented.
 * The `ink/leesprompte` panel is cut here per product-owner decision (Lovable has
 * no equivalent element on the reading page); the block/class stays registered
 * and still renders on reading-storie / reading-artikel, which keep using it.
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'gedig', 'Gedig' )
	: 'Gedig';

$ink_gedig_feather_svg = '<span aria-hidden="true" style="display:inline-flex;vertical-align:-2px;margin-right:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>';

$ink_gedig_heart_svg = '<span aria-hidden="true" style="display:inline-flex;vertical-align:-2px;margin-right:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}},"border":{"bottom":{"width":"1px","color":"var:preset|color|border","style":"solid"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24);border-bottom-width:1px;border-bottom-color:var(--wp--preset--color--border);border-bottom-style:solid">
	<!-- wp:group {"className":"ink-gedig-intro","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group ink-gedig-intro">
		<!-- wp:paragraph {"className":"ink-lees-tipe","textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"sm","textColor":"accent"} -->
		<p class="ink-lees-tipe has-text-align-center has-accent-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:500;background-color:color-mix(in srgb, currentColor 15%, transparent)"><?php echo $ink_gedig_feather_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","fontSize":"xxxxl","style":{"typography":{"fontStyle":"italic","fontWeight":"600","fontFamily":"Georgia, serif","letterSpacing":"-0.9px","lineHeight":"1.111"}}} /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":40,"style":{"border":{"radius":"9999px"}}} /-->

			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-md-font-size"><?php esc_html_e( 'deur', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:post-author-name {"fontSize":"md","textColor":"ink-text","style":{"typography":{"fontWeight":"500"}}} /-->

			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-md-font-size">·</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"md","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ink-gedig-hint","fontSize":"sm"} -->
		<p class="ink-gedig-hint has-sm-font-size"><?php echo $ink_gedig_heart_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html__( 'Merk hierdie reël', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"576px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gedig-body /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"className":"ink-reaksie-bar"} -->
		<div class="wp-block-group ink-reaksie-bar">
			<!-- wp:ink/reaksie-tellers /-->

			<!-- wp:ink/leeslys-knoppie /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","className":"ink-outeur-kaart-band","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}},"border":{"top":{"width":"1px","color":"var:preset|color|border","style":"solid"},"bottom":{"width":"1px","color":"var:preset|color|border","style":"solid"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-outeur-kaart-band" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24);border-top-width:1px;border-top-color:var(--wp--preset--color--border);border-top-style:solid;border-bottom-width:1px;border-bottom-color:var(--wp--preset--color--border);border-bottom-style:solid;background-color:color-mix(in srgb, var(--wp--preset--color--secondary) 30%, transparent)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/outeur-kaart /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
