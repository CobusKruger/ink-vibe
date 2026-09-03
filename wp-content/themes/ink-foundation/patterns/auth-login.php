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
 *
 * The form POSTs to WordPress's OWN login endpoint (`wp-login.php`, no `action`
 * query arg — core's default `login` handler) — auth is USED, never
 * reimplemented, the same commitment as `auth-register.php` /
 * `auth-forgot-password.php`. Epic 19 auth-fidelity pass: this used to be the
 * core `wp:loginout` block (`displayLoginAsForm`), which renders WordPress's
 * own raw, unstyled `wp_login_form()` markup with no hook for the theme's own
 * classes — replaced with the same hand-authored `ink-auth-*` markup shape the
 * other two auth patterns already use, so all three are stylable/consistent
 * and the "Wagwoord vergeet?" link can sit inline with the password label
 * (Lovable's `Auth.tsx` layout) instead of the block's own fixed-below-form
 * position.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"480px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card ink-auth-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card ink-auth-card">
		<!-- wp:heading {"level":1,"fontSize":"xxl"} -->
		<h1 class="wp-block-heading has-xxl-font-size">Meld aan</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Meld aan by jou rekening met jou e-pos en wagwoord.</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php
		// Renders WordPress's OWN login handler in-theme (Afrikaans, single-column) — auth is used, not rebuilt.
		// A failed attempt is caught by Ink\Accounts\AuthRedirects::loginFailed() (fired
		// on WordPress core's own `wp_login_failed` action) and lands back HERE with a
		// `?meld_aan=fout` marker + the attempted username, rather than on WordPress
		// core's raw, unbranded `wp-login.php` error screen.
		$ink_meld_aan_gebruikersnaam = isset( $_GET['log'] ) && is_scalar( $_GET['log'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['log'] ) ) : '';
		if ( isset( $_GET['meld_aan'] ) && 'fout' === $_GET['meld_aan'] ) :
			?>
			<p class="ink-auth-notice ink-auth-notice--fout" role="alert"><?php echo esc_html__( 'Ons kon nie jou aanmelding met daardie besonderhede verwerk nie. Gaan jou gebruikersnaam/e-pos en wagwoord na.', 'ink-foundation' ); ?></p>
		<?php endif; ?>
		<form name="loginform" class="ink-auth-form" action="<?php echo esc_url( site_url( 'wp-login.php', 'login_post' ) ); ?>" method="post">
			<p class="ink-auth-field">
				<label for="user_login"><?php echo esc_html__( 'Gebruikersnaam of e-pos adres', 'ink-foundation' ); ?></label>
				<input type="text" name="log" id="user_login" value="<?php echo esc_attr( $ink_meld_aan_gebruikersnaam ); ?>" autocapitalize="off" autocorrect="off" autocomplete="username" required="required" />
			</p>
			<p class="ink-auth-field">
				<span class="ink-auth-field__row">
					<label for="user_pass"><?php echo esc_html__( 'Wagwoord', 'ink-foundation' ); ?></label>
					<a class="ink-auth-field__forgot" href="<?php echo esc_url( home_url( '/wagwoord-herstel' ) ); ?>"><?php echo esc_html__( 'Wagwoord vergeet?', 'ink-foundation' ); ?></a>
				</span>
				<input type="password" name="pwd" id="user_pass" autocomplete="current-password" required="required" />
			</p>
			<p class="ink-auth-remember">
				<label>
					<input name="rememberme" type="checkbox" id="rememberme" value="forever" />
					<?php echo esc_html__( 'Onthou my', 'ink-foundation' ); ?>
				</label>
			</p>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( ! empty( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : home_url( '/' ) ); ?>" />
			<p class="ink-auth-submit">
				<button type="submit" name="wp-submit" class="wp-element-button"><?php echo esc_html__( 'Meld aan', 'ink-foundation' ); ?></button>
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
		<p class="has-muted-text-color has-text-color has-sm-font-size">Nog nie 'n lid nie? <a href="/registreer">Registreer</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
