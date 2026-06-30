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
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"480px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size">Wagwoord-herstel</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size">Voer jou e-pos in en ons stuur 'n skakel om jou wagwoord te herstel.</p>
		<!-- /wp:paragraph -->

		<!-- wp:html -->
		<?php // Renders WordPress's OWN lost-password handler in-theme (Afrikaans, single-column) — auth is used, not rebuilt. ?>
		<form name="lostpasswordform" class="ink-auth-form" action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="post">
			<p class="ink-auth-field">
				<label for="user_login"><?php echo esc_html__( 'E-pos of gebruikersnaam', 'ink-foundation' ); ?></label>
				<input type="text" name="user_login" id="user_login" autocapitalize="off" autocorrect="off" autocomplete="username" aria-describedby="user_login-wenk" required="required" />
				<span class="ink-auth-hint" id="user_login-wenk"><?php echo esc_html__( 'Vul die e-pos of gebruikersnaam in wat aan jou rekening gekoppel is.', 'ink-foundation' ); ?></span>
			</p>
			<?php do_action( 'lostpassword_form' ); ?>
			<p class="ink-auth-submit">
				<button type="submit" name="wp-submit" class="wp-element-button"><?php echo esc_html__( 'Herstel wagwoord', 'ink-foundation' ); ?></button>
			</p>
		</form>
		<!-- /wp:html -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">Onthou jy jou wagwoord? <a href="/meld-aan">Meld aan</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
