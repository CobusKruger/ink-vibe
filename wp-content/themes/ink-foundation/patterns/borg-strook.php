<?php
/**
 * Title: Borg-strook
 * Slug: ink-foundation/borg-strook
 * Categories: ink-foundation
 * Description: 'n Subtiele borg-strook vir die tuisblad (Storie 14.3, FR-58).
 *
 * The subtle homepage sponsor strip (Story 14.3). The strip itself is the
 * server-rendered `ink/borg-strook` block (ink-core/Sponsors) — all business
 * logic (which sponsor, rotation, collapse-when-none) stays in ink-core
 * (three-layer separation). This pattern is a token-only frame: it carries NO
 * user-facing copy (the "Ons borge" eyebrow is rendered by the block from the
 * ink-core terminology registry), so it is structural-only and copy-scan-exempt.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"aside","align":"full","lock":{"move":true,"remove":true},"backgroundColor":"surface-alt","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-24","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<aside class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/borg-strook /-->
	</div>
	<!-- /wp:group -->
</aside>
<!-- /wp:group -->
