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
 * IDENTITY STRIP + TAB SHELL + OORSIG + BYDRAES (My Profiel rebuild, this build
 * step — §5.1–§5.4): builds the identity strip, the 7-tab shell skeleton, the
 * Oorsig tab ("Oor my"/"In 'n oogopslag"/"Onlangse aktiwiteit") and the Bydraes
 * tab's new unified per-post render (`Ink\Social\BydraesSurface`, §5.4/§3 —
 * replaces the two previously separate `ink/leesgetalle` + `ink/vasgespel-bestuur`
 * blocks with one query/render carrying title+type+date+read-count+pin+edit/view
 * per row). It deliberately does NOT populate the still-genuinely-new tabs
 * (Wie-ek-volg's embed, Kennisgewings, the Lidmaatskap status card) — those
 * remain a separate, later step and still carry an explicit `<!-- WP7: … -->`
 * placeholder marker. Leeslys/Aktiwiteit/Lidmaatskap's existing blocks/patterns
 * (leeslys, volg-voer, lidmaatskap-hernu) stay moved-as-is, unchanged.
 *
 * Private-only data lives HERE and nowhere public (FR-40): the "wins needed"
 * subtext (`ink_foundation_gradering_wins_needed`, Story 5.9), the read-count
 * surface (Story 9.12, now surfaced per-post on Bydraes via `BydraesSurface`),
 * the bio/stat cards, and the Kennisgewings-derived "Ongelees" count + "Onlangse
 * aktiwiteit" preview render only here, and never on the public Skrywerprofiel.
 * The Gradering badge + wins-needed subtext moved from their own standalone
 * section into the identity strip, and Leesgetalle moved from its own Oorsig
 * section to a per-post Bydraes stat (product-owner decisions 2026-09-06,
 * strategy doc §7 items 4/5).
 *
 * Three-layer: presentation only. The Gradering badge + wins-needed subtext,
 * the tagline (`Ink\Social\Tagline`), the Oorsig stat reads (`Social\Api`,
 * `Notifications\KennisgewingsSurface`, `Entitlement\Api`), the Bydraes-tab
 * query (`Social\BydraesSurface`), and the avatar/name/public-URL reads are
 * `class_exists`/`function_exists`-guarded `ink-core` reads (display, never a
 * gate). The edit-profile modal (`ink/profiel-redigeer`), following-feed,
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
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}},"layout":{"type":"constrained"}} -->
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
<?php if ( '' !== $ink_tagline ) : ?>
					<!-- wp:paragraph {"fontSize":"md","className":"ink-profiel-identiteit__leuse"} -->
					<p class="has-md-font-size ink-profiel-identiteit__leuse" data-ink-profiel-veld="leuse">&#8220;<?php echo esc_html( $ink_tagline ); ?>&#8221;</p>
					<!-- /wp:paragraph -->
<?php endif; ?>
				</div>
				<!-- /wp:group -->
			</div>
			<!-- /wp:group -->

			<!-- wp:group {"className":"ink-profiel-identiteit__aksies","layout":{"type":"flex","flexWrap":"wrap"}} -->
			<div class="wp-block-group ink-profiel-identiteit__aksies">
				<?php // A real <button>, not a link — matches Lovable's onClick-only "Edit profile" control and the codebase's own convention for JS-triggered controls (vasgespel/volg toggles are also <button type="button">, never <a>). ?>
				<!-- wp:html -->
				<button type="button" class="wp-element-button ink-profiel-identiteit__wysig is-style-outline" data-ink-profiel-redigeer-trigger><?php echo esc_html__( 'Wysig profiel', 'ink-foundation' ); ?></button>
				<!-- /wp:html -->

				<!-- wp:buttons {"layout":{"type":"flex","flexWrap":"wrap"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button -->
					<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( home_url( '/skryf/' ) ); ?>"><?php echo esc_html__( 'Nuwe bydrae', 'ink-foundation' ); ?></a></div>
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

		<?php // The real "Wysig profiel" edit modal (Ink\Social\ProfileEditor) — hidden by default; opened by the trigger button above via profiel-edit.js. ?>
		<!-- wp:ink/profiel-redigeer /-->

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
						<!-- wp:heading {"level":3,"fontSize":"lg"} -->
						<h3 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Oor my', 'ink-foundation' ); ?></h3>
						<!-- /wp:heading -->

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

						<!-- wp:html -->
						<button type="button" class="wp-element-button ink-profiel-oormy__wysig is-style-subtle" data-ink-profiel-redigeer-trigger><?php echo esc_html__( 'Wysig', 'ink-foundation' ); ?></button>
						<!-- /wp:html -->
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

		<?php // Wie ek volg — genuinely new component (ink/volg-lys, §5.6), not yet embedded per the task's own instruction; next build step wires it in. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="wie-ek-volg" data-ink-profiel-panel="wie-ek-volg">
			<!-- WP7: Wie-ek-volg-oortjie-inhoud (ink/volg-lys — docs/my-profiel-rebuild-strategy.md §5.6). -->
		</section>
		<!-- /wp:group -->

		<?php // Aktiwiteit — Story 9.3's following-feed moved as-is (§5.7 says "move it into this tab as-is structurally"; the richer card restyle is later). ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="aktiwiteit" data-ink-profiel-panel="aktiwiteit">
			<!-- wp:ink/volg-voer /-->
		</section>
		<!-- /wp:group -->

		<?php // Kennisgewings — genuinely new component (KennisgewingsSurface, §5.8), the biggest remaining gap; not yet embedded per the task's own instruction. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="kennisgewings" data-ink-profiel-panel="kennisgewings">
			<!-- WP7: Kennisgewings-oortjie-inhoud (Ink\Notifications\KennisgewingsSurface — docs/my-profiel-rebuild-strategy.md §5.8). -->
		</section>
		<!-- /wp:group -->

		<?php // Lidmaatskap — Story 4.5's renewal pattern moved as-is (data plumbing untouched per strategy §2); the left-hand status card (§5.9) is later. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="lidmaatskap" data-ink-profiel-panel="lidmaatskap">
			<!-- WP7: Lidmaatskap-status-kaart (Ink\Entitlement\Api::memberSinceFor()/renewalDateFor() — docs/my-profiel-rebuild-strategy.md §5.9). -->
			<?php // Story 4.5 / 9.4: the lidmaatskap renewal section (supersedes the interim host). ?>
			<!-- wp:pattern {"slug":"ink-foundation/lidmaatskap-hernu"} /-->
		</section>
		<!-- /wp:group -->

	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
