<?php
/**
 * Title: Gemeenskap-bladsy
 * Slug: ink-foundation/gemeenskap
 * Categories: ink-foundation, page
 * Description: Die Gemeenskap-bekeringsbladsy (Storie 15.2, FR-60) — held, waardekolomme (Vir skrywers / Vir lesers), Hoe INK werk, Gemeenskapsbeginsels en sluitende oproep tot aksie.
 *
 * Presentation only (three-layer separation): a static marketing/conversion page.
 * No business logic, no post queries, no server-rendered ink-core block. The Lovable
 * design's live statistics counters and the "Kollig" featured writer/reader
 * spotlight are dynamic-data surfaces and are deliberately deferred (a future
 * ink-core block), not faked with hardcoded numbers. All copy is human-authored
 * Afrikaans from docs/ui-copy-translations.md (Gemeenskap-bladsy) — never
 * AI-translated; raw block-content the way the sibling hero / cta-band patterns are.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Die INK-gemeenskap</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":1,"fontSize":"3xl"} -->
		<h1 class="wp-block-heading has-3xl-font-size">'n Gemeenskap vir skrywers wat gelees wil word, en lesers wat ontroer wil word.</h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg"} -->
		<p class="has-lg-font-size">INK is 'n niewinsgerigte literêre tuiste gebou rondom 'n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei 'n beter plek verdien om mekaar te vind.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/registreer">Sluit aan as skrywer</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"primary"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-primary-color has-text-color wp-element-button" href="/registreer">Sluit aan as leser</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Vir skrywers</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">Plaas werk wat werklik gelees word — en ontvang die soort terugvoer wat jou laat groei.</p>
		<!-- /wp:paragraph -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Gestruktureerde terugvoer</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">INK is eerste en voorste vir lesers gebou, sodat jou werk mense bereik wat gekom het om te lees — nie om gelees te word nie.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Maandelikse uitdagings</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Uitdagings wat jou skryfvermoëns toets, met erkenning vir uitstaande inskrywings en 'n gewaarwaarborgde gehoor.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">'n Profiel wat saam met jou groei</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Speld jou beste werk vas, vertoon jou prestasies, en laat lesers jou volgende hoofstuk volg.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->

		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Vir lesers</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">Ontdek skrywers die volg werd is, en word die soort leser wat skrywers onthou.</p>
		<!-- /wp:paragraph -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Ontdek nuwe stemme</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Saamgestelde stories en gedigte van opkomende skrywers — kort genoeg vir 'n koffiepouse, diep genoeg om by jou te bly.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Reageer met bedoeling</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Merk 'n sin uit. Los 'n gestruktureerde nota. Sê vir 'n skrywer wat geraak het, in plaas van om verby te blaai.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Bou jou leeslys</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Stoor werke om weer te besoek, volg skrywers wat jy liefhet, en laat jou gestoorde werk subtiel wys wat die lees werd is.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Ondersteun 'n nonprofit</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">INK is 'n gemeenskap, nie 'n markplek nie. Jou tyd hier ondersteun direk onafhanklike literêre werk.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Hoe INK werk</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-lg-font-size">'n Eenvoudige siklus vir beide kante van die bladsy.</p>
		<!-- /wp:paragraph -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-32"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:heading {"level":3,"fontSize":"lg"} -->
				<h3 class="wp-block-heading has-lg-font-size">Vir lesers</h3>
				<!-- /wp:heading -->

				<!-- wp:list -->
				<ul>
					<li><strong>Lees</strong> — Blaai deur saamgestelde stories en gedigte, of volg skrywers wie se stemme jy vertrou.</li>
					<li><strong>Reageer</strong> — Merk 'n reël. Los 'n gestruktureerde kritiek. Stoor dit na jou leeslys.</li>
					<li><strong>Verbind</strong> — Ontdek meer skrywers deur wat ander deurdagte lesers stoor.</li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:heading {"level":3,"fontSize":"lg"} -->
				<h3 class="wp-block-heading has-lg-font-size">Vir skrywers</h3>
				<!-- /wp:heading -->

				<!-- wp:list -->
				<ul>
					<li><strong>Skryf</strong> — Publiseer 'n stuk op sy eie of as 'n inskrywing vir 'n maandelikse uitdaging.</li>
					<li><strong>Ontvang gestruktureerde terugvoer</strong> — Lof, insig en voorstelle — van lesers wat gekom het om te lees.</li>
					<li><strong>Bou jou gehoor</strong> — Bou 'n profiel wat lesers volg, en kyk hoe jou leserskring groei.</li>
				</ul>
				<!-- /wp:list -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"surface-alt","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-surface-alt-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:paragraph {"style":{"typography":{"fontStyle":"normal","fontWeight":"600","textTransform":"uppercase","letterSpacing":"0.08em"}},"fontSize":"sm","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-sm-font-size" style="font-style:normal;font-weight:600;letter-spacing:0.08em;text-transform:uppercase">Hoe ons mekaar behandel</p>
		<!-- /wp:paragraph -->

		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size">Gemeenskapsbeginsels</h2>
		<!-- /wp:heading -->

		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Gee terugvoer met sorg</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Terugvoer is 'n gawe. Ons prys spesifiek, stel saggies voor, en trep nooit op mense nie.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Lees grootmoedig</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Elke stuk hier het moed gekos om te publiseer. Begin met wat werk.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Skrywers en lesers, gelyke vennote</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Geen groep bestaan sonder die ander nie. Albei is die gemeenskap.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->

			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"lg"} -->
					<h3 class="wp-block-heading has-lg-font-size">Stil bo luidrugtig</h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
					<p class="has-muted-text-color has-text-color has-md-font-size">Weerklank wen van bereik. 'n Deurdagte leser tel meer as 'n virale oomblik.</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:column -->
		</div>
		<!-- /wp:columns -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-80","bottom":"var:preset|spacing|s-80","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"secondary","textColor":"text","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-text-color has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-80);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-80);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"3xl"} -->
		<h2 class="wp-block-heading has-text-align-center has-3xl-font-size">Gereed om by INK aan te sluit?</h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"lg","textColor":"muted-text"} -->
		<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size">Dit is gratis, dit is niewinsgericht, en dit word stilletjies die beste plek aanlyn om te lees en gelees te word.</p>
		<!-- /wp:paragraph -->

		<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div class="wp-block-buttons">
			<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt"} -->
			<div class="wp-block-button"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="/registreer">Skep jou rekening</a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-outline","textColor":"primary"} -->
			<div class="wp-block-button is-style-outline"><a class="wp-block-button__link has-primary-color has-text-color wp-element-button" href="/lees">Kyk eers rond</a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
