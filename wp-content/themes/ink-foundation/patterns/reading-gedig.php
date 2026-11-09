<?php
/**
 * Title: Leesblad — Gedig
 * Slug: ink-foundation/reading-gedig
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n gedig — tipe-etiket, titel, byskrif en die strofe-bewuste digbundel-uitleg (Storie 7.2, FR-25). Reëls, leë reëls/strofes en inkeping word verbatim bewaar; Romeinse strofemerkers word gestileer.
 *
 * Presentation only (three-layer separation). The page follows Lovable's `ReadStory.tsx`
 * poetry branch node for node (acceptance 2026-11-02): raw-HTML wrappers around the dynamic
 * blocks, so WordPress's group layout adds no wrappers, block-gap margins or positioning.
 * Fragments (byline with read time, type badge, hint, share) come from inc/reading-parts.php.
 * The header column is Lovable's max-w-3xl (768px, `.ink-lees-intro`, reading.css). Copy is
 * authored Afrikaans (docs/ui-copy-translations.md); the type label comes from the terminology
 * registry via ink_foundation_term().
 *
 * @package Ink\Foundation
 */

$ink_type = 'gedig';

require_once get_theme_file_path( 'inc/reading-parts.php' );
?>
<!-- wp:html -->
<div data-audit-id="reading-header" class="alignfull ink-band ink-band--sm ink-band--cream-soft ink-band--border-b ink-lees-kop">
	<div class="ink-container"><div data-audit-id="reading-header-inner" class="ink-lees-intro">
		<?php echo ink_foundation_reading_badge( $ink_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped. Label: ink_foundation_term( 'gedig' ). ?>
<!-- /wp:html -->

<!-- wp:post-title {"level":1,"className":"ink-heading-h1-reading-poem"} /-->

<!-- wp:html -->
		<?php /* The byline is the `.ink-lees-byline` row: one author link, a bullet, the read time. */ echo ink_foundation_reading_byline(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped. ?>
<!-- /wp:html -->

<!-- wp:ink/lees-wenner /-->

<!-- wp:html -->
<?php if ( is_user_logged_in() ) : // Owner ruling 2026-11-02: the interaction hint stays members-only (logged-out MISSING is accepted in the map). ?>
		<?php echo ink_foundation_reading_hint( $ink_type ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped. ?>
<?php endif; ?>
	</div></div>
</div>
<article data-audit-id="reading-body" class="alignfull ink-band ink-band--poem"><div class="ink-container"><div data-audit-id="gedig-body-col" class="ink-lees-kolom ink-lees-kolom--gedig">
<!-- /wp:html -->

<!-- wp:ink/gedig-body /-->

<!-- wp:html -->
	</div></div>
</article>

<div data-audit-id="gedig-engagement-bar" class="alignfull ink-reaksie-bar"><div class="ink-container">
	<div data-audit-id="reading-engagement-pill" class="ink-toolbar ink-reaksie-bar__pil">
<!-- /wp:html -->

<!-- wp:ink/reaksie-tellers {"variant":"enkel"} /-->

<!-- wp:ink/kommentaar-telling /-->

<!-- wp:ink/leeslys-knoppie /-->

<!-- wp:html -->
		<?php echo ink_foundation_reading_share_button(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built escaped. ?>
	</div>
</div></div>

<section data-audit-id="reading-author-band" class="alignfull ink-outeur-kaart-band ink-band ink-band--sm ink-band--stone ink-band--border-y"><div class="ink-container">
<!-- /wp:html -->

<!-- wp:ink/outeur-kaart /-->

<!-- wp:html -->
</div></section>

<section data-audit-id="reading-responses" class="alignfull ink-band ink-band--sm"><div class="ink-container">
<!-- /wp:html -->

<!-- wp:ink/gemeenskapsreaksies /-->

<!-- wp:html -->
<div class="ink-lees-kolom">
<!-- /wp:html -->

<!-- wp:html -->
</div></div></section>
<!-- /wp:html -->
