<?php
/**
 * Title: Registreer
 * Slug: ink-foundation/auth-register
 * Categories: ink-foundation
 * Description: Enkelkolom-registrasieskerm wat WordPress se eie registrasiemeganisme gebruik (geen herbou van outentisering nie). Sluit 'n grasieus-degraderende sosiale-aanmeldnaat (R6, Storie 3.5) in wat slegs verskyn as 'n gekeurde sosiale-aanmeld-inprop aktief is.
 *
 * Presentation only (three-layer separation). The form POSTs to WordPress's own
 * registration endpoint (`wp-login.php?action=register` → `register_new_user`) —
 * auth is USED, never reimplemented; the `register_form` action is fired so WP
 * core and plugins inject their own fields. The social-login section is a SEAM:
 * it renders the vetted plugin's buttons via the ink-core render action ONLY when
 * the plugin is available, and emits nothing otherwise. A socially- OR e-mail-
 * registered account lands at Brons / gratis lid through the SAME user_register
 * path (Story 3.1) — no OAuth / defaults logic lives in this theme file.
 * All copy is Afrikaans, curated in docs/ui-copy-translations.md (labels + the
 * username/e-pos field hints).
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

		<!-- wp:html -->
		<?php // Renders WordPress's OWN registration handler in-theme (Afrikaans, single-column) — auth is used, not rebuilt. ?>
		<form name="registerform" class="ink-auth-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post" novalidate="novalidate">
			<p class="ink-auth-field">
				<label for="user_login"><?php echo esc_html__( 'Gebruikersnaam', 'ink-foundation' ); ?></label>
				<input type="text" name="user_login" id="user_login" autocapitalize="off" autocorrect="off" autocomplete="username" aria-describedby="user_login-wenk" required="required" />
				<span class="ink-auth-hint" id="user_login-wenk"><?php echo esc_html__( "Kies 'n gebruikersnaam — ander lede sal dit sien.", 'ink-foundation' ); ?></span>
			</p>
			<p class="ink-auth-field">
				<label for="user_email"><?php echo esc_html__( 'E-pos', 'ink-foundation' ); ?></label>
				<input type="email" name="user_email" id="user_email" autocomplete="email" aria-describedby="user_email-wenk" required="required" />
				<span class="ink-auth-hint" id="user_email-wenk"><?php echo esc_html__( 'Ons stuur jou intekenbesonderhede na hierdie adres.', 'ink-foundation' ); ?></span>
			</p>
			<?php do_action( 'register_form' ); ?>
			<p class="ink-auth-submit">
				<button type="submit" name="wp-submit" class="wp-element-button"><?php echo esc_html__( 'Registreer', 'ink-foundation' ); ?></button>
			</p>
		</form>
		<!-- /wp:html -->
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
			echo esc_html__( 'As jy \'n sosiale media-rekening gebruik, sien INK jou basiese besonderhede.', 'ink-foundation' );
			printf(
				' <a href="%1$s">%2$s</a>',
				esc_url( $ink_privacy_url ),
				esc_html__( 'Privaatheidsbeleid', 'ink-foundation' )
			);
			?>
		</p>
		<!-- /wp:paragraph -->
<?php endif; ?>

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">Reeds 'n rekening? <a href="/meld-aan">Meld aan</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
