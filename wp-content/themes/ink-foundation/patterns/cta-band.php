<?php
/**
 * Title: Oproep tot aksie
 * Slug: ink-foundation/cta-band
 * Categories: call-to-action, ink-foundation
 * Description: Die tuisblad se HRA-baan (Epic 19, Storie 19.5, §8) — 'n terracotta-gradiëntbaan (135°, primary → primary-light), rondte-hoeke (24px), gesentreer, met twee dowwe dekoratiewe sirkels en twee xl-knoppies. Voorbladvormgewing leef in home.css.
 *
 * Presentation only (three-layer separation): no business logic. The markup follows
 * Lovable's CallToAction.tsx node for node (section > container > band > circles +
 * content), emitted as raw HTML so WordPress's group layout adds no wrappers, block-gap
 * margins or positioning (acceptance 2026-11-02, as the header). Copy is authored
 * Afrikaans through the `ink-foundation` text domain (Gate D). The gradient panel and
 * circles are painted in assets/css/home.css; the buttons are the `button-primary
 * on-dark` and `button-outline on-dark` primitives at size xl.
 *
 * MECHANISM (accepted, maps/tuis.csv): Lovable's two buttons are `<button>`s with no
 * destination; here they are links to /skryf/ and /ontdek/, styled by the same recipes.
 *
 * @package Ink\Foundation
 */

$ink_cta_write = function_exists( 'ink_foundation_is_member_logged_in' ) && ink_foundation_is_member_logged_in()
	? home_url( '/skryf/' )
	: add_query_arg( 'redirect_to', rawurlencode( home_url( '/skryf/' ) ), home_url( '/teken-in/' ) );
?>
<!-- Bottom padding is deliberately s-64, NOT the s-80 that mirrors the top: the theme footer contributes its own 80px margin-top on every page, so an s-80 bottom padding put 160px between the band and the footer against the 144px above it (64px of borg-strook bottom padding + this section's 80px top padding). Lovable has the identical 160px-below/144px-above asymmetry (measured live), but the product owner overrode it directly on 2026-09-05 ("huge spacing below … matches Lovable, but isn't right; it should match the spacing above"), so 64 + the footer's 80 = the 144px above. A deliberate, disclosed divergence from the reference. -->
<!-- wp:html -->
<section data-audit-id="cta" class="alignfull ink-band ink-band--lg ink-cta">
	<div class="ink-container">
		<div data-audit-id="cta-band" class="ink-cta-band">
			<div data-audit-id="cta-circle-left" class="ink-cta-band__sirkel ink-cta-band__sirkel--links" aria-hidden="true"></div>
			<div data-audit-id="cta-circle-right" class="ink-cta-band__sirkel ink-cta-band__sirkel--regs" aria-hidden="true"></div>
			<div data-audit-id="cta-content" class="ink-cta-band__inhoud">
				<h2 data-audit-id="cta-h2" class="ink-heading-h2-on-dark"><?php esc_html_e( 'Jou woorde verdien lesers', 'ink-foundation' ); ?></h2>
				<p data-audit-id="cta-text" class="ink-cta-band__teks"><?php esc_html_e( "Of jy nou 'n ervare skrywer is of pas begin, ons gemeenskap is hier om te lees, betrokke te raak en jou te help groei.", 'ink-foundation' ); ?></p>
				<div data-audit-id="cta-actions" class="ink-cta-band__knoppies">
					<a data-audit-id="cta-btn-primary" class="ink-btn ink-btn--xl ink-btn--on-dark" href="<?php echo esc_url( $ink_cta_write ); ?>"><?php echo str_replace( 'class="ink-icon"', 'class="ink-icon ink-btn__icon--start"', ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) ) . esc_html__( 'Begin vandag skryf', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a>
					<a data-audit-id="cta-btn-secondary" class="ink-btn ink-btn--xl ink-btn--outline-on-dark" href="<?php echo esc_url( home_url( '/ontdek/' ) ); ?>"><?php echo str_replace( 'class="ink-icon"', 'class="ink-icon ink-btn__icon--start"', ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ) ) . esc_html__( 'Ontdek stories', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- /wp:html -->
