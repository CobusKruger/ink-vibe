<?php
/**
 * Title: Kontak-bladsy
 * Slug: ink-foundation/kontak
 * Categories: ink-foundation, page
 * Description: Die Kontak-bladsy (Storie 15.4, FR-61) — 'n held plus die pasgemaakte ink-core kontakvorm-blok (ink/kontak-vorm). Geen CF7 / Fluent Forms nie.
 *
 * Presentation only (three-layer separation): a hero intro plus the server-rendered
 * ink/kontak-vorm block (Ink\Forms\ContactForm) — the form markup, nonce, field names
 * and handler ALL live in ink-core. The theme only embeds the block. No form logic
 * here. Copy is Afrikaans; the Kontak microcopy not yet curated in
 * ui-copy-translations.md is flagged with the standing human-copy-pending marker
 * inside the server block (docs/afrikaans-copy-worklist.md).
 *
 * Theme visual-fidelity Phase 2, page 15 (docs/theme-fidelity-rework-plan.md).
 * No live Lovable route exists for this page (MISSING_IN_CURRENT_LOVABLE_REPO,
 * page-map.csv) — fidelity target is internal design-system consistency with
 * the 14 already-verified pages, same approach as page 13/lidmaatskap. Eyebrow
 * matched to the sitewide plain-eyebrow recipe (xs/primary/500/0.2em, see
 * oor-ink.php/gemeenskap.php); the `ink-kontak-hero` class scopes the still-open
 * "xxxl" fluid-clamp() font-size pin (theme.json global styles.css) the same way
 * `ink-lidmaatskap-hero`/`ink-oor-ink-hero` already do. The form itself had ZERO
 * CSS before this pass — see assets/css/kontak.css.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-kontak-hero","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<section class="wp-block-group alignfull ink-kontak-hero" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500","textTransform":"uppercase","letterSpacing":"0.2em"}},"fontSize":"xs","textColor":"primary"} -->
		<p class="has-primary-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:500;letter-spacing:0.2em;text-transform:uppercase">Kontak</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"xxxl"} -->
		<h1 class="wp-block-heading has-xxxl-font-size">Kom in kontak met INK</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">Het jy &#8217;n vraag, &#8217;n voorstel, of wil jy as borg betrokke raak? Stuur ons &#8217;n boodskap.</p>
		<!-- /wp:paragraph -->

		<!-- wp:ink/kontak-vorm /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
