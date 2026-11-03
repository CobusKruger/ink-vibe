<?php
/**
 * Title: Leesblad — InkPols-uitgawe
 * Slug: ink-foundation/reading-inkpols
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n InkPols-uitgawe: balk, flipbook, inhoud en vorige/volgende (N3).
 *
 * Presentation only. The three ink-core blocks emit their own bands. Lovable has no
 * post-content section, so the single has no body section (`04-inkpols` §1). The group has no
 * padding: it takes the blocks out of `main`'s constrained layout.
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:group {"align":"full","className":"ink-inkpols ink-inkpols--enkel","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull ink-inkpols ink-inkpols--enkel">
	<!-- wp:ink/inkpols-besonderhede /-->
	<!-- wp:ink/inkpols-leser /-->
	<!-- wp:ink/inkpols-inhoud /-->
</div>
<!-- /wp:group -->
