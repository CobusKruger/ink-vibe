<?php
/**
 * Title: Borg-strook
 * Slug: ink-foundation/borg-strook
 * Categories: ink-foundation
 * Description: 'n Borg-afdeling vir die tuisblad (Storie 14.3 / 19.5, §7, FR-58).
 *
 * The homepage sponsor section (Story 14.3, restyled to fidelity in 19.5 §7). The
 * section itself is the server-rendered `ink/borg-strook` block (ink-core/Sponsors) —
 * ALL business logic (which sponsors are active, their tier, rotation, collapse-when-none)
 * stays in ink-core (three-layer separation). The block emits the eyebrow, heading,
 * intro, the per-tier chips (`ink-borg-strook__chip--{goud|silwer|brons}`) and the
 * "Word 'n borg" CTA; this pattern is a token-only band frame that carries NO
 * user-facing copy, so it is structural-only and copy-scan-exempt. The calm
 * `secondary`/20 band + centring + chip styling live in assets/css/home.css
 * (front-page-only — the borg strip is a home section). Fades up once on mount via
 * .ink-animate-fade-up (reduced-motion safe, §0.6).
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:group {"tagName":"aside","align":"full","className":"ink-borg-band ink-animate-fade-up","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<aside class="wp-block-group alignfull ink-borg-band ink-animate-fade-up" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/borg-strook /-->
	</div>
	<!-- /wp:group -->
</aside>
<!-- /wp:group -->
