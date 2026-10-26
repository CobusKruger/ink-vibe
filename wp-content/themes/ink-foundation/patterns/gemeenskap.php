<?php
/**
 * Title: Gemeenskap-bladsy
 * Slug: ink-foundation/gemeenskap
 * Categories: ink-foundation, page
 * Description: Die Gemeenskap-bekeringsbladsy (Storie 15.2, FR-60) — held, waarde-kaarte (Vir skrywers / Vir lesers), Hoe INK werk, Gemeenskapsbeginsels en sluitende oproep tot aksie.
 *
 * Presentation only (three-layer separation): a static marketing/conversion page.
 * No business logic, no post queries, no server-rendered ink-core block. Story
 * 15.2's AC (epics.md) and EXPERIENCE.md's page-map row for gemeenskap both scope
 * this page to "value props, principles, how-it-works, and CTAs" — Lovable's
 * Community.tsx ALSO carries a live statistics counter strip and a "This Month's
 * Spotlight" featured-writer/-reader block, neither mentioned in either citation.
 * Both are dynamic-data surfaces (would need a real ink-core data source, not
 * fakeable with hardcoded numbers/quotes). Story 15.2's AC #3 documents these as
 * DEFERRED pending a future ink-core block — NOT "out of scope forever": the AC
 * explicitly names them as real Lovable-design surfaces that need live data, and
 * docs/ui-copy-translations.md already carries fully ratified Afrikaans copy for
 * both (Statistieke + Kollig sections) waiting on that future build. Re-confirmed
 * 2026-09-05 (Theme-Fidelity third pass, page 12): not a stale decision (Lovable's
 * content hasn't changed since the citation), but the "out of scope" framing this
 * docblock used to carry overstated Story 15.2's actual "deferred" language — a
 * genuine open feature-vs-style scope item, same class as my-profiel's/ontdek's
 * flagged deferrals, not a closed one. All copy is human-authored Afrikaans from
 * docs/ui-copy-translations.md (Gemeenskap-bladsy) — never AI-translated.
 *
 * Theme-Fidelity re-audit (page 12, 2026-09) rebuilt this pattern's markup against
 * real getComputedStyle() measurement of both DOMs (not the earlier screenshot-only
 * pass) and found the previous version diverged from Lovable on nearly every
 * section: hero content was left-aligned instead of centered with a ~768px reading
 * column, the H1/H2s used the sitewide fluid/flat presets instead of Lovable's
 * actual fixed Tailwind-breakpoint steps, the eyebrow labels were muted-grey/14px
 * instead of primary-coloured/12px/wider-tracked, the "Vir skrywers"/"Vir lesers"
 * benefit lists were a flat 4-card grid instead of Lovable's single bordered card
 * per audience (icon + heading + intro + an icon-led vertical list), "Hoe INK
 * werk" was a plain bullet list instead of numbered circular step badges, the
 * principles cards used the generic boxed `is-style-card` instead of Lovable's
 * left-border accent treatment, and — the largest single defect — the closing CTA
 * band had backgroundColor/textColor set backwards (light `secondary` bg + dark
 * `ink-text` copy) where Lovable's is a genuinely DARK inverted section (near-black
 * bg, light copy); it read as the wrong section entirely. All fixed below; the
 * section-specific LAYOUT lives in theme.json's global `styles.css` under the
 * `.ink-gemeenskap-*` namespace; every primitive (bands, eyebrows, headings,
 * buttons, the panel cards, icon tiles, step badges, accent blocks) is a class from
 * `assets/css/primitives.css` (Fidelity remediation Stage 3).
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-hero ink-band ink-band--intro ink-band--landing","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-hero ink-band ink-band--intro ink-band--landing" data-audit-id="gemeenskap-hero" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained","contentSize":"48rem"}} -->
	<div class="wp-block-group" data-audit-id="gemeenskap-hero-inner">
		<!-- wp:paragraph {"align":"center","className":"ink-eyebrow ink-eyebrow--tracked","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|s-24"}}}} -->
		<p class="has-text-align-center ink-eyebrow ink-eyebrow--tracked" data-audit-id="gemeenskap-hero-eyebrow" style="margin-bottom:var(--wp--preset--spacing--s-24)">Die INK-gemeenskap</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"textAlign":"center","className":"ink-heading-h1-hero-landing","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|s-24"}}}} -->
		<h1 class="wp-block-heading has-text-align-center ink-heading-h1-hero-landing" data-audit-id="gemeenskap-hero-h1" style="margin-bottom:var(--wp--preset--spacing--s-24)">&#8217;n Gemeenskap vir skrywers wat gelees wil word, en lesers wat ontroer wil word.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|s-40"}}},"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size" data-audit-id="gemeenskap-hero-intro" style="margin-bottom:var(--wp--preset--spacing--s-40)">INK is &#8217;n niewinsgerigte literêre tuiste gebou rondom &#8217;n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei &#8217;n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons" data-audit-id="gemeenskap-hero-actions">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-lg"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-lg"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-hero-btn-primary" href="/registreer"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) . esc_html__( 'Sluit aan as skrywer', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-ink-neutral ink-btn-lg"} -->
			<div class="wp-block-button is-style-ink-neutral ink-btn-lg"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-hero-btn-secondary" href="/registreer"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ) . esc_html__( 'Sluit aan as leser', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-value ink-band ink-band--lg","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained","contentSize":"72rem"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-value ink-band ink-band--lg" data-audit-id="gemeenskap-value" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-32","top":"var:preset|spacing|s-32"}}}} -->
	<div class="wp-block-columns" data-audit-id="gemeenskap-value-grid">
		<!-- wp:column {"lock":{"move":true,"remove":true}} -->
		<div class="wp-block-column" data-audit-id="gemeenskap-value-col">
			<!-- wp:group {"className":"ink-gemeenskap-card ink-card--panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group ink-gemeenskap-card ink-card--panel" data-audit-id="gemeenskap-card-writers">
				<!-- wp:html -->
				<div class="ink-gemeenskap-card__head" data-audit-id="gemeenskap-card-writers-head"><span class="ink-gemeenskap-card__icon ink-icon-tile ink-icon-tile--circle" data-audit-id="gemeenskap-card-writers-icon"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG. */ ?></span><h2 class="ink-heading-h2-block" data-audit-id="gemeenskap-card-writers-h2">Vir skrywers</h2></div>
				<!-- /wp:html -->

				<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
				<p class="has-muted-text-color has-text-color has-md-font-size" data-audit-id="gemeenskap-card-writers-intro">Plaas werk wat werklik gelees word — en ontvang die soort terugvoer wat jou laat groei.</p>
				<!-- /wp:paragraph -->

				<!-- wp:html -->
				<ul data-audit-id="gemeenskap-card-writers-list">
					<li data-audit-id="gemeenskap-card-writers-item"><?php echo ink_foundation_icon( '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item" data-audit-id="gemeenskap-card-writers-item-title">Gestruktureerde terugvoer</h3><p data-audit-id="gemeenskap-card-writers-item-text">Lesers reageer met lof, insig en voorstelle — nie net &#8217;n duimpie nie. Terugvoer wat jy werklik kan gebruik.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Regte lesers, nie net ander skrywers nie</h3><p>INK is eerste en voorste vir lesers gebou, sodat jou werk mense bereik wat gekom het om te lees — nie om gelees te word nie.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Maandelikse uitdagings</h3><p>Uitdagings wat jou skryfvermoëns toets, met erkenning vir uitstaande inskrywings en &#8217;n gewaarborgde gehoor.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.937A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">&#8217;n Profiel wat saam met jou groei</h3><p>Speld jou beste werk vas, vertoon jou prestasies, en laat lesers jou volgende hoofstuk volg.</p></div></li>
				</ul>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"lock":{"move":true,"remove":true}} -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"ink-gemeenskap-card ink-card--panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group ink-gemeenskap-card ink-card--panel" data-audit-id="gemeenskap-card-readers">
				<!-- wp:html -->
				<div class="ink-gemeenskap-card__head"><span class="ink-gemeenskap-card__icon ink-icon-tile ink-icon-tile--circle"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?></span><h2 class="ink-heading-h2-block">Vir lesers</h2></div>
				<!-- /wp:html -->

				<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
				<p class="has-muted-text-color has-text-color has-md-font-size">Ontdek skrywers die volg werd is, en word die soort leser wat skrywers onthou.</p>
				<!-- /wp:paragraph -->

				<!-- wp:html -->
				<ul>
					<li><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Ontdek nuwe stemme</h3><p>Saamgestelde stories en gedigte van opkomende skrywers — kort genoeg vir &#8217;n koffiepouse, diep genoeg om by jou te bly.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Reageer met bedoeling</h3><p>Merk &#8217;n sin uit. Los &#8217;n gestruktureerde nota. Sê vir &#8217;n skrywer wat geraak het, in plaas van om verby te blaai.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Bou jou leeslys</h3><p>Stoor werke om weer te besoek, volg skrywers wat jy liefhet, en laat jou gestoorde werk subtiel wys wat die lees werd is.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3 class="ink-heading-h5-list-item">Ondersteun &#8217;n nonprofit</h3><p>INK is &#8217;n gemeenskap, nie &#8217;n markplek nie. Jou tyd hier ondersteun direk onafhanklike literêre werk.</p></div></li>
				</ul>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-hiw ink-band ink-band--strip ink-band--lg","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained","contentSize":"64rem"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-hiw ink-band ink-band--strip ink-band--lg" data-audit-id="gemeenskap-hiw" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-48"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" data-audit-id="gemeenskap-hiw-inner">
		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" data-audit-id="gemeenskap-hiw-head">
			<!-- wp:heading {"textAlign":"center","level":2,"className":"ink-heading-h2-section"} -->
			<h2 class="wp-block-heading has-text-align-center ink-heading-h2-section" data-audit-id="gemeenskap-hiw-h2">Hoe INK werk</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","fontSize":"lg","textColor":"muted-text"} -->
			<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size" data-audit-id="gemeenskap-hiw-intro">&#8217;n Eenvoudige siklus vir beide kante van die bladsy.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-40","top":"var:preset|spacing|s-40"}}}} -->
		<div class="wp-block-columns" data-audit-id="gemeenskap-hiw-grid">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column" data-audit-id="gemeenskap-hiw-col">
				<!-- wp:html -->
				<h3 class="ink-heading-h3" data-audit-id="gemeenskap-hiw-col-h3"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?>Vir lesers</h3>
				<ol class="ink-gemeenskap-steps" data-audit-id="gemeenskap-hiw-steps">
					<li data-audit-id="gemeenskap-hiw-step-li"><div class="ink-step-badge" aria-hidden="true" data-audit-id="gemeenskap-hiw-step-badge">1</div><div><strong data-audit-id="gemeenskap-hiw-step-title">Lees</strong><span data-audit-id="gemeenskap-hiw-step-text">Blaai deur saamgestelde stories en gedigte, of volg skrywers wie se stemme jy vertrou.</span></div></li>
					<li><div class="ink-step-badge" aria-hidden="true">2</div><div><strong>Reageer</strong><span>Merk &#8217;n reël. Los &#8217;n gestruktureerde kritiek. Stoor dit na jou leeslys.</span></div></li>
					<li><div class="ink-step-badge" aria-hidden="true">3</div><div><strong>Verbind</strong><span>Ontdek meer skrywers deur wat ander deurdagte lesers stoor.</span></div></li>
				</ol>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:html -->
				<h3 class="ink-heading-h3"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?>Vir skrywers</h3>
				<ol class="ink-gemeenskap-steps">
					<li><div class="ink-step-badge" aria-hidden="true">1</div><div><strong>Skryf</strong><span>Publiseer &#8217;n stuk op sy eie of as &#8217;n inskrywing vir &#8217;n maandelikse uitdaging.</span></div></li>
					<li><div class="ink-step-badge" aria-hidden="true">2</div><div><strong>Ontvang gestruktureerde terugvoer</strong><span>Lof, insig en voorstelle — van lesers wat gekom het om te lees.</span></div></li>
					<li><div class="ink-step-badge" aria-hidden="true">3</div><div><strong>Bou jou gehoor</strong><span>Bou &#8217;n profiel wat lesers volg, en kyk hoe jou leserskring groei.</span></div></li>
				</ol>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-principles ink-band ink-band--lg","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained","contentSize":"64rem"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-principles ink-band ink-band--lg" data-audit-id="gemeenskap-principles" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-48"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group" data-audit-id="gemeenskap-principles-inner">
		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"default"}} -->
		<div class="wp-block-group" data-audit-id="gemeenskap-principles-head">
			<!-- wp:paragraph {"align":"center","className":"ink-eyebrow ink-eyebrow--tracked","style":{"spacing":{"margin":{"bottom":"0"}}}} -->
			<p class="has-text-align-center ink-eyebrow ink-eyebrow--tracked" data-audit-id="gemeenskap-principles-eyebrow" style="margin-bottom:0">Hoe ons mekaar behandel</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"textAlign":"center","level":2,"className":"ink-heading-h2-section"} -->
			<h2 class="wp-block-heading has-text-align-center ink-heading-h2-section" data-audit-id="gemeenskap-principles-h2">Gemeenskapsbeginsels</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"className":"ink-gemeenskap-principles__grid","lock":{"move":true,"remove":true},"layout":{"type":"default"}} -->
		<div class="wp-block-group ink-gemeenskap-principles__grid" data-audit-id="gemeenskap-principles-grid">
		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle ink-accent-block","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle ink-accent-block" data-audit-id="gemeenskap-principle-card">
					<!-- wp:heading {"level":3,"className":"ink-heading-h3"} -->
					<h3 class="wp-block-heading ink-heading-h3" data-audit-id="gemeenskap-principle-title">Gee terugvoer met sorg</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color" data-audit-id="gemeenskap-principle-text">Terugvoer is &#8217;n gawe. Ons prys spesifiek, stel saggies voor, en trap nooit op mense nie.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle ink-accent-block","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle ink-accent-block">
					<!-- wp:heading {"level":3,"className":"ink-heading-h3"} -->
					<h3 class="wp-block-heading ink-heading-h3">Lees grootmoedig</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color">Elke stuk hier het moed gekos om te publiseer. Begin met wat werk.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle ink-accent-block","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle ink-accent-block">
					<!-- wp:heading {"level":3,"className":"ink-heading-h3"} -->
					<h3 class="wp-block-heading ink-heading-h3">Skrywers en lesers, gelyke vennote</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color">Geen groep bestaan sonder die ander nie. Albei is die gemeenskap.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle ink-accent-block","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle ink-accent-block">
					<!-- wp:heading {"level":3,"className":"ink-heading-h3"} -->
					<h3 class="wp-block-heading ink-heading-h3">Stil bo luidrugtig</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color">Weerklank wen van bereik. &#8217;n Deurdagte leser tel meer as &#8217;n virale oomblik.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
		</div>
		<!-- /wp:group -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-cta ink-band ink-band--dark ink-band--lg","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-cta ink-band ink-band--dark ink-band--lg" data-audit-id="gemeenskap-cta" style="padding-right:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)">
	<!-- Lovable narrows only the paragraph (`max-w-xl`); the h2 and the button row span the container (spine S-15). -->
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"0"}},"layout":{"type":"default"}} -->
	<div class="wp-block-group alignwide" data-audit-id="gemeenskap-cta-inner">
		<!-- wp:heading {"textAlign":"center","level":2,"className":"ink-heading-h2-on-dark","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|s-16"}}}} -->
		<h2 class="wp-block-heading has-text-align-center ink-heading-h2-on-dark" data-audit-id="gemeenskap-cta-h2" style="margin-bottom:var(--wp--preset--spacing--s-16)">Gereed om by INK aan te sluit?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"spacing":{"margin":{"bottom":"var:preset|spacing|s-40"}}},"fontSize":"lg"} -->
		<p class="has-text-align-center has-lg-font-size" data-audit-id="gemeenskap-cta-text" style="margin-bottom:var(--wp--preset--spacing--s-40)">Dit is gratis, dit is niewinsgerig, en dit word stilletjies die beste plek aanlyn om te lees en gelees te word.</p>
		<!-- /wp:paragraph -->

		<!-- The CTA pair on the dark band: core/button blocks with the primitives `button-primary` (`is-style-ink-primary`) and `button-outline on-dark` (`is-style-ink-outline-on-dark`) block styles, lg size wrapper. -->
		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"}} -->
		<div class="wp-block-buttons" data-audit-id="gemeenskap-cta-actions">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-lg"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-lg"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-cta-btn-primary" href="/registreer"><?php echo esc_html__( 'Skep jou rekening', 'ink-foundation' ) . ink_foundation_icon( '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the label is esc_html__(); ink_foundation_icon() returns trusted, self-escaped inline SVG. ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-ink-outline-on-dark ink-btn-lg"} -->
			<div class="wp-block-button is-style-ink-outline-on-dark ink-btn-lg"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-cta-btn-secondary" href="/ontdek/"><?php esc_html_e( 'Kyk eers rond', 'ink-foundation' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
