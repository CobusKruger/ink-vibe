<?php
/**
 * Title: INK Header
 * Slug: ink-foundation/header-main
 * Categories: header
 * Block Types: core/template-part/header
 * Description: Werf-wye kopstuk (Epic 19, Storie 19.2, §1) — kleefbaar (sticky), deurskynende oppervlak + agtergrond-vervaging + 1px onderrand, 64px-ry. Veer-glief (terracotta) langs die Lora-woordmerk; navigasie-skakels met onderstreep-glы en fokusring, wat invou na 'n hamburger op klein skerms; ingeteken toon "Begin skryf" (primêr), uitgeteken toon "Teken in" (skim) + "Sluit aan" (primêr).
 *
 * Presentation only (three-layer separation): the ONE bit of logic here —
 * `is_user_logged_in()` — is a view-state check, not business logic (it decides
 * which of two already-built markup branches to print, same as {@see
 * ink-foundation/skryf} and {@see ink-foundation/lidmaatskap-hernu} already do
 * from inside their own pattern PHP; WordPress natively executes `patterns/*.php`
 * server-side via its Pattern-File-Header convention, so this is the same
 * mechanism, not a bespoke one). Fourth-pass fidelity fix (2026-09-05, direct
 * product-owner finding): every visitor used to see "Begin skryf" regardless of
 * auth state — Lovable's `Header.tsx` shows "Start Writing" only when `user` is
 * set, and "Sign in" (ghost) + "Join Inkwell" (primary) otherwise. Logged-out now
 * renders `is-style-ink-ghost` "Teken in" (linking to the existing `/meld-aan`
 * page — the URL is unchanged, only the label) + `is-style-ink-primary` "Sluit
 * aan" (`/registreer`); logged-in keeps "Begin skryf" exactly as before. Both new
 * labels are sourced from the {@see \Ink\I18n\Terms} registry via
 * `ink_foundation_term()` (`teken_in`/`sluit_aan`) — single-source, since "Teken
 * in" now also replaces the competing "Meld aan" label across the auth pages, the
 * write-page gate and the membership-renewal fallback (same product-owner
 * instruction: "'meld aan' ... should always be 'teken in'").
 *
 * The sticky / translucent / blur / border / row-height / nav-hover treatment
 * lives site-wide on the `is-style-ink-header` block style (functions.php) — NOT
 * in home.css, which is front-page-only, because the header renders on every
 * page (the same reason the two button styles above are registered in
 * functions.php rather than home.css). Copy is authored Afrikaans via the
 * `ink-foundation` text domain. The feather is a decorative inline SVG
 * (aria-hidden), coloured via the `primary` token (§0.9). The site-title renders
 * at heading level 0 (a <p>, not an <h1>) so the page keeps a single visible <h1>
 * (the hero heading).
 *
 * Row structure (Epic 19 lees-gedig re-audit, docs/theme-fidelity-audit-handoff.md
 * §6, finding #1 — this is the site-wide header, so the fix applies everywhere,
 * not just lees-gedig): Lovable's header row is THREE flex children (logo / nav /
 * auth) under one `justify-content:space-between`, with the nav itself carrying no
 * justify-content of its own (computes to the CSS-initial `normal`) and a 32px
 * column-gap. This row used to nest the nav + the "Begin skryf" button together
 * inside a SECOND group, so the row only ever had two flex children and the nav's
 * own `justifyContent:"right"` pushed its links flush against the button
 * (`justify-content:flex-end`, 24px gap) instead of reading centered in its own
 * slot. Un-nesting them into three direct siblings of `.ink-header-row` and
 * dropping the nav's own justifyContent (so it falls back to the initial `normal`)
 * reproduces Lovable's computed values exactly; the column-gap is bumped from
 * `s-24` (24px) to `s-32` (32px) to match. The nav link colour itself was
 * re-measured fresh against Lovable and already matches (muted-foreground grey at
 * rest, not near-black — a stale claim in an earlier, uncorrected audit pass) so
 * it is deliberately left untouched here.
 *
 * @package Ink\Foundation
 */
?>
<!-- wp:group {"align":"full","className":"is-style-ink-header","templateLock":"contentOnly","style":{"spacing":{"padding":{"top":"0","bottom":"0","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}},"layout":{"type":"constrained"}} -->
<div data-audit-id="header" class="wp-block-group alignfull is-style-ink-header" style="padding-top:0;padding-bottom:0;padding-left:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-16)">
	<!-- wp:group {"align":"wide","className":"ink-header-row","layout":{"type":"flex","justifyContent":"space-between","verticalAlignment":"center","flexWrap":"wrap"}} -->
	<div data-audit-id="header-row" class="wp-block-group alignwide ink-header-row">
		<!-- wp:group {"className":"ink-header-brand","style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"flex","verticalAlignment":"center"}} -->
		<div data-audit-id="header-logo" class="wp-block-group ink-header-brand">
			<!-- wp:html -->
			<span class="ink-header-feather" aria-hidden="true" style="display:inline-flex;color:var(--wp--preset--color--primary)"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" data-audit-id="header-logo-icon"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg></span>
			<!-- /wp:html -->

			<!-- wp:site-title {"level":0,"fontFamily":"display","fontSize":"xl","style":{"typography":{"fontStyle":"normal","fontWeight":"600"}},"textColor":"ink-text"} /-->
		</div>
		<!-- /wp:group -->

		<!-- wp:navigation {"textColor":"muted-text","overlayTextColor":"text","overlayBackgroundColor":"surface","style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}},"layout":{"type":"flex","orientation":"horizontal"}} -->
			<!-- wp:navigation-link {"label":"Tuis","url":"/"} /-->
			<!-- wp:navigation-link {"label":"Ontdek","url":"/ontdek"} /-->
			<!-- wp:navigation-link {"label":"Opleiding","url":"/opleiding"} /-->
			<!-- wp:navigation-link {"label":"Uitdagings","url":"/uitdaging/"} /-->
			<!-- wp:navigation-link {"label":"Gemeenskap","url":"/gemeenskap"} /-->
			<!-- wp:navigation-link {"label":"My profiel","url":"/my-profiel"} /-->
		<!-- /wp:navigation -->

<?php if ( function_exists( 'is_user_logged_in' ) && is_user_logged_in() ) : ?>
	<?php
	// Signed-in user menu (Lovable Header.tsx: Avatar + DropdownMenu). View-state only:
	// initials from the display name (first letters of the first two words), the
	// account email as the menu's muted heading, and the logout URL — all WordPress
	// core reads, no business logic.
	$ink_menu_user  = wp_get_current_user();
	$ink_menu_words = preg_split( '/\s+/u', trim( (string) $ink_menu_user->display_name ), -1, PREG_SPLIT_NO_EMPTY );
	$ink_menu_init  = '';
	foreach ( array_slice( (array) $ink_menu_words, 0, 2 ) as $ink_menu_word ) {
		$ink_menu_init .= mb_strtoupper( mb_substr( $ink_menu_word, 0, 1 ) );
	}
	if ( '' === $ink_menu_init ) {
		$ink_menu_init = mb_strtoupper( mb_substr( (string) $ink_menu_user->user_login, 0, 2 ) );
	}
	?>
			<!-- wp:group {"className":"ink-header-actions","style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"flex","verticalAlignment":"center","flexWrap":"nowrap"}} -->
			<div data-audit-id="header-actions" class="wp-block-group ink-header-actions">
				<!-- wp:buttons -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-ink-primary ink-btn-sm"} -->
					<div class="wp-block-button is-style-ink-primary ink-btn-sm"><a data-audit-id="header-btn-write" class="wp-block-button__link wp-element-button" href="/skryf"><?php esc_html_e( 'Begin skryf', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->

				<!-- wp:html -->
				<div class="ink-user-menu" data-ink-user-menu data-audit-id="header-user-menu">
					<button type="button" class="ink-user-menu__trigger" aria-haspopup="true" aria-expanded="false" aria-controls="ink-user-menu-panel" aria-label="<?php echo esc_attr__( 'Gebruikerskeuselys', 'ink-foundation' ); ?>" data-ink-user-menu-trigger data-audit-id="header-user-menu-trigger"><span class="ink-avatar ink-avatar--36 ink-avatar--initials" aria-hidden="true"><?php echo esc_html( $ink_menu_init ); ?></span></button>
					<div class="ink-user-menu__panel" id="ink-user-menu-panel" role="menu" hidden data-ink-user-menu-panel>
						<div class="ink-user-menu__email"><?php echo esc_html( $ink_menu_user->user_email ); ?></div>
						<div class="ink-user-menu__separator" role="separator"></div>
						<a class="ink-user-menu__item" role="menuitem" href="/my-profiel"><?php esc_html_e( 'My profiel', 'ink-foundation' ); ?></a>
						<a class="ink-user-menu__item" role="menuitem" href="/opleiding"><?php esc_html_e( 'Opleiding', 'ink-foundation' ); ?></a>
						<div class="ink-user-menu__separator" role="separator"></div>
						<a class="ink-user-menu__item" role="menuitem" href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Meld af', 'ink-foundation' ); ?></a>
					</div>
				</div>
				<!-- /wp:html -->
			</div>
			<!-- /wp:group -->
<?php else : ?>
		<!-- wp:buttons {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<div data-audit-id="header-actions" class="wp-block-buttons">
			<!-- wp:button {"className":"is-style-ink-ghost ink-btn-sm"} -->
			<div class="wp-block-button is-style-ink-ghost ink-btn-sm"><a data-audit-id="header-btn-signin" class="wp-block-button__link wp-element-button" href="/meld-aan"><?php echo esc_html( ink_foundation_term( 'teken_in', 'Teken in' ) ); ?></a></div>
			<!-- /wp:button -->

			<!-- wp:button {"className":"is-style-ink-primary ink-btn-sm"} -->
			<div class="wp-block-button is-style-ink-primary ink-btn-sm"><a class="wp-block-button__link wp-element-button" data-audit-id="header-btn-join" href="/registreer"><?php echo esc_html( ink_foundation_term( 'sluit_aan', 'Sluit aan' ) ); ?></a></div>
			<!-- /wp:button -->
		</div>
		<!-- /wp:buttons -->
<?php endif; ?>
	</div>
	<!-- /wp:group -->
</div>
<!-- /wp:group -->
