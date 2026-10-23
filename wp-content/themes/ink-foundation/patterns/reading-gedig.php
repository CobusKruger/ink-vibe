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
 * the genre pill is the primitives `pill tint` in the sage tone (`ink-tone-accent`);
 * Lovable tints poetry at 15%, but owner ruling NR-3 sets every sage tint at 10%
 * (Fidelity remediation Stage 3), so no poetry-only override remains. The
 * hint pill's copy ("Merk hierdie reël") reuses the one ratified INK phrase for
 * line-reaction guidance (afrikaans-terms.md's "hooglignering" row) — no new
 * Afrikaans invented. The `ink/leesprompte` panel was never embedded here (no
 * Lovable equivalent) and, as of the fourth pass, has been removed from
 * reading-storie/reading-artikel too (docs/theme-fidelity-audit-handoff.md
 * §6/fourth pass) — a product-owner call that the panel's copy read as broken
 * Afrikaans, matched nothing in Lovable, and served no purpose. The block
 * class itself was deleted, not just unembedded.
 *
 * lees-gedig re-audit (docs/theme-fidelity-audit-handoff.md §6, this pass):
 * the title's font-family pin to literal Georgia (a prior pass's deliberate
 * replica of a then-real Lovable bug — Lovable declared Lora but shipped no
 * `@font-face` for it, silently falling back to Georgia) is REMOVED now that the
 * bug is fixed upstream in ink-lovable (Lora loads for real there); the title
 * falls through to the theme's own `elements.heading` font-family token
 * (`var:preset|font-family|heading` → the theme's self-hosted Lora stack), the
 * same mechanism every other `wp:post-title` in the theme already relies on —
 * no literal font stack repeated here. The whole-poem reaction bar (product-owner
 * decision: collapse `ink/reaksie-tellers` to a single hartjie count on this page
 * only, via a new `{"variant":"enkel"}` block attribute — storie/artikel keep the
 * un-collapsed `render()` default and are untouched) has also been pulled out of
 * the body section to sit as its own top-level block, directly under
 * `<main class="ink-reading-main">` (see `templates/single-gedig.html`) rather
 * than nested inside the body `<section>`'s narrow content group — CSS
 * `position:sticky`'s containing block is the element's own DOM parent, so this
 * placement is what lets `.ink-reaksie-bar` float pinned to the viewport bottom
 * while the Author-card and Gemeenskapsreaksies sections beneath it scroll past
 * (matching Lovable's `ReadStory.tsx` "Floating Action Bar", a `sticky bottom-6`
 * div that is likewise a direct child of `<main>`) — see `assets/css/reading.css`
 * (enqueued only on `is_singular('gedig')`, so this never reaches storie/artikel).
 * `data-audit-id` measurement anchors were added on the title (via a
 * `render_block_core/post-title` filter, gedig-scoped, in `functions.php` —
 * `wp:post-title` is a dynamic core block with no static markup to hand-edit),
 * the type badge, the hint pill, each poem line (`GedigBody::toHtml()`), and the
 * author-card name/bio (`ReadingAuthorCard::toHtml()`).
 *
 * Fourth pass (docs/theme-fidelity-audit-handoff.md, 2026-09-05): removed the
 * invented "deur" label between avatar and name (Lovable's `ReadStory.tsx` has
 * none, just avatar+name as one `<Link>` — approximated here with `isLink` on
 * both the `core/avatar` and `core/post-author-name` blocks, since this pattern
 * stays presentation-only core blocks rather than hand-built markup); fixed the
 * separator to a real bullet (`•`, U+2022, was `·` middot); header section
 * padding corrected to Lovable's uniform `py-12` (48px top+bottom, was 64/48);
 * added the header-section background (`bg-cream/50` — imperceptible against
 * this theme's own page background, which is the same colour, but added for
 * literal parity per product-owner decision to treat gedig/storie identically).
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'gedig', 'Gedig' )
	: 'Gedig';

$ink_gedig_feather_svg = '<span aria-hidden="true" style="display:inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>';

$ink_gedig_heart_svg = '<span aria-hidden="true" style="display:inline-flex"><svg data-audit-id="reading-hint-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--sm ink-band--stone ink-band--border-b","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="reading-header" class="wp-block-group alignfull ink-band ink-band--sm ink-band--stone ink-band--border-b" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"className":"ink-gedig-intro","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div data-audit-id="reading-header-inner" class="wp-block-group ink-gedig-intro">
		<!-- wp:paragraph {"className":"ink-lees-tipe ink-pill ink-pill--tint ink-tone-accent","textAlign":"center"} -->
		<p data-audit-id="gedig-badge" class="ink-lees-tipe ink-pill ink-pill--tint ink-tone-accent has-text-align-center"><?php echo $ink_gedig_feather_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","className":"ink-heading-h1-reading-poem"} /-->

		<!-- wp:group {"className":"ink-lees-byline","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div data-audit-id="reading-byline" class="wp-block-group ink-lees-byline">
			<!-- wp:avatar {"size":40,"isLink":true,"className":"ink-avatar ink-avatar--40"} /-->

			<!-- wp:post-author-name {"isLink":true,"fontSize":"md","textColor":"ink-text","style":{"typography":{"fontWeight":"500"}}} /-->

			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<p data-audit-id="reading-byline-sep" class="has-muted-text-color has-text-color has-md-font-size">•</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"md","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ink-gedig-hint ink-pill ink-pill--hint ink-tone-accent"} -->
		<p data-audit-id="gedig-hint" class="ink-gedig-hint ink-pill ink-pill--hint ink-tone-accent"><?php echo $ink_gedig_heart_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html__( 'Merk hierdie reël', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--poem","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="reading-body" class="wp-block-group alignfull ink-band ink-band--poem" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"576px"}} -->
	<div data-audit-id="gedig-body-col" class="wp-block-group">
		<!-- wp:ink/gedig-body /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"lock":{"move":true,"remove":true},"className":"ink-reaksie-bar ink-toolbar"} -->
<div data-audit-id="gedig-engagement-bar" class="wp-block-group ink-reaksie-bar ink-toolbar">
	<!-- wp:ink/reaksie-tellers {"variant":"enkel"} /-->

	<!-- wp:ink/kommentaar-telling /-->

	<!-- wp:ink/leeslys-knoppie /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-outeur-kaart-band ink-band ink-band--sm ink-band--stone ink-band--border-y","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="reading-author-band" class="wp-block-group alignfull ink-outeur-kaart-band ink-band ink-band--sm ink-band--stone ink-band--border-y" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/outeur-kaart /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--sm","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="reading-responses" class="wp-block-group alignfull ink-band ink-band--sm" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
