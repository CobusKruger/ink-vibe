<?php
/**
 * Title: My Profiel
 * Slug: ink-foundation/my-profiel
 * Categories: ink-foundation
 * Description: Die private My Profiel-bladsy (Storie 9.4, FR-40): die aangemelde lid se eie kontroleskerm — 'n identiteitsstrook (avatar/naam/leuse/Gradering + Wysig-profiel/Nuwe-bydrae/Sien-openbare-bladsy-aksies) bo 'n 7-oortjie-raamwerk (Oorsig/Bydraes/Leeslys/Wie-ek-volg/Aktiwiteit/Kennisgewings/Lidmaatskap, docs/my-profiel-rebuild-strategy.md §5.1–5.2).
 *
 * PRIVATE surface — the member's OWN dashboard. Unlike the public Skrywerprofiel
 * (the `ink/skrywerprofiel` block on the author template), this is current-user
 * content, so the per-user bridges resolve correctly in pattern PHP (auth is
 * established before `init`) — the same mechanism `lidmaatskap-hernu` relies on.
 *
 * IDENTITY STRIP + TAB SHELL + ALL 7 TABS (My Profiel rebuild, §5.1–§5.9):
 * builds the identity strip, the 7-tab shell skeleton, the Oorsig tab ("Oor
 * my"/"In 'n oogopslag"/"Onlangse aktiwiteit"), the Bydraes tab's unified
 * per-post render (`Ink\Social\BydraesSurface`, §5.4/§3 — replaces the two
 * previously separate `ink/leesgetalle` + `ink/vasgespel-bestuur` blocks with
 * one query/render carrying title+type+date+read-count+pin+edit/view per row),
 * the Wie-ek-volg tab (`ink/volg-lys`, §5.6), the Kennisgewings tab
 * (`Ink\Notifications\KennisgewingsSurface`, §5.8), and the Lidmaatskap tab's
 * left-hand status card (`Ink\Entitlement\Api::memberSinceFor()`/
 * `renewalDateFor()`, §5.9). Leeslys/Aktiwiteit/Lidmaatskap's existing
 * blocks/patterns (leeslys, volg-voer, lidmaatskap-hernu) stay moved-as-is,
 * unchanged — lidmaatskap-hernu's own plan-card render is untouched, only
 * wrapped in a columns split alongside the new status card.
 *
 * Private-only data lives HERE and nowhere public (FR-40): the "wins needed"
 * subtext (`ink_foundation_gradering_wins_needed`, Story 5.9), the read-count
 * surface (Story 9.12, now surfaced per-post on Bydraes via `BydraesSurface`),
 * the bio/stat cards, the full Kennisgewings list + "Ongelees" count + "Onlangse
 * aktiwiteit" preview render, and the Lidmaatskap status card's dates, only
 * here, and never on the public Skrywerprofiel. The Gradering badge +
 * wins-needed subtext moved from their own standalone section into the
 * identity strip, and Leesgetalle moved from its own Oorsig section to a
 * per-post Bydraes stat (product-owner decisions 2026-09-06, strategy doc §7
 * items 4/5).
 *
 * Three-layer: presentation only. The Gradering badge + wins-needed subtext,
 * the tagline (`Ink\Social\Tagline`), the Oorsig stat reads (`Social\Api`,
 * `Notifications\KennisgewingsSurface`, `Entitlement\Api`), the Bydraes-tab
 * query (`Social\BydraesSurface`), the Kennisgewings-tab render
 * (`Notifications\KennisgewingsSurface::toHtml()`), the Lidmaatskap status-card
 * dates (`Entitlement\Api::memberSinceFor()`/`renewalDateFor()`), and the
 * avatar/name/public-URL reads are `class_exists`/`function_exists`-guarded
 * `ink-core` reads (display, never a gate). The edit-profile modal
 * (`ink/profiel-redigeer`), following-list (`ink/volg-lys`), following-feed,
 * leeslys, and lidmaatskap renewal are existing blocks/patterns, embedded here.
 * Copy is authored Afrikaans (ui-copy-translations.md "My Profiel-bladsy") via
 * the `ink-foundation` text domain; term labels via the registry. Unauthored
 * copy (the Oorsig "no bio yet" fallback, the Bydraes empty state) carries a
 * `[NEEDS HUMAN AFRIKAANS]` placeholder per the standard
 * [[afrikaans-copy-debt-process]] rather than invented Afrikaans — see
 * `docs/afrikaans-translation-sheet.md` (OORSIG-BIO-LEEG / BYDRAES-LEEG).
 * Sentence case; structural wrappers locked (move/remove) per Storie 1.6.
 */

$ink_user_id      = get_current_user_id();
$ink_wins_needed  = function_exists( 'ink_foundation_gradering_wins_needed' )
	? ink_foundation_gradering_wins_needed()
	: '';
$ink_badge        = function_exists( 'ink_foundation_gradering_badge' )
	? ink_foundation_gradering_badge()
	: '';
$ink_tagline      = class_exists( '\Ink\Social\Tagline' )
	? \Ink\Social\Tagline::get( $ink_user_id )
	: '';
$ink_avatar       = function_exists( 'get_avatar' ) ? (string) get_avatar( $ink_user_id, 112 ) : '';
$ink_display_name = (string) get_the_author_meta( 'display_name', $ink_user_id );
$ink_public_url   = (string) get_author_posts_url( $ink_user_id );

// Oorsig tab data (My Profiel rebuild §5.3). Product-owner decision 2026-09-06
// (§7 item 5): Gradering lives in the identity strip above, Leesgetalle moved
// to the Bydraes tab — Oorsig hosts neither directly any more.
$ink_bio        = trim( (string) get_the_author_meta( 'description', $ink_user_id ) );
$ink_bydraes    = class_exists( '\Ink\Social\BydraesSurface' ) ? \Ink\Social\BydraesSurface::rows( $ink_user_id ) : array();
$ink_volg_getal = class_exists( '\Ink\Social\Api' ) ? \Ink\Social\Api::followingCount( $ink_user_id ) : 0;
$ink_ongelees   = class_exists( '\Ink\Notifications\KennisgewingsSurface' ) ? \Ink\Notifications\KennisgewingsSurface::unreadCount( $ink_user_id ) : 0;
$ink_hernu_datum = class_exists( '\Ink\Entitlement\Api' ) ? \Ink\Entitlement\Api::renewalDateFor( $ink_user_id ) : null;
$ink_onlangs    = class_exists( '\Ink\Notifications\KennisgewingsSurface' ) ? \Ink\Notifications\KennisgewingsSurface::recent( $ink_user_id ) : array();

// Kennisgewings tab (§5.8): the full list + "Merk alles as gelees" button, rendered
// server-side by the same ink-core read-model the Oorsig "Ongelees" stat/"Onlangse
// aktiwiteit" card already read above — computed once as a guarded PHP value (this
// file's established `ink-core`-bridge convention), then echoed in the panel markup.
$ink_kennisgewings_html = class_exists( '\Ink\Notifications\KennisgewingsSurface' )
	? \Ink\Notifications\KennisgewingsSurface::toHtml( \Ink\Notifications\KennisgewingsSurface::rows( $ink_user_id ) )
	: '';

// Lidmaatskap tab (§5.9): the status-card dates. "Lid sedert" reuses a fresh read
// (memberSinceFor); "Hernieu" reuses the SAME renewal date already computed above
// for the Oorsig one-liner ($ink_hernu_datum) — one call, two consumers, per this
// file's own "compute once, share it" convention (see KennisgewingsSurface::rows()
// for the same principle on the ink-core side). Both getters share ONE underlying
// gate (Entitlement\MembershipDates::activeInkMembership() — literally-active
// WooCommerce status): the card renders only when at least one resolves, since
// that is only ever true for a genuinely active INK membership — never fabricated
// for a non-member, and no new membership-state source is invented here.
$ink_lid_sedert        = class_exists( '\Ink\Entitlement\Api' ) ? \Ink\Entitlement\Api::memberSinceFor( $ink_user_id ) : null;
$ink_lidmaatskap_aktief = ( null !== $ink_hernu_datum ) || ( null !== $ink_lid_sedert );
?>
<?php // Identity band (My Profiel rebuild, Tier-0 structural match to Profile.tsx's <section className="border-b border-border bg-cream/40">): a full-bleed tinted band housing ONLY the identity strip, distinct from the tab-shell section below (Lovable never puts the tabs inside this band). ?>
<!-- wp:group {"tagName":"section","className":"ink-profiel-identiteit-band","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-40","bottom":"var:preset|spacing|s-40","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull ink-profiel-identiteit-band" style="padding-top:var(--wp--preset--spacing--s-40);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-40);padding-left:var(--wp--preset--spacing--s-24)">
	<?php // WIDTH BUG FIX (product-owner live-inspection pass): this wrapper redundantly
	// re-declared "layout":{"type":"constrained"} — the SAME bug already found and
	// fixed on ontdek.php (Theme-Fidelity fourth-pass re-audit, page 11): re-declaring
	// "constrained" here makes WP treat this alignwide box as a FRESH constrained
	// layout root, clamping any child lacking its own alignwide/alignfull class (the
	// identity strip group below has none) down to contentSize (768px) instead of
	// letting it fill this wrapper's already-correct wideSize (1368px) — confirmed
	// live via getComputedStyle before this fix (.ink-profiel-identiteit measured
	// 768px inside a 1368px .alignwide ancestor). Dropping the redundant "layout"
	// leaves this a plain flow group, so the identity strip fills the full wide width. ?>
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true}} -->
	<div class="wp-block-group alignwide">

		<?php // Identity strip (My Profiel rebuild §5.1): avatar, "Jou profiel" eyebrow, serif H1 name, Gradering badge + wins-needed, tagline, three actions. ?>
		<!-- wp:group {"className":"ink-profiel-identiteit","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
		<div class="wp-block-group ink-profiel-identiteit">

			<!-- wp:group {"className":"ink-profiel-identiteit__persoon","style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"flex","verticalAlignment":"center","flexWrap":"nowrap"}} -->
			<div class="wp-block-group ink-profiel-identiteit__persoon">
<?php if ( '' !== $ink_avatar ) : ?>
				<!-- wp:html -->
				<div class="ink-profiel-identiteit__avatar"><?php echo $ink_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns a fully-escaped <img>. ?></div>
				<!-- /wp:html -->
<?php endif; ?>
				<!-- wp:group {"className":"ink-profiel-identiteit__inhoud","layout":{"type":"constrained"}} -->
				<div class="wp-block-group ink-profiel-identiteit__inhoud">
					<!-- wp:paragraph {"fontSize":"xs","className":"ink-profiel-identiteit__etiket"} -->
					<p class="has-xs-font-size ink-profiel-identiteit__etiket"><?php echo esc_html__( 'Jou profiel', 'ink-foundation' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":1,"fontSize":"xxl","className":"ink-profiel-identiteit__naam"} -->
					<h1 class="wp-block-heading has-xxl-font-size ink-profiel-identiteit__naam" data-ink-profiel-veld="naam"><?php echo esc_html( $ink_display_name ); ?></h1>
					<!-- /wp:heading -->
<?php if ( '' !== $ink_badge || '' !== $ink_wins_needed ) : ?>
					<!-- wp:group {"className":"ink-profiel-identiteit__gradering","layout":{"type":"flex","verticalAlignment":"center","flexWrap":"wrap"}} -->
					<div class="wp-block-group ink-profiel-identiteit__gradering">
	<?php if ( '' !== $ink_badge ) : ?>
						<!-- wp:html -->
						<?php echo $ink_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink-core bridge returns escaped, token-only badge markup. ?>
						<!-- /wp:html -->
<?php endif; ?>
	<?php if ( '' !== $ink_wins_needed ) : ?>
						<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text","className":"ink-my-profiel__wins-needed"} -->
						<p class="has-muted-text-color has-text-color has-sm-font-size ink-my-profiel__wins-needed"><?php echo esc_html( $ink_wins_needed ); ?></p>
						<!-- /wp:paragraph -->
<?php endif; ?>
					</div>
					<!-- /wp:group -->
<?php endif; ?>
					<?php // Two REAL bugs found live (Tier 2, profiel-edit.js's server-render-then-flip pattern): (1) this whole <p> was PHP-conditional on a non-empty tagline, so a member's FIRST-EVER tagline save had no element for the JS's [data-ink-profiel-veld="leuse"] selector to find — the identity strip silently never updated until reload; (2) even once the <p> existed, the JS's `el.textContent = data.tagline` blindly overwrote the PHP-baked literal quote-mark characters, since that selector sat on the SAME element as the quotes. Fixed by ALWAYS rendering the wrapper with the quote marks as PERMANENT sibling text, moving [data-ink-profiel-veld="leuse"] onto an INNER span (so the JS's existing textContent-only update never touches the quotes), and adding a separate wrapper attribute purely for the empty/non-empty visibility toggle — never bare empty quotes, but now a CSS concern, not a markup-presence one. ?>
					<!-- wp:paragraph {"fontSize":"md","className":"ink-profiel-identiteit__leuse<?php echo '' === $ink_tagline ? ' is-empty' : ''; ?>"} -->
					<p class="has-md-font-size ink-profiel-identiteit__leuse<?php echo '' === $ink_tagline ? ' is-empty' : ''; ?>" data-ink-profiel-leuse-wrap>&#8220;<span data-ink-profiel-veld="leuse"><?php echo esc_html( $ink_tagline ); ?></span>&#8221;</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"ink-profiel-identiteit__aksies","layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-group ink-profiel-identiteit__aksies">
				<?php // A real <button>, not a link — matches Lovable's onClick-only "Edit profile" control and the codebase's own convention for JS-triggered controls (vasgespel/volg toggles are also <button type="button">, never <a>). Styled directly via the BEM class in profiel.css (Tier-2 finding: a bare button never matches the `.wp-block-button.is-style-*` selector chain a real is-style-outline needs, so that className was a dead no-op — removed rather than left as a misleading label). ?>
				<!-- wp:html -->
				<button type="button" class="wp-element-button ink-profiel-identiteit__wysig" data-ink-profiel-redigeer-trigger><?php echo esc_html__( 'Wysig profiel', 'ink-foundation' ); ?></button>
				<!-- /wp:html -->

				<!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-ink-primary"} -->
					<div class="wp-block-button is-style-ink-primary"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/skryf/' ) ); ?>"><?php echo esc_html__( 'Nuwe bydrae', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->

					<!-- wp:button {"className":"is-style-subtle"} -->
					<div class="wp-block-button is-style-subtle"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $ink_public_url ); ?>"><?php echo esc_html__( 'Sien openbare bladsy', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->

		</div>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<?php // The real "Wysig profiel" edit modal (Ink\Social\ProfileEditor) — hidden by default (fixed-position overlay when open); opened by the trigger button above via profiel-edit.js. Lives outside both bands — its own overlay, not part of either's document flow. ?>
<!-- wp:ink/profiel-redigeer /-->

<?php // Tab shell + all 7 panels — a SEPARATE full-bleed section from the identity band above (Tier-0 structural match: Lovable's tabs live in their own `container mx-auto px-4 py-10`, not inside the bordered identity <section>). ?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<?php // WIDTH BUG FIX — same root cause as the identity band above: the redundant
	// "layout":{"type":"constrained"} clamped the tab nav AND every one of the 7
	// panel sections below (none carry their own alignwide/alignfull) to contentSize
	// (768px) inside this already-correct wideSize (1368px) wrapper — confirmed live
	// (.ink-profiel-tabs / #bydraes both measured 768px before this fix). Dropped for
	// the same reason, matching ontdek.php's own proven fix for this exact mistake. ?>
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}}} -->
	<div class="wp-block-group alignwide">

		<?php // Tab shell (My Profiel rebuild §5.2): 7 ratified tabs, mirroring ontdek-tabs.js's progressive-enhancement mechanics (data-ink-profiel-tab/-panel, [hidden], #hash). ?>
		<!-- wp:group {"tagName":"nav","className":"ink-profiel-tabs","lock":{"move":true,"remove":true},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<nav class="wp-block-group ink-profiel-tabs" aria-label="<?php esc_attr_e( 'My profiel-oortjies', 'ink-foundation' ); ?>">
			<button type="button" class="ink-profiel-tabs__knoppie is-active" data-ink-profiel-tab="oorsig" aria-selected="true"><?php echo esc_html__( 'Oorsig', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="bydraes" aria-selected="false"><?php echo esc_html__( 'Bydraes', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="leeslys" aria-selected="false"><?php echo esc_html__( 'Leeslys', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="wie-ek-volg" aria-selected="false"><?php echo esc_html__( 'Wie ek volg', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="aktiwiteit" aria-selected="false"><?php echo esc_html__( 'Aktiwiteit', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="kennisgewings" aria-selected="false"><?php echo esc_html__( 'Kennisgewings', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-profiel-tabs__knoppie" data-ink-profiel-tab="lidmaatskap" aria-selected="false"><?php echo esc_html__( 'Lidmaatskap', 'ink-foundation' ); ?></button>
		</nav>
		<!-- /wp:group -->

		<?php // Oorsig (§5.3): "Oor my" (2/3) + "In 'n oogopslag" (1/3) + "Onlangse aktiwiteit". ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="oorsig" data-ink-profiel-panel="oorsig">

			<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
			<div class="wp-block-columns">

				<?php // "Oor my" — bio + Wysig affordance (2/3-width, matches Lovable's lg:col-span-2). ?>
				<!-- wp:column {"width":"66.66%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" style="flex-basis:66.66%">
					<!-- wp:group {"className":"is-style-card ink-profiel-oormy","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group is-style-card ink-profiel-oormy">
						<?php // Heading + "Wysig" affordance side by side (Tier-0 structural match to Profile.tsx's <div className="flex items-center justify-between mb-3"> — Lovable's Edit control sits beside the "About" heading, not after the bio text). ?>
						<!-- wp:html -->
						<div class="ink-profiel-oormy__kop">
							<h3 class="ink-profiel-oormy__titel"><?php echo esc_html__( 'Oor my', 'ink-foundation' ); ?></h3>
							<button type="button" class="wp-element-button ink-profiel-oormy__wysig" data-ink-profiel-redigeer-trigger><?php echo esc_html__( 'Wysig', 'ink-foundation' ); ?></button>
						</div>
						<!-- /wp:html -->

						<!-- wp:paragraph {"className":"ink-profiel-oormy__bio"} -->
						<p class="ink-profiel-oormy__bio" data-ink-profiel-veld="bio"><?php
						echo '' !== $ink_bio
							? esc_html( $ink_bio )
							// No ratified Afrikaans exists yet for a "no bio" fallback — flagged
							// per the standard [[afrikaans-copy-debt-process]] rather than
							// invented (see docs/afrikaans-translation-sheet.md OORSIG-BIO-LEEG /
							// docs/afrikaans-copy-worklist.md).
							: esc_html__( '[NEEDS HUMAN AFRIKAANS] — "no bio yet" fallback copy not yet authored in ui-copy-translations.md.', 'ink-foundation' );
						?></p>
						<!-- /wp:paragraph -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->

				<?php // "In 'n oogopslag" — Bydraes / Wie ek volg / Ongelees + membership one-liner (1/3-width). ?>
				<!-- wp:column {"width":"33.33%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" style="flex-basis:33.33%">
					<!-- wp:group {"className":"is-style-card ink-profiel-oogopslag","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group is-style-card ink-profiel-oogopslag">
						<!-- wp:heading {"level":3,"fontSize":"lg"} -->
						<h3 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( "In 'n oogopslag", 'ink-foundation' ); ?></h3>
						<!-- /wp:heading -->

						<!-- wp:html -->
						<dl class="ink-profiel-oogopslag__lys">
							<div class="ink-profiel-oogopslag__stat">
								<dt><?php echo esc_html__( 'Bydraes', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( count( $ink_bydraes ) ) ); ?></dd>
							</div>
							<div class="ink-profiel-oogopslag__stat">
								<dt><?php echo esc_html__( 'Wie ek volg', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $ink_volg_getal ) ); ?></dd>
							</div>
							<div class="ink-profiel-oogopslag__stat">
								<dt><?php echo esc_html__( 'Ongelees', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $ink_ongelees ) ); ?></dd>
							</div>
						</dl>
						<!-- /wp:html -->
<?php if ( null !== $ink_hernu_datum && '' !== $ink_hernu_datum ) : ?>
						<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text","className":"ink-profiel-oogopslag__lidmaatskap"} -->
						<p class="has-muted-text-color has-text-color has-sm-font-size ink-profiel-oogopslag__lidmaatskap"><?php
						echo esc_html(
							sprintf(
								/* translators: %s: the member's next renewal date. */
								__( 'INK-lid · hernieu %s', 'ink-foundation' ),
								$ink_hernu_datum
							)
						);
						?></p>
						<!-- /wp:paragraph -->
<?php endif; ?>
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->

			</div>
			<!-- /wp:columns -->

			<?php // "Onlangse aktiwiteit" — first 3 Kennisgewings rows (§7 decision 4). Empty → heading-only shell (no invented copy), mirroring SkrywerProfiel::toHtml()'s "keep the empty shell" precedent for its own no-data card. ?>
			<!-- wp:group {"className":"is-style-card ink-profiel-onlangs","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card ink-profiel-onlangs">
				<!-- wp:heading {"level":3,"fontSize":"lg"} -->
				<h3 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Onlangse aktiwiteit', 'ink-foundation' ); ?></h3>
				<!-- /wp:heading -->
<?php if ( array() !== $ink_onlangs ) : ?>
				<!-- wp:html -->
				<ul class="ink-profiel-onlangs__lys">
<?php foreach ( $ink_onlangs as $ink_kennisgewing ) : ?>
					<li class="ink-profiel-onlangs__item<?php echo ! empty( $ink_kennisgewing['unread'] ) ? ' is-unread' : ''; ?>"><?php echo esc_html( (string) $ink_kennisgewing['text'] ); ?></li>
<?php endforeach; ?>
				</ul>
				<!-- /wp:html -->
<?php endif; ?>
			</div>
			<!-- /wp:group -->

		</section>
		<!-- /wp:group -->

		<?php // Bydraes (§5.4): the unified per-post render (title+type+date+read-count+pin+edit/view in one row), replacing the two previously separate blocks (Story 9.5 pin-management, Story 9.12 read-counts) — same underlying data, one render. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="bydraes" data-ink-profiel-panel="bydraes">
			<!-- wp:ink/bydraes /-->
		</section>
		<!-- /wp:group -->

		<?php // Leeslys — Story 7.7's block moved as-is (§5.5 says "move it as-is into this tab"; the empty-state copy fix is a separate later change). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="leeslys" data-ink-profiel-panel="leeslys">
			<!-- wp:ink/leeslys /-->
		</section>
		<!-- /wp:group -->

		<?php // Wie ek volg (§5.6) — the following-list block (Api::followeeIdsFor() resolved to writer cards + reused FollowToggle unfollow control). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="wie-ek-volg" data-ink-profiel-panel="wie-ek-volg">
			<!-- wp:ink/volg-lys /-->
		</section>
		<!-- /wp:group -->

		<?php // Aktiwiteit — Story 9.3's following-feed moved as-is (§5.7 says "move it into this tab as-is structurally"; the richer card restyle is later). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="aktiwiteit" data-ink-profiel-panel="aktiwiteit">
			<!-- wp:ink/volg-voer /-->
		</section>
		<!-- /wp:group -->

		<?php // Kennisgewings (§5.8) — the read-model's rendered list + "Merk alles as gelees" button; $ink_kennisgewings_html computed above (guarded ink-core bridge). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="kennisgewings" data-ink-profiel-panel="kennisgewings">
			<!-- wp:html -->
			<?php echo $ink_kennisgewings_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- KennisgewingsSurface::toHtml() escapes internally. ?>
			<!-- /wp:html -->
		</section>
		<!-- /wp:group -->

		<?php // Lidmaatskap — Story 4.5's renewal pattern (data plumbing untouched per strategy §2) + the left-hand status card (§5.9). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="lidmaatskap" data-ink-profiel-panel="lidmaatskap">
<?php if ( $ink_lidmaatskap_aktief ) : ?>
			<?php // Status card only when the member genuinely has an active INK membership (never fabricated) — narrower left column, renewal section takes the rest (§5.9). ?>
			<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
			<div class="wp-block-columns">
				<!-- wp:column {"width":"33.33%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" style="flex-basis:33.33%">
					<!-- wp:group {"className":"is-style-card ink-lidmaatskap-status","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
					<div class="wp-block-group is-style-card ink-lidmaatskap-status">
						<!-- wp:paragraph {"fontSize":"xs","className":"ink-lidmaatskap-status__etiket"} -->
						<p class="has-xs-font-size ink-lidmaatskap-status__etiket"><?php echo esc_html__( 'INK-lid', 'ink-foundation' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"fontSize":"lg"} -->
						<h3 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Aktiewe lidmaatskap', 'ink-foundation' ); ?></h3>
						<!-- /wp:heading -->

						<!-- wp:html -->
						<dl class="ink-lidmaatskap-status__lys">
							<div class="ink-lidmaatskap-status__ry">
								<dt><?php echo esc_html__( 'Status', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html__( 'Aktief', 'ink-foundation' ); ?></dd>
							</div>
<?php if ( null !== $ink_hernu_datum ) : ?>
							<div class="ink-lidmaatskap-status__ry">
								<dt><?php echo esc_html__( 'Hernieu', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( $ink_hernu_datum ); ?></dd>
							</div>
<?php endif; ?>
<?php if ( null !== $ink_lid_sedert ) : ?>
							<div class="ink-lidmaatskap-status__ry">
								<dt><?php echo esc_html__( 'Lid sedert', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( $ink_lid_sedert ); ?></dd>
							</div>
<?php endif; ?>
						</dl>
						<!-- /wp:html -->
					</div>
					<!-- /wp:group -->
				</div>
				<!-- /wp:column -->

				<!-- wp:column {"width":"66.66%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" style="flex-basis:66.66%">
					<?php // Story 4.5 / 9.4: the lidmaatskap renewal section (supersedes the interim host) — kept exactly as-is, only wrapped. ?>
					<!-- wp:pattern {"slug":"ink-foundation/lidmaatskap-hernu"} /-->
				</div>
				<!-- /wp:column -->
			</div>
			<!-- /wp:columns -->
<?php else : ?>
			<?php // Story 4.5 / 9.4: the lidmaatskap renewal section (supersedes the interim host). No status card for a non-member — never a fabricated "Aktief" state. ?>
			<!-- wp:pattern {"slug":"ink-foundation/lidmaatskap-hernu"} /-->
<?php endif; ?>
		</section>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
