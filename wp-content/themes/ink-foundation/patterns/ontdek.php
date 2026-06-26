<?php
/**
 * Title: Ontdek-skanderaal
 * Slug: ink-foundation/ontdek
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Ontdek-skanderaal: konteks-inleiding, Bydraes/Skrywers-oortjies en die werke-argief. Die Skrywers-oortjie en filter/sorteer-kontroles kom met Stories 8.2/8.3.
 *
 * The hub shell (Story 8.1). The tab labels read from the ink-core terminology
 * registry via the `ink_foundation_term()` bridge (single-source, never a bare
 * literal). The works archive itself is the server-rendered `ink/ontdek-werke`
 * block (ink-core/Discovery) — all business logic stays in ink-core (three-layer).
 *
 * @package Ink\Foundation
 */

$ink_bydraes_label  = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'bydrae_plural', 'Bydraes' )
	: 'Bydraes';
$ink_skrywers_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'skrywer_plural', 'Skrywers' )
	: 'Skrywers';
?>
<!-- wp:pattern {"slug":"ink-foundation/archive-intro"} /-->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:buttons {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"flex","flexWrap":"wrap"}} -->
	<div class="wp-block-buttons alignwide">
		<!-- wp:button {"className":"is-style-pill","backgroundColor":"primary","textColor":"surface-alt"} -->
		<div class="wp-block-button is-style-pill"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="#bydraes"><?php echo esc_html( $ink_bydraes_label ); ?></a></div>
		<!-- /wp:button -->

		<!-- wp:button {"className":"is-style-pill is-style-outline","textColor":"primary"} -->
		<div class="wp-block-button is-style-pill is-style-outline"><a class="wp-block-button__link has-primary-color has-text-color wp-element-button" href="#skrywers"><?php echo esc_html( $ink_skrywers_label ); ?></a></div>
		<!-- /wp:button -->
	</div>
	<!-- /wp:buttons -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" id="bydraes" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-werke /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" id="skrywers" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/ontdek-skrywers /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
