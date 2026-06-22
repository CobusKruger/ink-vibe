<?php
/**
 * Title: Registreer
 * Slug: ink-foundation/auth-register
 * Categories: ink-foundation
 * Description: Enkelkolom-registrasieskerm wat WordPress se eie registrasiemeganisme gebruik (geen herbou van outentisering nie). Sluit 'n grasieus-degraderende sosiale-aanmeldnaat (R6, Storie 3.5) in wat slegs verskyn as 'n gekeurde sosiale-aanmeld-inprop aktief is.
 *
 * Presentation only (three-layer separation). The social-login section is a SEAM:
 * it renders the vetted plugin's buttons via the ink-core render action ONLY when
 * the plugin is available, and emits nothing otherwise. A socially-registered
 * account lands at Brons / gratis lid through the SAME user_register path as
 * e-mail signup (Story 3.1) — no OAuth / defaults logic lives in this theme file.
 * All copy is Afrikaans; un-authored microcopy is marked [NEEDS HUMAN AFRIKAANS].
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"480px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size">Skep jou rekening</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Registreer met jou e-pos om as gratis lid te lees, te reageer en skrywers te volg.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt","width":100} -->
			<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/wp-login.php?action=register">Registreer</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
<?php if ( function_exists( 'ink_foundation_social_login_available' ) && ink_foundation_social_login_available() ) : ?>
		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:paragraph {"align":"center","fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-text-align-center has-muted-text-color has-text-color has-sm-font-size"><?php
			// [NEEDS HUMAN AFRIKAANS] — social divider line not yet authored in ui-copy-translations.md.
			echo esc_html__( 'Of gaan voort met', 'ink-foundation' );
		?> <span class="ink-needs-human-af" hidden>[NEEDS HUMAN AFRIKAANS]</span></p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<div class="ink-social-login-buttons"><?php ink_foundation_social_login_buttons(); ?></div>
		<!-- /wp:html -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size"><?php
			// [NEEDS HUMAN AFRIKAANS] — POPIA social-login consent note not yet authored.
			echo esc_html__( 'Deur met \'n sosiale rekening voort te gaan, deel jy basiese profielinligting met INK.', 'ink-foundation' );
		?> <a href="<?php echo esc_url( '/privaatheidsbeleid' ); ?>"><?php echo esc_html__( 'Privaatheidsbeleid', 'ink-foundation' ); ?></a> <span class="ink-needs-human-af" hidden>[NEEDS HUMAN AFRIKAANS]</span></p>
		<!-- /wp:paragraph -->
<?php endif; ?>

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">Reeds 'n rekening? <a href="/meld-aan">Meld aan</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
