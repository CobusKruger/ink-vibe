<?php
/**
 * Title: Oor INK-bladsy
 * Slug: ink-foundation/oor-ink
 * Categories: ink-foundation, page
 * Description: Die Oor INK-bladsy (Storie 15.3, FR-60) — missie, kontak, borge en organisasie-bladsye. Saamgestel uit bestaande dele plus duidelik-gemerkte organisasie-plekhouers.
 *
 * Assembly-only (three-layer separation): static mission/about prose + the
 * already-built ink-foundation/borg-erkenning sponsor section (which wraps the
 * server-rendered ink/borg-erkenning block — the sanctioned ink-core seam). No new
 * sponsor logic, no post queries here. All prose is human-authored Afrikaans
 * (ui-copy-translations.md) — never AI-translated. Org details resolved (Story 17.1):
 * founding year = 2018 (provisional per ui-copy-translations.md L33, pending final
 * founder confirmation — a one-line later edit, not a blocker); legal status uses the
 * confirmed generic non-profit framing with no legal-registration detail and never
 * any US nonprofit legal-status wording (project-context "Org placeholders" rule).
 *
 * Section wrapper classes (`ink-oor-ink-hero`/`-org`/`-kontak`/`-meer`) are the
 * layout scoping hooks; the bands, eyebrow, headings, button and links are the
 * shared `assets/css/primitives.css` classes (Fidelity remediation Stage 3). This page has no
 * live Lovable route (assembly-only, `page-map.csv`), so its fidelity target is
 * internal design-system consistency with the already-verified pages, not a
 * page-to-page diff — the eyebrow recipe below matches the Lovable-verified
 * gemeenskap plain-uppercase-eyebrow recipe (xs/primary/medium/0.2em), the closest
 * established analog, replacing the previous ungrounded muted-grey/sm/0.08em value.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-hero ink-band","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="oor-ink-hero" class="wp-block-group alignfull ink-oor-ink-hero ink-band" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"className":"ink-eyebrow ink-eyebrow--tracked"} -->
		<p data-audit-id="oor-ink-hero-eyebrow" class="ink-eyebrow ink-eyebrow--tracked">Oor INK</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"className":"ink-heading-h1-entity"} -->
		<h1 data-audit-id="oor-ink-hero-h1" class="wp-block-heading ink-heading-h1-entity">Ons missie</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg"} -->
		<p data-audit-id="oor-ink-hero-lede" class="has-lg-font-size">INK is &#8217;n niewinsgerigte literêre tuiste gebou rondom &#8217;n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei &#8217;n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p data-audit-id="oor-ink-hero-sub" class="has-muted-text-color has-text-color has-md-font-size">&#8217;n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert 2018.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-org ink-band ink-band--cream","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="oor-ink-org" class="wp-block-group alignfull ink-oor-ink-org ink-band ink-band--cream" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"className":"ink-heading-h2-block"} -->
		<h2 data-audit-id="oor-ink-org-h2" class="wp-block-heading ink-heading-h2-block">Ons organisasie</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md"} -->
		<p data-audit-id="oor-ink-org-text" class="has-md-font-size">INK is &#8217;n niewinsgerigte gemeenskapsorganisasie, gestig in 2018.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-kontak ink-band","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="oor-ink-kontak" class="wp-block-group alignfull ink-oor-ink-kontak ink-band" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"className":"ink-heading-h2-block"} -->
		<h2 data-audit-id="oor-ink-kontak-h2" class="wp-block-heading ink-heading-h2-block">Kontak</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p data-audit-id="oor-ink-kontak-text" class="has-muted-text-color has-text-color has-md-font-size">Het jy &#8217;n vraag of wil jy by INK betrokke raak? Ons hoor graag van jou.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div data-audit-id="oor-ink-kontak-actions" class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-lg"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-lg"><a data-audit-id="oor-ink-kontak-btn" class="wp-block-button__link wp-element-button" href="/kontak">Kontak ons</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"ink-foundation/borg-erkenning"} /-->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-meer ink-band ink-band--cream","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="oor-ink-meer" class="wp-block-group alignfull ink-oor-ink-meer ink-band ink-band--cream" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"className":"ink-heading-h2-block"} -->
		<h2 data-audit-id="oor-ink-meer-h2" class="wp-block-heading ink-heading-h2-block">Meer oor INK</h2>
		<!-- /wp:heading -->

		<!-- wp:list -->
		<ul data-audit-id="oor-ink-meer-list">
			<li data-audit-id="oor-ink-meer-item"><a class="ink-link" data-audit-id="oor-ink-meer-link" href="/gemeenskap">Die INK-gemeenskap</a></li>
			<li><a class="ink-link" href="/uitdagings">Maandelikse uitdagings</a></li>
			<li><a class="ink-link" href="/kontak">Word &#8217;n borg</a></li>
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
