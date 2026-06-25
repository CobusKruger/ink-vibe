<?php
/**
 * Title: Onboarding
 * Slug: ink-foundation/onboarding
 * Categories: ink-foundation
 * Description: Sagte, oorslaanbare, eenmalige onboarding-skerm na registrasie — profiel voltooi + een eerste sosiale aksie (volg 'n skrywer / stoor 'n bydrae na jou leeslys), wat grasieus degradeer op 'n dun katalogus. Aanbieding alleen; geen besigheidslogika.
 *
 * Presentation only (three-layer separation). All copy is Afrikaans, sentence
 * case, "jy"-voice, human-authored in the approved glossary / ui-copy-translations.md
 * (never invented / AI-translated). All output escaped.
 *
 * Scope: the onboarding SURFACE + first-action PROMPT (a graceful-degrading
 * seam). The follow graph (Story 9.2) and leeslys (Story 7.7) are NOT built here
 * — when the catalogue is thin/empty or those subsystems are absent, the soft /
 * empty state renders and the flow stays skippable. No follow/leeslys table,
 * REST write, or business logic lives in this theme file.
 */

// Presentation glue (graceful when ink-core is inactive). NOT business logic.
$ink_skrywer = function_exists( 'ink_foundation_term' ) ? ink_foundation_term( 'skrywer', 'skrywer' ) : 'skrywer';
$ink_bydrae  = function_exists( 'ink_foundation_term' ) ? ink_foundation_term( 'bydrae', 'bydrae' ) : 'bydrae';
$ink_post_to = function_exists( 'admin_url' ) ? admin_url( 'admin-post.php' ) : '';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"560px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group is-style-card">
		<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
		<h1 class="wp-block-heading has-2xl-font-size"><?php echo esc_html__( 'Welkom by INK', 'ink-foundation' ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size"><?php echo esc_html__( 'Jy is nou \'n gratis lid. Welkom! Vertel ons asseblief meer van jou op die "My Profiel" bladsy.', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":2,"fontSize":"lg"} -->
			<h2 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Voltooi jou profiel', 'ink-foundation' ); ?></h2>
			<!-- /wp:heading -->

			<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
			<p class="has-muted-text-color has-text-color has-sm-font-size"><?php echo esc_html__( 'Gee jou naam en \'n kort beskrywing, sodat ander jou kan leer ken.', 'ink-foundation' ); ?></p>
			<!-- /wp:paragraph -->

			<!-- wp:buttons {"layout":{"type":"constrained"}} -->
			<div class="wp-block-buttons">
				<!-- wp:button {"className":"is-style-outline"} -->
				<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/my-profiel"><?php echo esc_html__( 'Wysig profiel', 'ink-foundation' ); ?></a></div>
				<!-- /wp:button -->
			</div>
			<!-- /wp:buttons -->
		</div>
		<!-- /wp:group -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->

		<!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
		<div class="wp-block-group">
			<!-- wp:heading {"level":2,"fontSize":"lg"} -->
			<h2 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Neem \'n eerste stap', 'ink-foundation' ); ?></h2>
			<!-- /wp:heading -->

			<!--
				First-action PROMPT (graceful-degrading seam). The follow graph
				(Story 9.2) and leeslys (Story 7.7) are NOT built. Until they land,
				the designed soft / empty state renders and the flow stays skippable.
				The approved empty-state copy is from ui-copy-translations.md line 658/659.
			-->
			<!-- wp:paragraph {"fontSize":"md"} -->
			<p class="has-md-font-size"><?php
				/* translators: %s: the singular skrywer label from the glossary. */
				echo esc_html( sprintf( __( 'Volg \'n %s of stoor \'n %2$s na jou leeslys.', 'ink-foundation' ), $ink_skrywer, $ink_bydrae ) );
			?></p>
			<!-- /wp:paragraph -->

			<!-- wp:group {"className":"is-style-emphasis","layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-emphasis">
				<!-- wp:paragraph {"fontSize":"md"} -->
				<p class="has-md-font-size"><?php echo esc_html__( 'Jy volg nog niemand nie', 'ink-foundation' ); ?></p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
				<p class="has-muted-text-color has-text-color has-sm-font-size"><?php
					/* translators: %s: the singular skrywer label from the glossary. */
					echo esc_html( sprintf( __( 'Volg \'n %s om hul nuwe stukke in jou aktiwiteitsvoer te sien.', 'ink-foundation' ), $ink_skrywer ) );
				?></p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"layout":{"type":"constrained"}} -->
				<div class="wp-block-buttons">
					<!-- wp:button {"className":"is-style-outline"} -->
					<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="/skrywers"><?php echo esc_html__( 'Ontdek skrywers', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:group -->

		<!-- wp:separator {"className":"is-style-wide"} -->
		<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>
		<!-- /wp:separator -->

		<!--
			Skip / complete affordance. Both "Slaan oor" and "Klaar" post to the
			ink-core admin-post handler, which sets `ink_onboarding_complete` under
			the nonce + own-record discipline. Completing OR skipping ends the flow;
			it never blocks account usage.
		-->
		<!-- wp:html -->
		<form class="ink-onboarding-actions" method="post" action="<?php echo esc_url( $ink_post_to ); ?>">
			<?php
			if ( function_exists( 'ink_foundation_onboarding_form_fields' ) ) {
				ink_foundation_onboarding_form_fields();
			}
			?>
			<input type="hidden" name="ink_redirect_to" value="<?php echo esc_url( home_url( '/' ) ); ?>" />
			<button type="submit" class="wp-element-button is-style-outline" name="ink_onboarding_choice" value="skip"><?php echo esc_html__( 'Slaan oor', 'ink-foundation' ); ?></button>
			<button type="submit" class="wp-element-button" name="ink_onboarding_choice" value="done"><?php echo esc_html__( 'Klaar', 'ink-foundation' ); ?></button>
		</form>
		<!-- /wp:html -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
