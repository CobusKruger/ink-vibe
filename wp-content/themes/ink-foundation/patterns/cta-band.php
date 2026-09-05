<?php
/**
 * Title: Oproep tot aksie
 * Slug: ink-foundation/cta-band
 * Categories: call-to-action, ink-foundation
 * Description: Die tuisblad se HRA-baan (Epic 19, Storie 19.5, §8) — 'n terracotta-gradiëntbaan (135°, primary → primary-light), rondte-hoeke (24px), gesentreer, met twee dowwe dekoratiewe sirkels en twee xl-knoppies. Voorbladvormgewing leef in home.css.
 *
 * Presentation only (three-layer separation): no business logic. This is a locked,
 * static pattern — copy is authored Afrikaans through the `ink-foundation` text domain
 * (Gate D), colours/spacing/radius all resolve to theme.json tokens (Gate A; "white" =
 * the `surface-alt` token). The gradient, the two aria-hidden decorative circles, the
 * rounded band and the inverted xl button treatment are painted in assets/css/home.css
 * (front-page-only, which is correct — the CTA band is a home section). The button icons
 * are decorative inline SVGs via ink_foundation_icon() (§0.9); the band fades up once on
 * mount via .ink-animate-fade-up (reduced-motion safe, §0.6).
 *
 * @package Ink\Foundation
 */

?>
<!-- Bottom padding is deliberately s-64, NOT the s-80 that mirrors the top: the theme footer contributes its own 80px margin-top on every page, so an s-80 bottom padding put 160px between the band and the footer against the 144px above it (64px of borg-strook bottom padding + this section's 80px top padding). Lovable has the identical 160px-below/144px-above asymmetry (measured live), but the product owner overrode it directly on 2026-09-05 ("huge spacing below … matches Lovable, but isn't right; it should match the spacing above"), so 64 + the footer's 80 = the 144px above. A deliberate, disclosed divergence from the reference. -->
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-80","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-80);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","className":"ink-cta-band ink-animate-fade-up","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide ink-cta-band ink-animate-fade-up">
		<!-- wp:html -->
		<span class="ink-cta-band__sirkel ink-cta-band__sirkel--links" aria-hidden="true"></span>
		<span class="ink-cta-band__sirkel ink-cta-band__sirkel--regs" aria-hidden="true"></span>
		<!-- /wp:html -->

		<!-- wp:group {"className":"ink-cta-band__inhoud","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-cta-band__inhoud">
			<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"hero","textColor":"surface-alt"} -->
			<h2 class="wp-block-heading has-text-align-center has-surface-alt-color has-text-color has-hero-font-size"><?php esc_html_e( 'Jou woorde verdien lesers', 'ink-foundation' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","className":"ink-cta-band__teks","fontSize":"lg","textColor":"surface-alt"} -->
			<p class="has-text-align-center ink-cta-band__teks has-surface-alt-color has-text-color has-lg-font-size"><?php esc_html_e( "Of jy nou 'n ervare skrywer is of pas begin, ons gemeenskap is hier om te lees, betrokke te raak en jou te help groei.", 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"ink-cta-band__knoppie ink-cta-band__knoppie--primer ink-btn-icon"} -->
				<div class="wp-block-button ink-cta-band__knoppie ink-cta-band__knoppie--primer ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/skryf"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) . esc_html__( 'Begin vandag skryf', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"ink-cta-band__knoppie ink-cta-band__knoppie--sekonder ink-btn-icon"} -->
				<div class="wp-block-button ink-cta-band__knoppie ink-cta-band__knoppie--sekonder ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/lees"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ) . esc_html__( 'Ontdek stories', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
