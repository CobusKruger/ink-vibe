<?php
/**
 * Title: My Profiel
 * Slug: ink-foundation/my-profiel
 * Categories: ink-foundation
 * Description: Die private My Profiel-bladsy (Storie 9.4, FR-40): die aangemelde lid se eie kontroleskerm — Gradering + "wins needed"-subteks (privaat), leesgetalle (9.12), die aktiwiteitsvoer van wie hulle volg (9.3), hul leeslys (7.7) en die lidmaatskap-hernuwingsafdeling.
 *
 * PRIVATE surface — the member's OWN dashboard. Unlike the public Skrywerprofiel
 * (the `ink/skrywerprofiel` block on the author template), this is current-user
 * content, so the per-user bridges resolve correctly in pattern PHP (auth is
 * established before `init`) — the same mechanism `lidmaatskap-hernu` relies on.
 *
 * Private-only data lives HERE and nowhere public (FR-40): the "wins needed"
 * subtext (`ink_foundation_gradering_wins_needed`, Story 5.9) and the read-count
 * surface (Story 9.12 fills the reserved slot). The public Skrywerprofiel renders
 * neither.
 *
 * Three-layer: presentation only. The Gradering badge + wins-needed subtext are
 * `class_exists`-guarded `ink-core` reads (display, never a gate). The
 * following-feed, leeslys and lidmaatskap-renewal are existing blocks/patterns,
 * embedded here. Copy is authored Afrikaans (ui-copy-translations.md "My Profiel")
 * via the `ink-foundation` text domain; term labels via the registry. Sentence
 * case; structural wrappers locked (move/remove) per Storie 1.6.
 */

$ink_wins_needed = function_exists( 'ink_foundation_gradering_wins_needed' )
	? ink_foundation_gradering_wins_needed()
	: '';
$ink_badge       = function_exists( 'ink_foundation_gradering_badge' )
	? ink_foundation_gradering_badge()
	: '';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-32"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size"><?php echo esc_html__( 'My profiel', 'ink-foundation' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:group {"className":"is-style-card ink-my-profiel__gradering","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-8"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group is-style-card ink-my-profiel__gradering">
			<!-- wp:heading {"level":2,"fontSize":"lg"} -->
			<h2 class="wp-block-heading has-lg-font-size"><?php echo esc_html( ink_foundation_term( 'gradering', 'Gradering' ) ); ?></h2>
			<!-- /wp:heading -->
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

		<?php // Story 9.12 (R8): the private per-bydrae read-count surface renders into this slot. ?>
		<!-- wp:group {"className":"ink-my-profiel__leesgetalle","lock":{"move":true,"remove":true},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group ink-my-profiel__leesgetalle" data-ink-slot="leesgetalle"></div>
		<!-- /wp:group -->

		<?php // Story 9.5: pin / unpin your own works (curation). ?>
		<!-- wp:ink/vasgespel-bestuur /-->

		<?php // Story 9.3: the following-feed (Aktiwiteit van wie jy volg). ?>
		<!-- wp:ink/volg-voer /-->

		<?php // Story 7.7: the member's leeslys (saved works). ?>
		<!-- wp:ink/leeslys /-->

		<?php // Story 4.5 / 9.4: the lidmaatskap renewal section (supersedes the interim host). ?>
		<!-- wp:pattern {"slug":"ink-foundation/lidmaatskap-hernu"} /-->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
