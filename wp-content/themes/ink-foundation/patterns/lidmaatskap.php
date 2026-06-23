<?php
/**
 * Title: Lidmaatskap
 * Slug: ink-foundation/lidmaatskap
 * Categories: ink-foundation, page
 * Block Types: core/post-content
 * Description: Assembly-only Lidmaatskap-blad (Storie 4.4, FR-7): 'n pryslys wat die drie vaste-termyn aansluitingsopsies vergelyk, 'n voordele-afdeling, 'n vrae-afdeling (FAQ) en 'n aankoop-oproep tot aksie. Saamgestel uit bestaande kernblokke + INK-blokstyle.
 *
 * Presentation only (three-layer separation). The plan ROWS — term label, the
 * WooCommerce-resolved price, the sellability flag, and the WC/PayFast purchase
 * URL — are sourced from `ink-core` via the `class_exists`-guarded
 * `ink_foundation_membership_plans()` bridge (which delegates to the
 * `Ink\Entitlement\Api` facade: the 4.1 plan registry + the 4.2 purchase
 * hand-off). The theme iterates the rows it is HANDED and renders them — it
 * computes no plan rule, hardcodes NO price (R60/R300/R600 come from the
 * WooCommerce product, admin-editable), and never re-queries WooCommerce. When
 * `ink-core` is inactive or a plan is not sellable, each slot degrades gracefully
 * (static label, no live price/CTA) — never a fatal, never an invented endpoint.
 *
 * Quality Gate A: every colour/spacing/type value resolves to a theme.json token.
 * Quality Gate D: term labels come from the ink-core terminology registry; the
 * per-plan descriptions, FAQ answers, and CTA prose are NOT yet curated — they are
 * clearly-marked [NEEDS HUMAN AFRIKAANS] placeholders (the rows reserved in
 * docs/ui-copy-translations.md), never invented or lifted from the Lovable mock.
 * NO vanity savings / %-off framing anywhere (FR-4 / Storie 4.1-AC3 / 4.5).
 * Structural wrappers are locked (move/remove) per the Storie 1.6 strategy; the
 * content inside stays editable for non-technical staff (NFR-6).
 */

$ink_plans     = function_exists( 'ink_foundation_membership_plans' ) ? ink_foundation_membership_plans() : array();
$ink_plan_noun = function_exists( 'ink_foundation_term' )
	? ink_foundation_term( 'membership_plan_plural', 'Aansluitingsopsies' )
	: 'Aansluitingsopsies';

// Fail-safe: when ink-core is inactive the bridge returns an empty list. Render the
// three fixed terms as static, price-less, CTA-less slots so the page skeleton still
// reads (the term LABELS are the only INK-held copy; the price/CTA need ink-core).
if ( empty( $ink_plans ) ) {
	$ink_fallback_terms = array(
		1  => array( 'term_1_month', '1 maand' ),
		6  => array( 'term_6_months', '6 maande' ),
		12 => array( 'term_12_months', '12 maande' ),
	);

	$ink_plans = array();

	foreach ( $ink_fallback_terms as $ink_months => $ink_term_keys ) {
		$ink_plans[] = array(
			'months'        => $ink_months,
			'term_label'    => function_exists( 'ink_foundation_term' )
				? ink_foundation_term( $ink_term_keys[0], $ink_term_keys[1] )
				: $ink_term_keys[1],
			'price'         => null,
			'price_display' => null,
			'is_available'  => false,
			'purchase_url'  => null,
		);
	}
}
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-24","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-24);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":1,"fontSize":"3xl"} -->
		<h1 class="wp-block-heading has-3xl-font-size"><?php echo esc_html( $ink_plan_noun ); ?></h1>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"lg","textColor":"muted-text"} -->
		<?php // [NEEDS HUMAN AFRIKAANS] — Lidmaatskap-blad intro-alinea nog nie gekureer in ui-copy-translations.md; menslike kopie nodig. ?>
		<p class="has-muted-text-color has-text-color has-lg-font-size">Kies die toegangstermyn wat jou pas. [NEEDS HUMAN AFRIKAANS]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:columns {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|s-24","left":"var:preset|spacing|s-24"}}}} -->
	<div class="wp-block-columns alignwide">
<?php
foreach ( $ink_plans as $ink_plan ) :
	$ink_term_label    = isset( $ink_plan['term_label'] ) ? (string) $ink_plan['term_label'] : '';
	$ink_price_display = isset( $ink_plan['price_display'] ) ? $ink_plan['price_display'] : null;
	$ink_is_available  = ! empty( $ink_plan['is_available'] );
	$ink_purchase_url  = isset( $ink_plan['purchase_url'] ) ? $ink_plan['purchase_url'] : null;
	?>
		<!-- wp:column {"lock":{"move":true,"remove":true}} -->
		<div class="wp-block-column">
			<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
			<div class="wp-block-group is-style-card">
				<!-- wp:heading {"level":2,"fontSize":"xl"} -->
				<h2 class="wp-block-heading has-xl-font-size"><?php echo esc_html( $ink_term_label ); ?></h2>
				<!-- /wp:heading -->

				<!-- wp:paragraph {"fontSize":"2xl","style":{"typography":{"fontWeight":"var:custom|font-weight|bold"}}} -->
				<p class="has-2xl-font-size" style="font-weight:var(--wp--custom--font-weight--bold)">
			<?php
			if ( null !== $ink_price_display && '' !== (string) $ink_price_display ) {
				// The display price is shaped in the ink-core read-model (PlanPresenter:
				// consistent ZAR `R<amount>.00`) from the WooCommerce runtime value — the
				// theme neither formats nor concatenates the currency symbol; it only
				// escapes the plain string it is handed.
				echo esc_html( (string) $ink_price_display );
			} else {
				// Graceful degrade: WooCommerce absent / plan unmapped / retired — no
				// fallback price literal. Curated placeholder phrasing.
				echo esc_html__( 'Prys binnekort beskikbaar', 'ink-foundation' );
			}
			?>
				</p>
				<!-- /wp:paragraph -->

				<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<?php // [NEEDS HUMAN AFRIKAANS] — lid-gerigte plan-beskrywing per termyn (ui-copy-translations.md "Aansluitingsopsies"-ry); menslike kopie nodig, geen besparingsraam. ?>
				<p class="has-muted-text-color has-text-color has-md-font-size">[NEEDS HUMAN AFRIKAANS] — beskrywing vir hierdie termyn.</p>
				<!-- /wp:paragraph -->

				<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"}} -->
				<div class="wp-block-buttons">
	<?php if ( $ink_is_available && null !== $ink_purchase_url && '' !== (string) $ink_purchase_url ) : ?>
					<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt","width":100,"className":"is-style-pill"} -->
					<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-pill"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( (string) $ink_purchase_url ); ?>"><?php echo esc_html__( 'Sluit aan', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->
	<?php else : ?>
					<!-- wp:button {"backgroundColor":"secondary","textColor":"muted-text","width":100,"className":"is-style-pill"} -->
					<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-pill"><a class="wp-block-button__link has-muted-text-color has-secondary-background-color has-text-color has-background wp-element-button" aria-disabled="true"><?php echo esc_html__( 'Binnekort beskikbaar', 'ink-foundation' ); ?></a></div>
					<!-- /wp:button -->
	<?php endif; ?>
				</div>
				<!-- /wp:buttons -->
			</div>
			<!-- /wp:group -->
		</div>
		<!-- /wp:column -->
<?php endforeach; ?>
	</div>
	<!-- /wp:columns -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size"><?php echo esc_html__( 'Wat jou lidmaatskap insluit', 'ink-foundation' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:list {"className":"is-style-default","style":{"spacing":{"blockGap":"var:preset|spacing|s-12"}}} -->
		<ul class="wp-block-list is-style-default" style="">
			<!-- wp:list-item {"fontSize":"md"} -->
			<li class="has-md-font-size">Jou ondersteuning hou INK advertensievry en onafhanklik.</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"fontSize":"md"} -->
			<?php // [NEEDS HUMAN AFRIKAANS] — verdere voordele nog nie gekureer; menslike kopie nodig. ?>
			<li class="has-md-font-size">[NEEDS HUMAN AFRIKAANS] — verdere voordeel van lidmaatskap.</li>
			<!-- /wp:list-item -->

			<!-- wp:list-item {"fontSize":"md"} -->
			<li class="has-md-font-size">[NEEDS HUMAN AFRIKAANS] — verdere voordeel van lidmaatskap.</li>
			<!-- /wp:list-item -->
		</ul>
		<!-- /wp:list -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-24","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-24);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size"><?php echo esc_html__( 'Vrae oor lidmaatskap', 'ink-foundation' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:details {"lock":{"move":true,"remove":true},"className":"is-style-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}}} -->
		<details class="wp-block-details is-style-card" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)"><summary><?php echo esc_html__( 'Hoe lank duur \'n lidmaatskap?', 'ink-foundation' ); ?></summary>
			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<?php // [NEEDS HUMAN AFRIKAANS] — FAQ-antwoord nog nie gekureer; menslike kopie nodig (moenie KI-vertaal nie). ?>
			<p class="has-muted-text-color has-text-color has-md-font-size">[NEEDS HUMAN AFRIKAANS] — antwoord oor termynlengtes (1 / 6 / 12 maande).</p>
			<!-- /wp:paragraph -->
		</details>
		<!-- /wp:details -->

		<!-- wp:details {"lock":{"move":true,"remove":true},"className":"is-style-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}}} -->
		<details class="wp-block-details is-style-card" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)"><summary><?php echo esc_html__( 'Hernu my lidmaatskap outomaties?', 'ink-foundation' ); ?></summary>
			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<?php // [NEEDS HUMAN AFRIKAANS] — FAQ-antwoord nog nie gekureer; menslike kopie nodig. Let wel: GEEN outo-hernuwing by lansering nie (FR-4). ?>
			<p class="has-muted-text-color has-text-color has-md-font-size">[NEEDS HUMAN AFRIKAANS] — antwoord oor hernuwing (geen outo-hernuwing by lansering).</p>
			<!-- /wp:paragraph -->
		</details>
		<!-- /wp:details -->

		<!-- wp:details {"lock":{"move":true,"remove":true},"className":"is-style-card","style":{"spacing":{"padding":{"top":"var:preset|spacing|s-16","bottom":"var:preset|spacing|s-16","left":"var:preset|spacing|s-16","right":"var:preset|spacing|s-16"}}}} -->
		<details class="wp-block-details is-style-card" style="padding-top:var(--wp--preset--spacing--s-16);padding-right:var(--wp--preset--spacing--s-16);padding-bottom:var(--wp--preset--spacing--s-16);padding-left:var(--wp--preset--spacing--s-16)"><summary><?php echo esc_html__( 'Hoe betaal ek?', 'ink-foundation' ); ?></summary>
			<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
			<?php // [NEEDS HUMAN AFRIKAANS] — FAQ-antwoord nog nie gekureer; menslike kopie nodig (PayFast/ZAR). ?>
			<p class="has-muted-text-color has-text-color has-md-font-size">[NEEDS HUMAN AFRIKAANS] — antwoord oor betaling (PayFast, ZAR).</p>
			<!-- /wp:paragraph -->
		</details>
		<!-- /wp:details -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->

<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"backgroundColor":"secondary","textColor":"text","layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull has-text-color has-secondary-background-color has-background" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group">
		<!-- wp:heading {"textAlign":"center","level":2,"fontSize":"3xl"} -->
		<h2 class="wp-block-heading has-text-align-center has-3xl-font-size"><?php echo esc_html__( 'Sluit vandag by INK aan', 'ink-foundation' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"align":"center","fontSize":"lg","textColor":"muted-text"} -->
		<?php // [NEEDS HUMAN AFRIKAANS] — Lidmaatskap-blad CTA-kopie (ui-copy-translations.md "Aansluitingsopsies"-ry); menslike kopie nodig. ?>
		<p class="has-text-align-center has-muted-text-color has-text-color has-lg-font-size">Kies 'n plan hierbo om jou lidmaatskap te begin. [NEEDS HUMAN AFRIKAANS]</p>
		<!-- /wp:paragraph -->
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
