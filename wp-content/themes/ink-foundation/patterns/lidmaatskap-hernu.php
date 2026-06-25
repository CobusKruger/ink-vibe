<?php
/**
 * Title: Lidmaatskap — hernieu
 * Slug: ink-foundation/lidmaatskap-hernu
 * Categories: ink-foundation
 * Description: Hernuwings-afdeling vir die My Profiel → Lidmaatskap-blad (Storie 4.5, FR-8): 'n aangemelde lid kies 'n vaste termyn (1 / 6 / 12 maande) om sy lidmaatskap te hernieu (toegang te verleng) deur 'n verdere vaste termyn via PayFast aan te koop. Saamgestel uit bestaande kernblokke + INK-blokstyle.
 *
 * Presentation only (three-layer separation). The renewal ROWS — term label, the
 * WooCommerce-resolved price, the sellability flag, and the WC/PayFast purchase URL
 * (the RENEW CTA target) — are sourced from `ink-core` via the `class_exists`-guarded
 * `ink_foundation_renewal_plans()` bridge, which delegates to the `Ink\Entitlement\Api`
 * facade. `Api::renewalRows()` REUSES the Story 4.4 `PlanPresenter` row shaping (the
 * 4.1 plan registry + the 4.2 purchase hand-off) — this story DUPLICATES no plan/price
 * logic. The theme iterates the rows it is HANDED and renders them; it computes no plan
 * rule, hardcodes NO price (R60/R300/R600 come from the WooCommerce product,
 * admin-editable), and never re-queries WooCommerce.
 *
 * "Renew" at launch IS the manual fixed-term purchase flow: the per-term renew CTA links
 * to the 4.2 PayFast checkout for that term (renew = buy another fixed term). There is
 * NO auto-renew / recurring affordance (Stories 4.9–4.11 are post-launch) and NO vanity
 * discount/savings/%-off framing anywhere (FR-4 / Storie 4.1-AC3 / 4.5).
 *
 * A `class_exists`/`function_exists`-guarded logged-in gate
 * (`ink_foundation_is_member_logged_in()`) shows the renew options only to a logged-in
 * lid; a "Meld aan om te hernieu" fallback shows otherwise. When `ink-core`/WooCommerce is
 * inactive or a plan is not sellable, each slot degrades gracefully (static label, no
 * live price/CTA) — never a fatal, never an invented endpoint.
 *
 * The My Profiel / Epic-9 boundary: this is a SELF-CONTAINED, REUSABLE section. The My
 * Profiel container (the page + tab framework) is Epic 9 (Story 9.4) + BuddyPress config
 * (Story 9.1) — NOT this story. Story 9.4 will embed this `ink-foundation/lidmaatskap-hernu`
 * slug as the Lidmaatskap tab; until then it is hosted on the interim
 * `page-my-profiel-lidmaatskap` template.
 *
 * Quality Gate A: every colour/spacing/type value resolves to a theme.json token.
 * Quality Gate D: term labels come from the ink-core terminology registry; the heading,
 * intro and renew-button copy are CURATED Afrikaans (ui-copy-translations.md "Lidmaatskap-blad"
 * My Profiel subsection), rendered via the `ink-foundation` text domain so the leak scan
 * catches them. Sentence case.
 * Structural wrappers are locked (move/remove) per Storie 1.6; content inside stays editable (NFR-6).
 */

$ink_renewal_plans    = function_exists( 'ink_foundation_renewal_plans' ) ? ink_foundation_renewal_plans() : array();
$ink_member_logged_in = function_exists( 'ink_foundation_is_member_logged_in' ) ? ink_foundation_is_member_logged_in() : false;

// Fail-safe: when ink-core is inactive the bridge returns an empty list. Render the
// three fixed terms as static, price-less, CTA-less slots so the section skeleton still
// reads (the term LABELS are the only INK-held copy; the price/CTA need ink-core).
if ( empty( $ink_renewal_plans ) ) {
	$ink_fallback_terms = array(
		1  => array( 'term_1_month', '1 maand' ),
		6  => array( 'term_6_months', '6 maande' ),
		12 => array( 'term_12_months', '12 maande' ),
	);

	$ink_renewal_plans = array();

	foreach ( $ink_fallback_terms as $ink_months => $ink_term_keys ) {
		$ink_renewal_plans[] = array(
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
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-48","bottom":"var:preset|spacing|s-48","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-48);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-48);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:group {"align":"wide","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-24"}},"layout":{"type":"constrained"}} -->
	<div class="wp-block-group alignwide">
		<!-- wp:heading {"level":2,"fontSize":"2xl"} -->
		<h2 class="wp-block-heading has-2xl-font-size"><?php echo esc_html__( 'Hernieu lidmaatskap', 'ink-foundation' ); ?></h2>
		<!-- /wp:heading -->

		<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
		<p class="has-muted-text-color has-text-color has-md-font-size"><?php echo esc_html__( 'Kies hoe lank jy jou INK-lidmaatskap wil verleng.', 'ink-foundation' ); ?></p>
		<!-- /wp:paragraph -->
<?php if ( $ink_member_logged_in ) : ?>
		<!-- wp:columns {"lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":{"top":"var:preset|spacing|s-24","left":"var:preset|spacing|s-24"}}}} -->
		<div class="wp-block-columns">
	<?php
	foreach ( $ink_renewal_plans as $ink_plan ) :
		$ink_months        = isset( $ink_plan['months'] ) ? (int) $ink_plan['months'] : 0;
		$ink_term_label    = isset( $ink_plan['term_label'] ) ? (string) $ink_plan['term_label'] : '';
		$ink_price_display = isset( $ink_plan['price_display'] ) ? $ink_plan['price_display'] : null;
		$ink_is_available  = ! empty( $ink_plan['is_available'] );
		$ink_purchase_url  = isset( $ink_plan['purchase_url'] ) ? $ink_plan['purchase_url'] : null;

		// Curated renew-button copy "Hernieu vir [N] maand(e)" — N from the row's term length,
		// singular/plural via _n() (the curated maand/maande forms). No price/savings in it.
		$ink_renew_label = sprintf(
		/* translators: %d: number of months in the chosen renewal term. */
			_n( 'Hernieu vir %d maand', 'Hernieu vir %d maande', $ink_months, 'ink-foundation' ),
			$ink_months
		);
		?>
			<!-- wp:column {"lock":{"move":true,"remove":true}} -->
			<div class="wp-block-column">
				<!-- wp:group {"className":"is-style-card","lock":{"move":true,"remove":true},"style":{"spacing":{"blockGap":"var:preset|spacing|s-16"}},"layout":{"type":"constrained"}} -->
				<div class="wp-block-group is-style-card">
					<!-- wp:heading {"level":3,"fontSize":"xl"} -->
					<h3 class="wp-block-heading has-xl-font-size"><?php echo esc_html( $ink_term_label ); ?></h3>
					<!-- /wp:heading -->

					<!-- wp:paragraph {"fontSize":"2xl","style":{"typography":{"fontWeight":"var:custom|font-weight|bold"}}} -->
					<p class="has-2xl-font-size" style="font-weight:var(--wp--custom--font-weight--bold)">
			<?php
			if ( null !== $ink_price_display && '' !== (string) $ink_price_display ) {
				// The display price is shaped in the ink-core read-model (consistent ZAR
				// R<amount>.00) from the WooCommerce runtime value — the theme neither
				// formats nor concatenates the currency symbol; it escapes the plain string.
				echo esc_html( (string) $ink_price_display );
			} else {
				// Graceful degrade: WooCommerce absent / plan unmapped / retired — no
				// fallback price literal. Curated placeholder phrasing.
				echo esc_html__( 'Prys binnekort beskikbaar', 'ink-foundation' );
			}
			?>
					</p>
					<!-- /wp:paragraph -->

					<!-- wp:buttons {"lock":{"move":true,"remove":true},"layout":{"type":"flex","justifyContent":"center"}} -->
					<div class="wp-block-buttons">
		<?php if ( $ink_is_available && null !== $ink_purchase_url && '' !== (string) $ink_purchase_url ) : ?>
						<!-- wp:button {"backgroundColor":"primary","textColor":"surface-alt","width":100,"className":"is-style-pill"} -->
						<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-pill"><a class="wp-block-button__link has-surface-alt-color has-primary-background-color has-text-color has-background wp-element-button" href="<?php echo esc_url( (string) $ink_purchase_url ); ?>"><?php echo esc_html( $ink_renew_label ); ?></a></div>
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
<?php else : ?>
		<!-- wp:paragraph {"fontSize":"md"} -->
		<p class="has-md-font-size"><a href="<?php echo esc_url( home_url( '/meld-aan' ) ); ?>"><?php echo esc_html__( 'Meld aan om jou lidmaatskap te hernieu.', 'ink-foundation' ); ?></a></p>
		<!-- /wp:paragraph -->
<?php endif; ?>
	</div>
	<!-- /wp:group -->
</section>
<!-- /wp:group -->
