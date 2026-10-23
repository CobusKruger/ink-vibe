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
$ink_avatar       = function_exists( 'get_avatar' ) ? (string) get_avatar( $ink_user_id, 112, '', '', array( 'class' => 'ink-avatar ink-avatar--profile', 'extra_attr' => 'data-audit-id="profiel-identity-avatar"' ) ) : '';
$ink_display_name = (string) get_the_author_meta( 'display_name', $ink_user_id );
$ink_public_url   = (string) get_author_posts_url( $ink_user_id );

// Lucide icons from Profile.tsx (fidelity row 02, F4/F8): trusted, hard-coded path data.
$ink_icon = static function ( string $paths ): string {
	return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ink-icon" aria-hidden="true" focusable="false">' . $paths . '</svg>';
};
$ink_icons = array(
	'pencil'    => '<path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z"/><path d="m15 5 4 4"/>',
	'pen-line'  => '<path d="M12 20h9"/><path d="M16.376 3.622a1 1 0 0 1 3.002 3.002L7.368 18.635a2 2 0 0 1-.855.506l-2.872.838a.5.5 0 0 1-.62-.62l.838-2.872a2 2 0 0 1 .506-.854z"/>',
	'arrow'     => '<path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>',
	'book-open' => '<path d="M12 7v14"/><path d="M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>',
	'bookmark'  => '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>',
	'users'     => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
	'activity'  => '<path d="M22 12h-2.48a2 2 0 0 0-1.93 1.46l-2.35 8.36a.25.25 0 0 1-.48 0L9.24 2.18a.25.25 0 0 0-.48 0l-2.35 8.36A2 2 0 0 1 4.49 12H2"/>',
	'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
	'crown'     => '<path d="M11.562 3.266a.5.5 0 0 1 .876 0L15.39 8.87a1 1 0 0 0 1.516.294L21.183 5.5a.5.5 0 0 1 .798.519l-2.834 10.246a1 1 0 0 1-.956.734H5.81a1 1 0 0 1-.957-.734L2.02 6.02a.5.5 0 0 1 .798-.519l4.276 3.664a1 1 0 0 0 1.516-.294z"/><path d="M5 21h14"/>',
);

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
<!-- wp:group {"tagName":"section","className":"ink-band ink-band--flush ink-band--intro ink-profiel-identiteit-band","align":"full","lock":{"move":true,"remove":true},"layout":{"type":"default"}} -->
<section class="wp-block-group alignfull ink-band ink-band--flush ink-band--intro ink-profiel-identiteit-band" data-audit-id="profiel-identity">
	<?php // No "constrained" layout anywhere in this pattern: it clamps un-aligned children to contentSize (768px) — the bug the earlier WIDTH BUG FIX passes chased wrapper by wrapper (fidelity F11/F34). ?>
	<?php // Fidelity Stage 4 (row 02): Lovable's `container mx-auto px-4 py-10 md:py-14` — the `ink-container` primitive; the py lives in profiel.css. ?>
	<!-- wp:group {"className":"ink-container ink-profiel-identiteit-houer","lock":{"move":true,"remove":true}} -->
	<div class="wp-block-group ink-container ink-profiel-identiteit-houer" data-audit-id="profiel-identity-container">

		<?php // Identity strip (My Profiel rebuild §5.1): avatar, "Jou profiel" eyebrow, serif H1 name, Gradering badge + wins-needed, tagline, three actions. ?>
		<?php // Row = avatar | text | actions (Profile.tsx:175 `flex flex-col md:flex-row md:items-center gap-6`; F2 — no extra person wrapper). Flex rules in profiel.css. ?>
		<!-- wp:group {"className":"ink-profiel-identiteit","lock":{"move":true,"remove":true}} -->
		<div class="wp-block-group ink-profiel-identiteit" data-audit-id="profiel-identity-row">

<?php if ( '' !== $ink_avatar ) : ?>
				<!-- wp:html -->
				<div class="ink-profiel-identiteit__avatar" data-audit-id="profiel-identity-avatar-wrap"><?php echo $ink_avatar; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() returns a fully-escaped <img>. ?></div>
				<!-- /wp:html -->
<?php endif; ?>
				<!-- wp:group {"className":"ink-profiel-identiteit__inhoud"} -->
				<div class="wp-block-group ink-profiel-identiteit__inhoud" data-audit-id="profiel-identity-text">
					<!-- wp:paragraph {"className":"ink-eyebrow ink-eyebrow--muted ink-profiel-identiteit__etiket"} -->
					<p class="ink-eyebrow ink-eyebrow--muted ink-profiel-identiteit__etiket" data-audit-id="profiel-identity-eyebrow"><?php echo esc_html__( 'Jou profiel', 'ink-foundation' ); ?></p>
					<!-- /wp:paragraph -->

					<!-- wp:heading {"level":1,"className":"ink-heading-h2-section ink-heading--regular ink-profiel-identiteit__naam"} -->
					<h1 class="wp-block-heading ink-heading-h2-section ink-heading--regular ink-profiel-identiteit__naam" data-ink-profiel-veld="naam" data-audit-id="profiel-identity-name"><?php echo esc_html( $ink_display_name ); ?></h1>
					<!-- /wp:heading -->
<?php if ( '' !== $ink_badge || '' !== $ink_wins_needed ) : ?>
					<!-- wp:group {"className":"ink-profiel-identiteit__gradering","layout":{"type":"flex","verticalAlignment":"center","flexWrap":"wrap"}} -->
					<div class="wp-block-group ink-profiel-identiteit__gradering" data-audit-id="profiel-identity-gradering">
	<?php if ( '' !== $ink_badge ) : ?>
						<!-- wp:html -->
						<?php echo preg_replace( '/^<span\b/', '<span data-audit-id="profiel-identity-gradering-badge"', $ink_badge, 1 ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink-core bridge returns escaped, token-only badge markup. ?>
						<!-- /wp:html -->
<?php endif; ?>
	<?php if ( '' !== $ink_wins_needed ) : ?>
						<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text","className":"ink-my-profiel__wins-needed"} -->
						<p class="has-muted-text-color has-text-color has-sm-font-size ink-my-profiel__wins-needed" data-audit-id="profiel-identity-wins-needed"><?php echo esc_html( $ink_wins_needed ); ?></p>
						<!-- /wp:paragraph -->
<?php endif; ?>
					</div>
					<!-- /wp:group -->
<?php endif; ?>
					<?php // Two REAL bugs found live (Tier 2, profiel-edit.js's server-render-then-flip pattern): (1) this whole <p> was PHP-conditional on a non-empty tagline, so a member's FIRST-EVER tagline save had no element for the JS's [data-ink-profiel-veld="leuse"] selector to find — the identity strip silently never updated until reload; (2) even once the <p> existed, the JS's `el.textContent = data.tagline` blindly overwrote the PHP-baked literal quote-mark characters, since that selector sat on the SAME element as the quotes. Fixed by ALWAYS rendering the wrapper with the quote marks as PERMANENT sibling text, moving [data-ink-profiel-veld="leuse"] onto an INNER span (so the JS's existing textContent-only update never touches the quotes), and adding a separate wrapper attribute purely for the empty/non-empty visibility toggle — never bare empty quotes, but now a CSS concern, not a markup-presence one. ?>
					<!-- wp:paragraph {"fontSize":"md","className":"ink-profiel-identiteit__leuse<?php echo '' === $ink_tagline ? ' is-empty' : ''; ?>"} -->
					<p class="has-md-font-size ink-profiel-identiteit__leuse<?php echo '' === $ink_tagline ? ' is-empty' : ''; ?>" data-ink-profiel-leuse-wrap data-audit-id="profiel-identity-tagline">&#8220;<span data-ink-profiel-veld="leuse"><?php echo esc_html( $ink_tagline ); ?></span>&#8221;</p>
					<!-- /wp:paragraph -->
				</div>
				<!-- /wp:group -->

			<!-- wp:group {"className":"ink-profiel-identiteit__aksies","layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-group ink-profiel-identiteit__aksies" data-audit-id="profiel-identity-actions">
				<?php // A real <button>, not a link — matches Lovable's onClick-only "Edit profile" control and the codebase's own convention for JS-triggered controls (vasgespel/volg toggles are also <button type="button">, never <a>). Styled by the shared `ink-btn ink-btn--outline` primitive (primitives.css), which — unlike the `.wp-block-button.is-style-*` block-style chain — matches a bare button. ?>
				<!-- wp:html -->
				<button type="button" class="ink-btn ink-btn--outline ink-profiel-identiteit__wysig" data-ink-profiel-redigeer-trigger data-audit-id="profiel-identity-edit-btn"><?php echo $ink_icon( $ink_icons['pencil'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Wysig profiel', 'ink-foundation' ); ?></button>
				<!-- /wp:html -->

				<!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} -->
				<div class="wp-block-buttons" data-audit-id="profiel-identity-buttons">
					<!-- wp:button {"className":"is-style-ink-primary"} -->
					<div class="wp-block-button is-style-ink-primary"><a class="wp-block-button__link wp-element-button" data-audit-id="profiel-identity-new-btn" href="<?php echo esc_url( home_url( '/skryf/' ) ); ?>"><?php echo $ink_icon( $ink_icons['pen-line'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Nuwe bydrae', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->

					<!-- wp:button {"className":"is-style-subtle"} -->
					<div class="wp-block-button is-style-subtle"><a class="wp-block-button__link wp-element-button" data-audit-id="profiel-identity-public-btn" href="<?php echo esc_url( $ink_public_url ); ?>"><?php echo esc_html__( 'Sien openbare bladsy', 'ink-foundation' ); ?><?php echo $ink_icon( $ink_icons['arrow'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?></a></div>
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
<?php // Fidelity Stage 4 (row 02): Lovable's `section.container mx-auto px-4 py-10` — the `ink-container` primitive (py in profiel.css), no constrained clamp (F11). ?>
<!-- wp:group {"tagName":"section","className":"ink-container ink-profiel-oortjies","lock":{"move":true,"remove":true},"layout":{"type":"default"}} -->
<section class="wp-block-group ink-container ink-profiel-oortjies" data-audit-id="profiel-tabs-section">
	<!-- wp:group {"className":"ink-profiel-oortjies__binne","lock":{"move":true,"remove":true}} -->
	<div class="wp-block-group ink-profiel-oortjies__binne" data-audit-id="profiel-tabs-wide">

		<?php // Tab shell (My Profiel rebuild §5.2): 7 ratified tabs, mirroring ontdek-tabs.js's progressive-enhancement mechanics (data-ink-profiel-tab/-panel, [hidden], #hash). ?>
		<!-- wp:group {"tagName":"nav","className":"ink-segmented ink-profiel-tabs","lock":{"move":true,"remove":true},"layout":{"type":"flex","flexWrap":"wrap"}} -->
		<nav class="wp-block-group ink-segmented ink-profiel-tabs" data-audit-id="profiel-tablist" aria-label="<?php esc_attr_e( 'My profiel-oortjies', 'ink-foundation' ); ?>">
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie is-active" data-ink-profiel-tab="oorsig" data-audit-id="profiel-tab-overview" aria-selected="true"><?php echo esc_html__( 'Oorsig', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="bydraes" data-audit-id="profiel-tab-posts" aria-selected="false"><?php echo $ink_icon( $ink_icons['book-open'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Bydraes', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="leeslys" data-audit-id="profiel-tab-reading" aria-selected="false"><?php echo $ink_icon( $ink_icons['bookmark'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Leeslys', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="wie-ek-volg" data-audit-id="profiel-tab-following" aria-selected="false"><?php echo $ink_icon( $ink_icons['users'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Wie ek volg', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="aktiwiteit" data-audit-id="profiel-tab-activity" aria-selected="false"><?php echo $ink_icon( $ink_icons['activity'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Aktiwiteit', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="kennisgewings" data-audit-id="profiel-tab-notifications" aria-selected="false"><?php echo $ink_icon( $ink_icons['bell'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Kennisgewings', 'ink-foundation' ); ?></button>
			<button type="button" class="ink-segmented__tab ink-profiel-tabs__knoppie" data-ink-profiel-tab="lidmaatskap" data-audit-id="profiel-tab-membership" aria-selected="false"><?php echo $ink_icon( $ink_icons['crown'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Lidmaatskap', 'ink-foundation' ); ?></button>
		</nav>
		<!-- /wp:group -->

		<?php // Oorsig (§5.3): "Oor my" (2/3) + "In 'n oogopslag" (1/3) + "Onlangse aktiwiteit". ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}}} -->
		<section class="wp-block-group ink-profiel-panel" id="oorsig" data-ink-profiel-panel="oorsig" data-audit-id="profiel-overview">

			<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
			<div class="wp-block-columns" data-audit-id="profiel-overview-grid">

				<?php // "Oor my" — bio + Wysig affordance (2/3-width, matches Lovable's lg:col-span-2). ?>
				<!-- wp:column {"width":"66.66%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" data-audit-id="profiel-about-col" style="flex-basis:66.66%">
					<!-- wp:group {"className":"is-style-card ink-profiel-oormy","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
					<div class="wp-block-group is-style-card ink-profiel-oormy" data-audit-id="profiel-about-card">
						<?php // Heading + "Wysig" affordance side by side (Tier-0 structural match to Profile.tsx's <div className="flex items-center justify-between mb-3"> — Lovable's Edit control sits beside the "About" heading, not after the bio text). ?>
						<!-- wp:html -->
						<div class="ink-profiel-oormy__kop" data-audit-id="profiel-about-head">
							<h2 class="ink-heading-h3 ink-heading--regular ink-profiel-oormy__titel" data-audit-id="profiel-about-title"><?php echo esc_html__( 'Oor my', 'ink-foundation' ); ?></h2>
							<button type="button" class="ink-btn ink-btn--sm ink-btn--ghost ink-profiel-oormy__wysig" data-ink-profiel-redigeer-trigger data-audit-id="profiel-about-edit-btn"><?php echo $ink_icon( $ink_icons['pencil'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline svg. ?><?php echo esc_html__( 'Wysig', 'ink-foundation' ); ?></button>
						</div>
						<!-- /wp:html -->

						<!-- wp:paragraph {"className":"ink-profiel-oormy__bio"} -->
						<p class="ink-profiel-oormy__bio" data-ink-profiel-veld="bio" data-audit-id="profiel-about-bio"><?php
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
				<div class="wp-block-column" data-audit-id="profiel-glance-col" style="flex-basis:33.33%">
					<!-- wp:group {"className":"is-style-card ink-profiel-oogopslag","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
					<div class="wp-block-group is-style-card ink-profiel-oogopslag" data-audit-id="profiel-glance-card">
						<!-- wp:heading {"className":"ink-heading-h3 ink-heading--regular"} -->
						<h2 class="wp-block-heading ink-heading-h3 ink-heading--regular" data-audit-id="profiel-glance-title"><?php echo esc_html__( "In 'n oogopslag", 'ink-foundation' ); ?></h2>
						<!-- /wp:heading -->

						<!-- wp:html -->
						<dl class="ink-profiel-oogopslag__lys" data-audit-id="profiel-glance-stats">
							<div class="ink-profiel-oogopslag__stat" data-audit-id="profiel-glance-stat">
								<dt class="ink-stat-label" data-audit-id="profiel-glance-stat-label"><?php echo esc_html__( 'Bydraes', 'ink-foundation' ); ?></dt>
								<dd data-audit-id="profiel-glance-stat-value"><?php echo esc_html( number_format_i18n( count( $ink_bydraes ) ) ); ?></dd>
							</div>
							<div class="ink-profiel-oogopslag__stat">
								<dt class="ink-stat-label"><?php echo esc_html__( 'Wie ek volg', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $ink_volg_getal ) ); ?></dd>
							</div>
							<div class="ink-profiel-oogopslag__stat">
								<dt class="ink-stat-label"><?php echo esc_html__( 'Ongelees', 'ink-foundation' ); ?></dt>
								<dd><?php echo esc_html( number_format_i18n( $ink_ongelees ) ); ?></dd>
							</div>
						</dl>
						<!-- /wp:html -->
<?php if ( null !== $ink_hernu_datum && '' !== $ink_hernu_datum ) : ?>
						<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text","className":"ink-profiel-oogopslag__lidmaatskap"} -->
						<p class="has-muted-text-color has-text-color has-sm-font-size ink-profiel-oogopslag__lidmaatskap" data-audit-id="profiel-glance-membership"><?php
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
			<!-- wp:group {"className":"is-style-card ink-profiel-onlangs","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
			<div class="wp-block-group is-style-card ink-profiel-onlangs" data-audit-id="profiel-recent-card">
				<!-- wp:heading {"className":"ink-heading-h3 ink-heading--regular"} -->
				<h2 class="wp-block-heading ink-heading-h3 ink-heading--regular" data-audit-id="profiel-recent-title"><?php echo esc_html__( 'Onlangse aktiwiteit', 'ink-foundation' ); ?></h2>
				<!-- /wp:heading -->
<?php if ( array() !== $ink_onlangs ) : ?>
				<!-- wp:html -->
				<ul class="ink-profiel-onlangs__lys" data-audit-id="profiel-recent-list">
<?php foreach ( $ink_onlangs as $ink_onlangs_i => $ink_kennisgewing ) : ?>
					<li class="ink-profiel-onlangs__item<?php echo ! empty( $ink_kennisgewing['unread'] ) ? ' is-unread' : ''; ?>"<?php echo 0 === $ink_onlangs_i ? ' data-audit-id="profiel-recent-item"' : ''; ?>><?php echo ! empty( $ink_kennisgewing['unread'] ) ? '<span class="ink-status-dot"' . ( 0 === $ink_onlangs_i ? ' data-audit-id="profiel-recent-dot"' : '' ) . ' aria-hidden="true"></span>' : ''; ?><?php echo esc_html( (string) $ink_kennisgewing['text'] ); ?></li>
<?php endforeach; ?>
				</ul>
				<!-- /wp:html -->
<?php endif; ?>
			</div>
			<!-- /wp:group -->

		</section>
		<!-- /wp:group -->

		<?php // Bydraes (§5.4): the unified per-post render (title+type+date+read-count+pin+edit/view in one row), replacing the two previously separate blocks (Story 9.5 pin-management, Story 9.12 read-counts) — same underlying data, one render. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="bydraes" data-ink-profiel-panel="bydraes" data-audit-id="profiel-posts">
			<!-- wp:ink/bydraes /-->
		</section>
		<!-- /wp:group -->

		<?php // Leeslys — Story 7.7's block moved as-is (§5.5 says "move it as-is into this tab"; the empty-state copy fix is a separate later change). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="leeslys" data-ink-profiel-panel="leeslys" data-audit-id="profiel-reading">
			<!-- wp:ink/leeslys /-->
		</section>
		<!-- /wp:group -->

		<?php // Wie ek volg (§5.6) — the following-list block (Api::followeeIdsFor() resolved to writer cards + reused FollowToggle unfollow control). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="wie-ek-volg" data-ink-profiel-panel="wie-ek-volg" data-audit-id="profiel-following">
			<!-- wp:ink/volg-lys /-->
		</section>
		<!-- /wp:group -->

		<?php // Aktiwiteit — Story 9.3's following-feed moved as-is (§5.7 says "move it into this tab as-is structurally"; the richer card restyle is later). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="aktiwiteit" data-ink-profiel-panel="aktiwiteit" data-audit-id="profiel-activity">
			<!-- wp:ink/volg-voer /-->
		</section>
		<!-- /wp:group -->

		<?php // Kennisgewings (§5.8) — the read-model's rendered list + "Merk alles as gelees" button; $ink_kennisgewings_html computed above (guarded ink-core bridge). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="kennisgewings" data-ink-profiel-panel="kennisgewings" data-audit-id="profiel-notifications">
			<!-- wp:html -->
			<?php echo $ink_kennisgewings_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- KennisgewingsSurface::toHtml() escapes internally. ?>
			<!-- /wp:html -->
		</section>
		<!-- /wp:group -->

		<?php // Lidmaatskap — Story 4.5's renewal pattern (data plumbing untouched per strategy §2) + the left-hand status card (§5.9). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true}} -->
		<section class="wp-block-group ink-profiel-panel" id="lidmaatskap" data-ink-profiel-panel="lidmaatskap" data-audit-id="profiel-membership">
<?php if ( $ink_lidmaatskap_aktief ) : ?>
			<?php // Status card only when the member genuinely has an active INK membership (never fabricated) — narrower left column, renewal section takes the rest (§5.9). ?>
			<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|s-24","top":"var:preset|spacing|s-24"}}}} -->
			<div class="wp-block-columns" data-audit-id="profiel-membership-grid">
				<!-- wp:column {"width":"33.33%","lock":{"move":true,"remove":true}} -->
				<div class="wp-block-column" style="flex-basis:33.33%">
					<!-- wp:group {"className":"is-style-card ink-lidmaatskap-status","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}}} -->
					<div class="wp-block-group is-style-card ink-lidmaatskap-status" data-audit-id="profiel-membership-status-card">
						<!-- wp:paragraph {"className":"ink-eyebrow ink-lidmaatskap-status__etiket"} -->
						<p class="ink-eyebrow ink-lidmaatskap-status__etiket" data-audit-id="profiel-membership-status-label"><?php echo esc_html__( 'INK-lid', 'ink-foundation' ); ?></p>
						<!-- /wp:paragraph -->

						<!-- wp:heading {"level":3,"className":"ink-heading-h3 ink-heading--regular"} -->
						<h3 class="wp-block-heading ink-heading-h3 ink-heading--regular" data-audit-id="profiel-membership-status-title"><?php echo esc_html__( 'Aktiewe lidmaatskap', 'ink-foundation' ); ?></h3>
						<!-- /wp:heading -->

						<!-- wp:html -->
						<dl class="ink-lidmaatskap-status__lys" data-audit-id="profiel-membership-status-list">
							<div class="ink-lidmaatskap-status__ry" data-audit-id="profiel-membership-status-row">
								<dt data-audit-id="profiel-membership-status-dt"><?php echo esc_html__( 'Status', 'ink-foundation' ); ?></dt>
								<dd data-audit-id="profiel-membership-status-dd"><?php echo esc_html__( 'Aktief', 'ink-foundation' ); ?></dd>
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
