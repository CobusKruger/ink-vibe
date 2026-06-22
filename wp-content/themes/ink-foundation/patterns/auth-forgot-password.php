<?php
/**
 * Title: Wagwoord-herstel
 * Slug: ink-foundation/auth-forgot-password
 * Categories: ink-foundation
 * Description: Enkelkolom-skerm vir wagwoord-herstel wat WordPress se eie verlore-wagwoord-meganisme gebruik (geen herbou van outentisering nie).
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

		<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt","width":100} -->
			<div class="wp-block-button has-custom-width wp-block-button__width-100"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/wp-login.php?action=lostpassword">Herstel wagwoord</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">Onthou jy jou wagwoord? <a href="/meld-aan">Meld aan</a></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
