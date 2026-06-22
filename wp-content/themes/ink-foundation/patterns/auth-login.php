<?php
/**
 * Title: Meld aan
 * Slug: ink-foundation/auth-login
 * Categories: ink-foundation
 * Description: Enkelkolom-aanmeldskerm wat WordPress se eie aanmeldmeganisme gebruik (geen herbou van outentisering nie).
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
