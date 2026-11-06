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
 * `ink-foundation` text domain (Gate D); the wordmark is the two-line "Skryf in / Afrikaans"
 * lockup (Lovable Wordmark.tsx), linked home. The ink-splat + heart are decorative inline SVGs (aria-hidden).
 * Org copy uses Afrikaans placeholders ([stigtingsjaar]) — never US non-profit boilerplate.
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:group {"align":"full","className":"is-style-ink-footer","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"textColor":"ink-text","layout":{"type":"constrained"}} -->
<div data-audit-id="footer" class="wp-block-group alignfull is-style-ink-footer has-ink-text-color has-text-color" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","className":"ink-footer-kolomme","layout":{"type":"default"}} -->
	<div data-audit-id="footer-grid" class="wp-block-group alignwide ink-footer-kolomme">
		<!-- Handelsmerk-kolom: veer + woordmerk + blurb + sosiale skakels. -->
		<!-- wp:group {"className":"ink-footer-handelsmerk","layout":{"type":"default"}} -->
		<div data-audit-id="footer-brand" class="wp-block-group ink-footer-handelsmerk">
			<!-- wp:group {"className":"ink-footer-handelsmerk__ry","style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","verticalAlignment":"center"}} -->
			<div data-audit-id="footer-logo" class="wp-block-group ink-footer-handelsmerk__ry">
				<!-- wp:html -->
				<a data-audit-id="footer-logo-link" class="ink-footer-logo-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="ink-footer-veer" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 561 711" fill="currentColor" focusable="false" aria-hidden="true" data-audit-id="footer-logo-icon" class="ink-logo-splat" style="height:2rem;width:auto"><g transform="matrix(2.115234,0,0,2.115234,-4.587873,-6.85954)"><path d="M137.895,286.504C137.772,283.769 137.88,282.557 136.786,276.443C135.269,267.964 135.155,266.142 126.483,265.748C95.647,264.344 67.601,249.345 53.623,238.342C-34.237,169.191 -3.467,35.034 99.628,7.984C198.847,-18.048 285.792,67.172 263.647,164.531C261.51,173.923 251.211,203.436 245.565,202.094C213.733,194.528 193.979,147.361 187.05,141.869C182.324,138.123 188.486,139.16 189.457,139.121C212.398,138.206 228.582,125.549 233.104,121.07C244.811,109.477 230.941,87.458 231.457,98.498C231.994,109.962 181.324,140.961 179.982,135.396C179.631,133.937 207.952,90.748 218.18,72.326C224.878,60.263 208.941,46.668 205.748,53.608C193.917,79.312 183.66,86.268 155.04,146.307C153.373,149.804 138.882,164.093 131.178,170.068C120.475,178.369 120.077,171.316 120.252,170.459C123.423,154.941 154.164,125.711 151.734,112.467C146.644,84.727 107.599,134.796 106.472,135.239C103.559,136.385 108.76,129.141 110.739,125.624C119.921,109.308 103.344,99.021 102.701,103.538C100.987,115.577 85.876,136.417 72.022,151.041C47.265,177.171 44.817,173.823 44.439,173.74C33.083,171.227 59.911,135.681 64.089,128.276C75.296,108.412 58.661,101.025 56.645,102.716C54.537,104.485 57.404,106.176 39.938,132.787C13.246,173.456 36.643,180.963 45.491,178.469C58.552,174.788 77.35,146.92 71.98,159.546C69.843,164.57 70.467,172.747 75.616,177.367C79.279,180.652 79.883,178.111 81.876,173.673C88.071,159.877 116.979,126.282 133.498,113.496C142.312,106.673 126.925,125.271 121.887,132.757C96.826,169.995 112.862,181.152 124.521,178.58C136.739,175.885 156.764,145.586 149.732,161.576C144.009,174.591 155.912,181.151 160.571,178.609C161.079,178.332 166.087,158.283 175.868,141.762C180.356,134.183 189.875,183.348 222.113,207.997C231.65,215.289 245.224,206.746 242.02,211.293C224.857,235.651 198.748,248.886 194.313,251.134C179.664,258.559 185.509,272.426 187.088,282.556C188.801,293.538 182.08,298.668 176.589,296.273C167.173,292.167 172.902,275.926 173.005,275.399C175.741,261.314 168.138,244.927 165.317,241.66C159.074,234.429 152.66,251.065 151.881,253.614C141.213,288.538 151.223,313.841 151.252,326.497C151.278,338.078 143.328,340.714 138.62,338.258C125.49,331.411 136.675,316.172 137.895,286.504Z"/></g></svg></span>
					<span class="ink-wordmark" data-audit-id="footer-logo-text"><span class="ink-wordmark__small"><?php esc_html_e( 'Skryf in', 'ink-foundation' ); ?></span><span class="ink-wordmark__big"><?php esc_html_e( 'Afrikaans', 'ink-foundation' ); ?></span></span>
				</a>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->

			<!-- wp:paragraph {"className":"ink-footer-blurb","fontSize":"sm","textColor":"muted-text"} -->
			<p data-audit-id="footer-blurb" class="ink-footer-blurb has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( "'n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert [stigtingsjaar].", 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- Theme-native social links (WordPress core social-links block) — the sanctioned replacement for the retired social-icon plugin. URLs are org-detail placeholders the owner sets pre-launch. -->
			<!-- wp:social-links {"className":"is-style-logos-only"} -->
			<ul data-audit-id="footer-social" class="wp-block-social-links is-style-logos-only">
				<!-- wp:social-link {"url":"#","service":"facebook"} /-->

				<!-- wp:social-link {"url":"#","service":"instagram"} /-->

				<!-- wp:social-link {"url":"#","service":"x"} /-->
			</ul>
			<!-- /wp:social-links -->
		</div>
		<!-- /wp:group -->

		<!-- Ontdek-kolom. -->
		<!-- wp:group {"className":"ink-footer-kolom","layout":{"type":"constrained"}} -->
		<div data-audit-id="footer-col" class="wp-block-group ink-footer-kolom">
			<!-- wp:heading {"level":3,"className":"ink-heading-h5"} -->
			<h3 data-audit-id="footer-col-heading" class="wp-block-heading ink-heading-h5"><?php esc_html_e( 'Ontdek', 'ink-foundation' ); ?></h3>
			<!-- /wp:heading -->

			<!-- wp:list {"className":"ink-footer-lys"} -->
			<ul data-audit-id="footer-col-list" class="wp-block-list ink-footer-lys">
				<li data-audit-id="footer-col-item"><a data-audit-id="footer-col-link" href="/lees"><?php esc_html_e( 'Jongste bydraes', 'ink-foundation' ); ?></a></li>
				<li><a href="/lees?tipe=gedig"><?php esc_html_e( 'Gedigversameling', 'ink-foundation' ); ?></a></li>
				<li><a href="/skrywers"><?php esc_html_e( 'Uitgesoekte skrywers', 'ink-foundation' ); ?></a></li>
				<li><a href="/uitdaging/"><?php esc_html_e( 'Maandelikse uitdagings', 'ink-foundation' ); ?></a></li>
			</ul>
			<!-- /wp:list -->
		</div>
		<!-- /wp:group -->

		<!-- Gemeenskap-kolom. -->
		<!-- wp:group {"className":"ink-footer-kolom","layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-footer-kolom">
			<!-- wp:heading {"level":3,"className":"ink-heading-h5"} -->
			<h3 class="wp-block-heading ink-heading-h5"><?php esc_html_e( 'Gemeenskap', 'ink-foundation' ); ?></h3>
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
			<!-- wp:heading {"level":3,"className":"ink-heading-h5"} -->
			<h3 class="wp-block-heading ink-heading-h5"><?php esc_html_e( 'Ondersteun ons', 'ink-foundation' ); ?></h3>
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
	<div data-audit-id="footer-bottom" class="wp-block-group alignwide ink-footer-onderbalk">
		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p data-audit-id="footer-copyright" class="has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( "© [stigtingsjaar] INK. 'n Niewinsgerigte gemeenskapsorganisasie.", 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php
		$ink_footer_hart = '<svg data-audit-id="footer-heart-icon" class="ink-footer-hart__ikoon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>';
		/* translators: %s is a decorative heart icon. */
		$ink_footer_line = sprintf( esc_html__( 'Gemaak met %s vir skrywers oral', 'ink-foundation' ), $ink_footer_hart );
		printf( '<p data-audit-id="footer-made-with" class="ink-footer-hart has-muted-text-color has-text-color has-sm-font-size">%s</p>', $ink_footer_line ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the sentence is esc_html__() with a trusted inline-SVG %s (no user input).
		?>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
