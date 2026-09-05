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
 * theme.json-global-CSS scoping hooks for the Epic-19 theme-fidelity re-audit
 * (page 14): the sitewide `xxl`/`xxxl` fluid-clamp bug (still open as of the
 * gemeenskap/page-12 pass) and the button height/padding/weight recipe, pinned the
 * same page-scoped `!important` way as gemeenskap/lidmaatskap. This page has no
 * live Lovable route (assembly-only, `page-map.csv`), so its fidelity target is
 * internal design-system consistency with the already-verified pages, not a
 * page-to-page diff — the eyebrow recipe below matches the Lovable-verified
 * gemeenskap plain-uppercase-eyebrow recipe (xs/primary/medium/0.2em), the closest
 * established analog, replacing the previous ungrounded muted-grey/sm/0.08em value.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-hero","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-oor-ink-hero" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"500","textTransform":"uppercase","letterSpacing":"0.2em"}},"fontSize":"xs","textColor":"primary"} -->
		<p class="has-primary-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:500;letter-spacing:0.2em;text-transform:uppercase">Oor INK</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"xxxl"} -->
		<h1 class="wp-block-heading has-xxxl-font-size">Ons missie</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg"} -->
		<p class="has-lg-font-size">INK is &#8217;n niewinsgerigte literêre tuiste gebou rondom &#8217;n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei &#8217;n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">&#8217;n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert 2018.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-org","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background ink-oor-ink-org" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"xxl"} -->
		<h2 class="wp-block-heading has-xxl-font-size">Ons organisasie</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md"} -->
		<p class="has-md-font-size">INK is &#8217;n niewinsgerigte gemeenskapsorganisasie, gestig in 2018.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-kontak","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-oor-ink-kontak" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"xxl"} -->
		<h2 class="wp-block-heading has-xxl-font-size">Kontak</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Het jy &#8217;n vraag of wil jy by INK betrokke raak? Ons hoor graag van jou.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/kontak">Kontak ons</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:pattern {"slug":"ink-foundation/borg-erkenning"} /-->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-oor-ink-meer","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background ink-oor-ink-meer" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"xxl"} -->
		<h2 class="wp-block-heading has-xxl-font-size">Meer oor INK</h2>
		<!-- /wp:heading -->

		<!-- wp:list -->
		<ul>
			<li><a href="/gemeenskap">Die INK-gemeenskap</a></li>
			<li><a href="/uitdagings">Maandelikse uitdagings</a></li>
			<li><a href="/kontak">Word &#8217;n borg</a></li>
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
