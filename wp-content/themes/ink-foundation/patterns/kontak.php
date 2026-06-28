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
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Kontak</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"3xl"} -->
		<h1 class="wp-block-heading has-3xl-font-size">Kom in kontak met INK</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">Het jy 'n vraag, 'n voorstel, of wil jy as borg betrokke raak? Stuur ons 'n boodskap.</p>
		<!-- /wp:paragraph -->

		<!-- wp:ink/kontak-vorm /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
