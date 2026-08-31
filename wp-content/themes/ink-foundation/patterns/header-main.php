<?php
/**
 * Title: INK Header
 * Slug: ink-foundation/header-main
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Werf-wye kopstuk (Epic 19, Storie 19.2, §1) — kleefbaar (sticky), deurskynende oppervlak + agtergrond-vervaging + 1px onderrand, 64px-ry. Veer-glief (terracotta) langs die Lora-woordmerk; navigasie-skakels met onderstreep-glы en fokusring, wat invou na 'n hamburger op klein skerms; "Begin skryf" as primêre HRA-knoppie (nie 'n kaal skakel nie).
 *
 * Presentation only (three-layer separation): no business logic. The sticky /
 * translucent / blur / border / row-height / nav-hover treatment lives site-wide
 * on the `is-style-ink-header` block style (functions.php) — NOT in home.css, which
 * is front-page-only, because the header renders on every page. Copy is authored
 * Afrikaans via the `ink-foundation` text domain. The feather is a decorative
 * inline SVG (aria-hidden), coloured via the `primary` token (§0.9). The site-title
 * renders at heading level 0 (a <p>, not an <h1>) so the page keeps a single visible
 * <h1> (the hero heading).
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"align":"full","className":"is-style-ink-header","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-ink-header" style="padding-top:0;padding-bottom:0;padding-left:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","className":"ink-header-row","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide ink-header-row">
		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","verticalAlignment":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:html -->
			<span class="ink-header-feather" aria-hidden="true" style="display:inline-flex;color:var(--wp--preset--color--primary)"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>
			<!-- /wp:html -->

			<!-- wp:site-title {"level":0,"fontFamily":"display","fontSize":"xl","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"textColor":"ink-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"flex","verticalAlignment":"center","flexWrap":"nowrap"}} -->
		<div class="wp-block-group">
			<!-- wp:navigation {"textColor":"muted-text","overlayTextColor":"text","overlayBackgroundColor":"surface","style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"flex","justifyContent":"right","orientation":"horizontal"}} -->
				<!-- wp:navigation-link {"label":"Tuis","url":"/"} /-->
				<!-- wp:navigation-link {"label":"Ontdek","url":"/ontdek"} /-->
				<!-- wp:navigation-link {"label":"Opleiding","url":"/opleiding"} /-->
				<!-- wp:navigation-link {"label":"Uitdagings","url":"/uitdagings"} /-->
				<!-- wp:navigation-link {"label":"Gemeenskap","url":"/gemeenskap"} /-->
				<!-- wp:navigation-link {"label":"My profiel","url":"/my-profiel"} /-->
			<!-- /wp:navigation -->

			<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-ink-primary","fontSize":"sm","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-8","right":"var:preset|spacing|s-12","bottom":"var:preset|spacing|s-8","left":"var:preset|spacing|s-12"}}}} -->
				<div class="wp-block-button is-style-ink-primary"><a class="wp-block-button__link has-sm-font-size wp-element-button" style="padding-top:var(--wp--preset--spacing--s-8);padding-right:var(--wp--preset--spacing--s-12);padding-bottom:var(--wp--preset--spacing--s-8);padding-left:var(--wp--preset--spacing--s-12)" href="/skryf"><?php esc_html_e( 'Begin skryf', 'ink-foundation' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
