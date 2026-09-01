<?php
/**
 * Title: Skryf
 * Slug: ink-foundation/skryf
 * Categories: ink-foundation
 * Description: Die Skryf-bladsy — die pasgemaakte voorkant-vorm waarmee 'n skrywer 'n gedig, storie of artikel plaas (vervang die Youzify-vorm, Storie 6.1). Tipe-keuse, tellers, opsionele media en uitdaging-skakeling, konsep/plaas, en 'n suksesskerm.
 *
 * Presentation only (three-layer separation). The submittable types, counters,
 * open challenges, the form action, the nonce and the field names ALL come from
 * the ink-core Submission facade via the `ink_foundation_skryf_*` bridges — no
 * submission logic lives in this template, which only renders and escapes. All
 * copy is Afrikaans, human-authored in ui-copy-translations.md ("Skryf-bladsy"),
 * never AI-translated.
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
// Per-type icon (Lucide glyphs, matching the Lovable reference's type-card
// icons exactly — feather/book-open/newspaper): theme presentation only, via
// the shared ink_foundation_icon() convention (§0.9).
$ink_skryf_icons      = array(
	'gedig'   => '<path d="M12.67 19a2 2 0 0 0 1.416-.588l6.154-6.172a6 6 0 0 0-8.49-8.49L5.586 9.914A2 2 0 0 0 5 11.328V18a1 1 0 0 0 1 1z"/><path d="M16 8 2 22"/><path d="M17.5 15H9"/>',
	'storie'  => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
	'artikel' => '<path d="M4 22h16a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2H8a2 2 0 0 0-2 2v16a2 2 0 0 1-2 2Zm0 0a2 2 0 0 1-2-2v-9c0-1.1.9-2 2-2h2"/><path d="M18 14h-8"/><path d="M15 18h-5"/><path d="M10 6h8v4h-8V6Z"/>',
);
$ink_skryf_first_slug = isset( $ink_skryf_types[0]['slug'] ) ? (string) $ink_skryf_types[0]['slug'] : 'gedig';

// Post-plaas success state (display-only; the marker comes from our own redirect).
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only state from our own wp_safe_redirect; values are allowlisted/absint'd and drive no write.
$ink_skryf_notice = isset( $_GET['ink_skryf'] ) && is_scalar( $_GET['ink_skryf'] ) ? sanitize_key( wp_unslash( $_GET['ink_skryf'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only state from our own wp_safe_redirect; values are allowlisted/absint'd and drive no write.
$ink_skryf_done_id = isset( $_GET['id'] ) && is_scalar( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
$ink_skryf_success = ( 'geplaas' === $ink_skryf_notice && $ink_skryf_done_id > 0 && function_exists( 'ink_foundation_skryf_success' ) )
	? ink_foundation_skryf_success( $ink_skryf_done_id )
	: array();
?>
<!-- wp:group {"tagName":"section","align":"full","lock":{"move":true,"remove":true},"style":{"spacing":{"padding":{"top":"var:preset|spacing|s-64","bottom":"var:preset|spacing|s-64","left":"var:preset|spacing|s-24","right":"var:preset|spacing|s-24"}}},"layout":{"type":"constrained","contentSize":"896px"}} -->
<section class="wp-block-group alignfull" style="padding-top:var(--wp--preset--spacing--s-64);padding-right:var(--wp--preset--spacing--s-24);padding-bottom:var(--wp--preset--spacing--s-64);padding-left:var(--wp--preset--spacing--s-24)">
<?php if ( ! empty( $ink_skryf_success ) ) : ?>
	<?php
	$ink_done_label = isset( $ink_skryf_success['type_label'] ) ? (string) $ink_skryf_success['type_label'] : '';
	$ink_done_label = function_exists( 'mb_strtolower' ) ? mb_strtolower( $ink_done_label ) : strtolower( $ink_done_label );
	$ink_done_title = isset( $ink_skryf_success['title'] ) ? (string) $ink_skryf_success['title'] : '';
	?>
	<!-- wp:heading {"level":1,"fontSize":"xxl"} -->
	<h1 class="wp-block-heading has-xxl-font-size"><?php printf( /* translators: %s: bydrae type (gedig/storie/artikel). */ esc_html__( 'Jou %s is gepubliseer', 'ink-foundation' ), esc_html( $ink_done_label ) ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-md-font-size"><?php printf( /* translators: %s: the bydrae title. */ esc_html__( 'Dankie dat jy “%s” gedeel het. Skryf is \'n gesprek — die gemeenskap groei wanneer skrywers mekaar lees en op mekaar reageer.', 'ink-foundation' ), esc_html( $ink_done_title ) ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph {"fontSize":"sm","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-sm-font-size"><?php echo esc_html__( 'Lees en reageer', 'ink-foundation' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:heading {"level":2,"fontSize":"lg"} -->
	<h2 class="wp-block-heading has-lg-font-size"><?php echo esc_html__( 'Gee \'n skrywer vandag \'n hupstoot', 'ink-foundation' ); ?></h2>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"fontSize":"md","textColor":"muted-text"} -->
	<p class="has-muted-text-color has-text-color has-md-font-size"><?php echo esc_html__( '\'n Deurdagte opmerking kan iemand se week verander. Begin met een van hierdie.', 'ink-foundation' ); ?></p>
	<!-- /wp:paragraph -->

	<!-- wp:paragraph -->
	<p><a href="/skryf"><?php echo esc_html__( 'Skryf nog \'n stuk', 'ink-foundation' ); ?></a> · <a href="/"><?php echo esc_html__( 'Terug na tuis', 'ink-foundation' ); ?></a></p>
	<!-- /wp:paragraph -->
<?php else : ?>
	<!-- wp:heading {"level":1,"textAlign":"center","className":"ink-skryf-title"} -->
	<h1 class="wp-block-heading has-text-align-center ink-skryf-title"><?php esc_html_e( 'Deel jou woorde', 'ink-foundation' ); ?></h1>
	<!-- /wp:heading -->

	<!-- wp:paragraph {"align":"center","fontSize":"md","textColor":"muted-text"} -->
	<p class="has-text-align-center has-muted-text-color has-text-color has-md-font-size"><?php esc_html_e( 'Elke storie begin met \'n enkele woord. Begin joune hier.', 'ink-foundation' ); ?></p>
	<!-- /wp:paragraph -->

	<?php if ( ! $ink_skryf_in ) : ?>
	<!-- wp:paragraph {"fontSize":"md"} -->
	<p class="has-md-font-size"><?php echo esc_html__( 'Meld aan om werk te plaas.', 'ink-foundation' ); ?> <a href="/meld-aan"><?php echo esc_html__( 'Meld aan', 'ink-foundation' ); ?></a></p>
	<!-- /wp:paragraph -->
	<?php else : ?>
		<?php if ( 'geen-toegang' === $ink_skryf_notice && function_exists( 'ink_foundation_skryf_denial' ) ) : ?>
	<!-- wp:paragraph {"className":"ink-skryf-denial"} -->
	<p class="ink-skryf-denial"><?php echo esc_html( ink_foundation_skryf_denial() ); ?> <a href="/lidmaatskap"><?php echo esc_html__( 'Sien aansluitingsopsies', 'ink-foundation' ); ?></a></p>
	<!-- /wp:paragraph -->
		<?php endif; ?>
	<!-- wp:html -->
	<form class="ink-skryf-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
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
					<?php if ( isset( $ink_skryf_icons[ $ink_slug ] ) ) : ?>
						<span class="ink-skryf-type__icon ink-skryf-type__icon--<?php echo esc_attr( $ink_slug ); ?>" aria-hidden="true"><?php echo ink_foundation_icon( $ink_skryf_icons[ $ink_slug ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG (§0.9). ?></span>
					<?php endif; ?>
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

		<div class="ink-skryf-field ink-skryf-field--body">
			<label for="ink-skryf-body"><?php echo esc_html__( 'Jou werk', 'ink-foundation' ); ?></label>
			<span class="ink-skryf-counter" data-words-label="<?php echo esc_attr__( 'woorde', 'ink-foundation' ); ?>" data-lines-label="<?php echo esc_attr__( 'reëls', 'ink-foundation' ); ?>" aria-live="polite"></span>
			<textarea id="ink-skryf-body" name="<?php echo esc_attr( $ink_skryf['field_body'] ); ?>" rows="16" placeholder="<?php echo esc_attr( $ink_skryf_ph[ $ink_skryf_first_slug ] ?? '' ); ?>" required></textarea>
		</div>

		<?php
		$ink_skryf_challenges = isset( $ink_skryf['open_challenges'] ) && is_array( $ink_skryf['open_challenges'] ) ? $ink_skryf['open_challenges'] : array();
		if ( ! empty( $ink_skryf_challenges ) ) :
			?>
		<fieldset class="ink-skryf-challenges">
			<legend><?php echo ink_foundation_icon( '<path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.937A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/><path d="M20 3v4"/><path d="M22 5h-4"/><path d="M4 17v2"/><path d="M5 18H3"/>' ) . esc_html__( 'Aktiewe uitdagings (opsioneel)', 'ink-foundation' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ink_foundation_icon() returns trusted, self-escaped inline SVG; the label is esc_html__() (§0.9). ?></legend>
			<p class="ink-skryf-challenges__hint"><?php echo esc_html__( 'Merk enige uitdagings waarop hierdie stuk reageer.', 'ink-foundation' ); ?></p>
			<?php
			foreach ( $ink_skryf_challenges as $ink_ch ) :
				$ink_ch_id    = isset( $ink_ch['id'] ) ? (int) $ink_ch['id'] : 0;
				$ink_ch_title = isset( $ink_ch['title'] ) ? (string) $ink_ch['title'] : '';
				if ( $ink_ch_id <= 0 ) {
					continue;
				}
				?>
				<label class="ink-skryf-challenge">
					<input type="checkbox" name="<?php echo esc_attr( $ink_skryf['field_challenges'] ?? 'ink_submission_uitdagings' ); ?>[]" value="<?php echo esc_attr( (string) $ink_ch_id ); ?>" />
					<span><?php echo esc_html( $ink_ch_title ); ?></span>
				</label>
			<?php endforeach; ?>
		</fieldset>
			<?php
		endif;
		?>

		<p class="ink-skryf-field">
			<label for="ink-skryf-image"><?php echo esc_html__( 'Voorbeeld-prent (opsioneel)', 'ink-foundation' ); ?></label>
			<input type="file" id="ink-skryf-image" name="<?php echo esc_attr( $ink_skryf['field_image'] ?? 'ink_submission_featured_image' ); ?>" accept="image/*" />
		</p>

		<p class="ink-skryf-field">
			<label for="ink-skryf-media"><?php echo esc_html__( 'Klank/video (opsioneel)', 'ink-foundation' ); ?></label>
			<input type="file" id="ink-skryf-media" name="<?php echo esc_attr( $ink_skryf['field_media'] ?? 'ink_submission_media' ); ?>" accept="audio/*,video/*" />
		</p>

		<?php
		if ( function_exists( 'ink_foundation_skryf_form_fields' ) ) {
			ink_foundation_skryf_form_fields();
		}
		?>

		<p class="ink-skryf-actions">
			<button type="submit" name="<?php echo esc_attr( $ink_skryf['intent_field'] ?? 'ink_submission_intent' ); ?>" value="<?php echo esc_attr( $ink_skryf['intent_draft'] ?? 'konsep' ); ?>" class="ink-skryf-draft"><?php echo esc_html__( 'Stoor konsep', 'ink-foundation' ); ?></button>
			<button type="submit" name="<?php echo esc_attr( $ink_skryf['intent_field'] ?? 'ink_submission_intent' ); ?>" value="<?php echo esc_attr( $ink_skryf['intent_publish'] ?? 'plaas' ); ?>" class="ink-skryf-submit"><?php echo esc_html__( 'Plaas', 'ink-foundation' ); ?></button>
		</p>
	</form>
	<!-- /wp:html -->
	<?php endif; ?>
<?php endif; ?>
</section>
<!-- /wp:group -->
