<?php
/**
 * Title: Held-seksie
 * Slug: ink-foundation/hero
 * Categories: featured, ink-foundation
 * Description: Tuisblad-held (Epic 19, Storie 19.2, §2) — twee-kolom-uitleg (≥1024px: inhoud links, uitdaging-kaart regs) met kenteken-pil, gradiënt-opskrif en die plus-patroon-tekstuur. Enkel-kolom onder 1024px via home.css. Presentasie alleen (drie-laag-skeiding): geen besigheidslogika nie.
 *
 * The RIGHT column (`.ink-hero-aside`) hosts the styled dynamic challenge card —
 * the `ink/huidige-uitdaging` block in its COMPACT variant (Storie 19.3, §3). All
 * per-uitdaging data + the open-challenge query live in `ink-core` (three-layer
 * separation); the block collapses to nothing when no challenge is open, so the aside
 * simply shows empty (no placeholder teaser). Its card styling (`is-style-ink-card`
 * recipe — surface-alt / 12px / border / shadow.sm / reduced-motion hover-lift, plus
 * the decorative corner tint + badge) is applied in `home.css` on the block's own
 * `.ink-huidige-uitdaging--kompak` markup — replicated there rather than wrapping the
 * COLLAPSING block in a static `is-style-ink-card` group (which would show an empty
 * card when no challenge is open). Copy is authored Afrikaans (docs/ui-copy-translations.md
 * — Held rows), via the `ink-foundation` text domain; never AI-translated. The gradient
 * accent phrase is split into an inline `.ink-text-gradient` span (§0.3); the
 * segments stay individually translatable.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-hero-texture","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-hero-texture" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","className":"ink-hero-grid ink-animate-fade-up","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-48"}},"layout":{"type":"grid","columnCount":2}} -->
	<div class="wp-block-group alignwide ink-hero-grid ink-animate-fade-up">
		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"className":"ink-hero-badge","fontSize":"sm"} -->
			<p class="ink-hero-badge has-sm-font-size"><?php esc_html_e( 'Waar woorde lesers vind', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"level":1,"fontSize":"hero","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}}} -->
			<h1 class="wp-block-heading has-hero-font-size" style="font-style:normal;font-weight:600"><?php esc_html_e( 'Stories wat verdien om', 'ink-foundation' ); ?> <span class="ink-text-gradient"><?php esc_html_e( 'gelees en gekoester', 'ink-foundation' ); ?></span> <?php esc_html_e( 'te word', 'ink-foundation' ); ?></h1>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-md-font-size"><?php esc_html_e( 'Sluit aan by \'n lewendige gemeenskap van skrywers en lesers met \'n passie vir Afrikaanse letterkunde.', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-ink-primary ink-btn-lg ink-btn-icon"} -->
				<div class="wp-block-button is-style-ink-primary ink-btn-lg ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/lees"><?php echo ink_foundation_icon( '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme-authored inline SVG icon (§0.9). ?><?php esc_html_e( 'Begin lees', 'ink-foundation' ); ?></a></div>
				<!-- /wp:button -->

				<!-- wp:button {"className":"is-style-ink-outline ink-btn-lg ink-btn-icon"} -->
				<div class="wp-block-button is-style-ink-outline ink-btn-lg ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/skryf"><?php esc_html_e( 'Deel jou werk', 'ink-foundation' ); ?><?php echo ink_foundation_icon( '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted theme-authored inline SVG icon (§0.9). ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ink-hero-aside","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-hero-aside">
			<!-- wp:ink/huidige-uitdaging {"variant":"kompak"} /-->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
