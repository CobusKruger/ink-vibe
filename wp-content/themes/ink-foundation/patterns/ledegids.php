<?php
/**
 * Title: Ledegids
 * Slug: ink-foundation/ledegids
 * Categories: ink-foundation, page
 * Description: Die ledegids — die skrywer-ontdekvlak vir die gemeenskap. Hergebruik die Storie 8.3 ink/ontdek-skrywers-blok (genre-filter + sorteer + skrywer-kaarte); geen parallelle gids nie.
 *
 * The member-directory route (FR-43). It does NOT rebuild a writer listing —
 * it reuses the proven Story 8.3 `ink/ontdek-skrywers` discovery block (the
 * single source for the writer query/cards), so the ledegids and the Ontdek
 * skrywers tab stay one listing with two entry points (Principle 8 — no
 * duplication). The rendered surface is INK's own block, not the default
 * BuddyPress members-directory screen.
 *
 * Three-layer: presentation only. The heading reads the glossary `ledegids`
 * term via the `ink_foundation_term()` bridge (never a bare literal — and never
 * "members list"/"directory" per the glossary); the intro is authored Afrikaans
 * through the `ink-foundation` text domain. Structural wrappers are locked
 * (move/remove) per Storie 1.6.
 *
 * @package Ink\Foundation
 */

$ink_ledegids_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'ledegids', 'Ledegids' )
	: 'Ledegids';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size"><?php echo esc_html( $ink_ledegids_label ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size"><?php echo esc_html__( 'Ontdek die skrywers van die gemeenskap.', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-skrywers /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
