<?php
/**
 * Title: Profiel-opsomming
 * Slug: ink-foundation/profile-summary
 * Categories: ink-foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide is-style-card">
		<!-- wp:media-text {"mediaWidth":30,"mediaPosition":"left","verticalAlignment":"top","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}}} -->
		<div class="wp-block-media-text alignwide is-stacked-on-mobile" style="grid-template-columns:30% auto">
			<figure class="wp-block-media-text__media">
				<!-- wp:image {"sizeSlug":"large"} -->
				<figure class="wp-block-image size-large"><img alt="Profielfoto van die skrywer"/></figure>
				<!-- /wp:image -->
			</figure>
			<div class="wp-block-media-text__content">
				<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size">Skrywer se naam</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-sm-font-size">Kortverhale · [N] werke · [N] volgelinge</p>
					<!-- /wp:paragraph -->

					<!-- wp:group {"className":"is-style-emphasis","layout":{"type":"constrained"}} -->
					<div class="wp-block-group is-style-emphasis">
						<!-- wp:paragraph {"fontSize":"md"} -->
						<p class="has-md-font-size">Kort bio van die skrywer. Voeg 'n paar sinne by oor die skrywer se werk en stem.</p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->

					<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
					<div class="wp-block-buttons">
						<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt"} -->
						<div class="wp-block-button"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="#">Volg</a></div>
						<!-- /wp:button -->
					</div>
					<!-- /wp:buttons -->
				</div>
				<!-- /wp:group -->
			</div>
		</div>
		<!-- /wp:media-text -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
