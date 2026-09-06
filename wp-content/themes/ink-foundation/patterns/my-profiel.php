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
 * IDENTITY STRIP + TAB SHELL (My Profiel rebuild, this build step — §5.1/§5.2):
 * this step builds ONLY the identity strip and the 7-tab shell skeleton, per the
 * strategy doc's own build order (§8, step 6). It deliberately does NOT populate
 * new tab content (Oorsig stats, the Bydraes/Kennisgewings/Wie-ek-volg reads) —
 * that is a separate, later step. To avoid regressing already-shipped, working
 * functionality in the meantime, every block that was ALREADY rendering on the
 * flat page (leesgetalle, vasgespel-bestuur, leeslys, volg-voer, the
 * lidmaatskap-hernu renewal pattern) is moved into its new tab home UNCHANGED —
 * none of that is "new tab content", it is the exact same already-working markup
 * relocated so the shell has somewhere real to put it. Only the genuinely new
 * tabs with no existing render today (Oorsig's stat cards, Wie-ek-volg,
 * Kennisgewings) carry an explicit `<!-- WP7: … -->` placeholder marker for the
 * next build step.
 *
 * Private-only data lives HERE and nowhere public (FR-40): the "wins needed"
 * subtext (`ink_foundation_gradering_wins_needed`, Story 5.9) and the read-count
 * surface (Story 9.12) render only here, and never on the public Skrywerprofiel.
 * The Gradering badge + wins-needed subtext moved from their own standalone
 * section into the identity strip (product-owner decision 2026-09-06, strategy
 * doc §7 item 5) — it is read-only for the member, nothing to edit or interact
 * with elsewhere.
 *
 * Three-layer: presentation only. The Gradering badge + wins-needed subtext,
 * the tagline (`Ink\Social\Tagline`), and the avatar/name/public-URL reads are
 * `class_exists`/`function_exists`-guarded `ink-core` reads (display, never a
 * gate). The edit-profile modal (`ink/profiel-redigeer`), following-feed,
 * leeslys, pin-management, read-counts and lidmaatskap renewal are existing
 * blocks/patterns, embedded here. Copy is authored Afrikaans
 * (ui-copy-translations.md "My Profiel-bladsy") via the `ink-foundation` text
 * domain; term labels via the registry. Sentence case; structural wrappers
 * locked (move/remove) per Storie 1.6.
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

		<?php // Oorsig — genuinely new content (stat cards + recent activity), no existing render to move. Next build step (§5.3) fills this in. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="oorsig" data-ink-profiel-panel="oorsig">
			<!-- WP7: Oorsig-oortjie-inhoud ("Oor my"-kaart + "In 'n oogopslag"-statistieke + "Onlangse aktiwiteit" — docs/my-profiel-rebuild-strategy.md §5.3). -->
		</section>
		<!-- /wp:group -->

		<?php // Bydraes — the two blocks already rendering on the flat page (Story 9.5 pin-management, Story 9.12 read-counts) moved here unchanged; §5.4's unified per-post card (title+type+read-count+pin+edit/view in one row) is later, deliberately not built this step. ?>
		<!-- wp:group {"tagName":"section","className":"ink-profiel-panel","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<section class="wp-block-group ink-profiel-panel" id="bydraes" data-ink-profiel-panel="bydraes">
			<?php // Story 9.12 (R8): the private per-bydrae read-count surface. ?>
			<!-- wp:group {"className":"ink-my-profiel__leesgetalle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group ink-my-profiel__leesgetalle" data-ink-slot="leesgetalle">
				<!-- wp:ink/leesgetalle /-->
			</div>
			<!-- /wp:group -->

			<?php // Story 9.5: pin / unpin your own works (curation). ?>
			<!-- wp:ink/vasgespel-bestuur /-->
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
