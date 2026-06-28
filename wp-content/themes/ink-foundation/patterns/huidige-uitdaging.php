<?php
/**
 * Title: Uitdaging-strook (tuisblad)
 * Slug: ink-foundation/huidige-uitdaging
 * Categories: featured, call-to-action, ink-foundation
 * Description: Statiese uitdaging-toegangspunt vir die Tuisblad (Storie 15.1, FR-59) — nooi besoekers na die uitdagings-argief. Dra geen per-uitdaging-logika nie (die dinamiese "huidige uitdaging"-oppervlak is Epiese 12/12A).
 *
 * Presentation only (three-layer separation): a static teaser that links to the
 * /uitdagings archive. NO challenge query logic lives here. All copy is Afrikaans,
 * human-authored in docs/ui-copy-translations.md (Uitdagingafdeling / "Vir skrywers"
 * rows) — never AI-translated. This is editable block-pattern starting content, so
 * copy sits as raw block markup the way the sibling hero / cta-band patterns do.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"sm","textColor":"accent"} -->
		<p class="has-accent-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Uitdaging</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Maandelikse uitdagings</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">Uitdagings wat jou skryfvermoëns toets, met erkenning vir uitstaande inskrywings en 'n gewaarwaarborgde gehoor.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/uitdagings">Ontdek uitdagings</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
