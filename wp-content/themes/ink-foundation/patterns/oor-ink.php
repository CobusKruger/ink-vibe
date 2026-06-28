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
 * (ui-copy-translations.md) — never AI-translated. Org-detail values use the
 * clearly-marked [stigtingsjaar] / [regstatus] placeholders (project-context "Org
 * placeholders" rule) pending real values at a pre-launch content gate — never any
 * US nonprofit legal-status wording.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Oor INK</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"3xl"} -->
		<h1 class="wp-block-heading has-3xl-font-size">Ons missie</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg"} -->
		<p class="has-lg-font-size">INK is 'n niewinsgerigte literêre tuiste gebou rondom 'n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei 'n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">'n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert [stigtingsjaar].</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Ons organisasie</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md"} -->
		<p class="has-md-font-size">INK is 'n niewinsgerigte gemeenskapsorganisasie. Regstatus: [regstatus]. Gestig in [stigtingsjaar].</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Kontak</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Het jy 'n vraag of wil jy by INK betrokke raak? Ons hoor graag van jou.</p>
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

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Meer oor INK</h2>
		<!-- /wp:heading -->

		<!-- wp:list -->
		<ul>
			<li><a href="/gemeenskap">Die INK-gemeenskap</a></li>
			<li><a href="/uitdagings">Maandelikse uitdagings</a></li>
			<li><a href="/kontak">Word 'n borg</a></li>
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
