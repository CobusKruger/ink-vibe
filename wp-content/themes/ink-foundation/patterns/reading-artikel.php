<?php
/**
 * Title: Leesblad — Artikel
 * Slug: ink-foundation/reading-artikel
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n artikel (prosa) — tipe-etiket, titel, byskrif en 'n leesbare hoofkolom op die 672px-leesmaat, identies aan reading-storie.php (Argetipe C, Storie 7.1, FR-24).
 *
 * Presentation only (three-layer separation). The post title, author, date and
 * body are core blocks resolved per-post at render time; the only ink-core
 * touch is the Afrikaans type label, sourced from the terminology registry via
 * the `ink_foundation_term()` bridge (single-source, never a bare literal).
 * No WP comments UI — comments are disabled site-wide (Ink\Engagement\Comments,
 * Story 1.8) and the reading surface adds none.
 *
 * Fourth pass rebuild (docs/theme-fidelity-audit-handoff.md, 2026-09-05): this
 * pattern used to be an entirely separate, bespoke design (uppercase eyebrow
 * badge, no hint pill, no sticky engagement bar, no author-card band). That was
 * itself the bug — `ink-lovable/src/pages/ReadStory.tsx` is the SINGLE shared
 * component for Poetry, Short Story AND Article (`src/data/works.ts`'s
 * `WorkType` has all three; the only branch anywhere in the file is
 * `isPoetry = work.type === "Poetry"`), so Article gets Short Story's exact
 * layout, only the Afrikaans type-label text differs. Rebuilt onto
 * reading-storie.php's exact block shape: same terracotta/primary badge pill
 * (was its own uppercase/accent eyebrow), same title sizing (xxxxxl, was
 * xxxxl — Lovable applies identical title classes to Short Story and Article),
 * same "Kies enige teks…" hint pill (was absent), same sticky `enkel`-variant
 * engagement bar as a direct child of `<main>` (was a non-sticky bar nested
 * inside the body section), same author-card band (was absent), same
 * Gemeenskapsreaksies/verwante-stukke/opleiding-verwant closing stack
 * (structure unchanged, now reached via the same layout as storie).
 *
 * `data-audit-id`s deliberately reuse storie's literal strings
 * ("storie-badge"/"storie-title"/"storie-hint"/"storie-engagement-bar"/
 * "storie-highlight"/"storie-paragraph"/"storie-author-name"/
 * "storie-author-bio") rather than minting "artikel-*" variants — this mirrors
 * `ReadStory.tsx` literally: its own `data-audit-id={isPoetry ? "gedig-x" :
 * "storie-x"}` ternary emits the SAME "storie-x" string for both Short Story
 * and Article, there is no third value anywhere in the source. See
 * `functions.php`'s `ink_foundation_audit_id_reading_title()` /
 * `ink_foundation_audit_id_reading_paragraph()`, both storie+artikel-gated and
 * both emitting "storie-title"/"storie-paragraph" for either post type, for
 * the same reason.
 *
 * Deliberate WP-only divergence from that shared shape (product-owner
 * decision, 2026-09-05, after the rebuild above): the badge pill's colour is
 * now `muted-text` (a gray) instead of reusing storie's `primary`/terracotta,
 * specifically SO Article reads as visually distinct from Story at a glance —
 * Lovable itself renders both in the identical terracotta pill (confirmed
 * above, `isPoetry` is the only branch), so this is not a fidelity fix, it's
 * an intentional exception layered on top of the otherwise-shared shape. Kept
 * the `data-audit-id="storie-badge"` anchor unchanged regardless, since that
 * identifies which Lovable branch this element structurally corresponds to,
 * not the colour actually rendered.
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'artikel', 'Artikel' )
	: 'Artikel';

$ink_storie_highlighter_svg = '<span aria-hidden="true" style="display:inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--sm ink-band--stone ink-band--border-b","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-band ink-band--sm ink-band--stone ink-band--border-b" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"className":"ink-lees-intro","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group ink-lees-intro">
		<!-- wp:paragraph {"className":"ink-lees-tipe ink-pill ink-pill--tint ink-tone-muted","textAlign":"center"} -->
		<p data-audit-id="storie-badge" class="ink-lees-tipe ink-pill ink-pill--tint ink-tone-muted has-text-align-center"><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","className":"ink-heading-h1-reading"} /-->

		<!-- wp:group {"className":"ink-lees-byline","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div class="wp-block-group ink-lees-byline">
			<!-- wp:avatar {"size":40,"isLink":true,"className":"ink-avatar ink-avatar--40"} /-->

			<!-- wp:post-author-name {"isLink":true,"fontSize":"md","textColor":"ink-text","style":{"typography":{"fontWeight":"500"}}} /-->

			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-md-font-size">•</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"md","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

<?php if ( is_user_logged_in() ) : ?>
		<!-- wp:paragraph {"className":"ink-storie-hint ink-pill ink-pill--hint","textAlign":"center"} -->
		<p data-audit-id="storie-hint" class="ink-storie-hint ink-pill ink-pill--hint has-text-align-center"><?php echo $ink_storie_highlighter_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html__( 'Kies enige teks om jou gunsteling passasies uit te lig', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
<?php endif; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--sm","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-band ink-band--sm" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:post-content {"className":"ink-lees-storie__prose","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"fontSize":"lg","layout":{"type":"constrained","contentSize":"672px"}} /-->
</section>
<!-- /wp:group -->

<!-- wp:group {"lock":{"move":true,"remove":true},"className":"ink-reaksie-bar ink-toolbar"} -->
<div data-audit-id="storie-engagement-bar" class="wp-block-group ink-reaksie-bar ink-toolbar">
	<!-- wp:ink/reaksie-tellers {"variant":"enkel"} /-->

	<!-- wp:ink/kommentaar-telling /-->

	<!-- wp:ink/leeslys-knoppie /-->
</div>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-outeur-kaart-band ink-band ink-band--sm ink-band--stone ink-band--border-y","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-outeur-kaart-band ink-band ink-band--sm ink-band--stone ink-band--border-y" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/outeur-kaart /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-band ink-band--sm","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-band ink-band--sm" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
