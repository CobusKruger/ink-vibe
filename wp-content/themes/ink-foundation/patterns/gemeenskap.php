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
 * section-specific CSS lives in theme.json's global `styles.css` under the
 * `.ink-gemeenskap-*` namespace (this page has no dedicated stylesheet — same
 * "reuse the global css string" choice already made for `ontdek`, not enough
 * bespoke CSS to justify a new enqueued file).
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-hero","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-hero" data-audit-id="gemeenskap-hero" style="padding-right:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained","contentSize":"48rem"}} -->
	<div class="wp-block-group">
		<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500","textTransform":"uppercase","letterSpacing":"0.2em"},"spacing":{"margin":{"bottom":"var:preset|spacing|s-24"}}},"fontSize":"xs","textColor":"primary"} -->
		<p class="has-text-align-center has-primary-color has-text-color has-xs-font-size" data-audit-id="gemeenskap-hero-eyebrow" style="margin-bottom:var(--wp--preset--spacing--s-24);font-style:normal;font-weight:500;letter-spacing:0.2em;text-transform:uppercase">Die INK-gemeenskap</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"textAlign":"center","fontSize":"xxxxl","style":{"typography":{"fontStyle":"normal","fontWeight":"600","lineHeight":"1"},"spacing":{"margin":{"bottom":"var:preset|spacing|s-24"}}}} -->
		<h1 class="wp-block-heading has-text-align-center has-xxxxl-font-size" data-audit-id="gemeenskap-hero-h1" style="margin-bottom:var(--wp--preset--spacing--s-24);font-style:normal;font-weight:600;line-height:1">'n Gemeenskap vir skrywers wat gelees wil word, en lesers wat ontroer wil word.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","style":{"typography":{"lineHeight":"1.5"},"spacing":{"margin":{"bottom":"var:preset|spacing|s-40"}}},"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size" data-audit-id="gemeenskap-hero-intro" style="margin-bottom:var(--wp--preset--spacing--s-40);line-height:1.5">INK is 'n niewinsgerigte literêre tuiste gebou rondom 'n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei 'n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-icon"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-icon"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-hero-btn-primary" href="/registreer"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ) . esc_html__( 'Sluit aan as skrywer', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"ink-gemeenskap-btn-neutral ink-btn-icon"} -->
			<div class="wp-block-button ink-gemeenskap-btn-neutral ink-btn-icon"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-hero-btn-secondary" href="/registreer"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ) . esc_html__( 'Sluit aan as leser', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__(). ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-value","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-value has-surface-alt-background-color has-background" style="padding-right:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-32","top":"var:preset|spacing|s-32"}}}} -->
	<div class="wp-block-columns alignwide">
		<!-- wp:column {"lock":{"move":true,"remove":true}} -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card ink-gemeenskap-card","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card ink-gemeenskap-card" data-audit-id="gemeenskap-card-writers">
				<!-- wp:html -->
				<div class="ink-gemeenskap-card__head"><span class="ink-gemeenskap-card__icon" data-audit-id="gemeenskap-card-writers-icon"><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG. */ ?></span><h2 class="has-xxl-font-size" data-audit-id="gemeenskap-card-writers-h2">Vir skrywers</h2></div>
				<!-- /wp:html -->

				<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
				<p class="has-muted-text-color has-text-color has-md-font-size">Plaas werk wat werklik gelees word — en ontvang die soort terugvoer wat jou laat groei.</p>
				<!-- /wp:paragraph -->

				<!-- wp:html -->
				<ul>
					<li><?php echo ink_foundation_icon( '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Gestruktureerde terugvoer</h3><p>Lesers reageer met lof, insig en voorstelle — nie net 'n duimpie nie. Terugvoer wat jy werklik kan gebruik.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Regte lesers, nie net ander skrywers nie</h3><p>INK is eerste en voorste vir lesers gebou, sodat jou werk mense bereik wat gekom het om te lees — nie om gelees te word nie.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/><path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/><path d="M4 22h16"/><path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/><path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/><path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Maandelikse uitdagings</h3><p>Uitdagings wat jou skryfvermoëns toets, met erkenning vir uitstaande inskrywings en 'n gewaarborgde gehoor.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.937A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>'n Profiel wat saam met jou groei</h3><p>Speld jou beste werk vas, vertoon jou prestasies, en laat lesers jou volgende hoofstuk volg.</p></div></li>
				</ul>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->

		<!-- wp:column {"lock":{"move":true,"remove":true}} -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card ink-gemeenskap-card","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card ink-gemeenskap-card">
				<!-- wp:html -->
				<div class="ink-gemeenskap-card__head"><span class="ink-gemeenskap-card__icon"><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?></span><h2 class="has-xxl-font-size">Vir lesers</h2></div>
				<!-- /wp:html -->

				<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
				<p class="has-muted-text-color has-text-color has-md-font-size">Ontdek skrywers die volg werd is, en word die soort leser wat skrywers onthou.</p>
				<!-- /wp:paragraph -->

				<!-- wp:html -->
				<ul>
					<li><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Ontdek nuwe stemme</h3><p>Saamgestelde stories en gedigte van opkomende skrywers — kort genoeg vir 'n koffiepouse, diep genoeg om by jou te bly.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Reageer met bedoeling</h3><p>Merk 'n sin uit. Los 'n gestruktureerde nota. Sê vir 'n skrywer wat geraak het, in plaas van om verby te blaai.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Bou jou leeslys</h3><p>Stoor werke om weer te besoek, volg skrywers wat jy liefhet, en laat jou gestoorde werk subtiel wys wat die lees werd is.</p></div></li>
					<li><?php echo ink_foundation_icon( '<path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?><div><h3>Ondersteun 'n nonprofit</h3><p>INK is 'n gemeenskap, nie 'n markplek nie. Jou tyd hier ondersteun direk onafhanklike literêre werk.</p></div></li>
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

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-hiw","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-hiw" data-audit-id="gemeenskap-hiw" style="padding-right:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-48"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"xxxl"} -->
			<h2 class="wp-block-heading has-text-align-center has-xxxl-font-size" data-audit-id="gemeenskap-hiw-h2">Hoe INK werk</h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"align":"center","fontSize":"lg","textColor":"muted-text"} -->
			<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size">'n Eenvoudige siklus vir beide kante van die bladsy.</p>
			<!-- /wp:paragraph -->
		</div>
		<!-- /wp:group -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-40","top":"var:preset|spacing|s-32"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:html -->
				<h3><?php echo ink_foundation_icon( '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?>Vir lesers</h3>
				<ol class="ink-gemeenskap-steps">
					<li data-audit-id="gemeenskap-hiw-step-li"><strong>Lees</strong><span>Blaai deur saamgestelde stories en gedigte, of volg skrywers wie se stemme jy vertrou.</span></li>
					<li><strong>Reageer</strong><span>Merk 'n reël. Los 'n gestruktureerde kritiek. Stoor dit na jou leeslys.</span></li>
					<li><strong>Verbind</strong><span>Ontdek meer skrywers deur wat ander deurdagte lesers stoor.</span></li>
				</ol>
				<!-- /wp:html -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:html -->
				<h3><?php echo ink_foundation_icon( '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>' ); /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. */ ?>Vir skrywers</h3>
				<ol class="ink-gemeenskap-steps">
					<li><strong>Skryf</strong><span>Publiseer 'n stuk op sy eie of as 'n inskrywing vir 'n maandelikse uitdaging.</span></li>
					<li><strong>Ontvang gestruktureerde terugvoer</strong><span>Lof, insig en voorstelle — van lesers wat gekom het om te lees.</span></li>
					<li><strong>Bou jou gehoor</strong><span>Bou 'n profiel wat lesers volg, en kyk hoe jou leserskring groei.</span></li>
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

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-principles","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-principles has-surface-alt-background-color has-background" data-audit-id="gemeenskap-principles" style="padding-right:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-48"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:paragraph {"align":"center","style":{"typography":{"fontStyle":"normal","fontWeight":"500","textTransform":"uppercase","letterSpacing":"0.2em"},"spacing":{"margin":{"bottom":"0"}}},"fontSize":"xs","textColor":"primary"} -->
			<p class="has-text-align-center has-primary-color has-text-color has-xs-font-size" style="margin-bottom:0;font-style:normal;font-weight:500;letter-spacing:0.2em;text-transform:uppercase">Hoe ons mekaar behandel</p>
			<!-- /wp:paragraph -->

			<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"xxl"} -->
			<h2 class="wp-block-heading has-text-align-center has-xxl-font-size" data-audit-id="gemeenskap-principles-h2">Gemeenskapsbeginsels</h2>
			<!-- /wp:heading -->
		</div>
		<!-- /wp:group -->

		<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle" data-audit-id="gemeenskap-principle-card">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size">Gee terugvoer met sorg</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color">Terugvoer is 'n gawe. Ons prys spesifiek, stel saggies voor, en trap nooit op mense nie.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"ink-gemeenskap-principle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size">Lees grootmoedig</h3>
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
				<!-- wp:group {"className":"ink-gemeenskap-principle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size">Skrywers en lesers, gelyke vennote</h3>
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
				<!-- wp:group {"className":"ink-gemeenskap-principle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-gemeenskap-principle">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size">Stil bo luidrugtig</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color">Weerklank wen van bereik. 'n Deurdagte leser tel meer as 'n virale oomblik.</p>
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

<!-- wp:group {"tagName":"section","align":"full","className":"ink-gemeenskap-cta","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"ink-text","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-gemeenskap-cta has-ink-text-background-color has-background" data-audit-id="gemeenskap-cta" style="padding-right:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained","contentSize":"36rem"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"xxxl","textColor":"surface-alt"} -->
		<h2 class="wp-block-heading has-text-align-center has-surface-alt-color has-text-color has-xxxl-font-size" data-audit-id="gemeenskap-cta-h2">Gereed om by INK aan te sluit?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"lg"} -->
		<p class="has-text-align-center has-lg-font-size">Dit is gratis, dit is niewinsgerig, en dit word stilletjies die beste plek aanlyn om te lees en gelees te word.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-primary ink-btn-icon"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-icon"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-cta-btn-primary" href="/registreer"><?php echo esc_html__( 'Skep jou rekening', 'ink-foundation' ) . ink_foundation_icon( '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- the label is esc_html__(); ink_foundation_icon() returns trusted, self-escaped inline SVG. ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"ink-gemeenskap-btn-neutral-dark"} -->
			<div class="wp-block-button ink-gemeenskap-btn-neutral-dark"><a class="wp-block-button__link wp-element-button" data-audit-id="gemeenskap-cta-btn-secondary" href="/lees"><?php esc_html_e( 'Kyk eers rond', 'ink-foundation' ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
