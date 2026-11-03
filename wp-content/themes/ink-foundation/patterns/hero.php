<?php
/**
 * Title: Held-seksie
 * Slug: ink-foundation/hero
 * Categories: featured, ink-foundation
 * Description: Tuisblad-held (Epic 19, Storie 19.2, §2) — twee-kolom-uitleg (≥1024px: inhoud links, uitdaging-kaart regs) met kenteken-pil, gradiënt-opskrif en die plus-patroon-tekstuur. Enkel-kolom onder 1024px via home.css. Presentasie alleen (drie-laag-skeiding): geen besigheidslogika nie.
 *
 * The markup follows Lovable's HeroSpotlight.tsx node for node (section > container >
 * grid > content | aside), emitted as raw HTML so WordPress's group layout adds no
 * wrappers, block-gap margins or positioning of its own (acceptance 2026-11-02, as the
 * header). The RIGHT column hosts the `ink/huidige-uitdaging` block in its COMPACT
 * variant (Storie 19.3, §3); all per-uitdaging data lives in `ink-core`. Copy is
 * authored Afrikaans (docs/ui-copy-translations.md — Held rows), via the
 * `ink-foundation` text domain; never AI-translated. The gradient accent phrase is an
 * inline `.ink-text-gradient` span (§0.3).
 *
 * MECHANISM (accepted, maps/tuis.csv): Lovable's "Share your work" is a `<button>` with
 * no destination; here it is a link to /skryf/, styled by the same recipe.
 *
 * @package Ink\Foundation
 */

$ink_hero_share = function_exists( 'ink_foundation_is_member_logged_in' ) && ink_foundation_is_member_logged_in()
	? home_url( '/skryf/' )
	: add_query_arg( 'redirect_to', rawurlencode( home_url( '/skryf/' ) ), home_url( '/teken-in/' ) );
?>
<!-- wp:html -->
<section data-audit-id="hero" class="alignfull ink-hero ink-hero-texture ink-band ink-band--hero">
	<div class="ink-container ink-hero__houer">
		<div data-audit-id="hero-grid" class="ink-hero-grid">
			<div data-audit-id="hero-content" class="ink-hero__inhoud ink-animate-fade-up">
				<span data-audit-id="hero-badge" class="ink-hero-badge ink-pill ink-pill--tint-lg"><?php esc_html_e( 'Waar woorde lesers vind', 'ink-foundation' ); ?></span>
				<h1 data-audit-id="hero-h1" class="ink-heading-h1-hero"><?php esc_html_e( 'Stories wat verdien om', 'ink-foundation' ); ?> <span class="ink-text-gradient"><?php esc_html_e( 'gelees en gekoester', 'ink-foundation' ); ?></span> <?php esc_html_e( 'te word', 'ink-foundation' ); ?></h1>
				<p data-audit-id="hero-paragraph" class="ink-hero__teks"><?php esc_html_e( 'Sluit aan by \'n lewendige gemeenskap van skrywers en lesers met \'n passie vir Afrikaanse letterkunde.', 'ink-foundation' ); ?></p>
				<div data-audit-id="hero-actions" class="ink-hero__aksies">
					<a data-audit-id="hero-btn-primary" class="ink-btn ink-btn--lg ink-btn--primary" href="<?php echo esc_url( home_url( '/ontdek/' ) ); ?>"><?php echo str_replace( 'class="ink-icon"', 'class="ink-icon ink-btn__icon--start"', ink_foundation_icon( '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme-authored inline SVG icon (§0.9). ?><?php esc_html_e( 'Begin lees', 'ink-foundation' ); ?></a>
					<a data-audit-id="hero-btn-outline" class="ink-btn ink-btn--lg ink-btn--outline" href="<?php echo esc_url( $ink_hero_share ); ?>"><?php esc_html_e( 'Deel jou werk', 'ink-foundation' ); ?><?php echo str_replace( 'class="ink-icon"', 'class="ink-icon ink-btn__icon--end"', ink_foundation_icon( '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme-authored inline SVG icon (§0.9). ?></a>
				</div>
			</div>
			<div data-audit-id="hero-aside" class="ink-hero-aside ink-animate-fade-up">
<!-- /wp:html -->
				<!-- wp:ink/huidige-uitdaging {"variant":"kompak"} /-->
<!-- wp:html -->
			</div>
		</div>
	</div>
</section>
<!-- /wp:html -->
