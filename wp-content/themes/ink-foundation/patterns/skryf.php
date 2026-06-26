<?php
/**
 * Title: Skryf
 * Slug: ink-foundation/skryf
 * Categories: ink-foundation
 * Description: Die Skryf-bladsy — die pasgemaakte voorkant-vorm waarmee 'n skrywer 'n gedig, storie of artikel plaas (vervang die Youzify-vorm, Storie 6.1). Tipe-keuse, titel en inhoud; die konsep word gestoor sonder lidmaatskapkontrole (publiseer + die hek kom in 6.7/6.8).
 *
 * Presentation only (three-layer separation). The submittable types, the form
 * action, the nonce and the field names ALL come from the ink-core Submission
 * facade via the `ink_foundation_skryf_*` bridges — no submission logic lives in
 * this template, which only renders and escapes. All copy is Afrikaans,
 * human-authored in ui-copy-translations.md ("Skryf-bladsy"), never AI-translated.
 */

$ink_skryf       = function_exists( 'ink_foundation_skryf_model' ) ? ink_foundation_skryf_model() : array();
$ink_skryf_types = isset( $ink_skryf['types'] ) && is_array( $ink_skryf['types'] ) ? $ink_skryf['types'] : array();
$ink_skryf_in    = function_exists( 'ink_foundation_is_member_logged_in' ) && ink_foundation_is_member_logged_in();

// Per-type supporting descriptions + body placeholders (theme presentation copy).
$ink_skryf_desc       = array(
	'gedig'   => __( 'Druk emosies uit deur vers, ritme en beelding.', 'ink-foundation' ),
	'storie'  => __( 'Skep \'n vertelling met karakters, intrige en betekenis.', 'ink-foundation' ),
	'artikel' => __( 'Deel \'n essay, besinning of joernalistieke stuk.', 'ink-foundation' ),
);
$ink_skryf_ph         = array(
	'gedig'   => __( "Begin jou gedig hier...\n\nWenk: Gebruik reëlbreuke om jou verse te struktureer.", 'ink-foundation' ),
	'storie'  => __( "Begin jou storie hier...\n\nWenk: Kortverhale is gewoonlik tussen 1 000 en 7 500 woorde.", 'ink-foundation' ),
	'artikel' => __( "Begin met 'n sterk openingsreël...\n\nWenk: Begin met die idee, grond dit dan in 'n storie.", 'ink-foundation' ),
);
$ink_skryf_first_slug = isset( $ink_skryf_types[0]['slug'] ) ? (string) $ink_skryf_types[0]['slug'] : 'gedig';
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
	<!-- wp:heading {"level":1,"fontSize":"2xl"} -->
	<h1 class="wp-block-heading has-2xl-font-size">Deel jou woorde</h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-md-font-size">Elke storie begin met 'n enkele woord. Begin joune hier.</p>
	<!-- /wp:paragraph -->

<?php if ( ! $ink_skryf_in ) : ?>
	<!-- wp:paragraph {"fontSize":"md"} -->
	<p class="has-md-font-size"><?php echo esc_html__( 'Meld aan om werk te plaas.', 'ink-foundation' ); ?> <a href="/meld-aan"><?php echo esc_html__( 'Meld aan', 'ink-foundation' ); ?></a></p>
	<!-- /wp:paragraph -->
<?php else : ?>
	<!-- wp:html -->
	<form class="ink-skryf-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<fieldset class="ink-skryf-types">
			<legend><?php echo esc_html__( 'Kies \'n tipe', 'ink-foundation' ); ?></legend>
			<?php
			$ink_first = true;
			foreach ( $ink_skryf_types as $ink_type ) :
				$ink_slug  = isset( $ink_type['slug'] ) ? (string) $ink_type['slug'] : '';
				$ink_label = isset( $ink_type['label'] ) ? (string) $ink_type['label'] : $ink_slug;
				if ( '' === $ink_slug ) {
					continue;
				}
				?>
				<label class="ink-skryf-type">
					<input type="radio" name="<?php echo esc_attr( $ink_skryf['field_type'] ); ?>" value="<?php echo esc_attr( $ink_slug ); ?>"<?php echo $ink_first ? ' checked' : ''; ?> data-counter-mode="<?php echo esc_attr( isset( $ink_type['counter_mode'] ) ? (string) $ink_type['counter_mode'] : 'words' ); ?>" data-placeholder="<?php echo esc_attr( $ink_skryf_ph[ $ink_slug ] ?? '' ); ?>" />
					<span class="ink-skryf-type__label"><?php echo esc_html( $ink_label ); ?></span>
					<?php if ( isset( $ink_skryf_desc[ $ink_slug ] ) ) : ?>
						<span class="ink-skryf-type__desc"><?php echo esc_html( $ink_skryf_desc[ $ink_slug ] ); ?></span>
					<?php endif; ?>
				</label>
				<?php
				$ink_first = false;
			endforeach;
			?>
		</fieldset>

		<p class="ink-skryf-field">
			<label for="ink-skryf-title"><?php echo esc_html__( 'Titel', 'ink-foundation' ); ?></label>
			<input type="text" id="ink-skryf-title" name="<?php echo esc_attr( $ink_skryf['field_title'] ); ?>" placeholder="<?php echo esc_attr__( 'Gee jou werk \'n titel...', 'ink-foundation' ); ?>" required />
		</p>

		<p class="ink-skryf-field">
			<label for="ink-skryf-body"><?php echo esc_html__( 'Jou werk', 'ink-foundation' ); ?></label>
			<textarea id="ink-skryf-body" name="<?php echo esc_attr( $ink_skryf['field_body'] ); ?>" rows="16" placeholder="<?php echo esc_attr( $ink_skryf_ph[ $ink_skryf_first_slug ] ?? '' ); ?>" required></textarea>
			<span class="ink-skryf-counter" data-words-label="<?php echo esc_attr__( 'woorde', 'ink-foundation' ); ?>" data-lines-label="<?php echo esc_attr__( 'reëls', 'ink-foundation' ); ?>" aria-live="polite"></span>
		</p>

		<?php
		if ( function_exists( 'ink_foundation_skryf_form_fields' ) ) {
			ink_foundation_skryf_form_fields();
		}
		?>

		<p class="ink-skryf-actions">
			<button type="submit" class="ink-skryf-submit"><?php echo esc_html__( 'Stoor konsep', 'ink-foundation' ); ?></button>
		</p>
	</form>
	<!-- /wp:html -->
<?php endif; ?>
</section>
<!-- /wp:group -->
