<?php
/**
 * Title: Leesblad — Uitdaging
 * Slug: ink-foundation/reading-uitdaging
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n uitdaging — badge, titel, taglyn, sluitingsdatum/
 * status/deelnemers, opdrag, inskrywings en 'n sluitende oproep tot aksie (Storie
 * 12.1, FR-45; Post-Epic-19 fidelity pass, workstream 6; third-pass structural
 * correspondence fixes, workstream 6b).
 *
 * Presentation only (three-layer separation). The reading header is core blocks
 * resolved per-post at render time; the type-badge label comes from the ink-core
 * terminology registry via the `ink_foundation_term()` bridge (single-source). The
 * editorial brief (opdrag, literêre middele, reëls, prys, hulpbronne) is authored as
 * the uitdaging post body and renders through core `post-content` — a single authored
 * blob (Story 12.1 decision), not split into the separate Prompt/Rules/Prize zones
 * the Lovable reference (`Challenge.tsx`) shows; splitting it would need new post-meta
 * fields, a content-model change out of scope for a visual-fidelity pass (see
 * `docs/theme-fidelity-rework-plan.md`, workstream 6 report). Lovable's separate
 * "Learning resources for this challenge" card grid (hulpbronne) has NO WP
 * counterpart at all, in any form — confirmed absent this pass (workstream 6b), not
 * folded into the opdrag blob either; it would need a new resource-link content
 * model (repeater field or CPT), a real feature build, not a style fix — flagged for
 * a product-owner decision, not guessed at here.
 *
 * The sluitingsdatum/Oop-Gesluit-status/deelnemers meta row and the inskrywings list
 * are both the server-rendered `ink/uitdaging-besonderhede` block (ink-core/
 * Challenges) — all business logic stays in ink-core — but rendered as TWO separate
 * embeds via its `variant` attribute (`kop` in the hero, `inskrywings` in its own
 * section below the opdrag). Workstream 6b's structural-correspondence audit found
 * the two previously fused into ONE embed placed in the hero, so the entries list
 * rendered ABOVE the prompt/opdrag content — the opposite of Lovable's order (Hero →
 * Prompt → Resources → Submissions → CTA). Splitting the block fixed the order
 * without changing where any business logic lives. No WP comments UI — comments are
 * disabled site-wide.
 *
 * The two CTA button rows + the closing band reuse copy already ratified in
 * `docs/ui-copy-translations.md` ("Uitdaging-detailbladsy" section) — never
 * freehand-translated here. The hero badge deliberately does NOT use that sheet's
 * "Weeklikse uitdaging" ("Weekly Challenge") string: INK's real cadence model is
 * Maandeliks/Jaarliks, never weekly, so that literal translation of Lovable's mockup
 * copy would misstate a real uitdaging's cadence; the badge keeps the existing
 * data-true type label instead. Lovable's "Editor's pick" (Trophy icon) meta item
 * also has ratified copy ("Die redakteur se keuse") but NO backing data anywhere in
 * the codebase — no per-uitdaging "is this an editor's pick" flag exists — so it is
 * deliberately NOT rendered here rather than shown as a permanent, potentially false
 * claim on every challenge; flagged for a product-owner decision (workstream 6b).
 *
 * The taglyn paragraph below the title renders the post excerpt ONLY when one has
 * been manually authored (`has_excerpt()`) — mirrors {@see \Ink\Challenges\CurrentChallenge::excerptFor()}'s
 * graceful-omit convention. No uitdaging post has a manually-authored excerpt yet
 * (confirmed this pass) — this is copy-debt to author, not a template bug; rendering
 * the auto-generated WordPress excerpt instead would just duplicate the opdrag text
 * directly beneath the title.
 *
 * @package Ink\Foundation
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'uitdaging', 'Uitdaging' )
	: 'Uitdaging';

$ink_uitdaging_archive = function_exists( 'get_post_type_archive_link' )
	? get_post_type_archive_link( 'uitdaging' )
	: '';
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-uitdaging__hero","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-uitdaging__hero" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"ink-uitdaging__terug","lock":{"move":true,"remove":true},"fontSize":"sm"} -->
		<p class="ink-uitdaging__terug has-sm-font-size"><a href="/"><?php esc_html_e( '← Terug na tuis', 'ink-foundation' ); ?></a></p>
		<!-- /wp:paragraph -->

		<!-- wp:paragraph {"className":"ink-lees-tipe ink-uitdaging__badge","lock":{"move":true,"remove":true},"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.025em"}},"fontSize":"xs","textColor":"primary"} -->
		<p class="ink-lees-tipe ink-uitdaging__badge has-primary-color has-text-color has-xs-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.025em;text-transform:uppercase"><?php echo ink_foundation_icon( '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.937A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>' ) . esc_html( $ink_type_label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html(). ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"fontSize":"hero"} /-->

		<?php if ( has_excerpt() ) : ?>
		<!-- wp:paragraph {"className":"ink-uitdaging__tagline","lock":{"move":true,"remove":true}} -->
		<p class="ink-uitdaging__tagline" data-audit-id="uitdaging-tagline"><?php echo esc_html( get_the_excerpt() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- esc_html() applied directly above. ?></p>
		<!-- /wp:paragraph -->
		<?php endif; ?>

		<!-- wp:ink/uitdaging-besonderhede {"variant":"kop"} /-->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-icon"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/skryf"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) . esc_html__( 'Skryf in', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-ink-outline ink-btn-icon"} -->
			<div class="wp-block-button is-style-ink-outline ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="#inskrywings"><?php echo esc_html__( 'Lees inskrywings', 'ink-foundation' ) . ink_foundation_icon( '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the label is esc_html__(); ink_foundation_icon() returns trusted, self-escaped inline SVG. ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-24","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"ink-uitdaging__opdrag","lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group ink-uitdaging__opdrag">
		<!-- wp:post-content {"lock":{"move":true,"remove":true}} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"layout":{"type":"constrained","contentSize":"1120px"}} -->
	<div class="wp-block-group">
		<!-- wp:ink/uitdaging-besonderhede {"variant":"inskrywings"} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-uitdaging-cta","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-uitdaging-cta">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained","contentSize":"576px","justifyContent":"center"}} -->
	<div class="wp-block-group">
		<!-- wp:html -->
		<span class="ink-uitdaging-cta__ikoon" aria-hidden="true"><?php echo ink_foundation_icon( '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.937A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG. ?></span>
		<!-- /wp:html -->

		<!-- wp:heading {"textAlign":"center","level":2,"className":"ink-uitdaging-cta__titel"} -->
		<h2 class="wp-block-heading has-text-align-center ink-uitdaging-cta__titel"><?php esc_html_e( 'Jou storie wag om geskryf te word', 'ink-foundation' ); ?></h2>
		<!-- /wp:heading -->

		<?php if ( function_exists( 'ink_foundation_uitdaging_cta_subtitel' ) ) : ?>
		<!-- wp:html -->
		<?php echo ink_foundation_uitdaging_cta_subtitel( get_the_ID() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- returns self-escaped HTML from Ink\Challenges\SinglePage::ctaSubtitleHtml(). ?>
		<!-- /wp:html -->
		<?php endif; ?>

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-icon"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-icon"><a class="wp-block-button__link wp-element-button" href="/skryf"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) . esc_html__( 'Begin skryf', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-ink-outline"} -->
			<div class="wp-block-button is-style-ink-outline"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $ink_uitdaging_archive ? $ink_uitdaging_archive : '/uitdaging/' ); ?>"><?php esc_html_e( 'Ontdek ander uitdagings', 'ink-foundation' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
