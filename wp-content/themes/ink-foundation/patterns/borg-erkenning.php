<?php
/**
 * Title: Borg-erkenning
 * Slug: ink-foundation/borg-erkenning
 * Categories: ink-foundation
 * Description: Die volledige borg-erkenningsafdeling vir Oor INK (Storie 14.4, FR-58).
 *
 * The full sponsor recognition section (Story 14.4) for the Oor INK page. The
 * section itself is the server-rendered `ink/borg-erkenning` block
 * (ink-core/Sponsors) — all business logic (which sponsors are active) and all
 * copy (eyebrow, heading, thank-you body, CTA) live in ink-core (three-layer
 * separation; copy from the terminology registry). This pattern is a token-only
 * frame carrying NO user-facing copy, so it is structural-only and copy-scan-exempt.
 * Available in the inserter for the Oor INK assembly (Story 15.3).
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/borg-erkenning /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
