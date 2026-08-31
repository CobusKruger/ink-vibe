<?php
/**
 * Title: INK Footer
 * Slug: ink-foundation/footer-main
 * Categories: footer
 * Block Types: core/template-part/footer
 * Description: Werf-wye voetstuk (Epic 19, Storie 19.5, §10) — 4-kolom-uitleg (≥768px): handelsmerk + blurb / Ontdek / Gemeenskap / Ondersteun ons, met 'n onderbalk (kopiereg + "Gemaak met ♥"). secondary/30-agtergrond, 1px-bo-rand. Werf-wye vormgewing leef op die is-style-ink-footer-blokstyl (functions.php), NIE home.css nie (die voetstuk verskyn op elke bladsy).
 *
 * Presentation only (three-layer separation): no business logic. The footer renders on
 * EVERY page, so its treatment (secondary/30 bg, top border, the 4-column grid that
 * collapses < 768px, the brand row, the link columns, the bottom bar + filled-terracotta
 * heart) ships site-wide on the `is-style-ink-footer` block style (functions.php) — NOT
 * in home.css, which is front-page-only. Copy is authored Afrikaans through the
 * `ink-foundation` text domain (Gate D); the wordmark is the core site-title (no
 * hardcoded brand literal). The feather + heart are decorative inline SVGs (aria-hidden).
 * Org copy uses Afrikaans placeholders ([stigtingsjaar]) — never US non-profit boilerplate.
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:group {"align":"full","className":"is-style-ink-footer","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"secondary","textColor":"ink-text","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-ink-footer has-ink-text-color has-secondary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","className":"ink-footer-kolomme","layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide ink-footer-kolomme">
		<!-- Handelsmerk-kolom: veer + woordmerk + blurb + sosiale skakels. -->
		<!-- wp:group {"className":"ink-footer-handelsmerk","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-footer-handelsmerk">
			<!-- wp:group {"className":"ink-footer-handelsmerk__ry","style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","verticalAlignment":"center"}} -->
			<div class="wp-block-group ink-footer-handelsmerk__ry">
				<!-- wp:html -->
				<span class="ink-footer-veer" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>
				<!-- /wp:html -->

				<!-- wp:site-title {"level":0,"fontFamily":"display","fontSize":"lg","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"textColor":"ink-text"} /-->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"ink-footer-blurb","fontSize":"sm","textColor":"muted-text"} -->
			<p class="ink-footer-blurb has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( "'n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert [stigtingsjaar].", 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- Theme-native social links (WordPress core social-links block) — the sanctioned replacement for the retired social-icon plugin. URLs are org-detail placeholders the owner sets pre-launch. -->
			<!-- wp:social-links {"className":"is-style-logos-only"} -->
			<ul class="wp-block-social-links is-style-logos-only">
				<!-- wp:social-link {"url":"#","service":"facebook"} /-->

				<!-- wp:social-link {"url":"#","service":"instagram"} /-->

				<!-- wp:social-link {"url":"#","service":"x"} /-->
			</ul>
			<!-- /wp:social-links -->
		</div>
		<!-- /wp:group -->

		<!-- Ontdek-kolom. -->
		<!-- wp:group {"className":"ink-footer-kolom","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-footer-kolom">
			<!-- wp:heading {"level":3,"fontSize":"md"} -->
			<h3 class="wp-block-heading has-md-font-size"><?php esc_html_e( 'Ontdek', 'ink-foundation' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"ink-footer-lys"} -->
			<ul class="wp-block-list ink-footer-lys">
				<li><a href="/lees"><?php esc_html_e( 'Jongste bydraes', 'ink-foundation' ); ?></a></li>
				<li><a href="/lees?tipe=gedig"><?php esc_html_e( 'Gedigversameling', 'ink-foundation' ); ?></a></li>
				<li><a href="/skrywers"><?php esc_html_e( 'Uitgesoekte skrywers', 'ink-foundation' ); ?></a></li>
				<li><a href="/uitdagings"><?php esc_html_e( 'Maandelikse uitdagings', 'ink-foundation' ); ?></a></li>
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:group -->

		<!-- Gemeenskap-kolom. -->
		<!-- wp:group {"className":"ink-footer-kolom","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-footer-kolom">
			<!-- wp:heading {"level":3,"fontSize":"md"} -->
			<h3 class="wp-block-heading has-md-font-size"><?php esc_html_e( 'Gemeenskap', 'ink-foundation' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"ink-footer-lys"} -->
			<ul class="wp-block-list ink-footer-lys">
				<li><a href="/gemeenskap"><?php esc_html_e( 'Skryfgroepe', 'ink-foundation' ); ?></a></li>
				<li><a href="/gemeenskap"><?php esc_html_e( 'Terugvoerkringe', 'ink-foundation' ); ?></a></li>
				<li><a href="/geleenthede"><?php esc_html_e( 'Geleenthede', 'ink-foundation' ); ?></a></li>
				<li><a href="/nuusbrief"><?php esc_html_e( 'Nuusbrief', 'ink-foundation' ); ?></a></li>
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:group -->

		<!-- Ondersteun ons-kolom. -->
		<!-- wp:group {"className":"ink-footer-kolom","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-footer-kolom">
			<!-- wp:heading {"level":3,"fontSize":"md"} -->
			<h3 class="wp-block-heading has-md-font-size"><?php esc_html_e( 'Ondersteun ons', 'ink-foundation' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"ink-footer-lys"} -->
			<ul class="wp-block-list ink-footer-lys">
				<li><a href="/borge"><?php esc_html_e( "Word 'n borg", 'ink-foundation' ); ?></a></li>
				<li><a href="/skenk"><?php esc_html_e( 'Skenk', 'ink-foundation' ); ?></a></li>
				<li><a href="/vrywillig"><?php esc_html_e( "Word 'n vrywilliger", 'ink-foundation' ); ?></a></li>
				<li><a href="/oor-ink"><?php esc_html_e( 'Meer oor INK', 'ink-foundation' ); ?></a></li>
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->

	<!-- Onderbalk: kopiereg + "Gemaak met ♥". -->
	<!-- wp:group {"align":"wide","className":"ink-footer-onderbalk","layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
	<div class="wp-block-group alignwide ink-footer-onderbalk">
		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( "© [stigtingsjaar] INK. 'n Niewinsgerigte gemeenskapsorganisasie.", 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php
		$ink_footer_hart = '<svg class="ink-footer-hart__ikoon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
		/* translators: %s is a decorative heart icon. */
		$ink_footer_line = sprintf( esc_html__( 'Gemaak met %s vir skrywers oral', 'ink-foundation' ), $ink_footer_hart );
		printf( '<p class="ink-footer-hart has-muted-text-color has-text-color has-sm-font-size">%s</p>', $ink_footer_line ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the sentence is esc_html__() with a trusted inline-SVG %s (no user input).
		?>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
