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
 *
 * Epic 19 auth-fidelity pass: both the FAILURE and SUCCESS outcomes of a POST
 * to `wp-login.php?action=register` are caught by
 * {@see \Ink\Accounts\AuthRedirects::registerSubmission()} (fired at priority 1
 * on `login_form_register`, pre-empting BuddyPress's own registration screen
 * handler, live whenever the `members` component is scoped on) and redirected
 * back HERE with a `?registreer=fout`/`voltooi` marker, rather than landing on
 * BuddyPress's separate, un-translated signup screen or WordPress core's raw
 * `wp-login.php?checkemail=registered`.
 *
 * Both "sign in" links read `teken_in` from the {@see \Ink\I18n\Terms} registry
 * (Epic 19 fourth-pass fidelity fix, 2026-09-05) — "Meld aan" retired sitewide
 * per direct product-owner instruction.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-auth","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained","contentSize":"448px"}} -->
<section data-audit-id="auth" class="wp-block-group alignfull ink-auth" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"className":"ink-card ink-card--lg ink-auth-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div data-audit-id="auth-card" class="wp-block-group ink-card ink-card--lg ink-auth-card">
		<!-- wp:heading {"level":1,"className":"ink-heading-h2-block"} -->
		<h1 data-audit-id="auth-heading" class="wp-block-heading ink-heading-h2-block">Skep jou rekening</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p data-audit-id="auth-intro" class="has-muted-text-color has-text-color has-sm-font-size">Registreer met jou e-pos om as gratis lid te lees, te reageer en skrywers te volg.</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php
		$ink_registreer_status = isset( $_GET['registreer'] ) && is_scalar( $_GET['registreer'] ) ? sanitize_key( wp_unslash( (string) $_GET['registreer'] ) ) : '';

		if ( 'voltooi' === $ink_registreer_status ) :
			?>
			<p data-audit-id="auth-notice" class="ink-notice ink-notice--ok" role="status"><?php echo esc_html__( 'Registrasie voltooi. Ons het jou intekenbesonderhede per e-pos gestuur.', 'ink-foundation' ); ?></p>
			<a data-audit-id="auth-secondary-btn" class="ink-btn ink-btn--neutral ink-btn--block" href="<?php echo esc_url( home_url( '/meld-aan' ) ); ?>"><?php echo esc_html( ink_foundation_term( 'teken_in', 'Teken in' ) ); ?></a>
		<?php else : ?>
			<?php // Renders WordPress's OWN registration handler in-theme (Afrikaans, single-column) — auth is used, not rebuilt. ?>
			<?php if ( 'fout' === $ink_registreer_status ) : ?>
				<p data-audit-id="auth-notice" class="ink-notice ink-notice--fout" role="alert"><?php echo esc_html__( 'Registrasie het misluk. Gaan jou besonderhede na en probeer weer.', 'ink-foundation' ); ?></p>
			<?php endif; ?>
			<form data-audit-id="auth-form" name="registerform" class="ink-auth-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post" novalidate="novalidate">
				<p data-audit-id="auth-username-field" class="ink-auth-field">
					<label data-audit-id="auth-username-label" class="ink-label" for="user_login"><?php echo esc_html__( 'Gebruikersnaam', 'ink-foundation' ); ?></label>
					<input data-audit-id="auth-username-input" type="text" class="ink-field" name="user_login" id="user_login" autocapitalize="off" autocorrect="off" autocomplete="username" aria-describedby="user_login-wenk" required="required" />
					<span data-audit-id="auth-field-hint" class="ink-hint" id="user_login-wenk"><?php echo esc_html__( "Kies 'n gebruikersnaam — ander lede sal dit sien.", 'ink-foundation' ); ?></span>
				</p>
				<p data-audit-id="auth-email-field" class="ink-auth-field">
					<label data-audit-id="auth-email-label" class="ink-label" for="user_email"><?php echo esc_html__( 'E-pos', 'ink-foundation' ); ?></label>
					<input data-audit-id="auth-email-input" type="email" class="ink-field" name="user_email" id="user_email" autocomplete="email" aria-describedby="user_email-wenk" required="required" />
					<span class="ink-hint" id="user_email-wenk"><?php echo esc_html__( 'Ons stuur jou intekenbesonderhede na hierdie adres.', 'ink-foundation' ); ?></span>
				</p>
				<?php do_action( 'register_form' ); ?>
				<p data-audit-id="auth-submit-wrap" class="ink-auth-submit">
					<button data-audit-id="auth-submit" type="submit" name="wp-submit" class="ink-btn ink-btn--primary ink-btn--block"><?php echo esc_html__( 'Registreer', 'ink-foundation' ); ?></button>
				</p>
			</form>
		<?php endif; ?>
		<!-- /wp:html -->
<?php if ( 'voltooi' !== $ink_registreer_status && function_exists( 'ink_foundation_social_login_available' ) && ink_foundation_social_login_available() ) : ?>
		<!-- wp:html -->
		<div data-audit-id="auth-divider" class="ink-divider ink-divider--label"><span data-audit-id="auth-divider-text" class="ink-divider__label">
			<?php
			// Social divider line (human-authored Afrikaans).
			echo esc_html__( 'Of gebruik eerder', 'ink-foundation' );
			?>
		</span></div>
		<!-- /wp:html -->

		<!-- wp:html -->
		<div data-audit-id="auth-oauth" class="ink-social-login-buttons"><?php ink_foundation_social_login_buttons(); ?></div>
		<!-- /wp:html -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p data-audit-id="auth-oauth-consent" class="has-muted-text-color has-text-color has-sm-font-size">
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

<?php if ( 'voltooi' !== $ink_registreer_status ) : ?>
		<!-- wp:paragraph {"className":"ink-auth-switch","fontSize":"sm","textColor":"muted-text"} -->
		<p data-audit-id="auth-switch" class="ink-auth-switch has-muted-text-color has-text-color has-sm-font-size">Reeds &#8217;n rekening? <a data-audit-id="auth-switch-link" href="/meld-aan"><?php echo esc_html( ink_foundation_term( 'teken_in', 'Teken in' ) ); ?></a></p>
		<!-- /wp:paragraph -->
<?php endif; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
