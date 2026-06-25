<?php
/**
 * Title: Meld aan
 * Slug: ink-foundation/auth-login
 * Categories: ink-foundation
 * Description: Enkelkolom-aanmeldskerm wat WordPress se eie aanmeldmeganisme gebruik (geen herbou van outentisering nie). Sluit 'n grasieus-degraderende sosiale-aanmeldnaat (R6, Storie 3.5) in wat slegs verskyn as 'n gekeurde sosiale-aanmeld-inprop aktief is.
 *
 * Presentation only (three-layer separation). The social-login section is a SEAM:
 * it renders the vetted plugin's buttons via the ink-core render action ONLY when
 * the plugin is available, and emits nothing otherwise — the e-mail auth path
 * always works. No OAuth / provider logic lives in this theme file. All copy is
 * Afrikaans, human-authored in ui-copy-translations.md (never AI-translated).
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"480px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size">Meld aan</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Meld aan by jou rekening met jou e-pos en wagwoord.</p>
		<!-- /wp:paragraph -->

		<!-- wp:loginout {"displayLoginAsForm":true,"redirectToCurrent":false} /-->
<?php if ( function_exists( 'ink_foundation_social_login_available' ) && ink_foundation_social_login_available() ) : ?>
		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:paragraph {"align":"center","fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-text-align-center has-muted-text-color has-text-color has-sm-font-size">
			<?php
			// Social divider line (human-authored Afrikaans).
			echo esc_html__( 'Of gebruik eerder', 'ink-foundation' );
			?>
		</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<div class="ink-social-login-buttons"><?php ink_foundation_social_login_buttons(); ?></div>
		<!-- /wp:html -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">
			<?php
			// POPIA social-login consent note (human-authored Afrikaans).
			$ink_privacy_url = function_exists( 'get_privacy_policy_url' ) ? get_privacy_policy_url() : '';
			if ( '' === $ink_privacy_url ) {
				$ink_privacy_url = home_url( '/privaatheidsbeleid' );
			}
			echo esc_html__( 'As jy \'n sosiale media-rekening gebruik, sien INK sekere basiese besonderhede.', 'ink-foundation' );
			printf(
				' <a href="%1$s">%2$s</a>',
				esc_url( $ink_privacy_url ),
				esc_html__( 'Privaatheidsbeleid', 'ink-foundation' )
			);
			?>
		</p>
		<!-- /wp:paragraph -->
<?php endif; ?>

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"fontSize":"sm"} -->
			<p class="has-sm-font-size"><a href="/wagwoord-herstel">Wagwoord vergeet?</a></p>
			<!-- /wp:paragraph -->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size">Nog nie 'n lid nie? <a href="/registreer">Registreer</a></p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
