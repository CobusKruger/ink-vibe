<?php
/**
 * Title: Argief-inleiding
 * Slug: ink-foundation/archive-intro
 * Categories: text, ink-foundation
 *
 * Fidelity Stage 4 (row 03): Lovable `Browse.tsx:389-391` puts the band's
 * py-14 md:py-20 on the inner `.container`, not the section, so the section is
 * `ink-band--flush` and `.ink-ontdek-intro__inner` carries the top padding
 * (theme.json). The bottom padding sits on the search band that follows
 * (`ontdek.php`), which continues this cream band down to its bottom rule.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-ontdek-intro ink-band ink-band--cream ink-band--flush","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section data-audit-id="intro" class="wp-block-group alignfull ink-ontdek-intro ink-band ink-band--cream ink-band--flush" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","className":"ink-ontdek-intro__inner","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div data-audit-id="intro-inner" class="wp-block-group alignwide ink-ontdek-intro__inner">
		<p data-audit-id="intro-eyebrow" class="ink-ontdek-intro__boskrif ink-eyebrow ink-eyebrow--kicker">
			<svg data-audit-id="intro-eyebrow-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon" aria-hidden="true" focusable="false"><path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/></svg>
			<span data-audit-id="intro-eyebrow-text">Die INK-gemeenskap</span>
		</p>

		<!-- wp:heading {"level":1,"className":"ink-heading-h1-index"} -->
		<h1 data-audit-id="intro-h1" class="wp-block-heading ink-heading-h1-index">Vind &#8217;n stuk wat jou aand verswelg, of &#8217;n skrywer wat jou bybly.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ink-ontdek-intro__tekst","fontSize":"lg","textColor":"muted-text"} -->
		<p data-audit-id="intro-text" class="ink-ontdek-intro__tekst has-muted-text-color has-text-color has-lg-font-size">Elke storie en gedig op INK en die skrywers daaragter. Soek volgens titel, tema of naam.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
