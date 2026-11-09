<?php
/**
 * Member email preferences (social-notification opt-outs).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Per-member opt-out of SOCIAL notification emails, by category.
 *
 * Storage is user meta holding the opted-out category slugs only (default = all
 * on). System events are never listed here and never honour it. Two surfaces:
 *  - the form on My Profiel → Kennisgewings ({@see formHtml()} → `admin-post.php`);
 *  - a signed one-click unsubscribe link in every social email's footer.
 *
 * @package Ink\Core
 */
final class Preferences {

	public const META        = 'ink_notif_optout';
	public const ACTION_SAVE = 'ink_notif_prefs';
	public const NONCE       = 'ink_notif_prefs_nonce';
	public const QUERY_UNSUB = 'ink_unsub';

	/**
	 * Wire the profile-form handler and the unsubscribe-link handler.
	 *
	 * @param TemplateStore $store Source of the opt-out categories.
	 */
	public function __construct( private readonly TemplateStore $store ) {}

	/**
	 * Register the hooks.
	 */
	public function register(): void {
		add_action( 'admin_post_' . self::ACTION_SAVE, array( $this, 'handleSave' ) );
		add_action( 'wp_loaded', array( $this, 'handleUnsubscribe' ) );
	}

	/**
	 * The category slugs $user_id has opted out of.
	 *
	 * @return list<string>
	 */
	public static function optOuts( int $user_id ): array {
		$stored = get_user_meta( $user_id, self::META, true );

		return is_array( $stored ) ? array_values( array_map( 'strval', $stored ) ) : array();
	}

	/**
	 * Whether $user_id has opted out of $category.
	 */
	public static function isOptedOut( int $user_id, string $category ): bool {
		return '' !== $category && in_array( $category, self::optOuts( $user_id ), true );
	}

	/**
	 * Replace the member's opted-out categories.
	 *
	 * @param list<string> $categories Opted-out category slugs.
	 */
	public static function save( int $user_id, array $categories ): void {
		update_user_meta( $user_id, self::META, array_values( array_unique( array_map( 'strval', $categories ) ) ) );
	}

	/**
	 * HMAC token proving an unsubscribe link was issued by us.
	 */
	public static function token( int $user_id, string $category ): string {
		return hash_hmac( 'sha256', $user_id . '|' . $category, wp_salt( 'auth' ) );
	}

	/**
	 * The one-click unsubscribe URL for a user + category.
	 */
	public static function unsubscribeUrl( int $user_id, string $category ): string {
		return add_query_arg(
			array(
				self::QUERY_UNSUB => $category,
				'u'               => $user_id,
				't'               => self::token( $user_id, $category ),
			),
			home_url( '/' )
		);
	}

	/**
	 * The footer line carrying the unsubscribe link (trusted, escaped HTML).
	 *
	 * @param int    $user_id  The recipient.
	 * @param string $category Opt-out category slug.
	 * @param string $label    Category label (Afrikaans).
	 */
	public static function footerHtml( int $user_id, string $category, string $label ): string {
		return sprintf(
			/* translators: 1: unsubscribe URL, 2: category label. */
			__( 'Jy ontvang hierdie e-pos omdat jy in aanmerking kom vir kennisgewings oor: %2$s. <a href="%1$s">Hou op om hierdie e-posse te ontvang</a>.', 'ink-core' ),
			esc_url( self::unsubscribeUrl( $user_id, $category ) ),
			esc_html( '' !== $label ? $label : $category )
		);
	}

	/**
	 * The "E-poskennisgewings" form for the My Profiel Kennisgewings tab.
	 *
	 * @param int $user_id The logged-in member.
	 * @return string Escaped HTML ('' when no social events exist).
	 */
	public function formHtml( int $user_id ): string {
		$categories = $this->store->socialCategories();

		if ( array() === $categories || $user_id <= 0 ) {
			return '';
		}

		$opted_out = self::optOuts( $user_id );
		$saved     = isset( $_GET['ink_notif_ok'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag.

		// Same panel recipe as the other My Profiel tabs: h2-block heading + muted intro,
		// then a card holding the shared `ink-checkbox` rows and the primary button.
		$html  = '<form class="ink-epos-voorkeure" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-audit-id="profiel-epos-voorkeure">';
		$html .= '<div class="ink-epos-voorkeure__kop">'
			. '<h2 class="ink-heading-h2-block ink-epos-voorkeure__titel">' . esc_html__( 'E-poskennisgewings', 'ink-core' ) . '</h2>'
			. '<p class="ink-epos-voorkeure__intro">' . esc_html__( 'Kies wanneer ons vir jou e-pos stuur. E-posse oor jou rekening, lidmaatskap en uitslae word altyd gestuur.', 'ink-core' ) . '</p>'
			. '</div>';
		$html .= '<input type="hidden" name="action" value="' . esc_attr( self::ACTION_SAVE ) . '">';
		$html .= wp_nonce_field( self::NONCE, '_wpnonce', true, false );
		$html .= '<div class="ink-card ink-epos-voorkeure__kaart">';

		foreach ( $categories as $slug => $label ) {
			$html .= '<label class="ink-epos-voorkeure__ry"><input class="ink-checkbox" type="checkbox" name="ink_notif[]" value="' . esc_attr( $slug ) . '"'
				. ( in_array( $slug, $opted_out, true ) ? '' : ' checked' ) . '> '
				. esc_html( $label ) . '</label>';
		}

		$html .= '<div class="ink-epos-voorkeure__aksies"><button type="submit" class="ink-btn ink-btn--primary">' . esc_html__( 'Stoor voorkeure', 'ink-core' ) . '</button>';
		$html .= $saved ? '<span class="ink-epos-voorkeure__status" role="status">' . esc_html__( 'Gestoor.', 'ink-core' ) . '</span>' : '';
		$html .= '</div></div></form>';

		return $html;
	}

	/**
	 * `admin-post.php` handler: store the member's own preferences.
	 */
	public function handleSave(): void {
		$user_id = get_current_user_id();

		if ( $user_id <= 0 ) {
			wp_die( esc_html__( 'Meld asseblief aan.', 'ink-core' ), '', array( 'response' => 403 ) );
		}

		check_admin_referer( self::NONCE );

		$wanted = array();

		if ( isset( $_POST['ink_notif'] ) && is_array( $_POST['ink_notif'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified above.
			foreach ( wp_unslash( $_POST['ink_notif'] ) as $slug ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified above; each value sanitised below.
				if ( is_scalar( $slug ) ) {
					$wanted[] = sanitize_key( (string) $slug );
				}
			}
		}

		// Opted-out = every offered category the member left unticked.
		self::save( $user_id, array_values( array_diff( array_keys( $this->store->socialCategories() ), $wanted ) ) );

		$back = wp_get_referer();
		$back = false !== $back ? $back : home_url( '/' );

		wp_safe_redirect( add_query_arg( 'ink_notif_ok', '1', remove_query_arg( 'ink_notif_ok', $back ) ) . '#kennisgewings' );
		exit;
	}

	/**
	 * Handle a one-click unsubscribe link (`?ink_unsub=<category>&u=<id>&t=<token>`).
	 */
	public function handleUnsubscribe(): void {
		if ( ! isset( $_GET[ self::QUERY_UNSUB ], $_GET['u'], $_GET['t'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- authenticated by the signed token below.
			return;
		}

		$category = sanitize_key( wp_unslash( (string) $_GET[ self::QUERY_UNSUB ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$user_id  = absint( wp_unslash( (string) $_GET['u'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$token    = sanitize_text_field( wp_unslash( (string) $_GET['t'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$categories = $this->store->socialCategories();

		if ( $user_id <= 0 || ! isset( $categories[ $category ] ) || ! hash_equals( self::token( $user_id, $category ), $token ) ) {
			wp_die( esc_html__( 'Hierdie skakel is nie geldig nie.', 'ink-core' ), '', array( 'response' => 400 ) );
		}

		self::save( $user_id, array_merge( self::optOuts( $user_id ), array( $category ) ) );

		wp_die(
			esc_html(
				sprintf(
					/* translators: %s: category label. */
					__( 'Klaar. Jy sal nie meer e-posse kry oor: %s nie. Jy kan dit enige tyd weer aanskakel op My Profiel.', 'ink-core' ),
					$categories[ $category ]
				)
			),
			esc_html__( 'Uitgeskryf', 'ink-core' ),
			array( 'response' => 200 )
		);
	}
}
