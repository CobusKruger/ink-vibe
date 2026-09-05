<?php
/**
 * Title: Wagwoord-herstel
 * Slug: ink-foundation/auth-forgot-password
 * Categories: ink-foundation
 * Description: Enkelkolom-skerm vir wagwoord-herstel wat WordPress se eie verlore-wagwoord-meganisme gebruik (geen herbou van outentisering nie).
 *
 * Presentation only (three-layer separation). The form POSTs to WordPress's own
 * lost-password endpoint (`wp-login.php?action=lostpassword` → `retrieve_password`)
 * — auth is USED, never reimplemented; the `lostpassword_form` action is fired so
 * core/plugins inject their own fields. The reset e-mail itself is Afrikaansed in
 * ink-core (Story 3.1 `retrieve_password_title` / `_message` filters). All copy is
 * Afrikaans, curated in docs/ui-copy-translations.md (label + the field hint).
 *
 * Epic 19 auth-fidelity pass: a successful submission is kept on this page via
 * the hidden `redirect_to` field (WordPress core's own `case 'lostpassword'`
 * handling in `wp-login.php` honours it directly, no hook needed) instead of
 * falling through to core's raw `wp-login.php?checkemail=confirm` screen. A
 * FAILED submission (unknown e-pos/gebruikersnaam) IS caught by a hook —
 * {@see \Ink\Accounts\AuthRedirects::lostPasswordFailed()}, fired on core's own
 * `lost_password` action — since core has no redirect to honour on that path,
 * only a raw in-place re-render.
 *
 * The "back to sign-in" link reads `teken_in` from the {@see \Ink\I18n\Terms}
 * registry (Epic 19 fourth-pass fidelity fix, 2026-09-05) — "Meld aan" retired
 * sitewide per direct product-owner instruction.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"480px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card ink-auth-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card ink-auth-card">
		<!-- wp:heading {"level":1,"fontSize":"xxl"} -->
		<h1 class="wp-block-heading has-xxl-font-size">Wagwoord-herstel</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Voer jou e-pos in en ons stuur &#8217;n skakel om jou wagwoord te herstel.</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php
		$ink_wagwoord_status = isset( $_GET['wagwoord'] ) && is_scalar( $_GET['wagwoord'] ) ? sanitize_key( wp_unslash( (string) $_GET['wagwoord'] ) ) : '';

		if ( 'gestuur' === $ink_wagwoord_status ) :
			?>
			<p class="ink-auth-notice ink-auth-notice--ok" role="status"><?php echo esc_html__( "As daar 'n rekening met daardie besonderhede bestaan, is 'n herstelskakel op pad per e-pos.", 'ink-foundation' ); ?></p>
			<a class="ink-auth-secondary" href="<?php echo esc_url( home_url( '/meld-aan' ) ); ?>"><?php echo esc_html__( 'Terug na aanmeld', 'ink-foundation' ); ?></a>
		<?php else : ?>
			<?php // Renders WordPress's OWN lost-password handler in-theme (Afrikaans, single-column) — auth is used, not rebuilt. ?>
			<?php if ( 'fout' === $ink_wagwoord_status ) : ?>
				<p class="ink-auth-notice ink-auth-notice--fout" role="alert"><?php echo esc_html__( "Ons kon nie 'n rekening met daardie besonderhede vind nie. Gaan jou e-pos of gebruikersnaam na.", 'ink-foundation' ); ?></p>
			<?php endif; ?>
			<form name="lostpasswordform" class="ink-auth-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">
				<p class="ink-auth-field">
					<label for="user_login"><?php echo esc_html__( 'E-pos of gebruikersnaam', 'ink-foundation' ); ?></label>
					<input type="text" name="user_login" id="user_login" autocapitalize="off" autocorrect="off" autocomplete="username" aria-describedby="user_login-wenk" required="required" />
					<span class="ink-auth-hint" id="user_login-wenk"><?php echo esc_html__( 'Vul die e-pos of gebruikersnaam in wat aan jou rekening gekoppel is.', 'ink-foundation' ); ?></span>
				</p>
				<?php do_action( 'lostpassword_form' ); ?>
				<input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/wagwoord-herstel/?wagwoord=gestuur' ) ); ?>" />
				<p class="ink-auth-submit">
					<button type="submit" name="wp-submit" class="wp-element-button"><?php echo esc_html__( 'Herstel wagwoord', 'ink-foundation' ); ?></button>
				</p>
			</form>
			<!-- /wp:html -->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size">Onthou jy jou wagwoord? <a href="/meld-aan"><?php echo esc_html( ink_foundation_term( 'teken_in', 'Teken in' ) ); ?></a></p>
			<!-- /wp:paragraph -->
		<?php endif; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
