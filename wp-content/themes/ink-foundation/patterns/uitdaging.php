<?php
/**
 * Title: Uitdagings-argief
 * Slug: ink-foundation/uitdaging
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Die Uitdagings-lys: kaartrooster oor uitdagings met sluitingsdatum en aftelling (Storie 12.2, FR-46).
 *
 * The challenges archive shell (Story 12.2). The list itself is the server-rendered
 * `ink/uitdaging-argief` block (ink-core/Challenges) — all business logic stays in
 * ink-core (three-layer separation). The section heading reads from the ink-core
 * terminology registry via the `ink_foundation_term()` bridge (single-source, never a
 * bare literal). The block emits its own full-bleed bands (intro band + list band,
 * the index-page shell shared with Opleiding/Biblioteek), so this shell adds no
 * padding or width of its own (fidelity Stage 4, row 14).
 *
 * @package Ink\Foundation
 */

$ink_uitdagings_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'uitdaging_plural', 'Uitdagings' )
	: 'Uitdagings';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
<section data-audit-id="uitdagings" class="wp-block-group alignfull" aria-label="<?php echo esc_attr( $ink_uitdagings_label ); ?>">
	<!-- wp:ink/uitdaging-argief /-->
</section>
<!-- /wp:group -->
