<?php
/**
 * Title: QA Component Gallery
 * Slug: ink-foundation/qa-bloks
 * Categories: ink-foundation
 * Description: Internal QA/component-gallery page (Phase 2, Theme Visual-Fidelity rework). Renders INK's data-dependent dynamic blocks with fixture data so their visual fidelity can be checked without real content existing. Not part of the public site IA — never link to it from navigation.
 *
 * === What this page is ===
 *
 * Several INK blocks (`ink/huidige-uitdaging`, `ink/wenner-kollig`,
 * `ink/uitgesoekte-bydraes`, `ink/borg-strook`, and likely more as later pages'
 * fidelity gets checked) query real WordPress content and render NOTHING when
 * that content doesn't exist. Almost none of it exists on this dev site — so
 * their visual fidelity against the Lovable reference could never be checked.
 * This page is a persistent, repo-tracked fixture harness: it embeds each such
 * block and feeds it realistic data, at a stable URL
 * (`/` . INK_FOUNDATION_QA_GALLERY_SLUG, defined in `functions.php`), so any
 * future fidelity-pass agent can load it and see the block actually rendered.
 *
 * See `functions.php`'s "QA/component gallery — fixture-data pattern" section for
 * how the fixture data itself is supplied (filter seams gated to THIS page only,
 * or real seeded WP content when no filter seam exists) and exactly how to extend
 * this for a new block. Short version: add your block's markup below with a clear
 * section heading, then wire its fixture data per that functions.php doc.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-64);padding-bottom:var(--wp--preset--spacing--s-24)">
	<!-- wp:heading {"level":1} -->
	<h1 class="wp-block-heading">QA Component Gallery</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph -->
	<p>Internal QA tooling — not part of the public site. Renders INK's data-dependent dynamic blocks with fixture data for visual-fidelity review against the Lovable reference. See <code>functions.php</code> ("QA/component gallery — fixture-data pattern") and <code>patterns/qa-bloks.php</code> for how to extend this page.</p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">1. Weekly-challenge card — compact variant</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/huidige-uitdaging</code> (variant <code>kompak</code>) — the hero-right-column card. Fixture data via the <code>ink_home_current_challenge</code> filter, gated to this page.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"ink-hero-aside","lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"420px","justifyContent":"left"}} -->
	<div class="wp-block-group ink-hero-aside">
		<!-- wp:ink/huidige-uitdaging {"variant":"kompak"} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">2. Weekly-challenge card — feature variant, and 3. Winner spotlight</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/huidige-uitdaging</code> (variant <code>kenmerk</code>) + <code>ink/wenner-kollig</code>, side by side exactly as the feature band on the tuisblad. Fixture data via the <code>ink_home_current_challenge</code> and <code>ink_home_featured_winner</code> filters, both gated to this page.</p>
	<!-- /wp:paragraph -->

	<!-- wp:group {"className":"ink-feature-grid","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}},"layout":{"type":"grid","columnCount":2}} -->
	<div class="wp-block-group ink-feature-grid">
		<!-- wp:ink/huidige-uitdaging {"variant":"kenmerk"} /-->

		<!-- wp:ink/wenner-kollig /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">4. Featured bydraes — editor's picks</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/uitgesoekte-bydraes</code> — the asymmetric grid (one spanning featured card + three standard cards). Fixture data via the <code>ink_home_featured_stream</code> filter, gated to this page. Self-declares <code>alignfull</code>.</p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:ink/uitgesoekte-bydraes /-->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">5. Sponsor strip — all three tier chips</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/borg-strook</code> — no filter seam exists ({@see Ink\Sponsors\Campaign::activeSponsors()} queries directly); this reads three REAL seeded <code>borg</code> posts titled <code>QA FIXTURE — …</code>, one per tier (goud/silwer/brons), evergreen (no campaign end date). That's DB state, not tracked by git — see the session report/commit notes for exactly what was seeded.</p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"aside","align":"full","className":"ink-borg-band","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<aside class="wp-block-group alignfull ink-borg-band" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/borg-strook /-->
	</div>
	<!-- /wp:group -->
</aside>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">6. Opleiding hub — featured shelf + card grid</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/opleiding-argief</code> — no filter seam exists (a live paginated <code>WP_Query</code>, {@see Ink\Training\Hub::runQuery()}); the real <code>/opleiding/</code> page EXCLUDES <code>QA FIXTURE — </code> titled <code>opleiding_artikel</code> posts by default (Epic-19 theme-fidelity rework finding — the same leak class fixed on the sponsor strip). This embed turns that exclusion back on via <code>ink_opleiding_argief_include_fixtures</code>, gated to this page only, so the three real seeded <code>QA FIXTURE — </code> <code>opleiding_artikel</code> posts stay visible here for the featured shelf + card-grid fidelity check.</p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/opleiding-argief /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-12"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignwide" style="padding-top:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-12)">
	<!-- wp:heading {"level":2} -->
	<h2 class="wp-block-heading">7. Biblioteek argief — featured shelf + card grid</h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><code>ink/biblioteek-argief</code> — no filter seam exists (a live paginated <code>WP_Query</code>, {@see Ink\Library\Archive::runQuery()}); the real <code>/biblioteek/</code> page EXCLUDES <code>QA FIXTURE — </code> titled <code>biblioteek_item</code> posts by default (Epic-19 theme-fidelity rework finding, re-found during the biblioteek re-audit — the same leak class already fixed on the sponsor strip and the Opleiding hub). This embed turns that exclusion back on via <code>ink_biblioteek_argief_include_fixtures</code>, gated to this page only, so the three real seeded <code>QA FIXTURE — </code> <code>biblioteek_item</code> posts stay visible here for the featured shelf + card-grid fidelity check.</p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:ink/biblioteek-argief /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
