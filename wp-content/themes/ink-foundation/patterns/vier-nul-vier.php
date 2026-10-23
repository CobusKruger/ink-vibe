<?php
/**
 * Title: 404
 * Slug: ink-foundation/vier-nul-vier
 * Categories: ink-foundation
 * Inserter: no
 *
 * The 404 body (`templates/404.html`). Fidelity remediation Stage 4, row 12:
 * before this the 404 fell back to `index.html` and rendered an empty `<main>`
 * between the header and footer — no heading, message or home link.
 *
 * Structure and values follow Lovable's `src/pages/NotFound.tsx`: a centred
 * band on the `muted` surface, viewport-tall (`min-h-screen`), holding a
 * "404" heading (Lora 36/40 700, `mb-4`), a muted 20/28 message (`mb-4`) and
 * an underlined primary link home. The site header and footer stay (the INK
 * template frame); whether a 404 should drop them as Lovable does is the same
 * owner call as the auth pages' chrome.
 *
 * COPY DEBT: the message and the link label have no authored Afrikaans yet
 * (`docs/afrikaans-translation-sheet.md` §13, IDs `404-BOODSKAP` and
 * `404-TUIS-SKAKEL`). They carry the needs-human-Afrikaans placeholder marker per the
 * copy-debt process — never an AI translation of Lovable's English.
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-vier-nul-vier","lock":{"move":true,"remove":true},"style":{"dimensions":{"minHeight":"100vh"},"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"},"blockGap":"var:preset|spacing|s-16"}},"backgroundColor":"muted","layout":{"type":"flex","orientation":"vertical","justifyContent":"center","verticalAlignment":"center"}} -->
<section data-audit-id="vier-nul-vier" class="wp-block-group alignfull ink-vier-nul-vier has-muted-background-color has-background" style="min-height:100vh;padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontWeight":"700","lineHeight":"2.5rem"}},"fontSize":"xxxxl","fontFamily":"heading"} -->
	<h1 data-audit-id="vier-nul-vier-heading" class="wp-block-heading has-text-align-center has-heading-font-family has-xxxxl-font-size" style="font-weight:700;line-height:2.5rem">404</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","style":{"typography":{"lineHeight":"1.75rem"}},"fontSize":"xl","textColor":"muted-text"} -->
	<p data-audit-id="vier-nul-vier-message" class="has-text-align-center has-muted-text-color has-text-color has-xl-font-size" style="line-height:1.75rem"><?php echo esc_html__( '[NEEDS HUMAN AFRIKAANS] — 404 message ("page not found") not yet authored in ui-copy-translations.md.', 'ink-foundation' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"align":"center","fontSize":"md"} -->
	<p data-audit-id="vier-nul-vier-home" class="has-text-align-center has-md-font-size"><a data-audit-id="vier-nul-vier-home-link" class="ink-link" style="text-decoration-line:underline" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html__( '[NEEDS HUMAN AFRIKAANS] — 404 link-home label not yet authored in ui-copy-translations.md.', 'ink-foundation' ); ?></a></p>
	<!-- /wp:paragraph -->
</section>
<!-- /wp:group -->
