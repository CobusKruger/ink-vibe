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
 * Fidelity Stage 4 (row 18, Treatment C — OFF-RECIPE): the intro is the
 * index-page intro recipe (`ink-band--intro`, py 56 → 80 on the inner
 * container, left-aligned max-w-3xl copy, 18px relaxed lede — Lovable
 * `Browse.tsx:389-402`); the directory section is the Ontdek content-panel
 * recipe (`ink-ontdek-paneel`). Both inner wide groups carry no `layout`, so
 * their children are not clamped to the 768px content size (see ontdek.php).
 *
 * @package Ink\Foundation
 */

$ink_ledegids_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'ledegids', 'Ledegids' )
	: 'Ledegids';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-ledegids-intro ink-band ink-band\u002d\u002dintro ink-band\u002d\u002dflush","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-ledegids-intro ink-band ink-band--intro ink-band--flush" data-audit-id="ledegids-intro" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","className":"ink-ledegids-intro__inner","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
	<div class="wp-block-group alignwide ink-ledegids-intro__inner" data-audit-id="ledegids-intro-inner">
		<!-- wp:heading {"level":1,"className":"ink-heading-h1-index"} -->
		<h1 class="wp-block-heading ink-heading-h1-index" data-audit-id="ledegids-h1"><?php echo esc_html( $ink_ledegids_label ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"className":"ink-ledegids-intro__tekst","fontSize":"lg","textColor":"muted-text"} -->
		<p class="ink-ledegids-intro__tekst has-muted-text-color has-text-color has-lg-font-size" data-audit-id="ledegids-lede"><?php echo esc_html__( 'Ontdek die skrywers van die gemeenskap.', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-ontdek-paneel","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-ontdek-paneel" data-audit-id="ledegids-directory" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true}} -->
	<div class="wp-block-group alignwide" data-audit-id="ledegids-directory-inner">
		<!-- wp:ink/ontdek-skrywers /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
