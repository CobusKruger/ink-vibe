<?php
/**
 * Title: Opleiding-hub
 * Slug: ink-foundation/opleiding
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Opleiding-hulpbronhub: uitgeligte strook, soek en kaartrooster oor opleidingsartikels (Storie 11.1, FR-54).
 *
 * The Opleiding hub shell (Story 11.1). The hub itself is the server-rendered
 * `ink/opleiding-argief` block (ink-core/Training) — all business logic stays in
 * ink-core (three-layer separation). A resource hub, not an LMS. The section
 * heading reads from the ink-core terminology registry via the
 * `ink_foundation_term()` bridge (single-source, never a bare literal).
 *
 * @package Ink\Foundation
 */

$ink_opleiding_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'opleiding', 'Opleiding' )
	: 'Opleiding';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section class="wp-block-group alignfull" data-audit-id="library-shell" aria-label="<?php echo esc_attr( $ink_opleiding_label ); ?>">
	<!-- wp:ink/opleiding-argief /-->
</section>
<!-- /wp:group -->
