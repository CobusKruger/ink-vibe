<?php
/**
 * E-pos admin screen: edit the copy of every registered email event.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * Settings → "INK e-posse". Lists every event registered in code (grouped) and
 * lets an administrator edit its subject, basic-HTML body, on/off switch and
 * per-term switches; send a test to themselves; or reset it to the code default.
 *
 * Events themselves are defined in code ({@see Template}); this screen never adds
 * or removes one. Writes are `manage_options` + nonce gated; bodies are run
 * through `wp_kses` with {@see Mailer::allowedHtml()}.
 *
 * @package Ink\Core
 */
final class AdminPage {

	public const SLUG  = 'ink-eposse';
	public const NONCE = 'ink_eposse_nonce';

	/**
	 * Wire the screen to the store and notifier.
	 *
	 * @param TemplateStore $store    The template store.
	 * @param Notifier      $notifier The notifier (test sends).
	 * @param SendLog       $log      The send log (display).
	 */
	public function __construct(
		private readonly TemplateStore $store,
		private readonly Notifier $notifier,
		private readonly SendLog $log,
	) {}

	/**
	 * Register the screen.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerScreen' ) );
	}

	/**
	 * Add the Settings submenu.
	 */
	public function registerScreen(): void {
		add_options_page(
			__( 'INK e-posse', 'ink-core' ),
			__( 'INK e-posse', 'ink-core' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render (and handle the POST of) the screen.
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Jy het nie toegang tot hierdie bladsy nie.', 'ink-core' ), '', array( 'response' => 403 ) );
		}

		$notice = '';

		if ( 'POST' === sanitize_text_field( wp_unslash( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) ) {
			check_admin_referer( self::NONCE );
			$notice = $this->handlePost();
		}

		echo '<div class="wrap"><h1>' . esc_html__( 'INK e-posse', 'ink-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'Hierdie e-posse word deur die webwerf gestuur. Jy kan die teks verander; nuwe e-posse word deur die ontwikkelaar bygevoeg. Gebruik die kodes tussen krulhakies (soos {skrywer}) waar die lid se besonderhede ingevul moet word.', 'ink-core' ) . '</p>';

		if ( '' !== $notice ) {
			echo '<div class="notice notice-success"><p>' . esc_html( $notice ) . '</p></div>';
		}

		echo '<form method="post">';
		wp_nonce_field( self::NONCE );
		// First submit button in the form = the Enter-key default, so Enter saves rather than test-sends.
		echo '<p><button type="submit" class="button button-primary" name="ink_action" value="save">' . esc_html__( 'Stoor alles', 'ink-core' ) . '</button></p>';

		$groups = array();

		foreach ( $this->store->all() as $template ) {
			$groups[ '' !== $template->group ? $template->group : __( 'Ander', 'ink-core' ) ][] = $template;
		}

		ksort( $groups );

		foreach ( $groups as $group => $templates ) {
			echo '<h2>' . esc_html( (string) $group ) . '</h2>';

			foreach ( $templates as $template ) {
				$this->renderEvent( $template );
			}
		}

		echo '<p><button type="submit" class="button button-primary" name="ink_action" value="save">' . esc_html__( 'Stoor alles', 'ink-core' ) . '</button></p>';
		echo '</form>';

		$this->renderLog();
		echo '</div>';
	}

	/**
	 * Render one event's editor.
	 */
	private function renderEvent( Template $template ): void {
		$key   = $template->key;
		$name  = 'ev[' . $key . ']';
		$label = '' !== $template->label ? $template->label : $key;
		$kind  = $template->isSocial()
			? __( 'Sosiaal (lid kan uitskryf)', 'ink-core' )
			: __( 'Stelsel (word altyd gestuur)', 'ink-core' );

		echo '<div class="postbox" style="padding:12px 16px;max-width:900px;">';
		echo '<h3 style="margin-top:0;">' . esc_html( $label ) . ' <small style="font-weight:normal;color:#666;">— ' . esc_html( $kind ) . '</small></h3>';

		echo '<p><label><input type="checkbox" name="' . esc_attr( $name ) . '[enabled]" value="1"' . checked( $this->store->isEnabled( $key ), true, false ) . '> '
			. esc_html__( 'Stuur hierdie e-pos', 'ink-core' ) . '</label></p>';

		if ( array() !== $template->terms ) {
			echo '<p>' . esc_html__( 'Stuur vir lidmaatskap-termyne:', 'ink-core' ) . ' ';

			foreach ( $template->terms as $months ) {
				echo '<label style="margin-right:12px;"><input type="checkbox" name="' . esc_attr( $name ) . '[terms][' . (int) $months . ']" value="1"'
					. checked( $this->store->isTermEnabled( $key, (int) $months ), true, false ) . '> '
					/* translators: %d: number of months. */
					. esc_html( sprintf( _n( '%d maand', '%d maande', (int) $months, 'ink-core' ), (int) $months ) ) . '</label>';
			}

			echo '</p>';
		}

		echo '<p><label>' . esc_html__( 'Onderwerp', 'ink-core' ) . '<br><input type="text" class="large-text" name="' . esc_attr( $name ) . '[subject]" value="' . esc_attr( $this->store->subject( $key ) ) . '"></label></p>';
		echo '<p><label>' . esc_html__( 'Boodskap (eenvoudige HTML: p, br, a, strong, em, ul, ol, li)', 'ink-core' ) . '<br><textarea class="large-text code" rows="7" name="' . esc_attr( $name ) . '[body]">' . esc_textarea( $this->store->body( $key ) ) . '</textarea></label></p>';

		if ( array() !== $template->tokens ) {
			echo '<p class="description">' . esc_html__( 'Beskikbare kodes:', 'ink-core' ) . ' <code>{' . implode( '}</code> <code>{', array_map( 'esc_html', $template->tokens ) ) . '}</code></p>';
		}

		echo '<p>';
		echo '<button type="submit" class="button" name="ink_action" value="test|' . esc_attr( $key ) . '">' . esc_html__( 'Stuur toets-e-pos aan my', 'ink-core' ) . '</button> ';
		echo '<button type="submit" class="button" name="ink_action" value="reset|' . esc_attr( $key ) . '" onclick="return confirm(\'' . esc_js( __( 'Herstel na die verstek-teks?', 'ink-core' ) ) . '\');">' . esc_html__( 'Herstel verstek', 'ink-core' ) . '</button>';
		echo '</p></div>';
	}

	/**
	 * Apply the POST: save changed fields, then run a test-send / reset if asked.
	 *
	 * @return string Notice text ('' when nothing to report).
	 */
	private function handlePost(): string {
		$action = isset( $_POST['ink_action'] ) && is_scalar( $_POST['ink_action'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['ink_action'] ) ) : 'save'; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- verified in render().
		$parts  = explode( '|', $action, 2 );
		$verb   = $parts[0];
		$target = $parts[1] ?? '';

		$posted = isset( $_POST['ev'] ) && is_array( $_POST['ev'] ) ? wp_unslash( $_POST['ev'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- verified in render(); each field sanitised in saveEvent().

		foreach ( $this->store->all() as $key => $template ) {
			if ( 'reset' === $verb && $key === $target ) {
				continue; // The reset discards this event's edits.
			}

			$fields = isset( $posted[ $key ] ) && is_array( $posted[ $key ] ) ? $posted[ $key ] : array();
			$this->saveEvent( $template, $fields );
		}

		if ( 'reset' === $verb && $this->store->isRegistered( $target ) ) {
			$this->store->reset( $target );

			return __( 'Verstek-teks herstel.', 'ink-core' );
		}

		if ( 'test' === $verb && $this->store->isRegistered( $target ) ) {
			$user = wp_get_current_user();
			$ok   = $this->notifier->preview( $target, (string) $user->user_email, $this->sampleContext( $this->store->get( $target ) ) );

			return $ok
				? sprintf(
					/* translators: %s: email address. */
					__( 'Toets-e-pos gestuur aan %s.', 'ink-core' ),
					(string) $user->user_email
				)
				: __( 'Die toets-e-pos kon nie gestuur word nie.', 'ink-core' );
		}

		return __( 'Gestoor.', 'ink-core' );
	}

	/**
	 * Persist only the values that differ from what is currently effective, so an
	 * untouched event keeps following its code defaults.
	 *
	 * @param Template            $template The event.
	 * @param array<string,mixed> $fields   Posted fields for it.
	 */
	private function saveEvent( Template $template, array $fields ): void {
		$key = $template->key;

		$subject = isset( $fields['subject'] ) && is_scalar( $fields['subject'] ) ? sanitize_text_field( (string) $fields['subject'] ) : null;
		$body    = isset( $fields['body'] ) && is_scalar( $fields['body'] ) ? wp_kses( (string) $fields['body'], Mailer::allowedHtml() ) : null;

		if ( null !== $subject && $subject !== $this->store->subject( $key ) ) {
			$this->store->setSubject( $key, $subject );
		}

		if ( null !== $body && $body !== $this->store->body( $key ) ) {
			$this->store->setBody( $key, $body );
		}

		$enabled = ! empty( $fields['enabled'] );

		if ( $enabled !== $this->store->isEnabled( $key ) ) {
			$this->store->setEnabled( $key, $enabled );
		}

		$terms = isset( $fields['terms'] ) && is_array( $fields['terms'] ) ? $fields['terms'] : array();

		foreach ( $template->terms as $months ) {
			$on = ! empty( $terms[ $months ] );

			if ( $on !== $this->store->isTermEnabled( $key, (int) $months ) ) {
				$this->store->setEnabled( $key . '_' . (int) $months, $on );
			}
		}
	}

	/**
	 * Placeholder values for a test send: each token shows as its own name.
	 *
	 * @return array<string, string>
	 */
	private function sampleContext( ?Template $template ): array {
		$context = array();

		foreach ( $template instanceof Template ? $template->tokens : array() as $token ) {
			$context[ $token ] = 'skrywer' === $token ? (string) wp_get_current_user()->display_name : '[' . $token . ']';
		}

		return $context;
	}

	/**
	 * Render the recent-sends table.
	 */
	private function renderLog(): void {
		echo '<h2>' . esc_html__( 'Onlangse e-posse', 'ink-core' ) . '</h2>';

		$rows = $this->log->recent();

		if ( array() === $rows ) {
			echo '<p>' . esc_html__( 'Nog niks gestuur nie.', 'ink-core' ) . '</p>';

			return;
		}

		echo '<table class="widefat striped" style="max-width:900px;"><thead><tr><th>' . esc_html__( 'Tyd (UTC)', 'ink-core' ) . '</th><th>' . esc_html__( 'E-pos', 'ink-core' ) . '</th><th>' . esc_html__( 'Aan', 'ink-core' ) . '</th><th>' . esc_html__( 'Uitslag', 'ink-core' ) . '</th></tr></thead><tbody>';

		foreach ( array_slice( $rows, 0, 50 ) as $row ) {
			echo '<tr><td>' . esc_html( (string) ( $row['time'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['key'] ?? '' ) ) . '</td><td>' . esc_html( (string) ( $row['to'] ?? '' ) ) . '</td><td>'
				. esc_html( ! empty( $row['ok'] ) ? __( 'Gestuur', 'ink-core' ) : __( 'Misluk', 'ink-core' ) ) . '</td></tr>';
		}

		echo '</tbody></table>';
	}
}
