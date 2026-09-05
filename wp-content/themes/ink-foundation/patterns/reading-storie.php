<?php
/**
 * Title: Leesblad — Storie
 * Slug: ink-foundation/reading-storie
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n storie (prosa) — tipe-etiket, titel, byskrif en 'n leesbare hoofkolom op die 768px-leesmaat (Argetipe C, Storie 7.1, FR-24).
 *
 * Presentation only (three-layer separation). The post title, author, date and
 * body are core blocks resolved per-post at render time; the only ink-core
 * touch is the Afrikaans type label, sourced from the terminology registry via
 * the `ink_foundation_term()` bridge (single-source, never a bare literal).
 * No WP comments UI — comments are disabled site-wide (Ink\Engagement\Comments,
 * Story 1.8) and the reading surface adds none.
 *
 * Text-highlight-reactions hint (post-Epic-19 storie fidelity pass, FR-24/26):
 * mirrors reading-gedig's `ink-gedig-hint` pill, but with the ratified copy for
 * THIS interaction — "Kies enige teks om jou gunsteling passasies uit te lig"
 * (docs/ui-copy-translations.md row 421, the exact translation of Lovable's own
 * "Select any text to highlight your favorite passages" wenk-etiket) and the
 * `highlight`/`highlight-foreground` design tokens rather than gedig's
 * accent-green tint, matching Lovable's own `bg-highlight/20` treatment for
 * this specific hint pill.
 *
 * Theme-fidelity THIRD pass (docs/theme-fidelity-audit-handoff.md, lees-storie,
 * 2026-09-03): the second pass's own "done" claims for this page didn't
 * survive fresh Tier-0/1/2 scrutiny — three real gaps found and fixed here.
 * (1) `ink/reaksie-tellers` rendered the un-collapsed 3-reaction `'volledig'`
 * default (confirmed live: "♥ 1 hartjie 👍 0 duim op ✨ 0 wows"), not the
 * single-heart `'enkel'` variant — the product-owner decision to collapse this
 * display (originally lees-gedig-only) is a decision about `ReadStory.tsx`'s
 * shared "Floating Action Bar" component, not gedig-specific, so it applies
 * here too. (2) `.ink-reaksie-bar` was nested inside the body `<section>`, so
 * `position:sticky` (assets/css/reading.css, now enqueued on this page too)
 * had no tall containing block to float across and rendered as a plain static
 * pill — moved to a top-level block, direct child of `<main class="ink-
 * reading-main">` (templates/single-storie.html), the same fix already applied
 * to reading-gedig.php and the same DOM shape Lovable's own sticky div has.
 * (3) `ink/outeur-kaart` was never wired in at all — Lovable's `ReadStory.tsx`
 * "Author Section" is shared, UNCONDITIONAL code (not gated by `isPoetry`), so
 * lees-storie is missing a section lees-gedig already has; added, reusing the
 * exact `ink/outeur-kaart` block + `.ink-outeur-kaart-band` framing
 * reading-gedig.php already established. `data-audit-id` measurement anchors
 * were added throughout (title via a `render_block_core/post-title` filter,
 * first paragraph via a new `render_block_core/post-content` filter — both
 * `functions.php`, storie-scoped; the badge/hint pills directly here; the
 * engagement bar wrapper directly here; author-card name/bio already
 * parametrised in `Ink\Social\ReadingAuthorCard`).
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'storie', 'Storie' )
	: 'Storie';

$ink_storie_highlighter_svg = '<span aria-hidden="true" style="display:inline-flex;vertical-align:-2px;margin-right:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"ink-lees-tipe","textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"sm","textColor":"primary"} -->
		<p data-audit-id="storie-badge" class="ink-lees-tipe has-text-align-center has-primary-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:500"><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","fontSize":"xxxxxl","style":{"typography":{"fontWeight":"600"}}} /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":40,"style":{"border":{"radius":"9999px"}}} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( 'deur', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:post-author-name {"fontSize":"sm","textColor":"ink-text","style":{"typography":{"fontWeight":"500"}}} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size">·</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"sm","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ink-storie-hint","textAlign":"center","fontSize":"sm"} -->
		<p data-audit-id="storie-hint" class="ink-storie-hint has-text-align-center has-sm-font-size"><?php echo $ink_storie_highlighter_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html__( 'Kies enige teks om jou gunsteling passasies uit te lig', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:post-content {"className":"ink-lees-storie__prose","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"fontSize":"lg","layout":{"type":"constrained","contentSize":"672px"}} /-->
</section>
<!-- /wp:group -->

<!-- wp:group {"lock":{"move":true,"remove":true},"className":"ink-reaksie-bar"} -->
<div data-audit-id="storie-engagement-bar" class="wp-block-group ink-reaksie-bar">
	<!-- wp:ink/reaksie-tellers {"variant":"enkel"} /-->

	<!-- wp:ink/kommentaar-telling /-->

	<!-- wp:ink/leeslys-knoppie /-->
</div>
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
		<!-- wp:ink/leesprompte /-->

		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
