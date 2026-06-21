<?php
/**
 * Title: Bladsy-argetipe C — Leesbladsy
 * Slug: ink-foundation/archetype-c-detail-reading
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Leesbladsy-skanderaal: sterk titel met metadata, 'n leesbare hoofkolom en verwante stukke. Die werklike leessjablone is Epiek 7.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"xs","textColor":"accent"} -->
		<p class="has-accent-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Tipe</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"3xl"} -->
		<h1 class="wp-block-heading has-3xl-font-size">Titel van die stuk</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size">deur [skrywer] · [datum]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"fontSize":"md"} -->
		<p class="has-md-font-size">Die leesbare hoofkolom begin hier. Vervang hierdie teks met die inhoud van die stuk.</p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"fontSize":"md"} -->
		<p class="has-md-font-size">'n Tweede paragraaf as plekhouer sodat die leesritme en kolombreedte sigbaar is.</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Verwante stukke</h2>
		<!-- /wp:heading -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Titel van die werk</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-sm-font-size">deur [skrywer]</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"fontSize":"md"} -->
					<p class="has-md-font-size"><a href="#">Lees meer</a></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Titel van die werk</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-sm-font-size">deur [skrywer]</p>
					<!-- /wp:paragraph -->

					<!-- wp:paragraph {"fontSize":"md"} -->
					<p class="has-md-font-size"><a href="#">Lees meer</a></p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
