<?php
/**
 * Title: Leesblad — Storie
 * Slug: ink-foundation/reading-storie
 * Categories: ink-foundation
 * Inserter: no
 * Description: Leessjabloon vir 'n storie (prosa) — tipe-etiket, titel, byskrif en 'n leesbare hoofkolom op die 768px-leesmaat (Argetipe C, Storie 7.1, FR-24).
 *
 * Presentation only (three-layer separation). The post title, author, date and
 * body are core blocks resolved per-post at render time; the only ink-core
 * touch is the Afrikaans type label, sourced from the terminology registry via
 * the `ink_foundation_term()` bridge (single-source, never a bare literal).
 * No WP comments UI — comments are disabled site-wide (Ink\Engagement\Comments,
 * Story 1.8) and the reading surface adds none.
 *
 * Text-highlight-reactions hint (post-Epic-19 storie fidelity pass, FR-24/26):
 * mirrors reading-gedig's `ink-gedig-hint` pill, but with the ratified copy for
 * THIS interaction — "Kies enige teks om jou gunsteling passasies uit te lig"
 * (docs/ui-copy-translations.md row 421, the exact translation of Lovable's own
 * "Select any text to highlight your favorite passages" wenk-etiket) and the
 * `highlight`/`highlight-foreground` design tokens (unused elsewhere in the
 * theme until now) rather than gedig's accent-green tint, matching Lovable's
 * own `bg-highlight/20` treatment for this specific hint pill.
 */

$ink_type_label = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'storie', 'Storie' )
	: 'Storie';

$ink_storie_highlighter_svg = '<span aria-hidden="true" style="display:inline-flex;vertical-align:-2px;margin-right:4px"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg></span>';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained","contentSize":"768px"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"className":"ink-lees-tipe","textAlign":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500"}},"fontSize":"sm","textColor":"primary"} -->
		<p class="ink-lees-tipe has-text-align-center has-primary-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:500"><?php echo esc_html( $ink_type_label ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:post-title {"level":1,"textAlign":"center","fontSize":"xxxxxl","style":{"typography":{"fontWeight":"600"}}} /-->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","flexWrap":"wrap","justifyContent":"center"}} -->
		<div class="wp-block-group">
			<!-- wp:avatar {"size":40,"style":{"border":{"radius":"9999px"}}} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size"><?php esc_html_e( 'deur', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:post-author-name {"fontSize":"sm","textColor":"ink-text","style":{"typography":{"fontWeight":"500"}}} /-->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size">·</p>
			<!-- /wp:paragraph -->

			<!-- wp:post-date {"fontSize":"sm","textColor":"muted-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:paragraph {"className":"ink-storie-hint","textAlign":"center","fontSize":"sm"} -->
		<p class="ink-storie-hint has-text-align-center has-sm-font-size"><?php echo $ink_storie_highlighter_svg; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static, hand-authored inline SVG, no user input */ ?><?php echo esc_html__( 'Kies enige teks om jou gunsteling passasies uit te lig', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:post-content {"className":"ink-lees-storie__prose","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"fontSize":"lg","layout":{"type":"constrained","contentSize":"672px"}} /-->

	<!-- wp:group {"lock":{"move":true,"remove":true},"className":"ink-reaksie-bar"} -->
	<div class="wp-block-group ink-reaksie-bar">
		<!-- wp:ink/reaksie-tellers /-->

		<!-- wp:ink/leeslys-knoppie /-->
	</div>
	<!-- /wp:group -->

	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"margin":{"top":"var:preset|spacing|s-48"}}},"layout":{"type":"constrained","contentSize":"672px"}} -->
	<div class="wp-block-group" style="margin-top:var(--wp--preset--spacing--s-48)">
		<!-- wp:ink/leesprompte /-->

		<!-- wp:ink/gemeenskapsreaksies /-->

		<!-- wp:ink/verwante-stukke /-->

		<!-- wp:ink/opleiding-verwant /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
