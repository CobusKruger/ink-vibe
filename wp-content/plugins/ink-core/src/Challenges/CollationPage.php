<?php
/**
 * Judge-email collation admin screen — Story 12A.2 (FR-50-R1, R1).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\Capabilities;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The redakteur judge-email collation screen (Story 12A.2, R1).
 *
 * A submenu under the Uitdagings admin menu (WP-admin chrome, no design system) that
 * replaces the manual assembly of the judge email: the editor picks an uitdaging, the
 * tool collates its entries (assigning the per-type EntryID, 12A.1), builds an
 * anonymized editable preview, and sends it to one or more judge addresses.
 *
 * All real logic is in the pure {@see Collation} statics; this shell only gathers the
 * entry rows (the overridable {@see entriesFor()}/{@see challengeBodyFor()} seams),
 * persists the numbers, renders, and sends — so the collation decisions are unit-tested
 * without WP. Every write is nonce + `MANAGE_CHALLENGES` gated; every read of a
 * superglobal goes through the `is_scalar` → `wp_unslash` → sanitise pattern
 * ({@see \Ink\Tiers\AdminProfile}/{@see \Ink\Content\FieldSets}); all output is escaped.
 *
 * The judge email is an editor-composed ad-hoc `wp_mail` (NOT a member-facing
 * Notifications form-letter), so this collaborator takes no Notifications dependency.
 *
 * @package Ink\Core
 */
class CollationPage {

	/**
	 * The admin-screen slug (submenu under the uitdaging CPT).
	 *
	 * @var string
	 */
	public const SCREEN_SLUG = 'ink-uitdaging-kollasie';

	/**
	 * Nonce actions for the collate + send writes.
	 */
	private const NONCE_COLLATE = 'ink_uitdaging_kollasie_nonce';
	private const NONCE_SEND    = 'ink_uitdaging_stuur_nonce';

	/**
	 * Register the admin screen. Invoked from {@see Module::register()} (on `init`).
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerScreen' ) );
	}

	/**
	 * Register the collation submenu under the Uitdagings CPT menu.
	 *
	 * Gated on {@see Capabilities::MANAGE_CHALLENGES} (granted to editor + admin at
	 * activation); {@see render()} re-checks the cap (defence in depth).
	 */
	public function registerScreen(): void {
		add_submenu_page(
			'edit.php?post_type=' . PostTypes::UITDAGING,
			__( 'Beoordelaar-e-pos', 'ink-core' ),
			__( 'Beoordelaar-e-pos', 'ink-core' ),
			Capabilities::MANAGE_CHALLENGES,
			self::SCREEN_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Render the collation screen and handle the collate / send POSTs.
	 *
	 * Capability-gated (defence in depth). The unit-testable logic lives in
	 * {@see collateRound()} + the pure {@see Collation} statics; the full rendered
	 * screen + the `wp_mail` send are verified by E2E (Story 18.8).
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_CHALLENGES ) ) {
			wp_die( esc_html__( 'Jy het nie toestemming om hierdie bladsy te sien nie.', 'ink-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Beoordelaar-e-pos', 'ink-core' ) . '</h1>';

		$action   = $this->postedAction();
		$selected = $this->postedUitdagingId();
		$preview  = '';
		$is_empty = false;
		$collated = false;

		if ( 'send' === $action && $this->verify( self::NONCE_SEND ) && $selected > 0 ) {
			$this->handleSend( $selected );
		}

		if ( 'collate' === $action && $this->verify( self::NONCE_COLLATE ) && $selected > 0 ) {
			$result   = $this->collateRound( $selected );
			$preview  = (string) $result['preview'];
			$is_empty = (bool) $result['empty'];
			$collated = true;
		}

		$this->renderSelectForm( $selected );

		if ( $collated ) {
			if ( $is_empty ) {
				echo '<p class="ink-kollasie__leeg">' . esc_html__( 'Hierdie uitdaging het geen mededingende inskrywings nie.', 'ink-core' ) . '</p>';
			} else {
				$this->renderPreviewForm( $selected, $preview );
			}
		}

		echo '</div>';
	}

	/**
	 * Collate one round: gather entries + existing numbers + body, compose the preview
	 * (pure {@see Collation::collate}), then persist the newly-assigned EntryIDs.
	 *
	 * Impure orchestration over the overridable seams — the single integration point the
	 * unit test drives (a subclass overriding {@see entriesFor()}/{@see challengeBodyFor()}
	 * asserts the preview + that EntryIDs were assigned). Idempotent: re-collation
	 * persists nothing new (EntryId::assign is first-wins).
	 *
	 * @param int $uitdaging_id The round.
	 * @return array{empty:bool, preview:string, ordered:list<array<string,mixed>>, assignments:array<int,int>}
	 */
	public function collateRound( int $uitdaging_id ): array {
		$entries  = $this->entriesFor( $uitdaging_id );
		$existing = array();

		foreach ( $entries as $entry ) {
			$id = (int) ( $entry['id'] ?? 0 );

			if ( $id > 0 ) {
				$existing[ $id ] = EntryId::numberFor( $id );
			}
		}

		$result = Collation::collate( $entries, $existing, $this->challengeBodyFor( $uitdaging_id ) );

		// Persist the (new) EntryIDs — first-wins, so re-collation writes nothing.
		$rows = array();

		foreach ( $result['ordered'] as $row ) {
			$rows[] = array(
				'id'     => (int) $row['id'],
				'type'   => (string) $row['type'],
				'number' => (int) $row['number'],
			);
		}

		Collation::assignRound( $rows );

		return $result;
	}

	/**
	 * The round's entries as collation rows. Overridable seam (testability).
	 *
	 * Reads the published bydraes carrying the round term (the same set the single page
	 * lists) and, per entry, its type, entry-time Gradering snapshot, title, content and
	 * author display name.
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{id:int, type:string, gradering:string, title:string, content:string, author_name:string}>
	 */
	protected function entriesFor( int $uitdaging_id ): array {
		if ( $uitdaging_id <= 0 ) {
			return array();
		}

		$query = new \WP_Query( SinglePage::entriesQueryArgs( $uitdaging_id ) );
		$rows  = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$id          = (int) $post->ID;
			$author      = get_userdata( (int) $post->post_author );
			$author_name = $author instanceof \WP_User ? (string) $author->display_name : '';

			$rows[] = array(
				'id'          => $id,
				'type'        => (string) $post->post_type,
				'gradering'   => Scalar::asString( get_post_meta( $id, Entry::GRADERING_META_KEY, true ) ),
				'title'       => (string) get_the_title( $post ),
				'content'     => Scalar::asString( $post->post_content ),
				'author_name' => $author_name,
			);
		}

		return $rows;
	}

	/**
	 * The uitdaging's editorial brief body. Overridable seam (testability).
	 *
	 * @param int $uitdaging_id The round.
	 * @return string
	 */
	protected function challengeBodyFor( int $uitdaging_id ): string {
		if ( $uitdaging_id <= 0 ) {
			return '';
		}

		$post = get_post( $uitdaging_id );

		return $post instanceof \WP_Post ? Scalar::asString( $post->post_content ) : '';
	}

	/**
	 * The published uitdagings, newest deadline-or-date first. Overridable seam.
	 *
	 * @return list<\WP_Post>
	 */
	protected function uitdagings(): array {
		$posts = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'publish',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'numberposts'      => 100,
				'suppress_filters' => false,
			)
		);

		return is_array( $posts ) ? array_values( $posts ) : array();
	}

	/**
	 * Validate + send the editor-edited preview to the judge recipients (ad-hoc wp_mail).
	 *
	 * @param int $uitdaging_id The round (for the subject line).
	 */
	private function handleSend( int $uitdaging_id ): void {
		$recipients = Collation::parseRecipients( $this->postedField( 'recipients' ) );
		$body       = $this->postedTextarea( 'preview' );

		if ( array() === $recipients || '' === trim( $body ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Verskaf ten minste een geldige e-posadres en \'n e-posinhoud.', 'ink-core' ) . '</p></div>';
			return;
		}

		/* translators: %s: the uitdaging title. */
		$subject = sprintf( __( 'INK uitdaging — inskrywings vir beoordeling: %s', 'ink-core' ), get_the_title( $uitdaging_id ) );

		$ok = wp_mail( $recipients, $subject, $body );

		if ( $ok ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Die beoordelaar-e-pos is gestuur.', 'ink-core' ) . '</p></div>';
		} else {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Die e-pos kon nie gestuur word nie.', 'ink-core' ) . '</p></div>';
		}
	}

	/**
	 * Render the uitdaging-select + collate form.
	 *
	 * @param int $selected The currently-selected uitdaging id.
	 */
	private function renderSelectForm( int $selected ): void {
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_COLLATE, self::NONCE_COLLATE );
		echo '<input type="hidden" name="ink_action" value="collate" />';
		echo '<p><label for="ink-uitdaging">' . esc_html( Terms::label( 'uitdaging' ) ) . ': </label>';
		echo '<select id="ink-uitdaging" name="uitdaging_id">';
		echo '<option value="0">' . esc_html__( '— Kies —', 'ink-core' ) . '</option>';

		foreach ( $this->uitdagings() as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $post->ID,
				selected( $selected, (int) $post->ID, false ),
				esc_html( (string) get_the_title( $post ) )
			);
		}

		echo '</select> ';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Stel saam', 'ink-core' ) . '</button>';
		echo '</p></form>';
	}

	/**
	 * Render the editable-preview + recipients + send form.
	 *
	 * @param int    $uitdaging_id The collated round.
	 * @param string $preview      The generated (editable) preview body.
	 */
	private function renderPreviewForm( int $uitdaging_id, string $preview ): void {
		echo '<form method="post">';
		wp_nonce_field( self::NONCE_SEND, self::NONCE_SEND );
		echo '<input type="hidden" name="ink_action" value="send" />';
		echo '<input type="hidden" name="uitdaging_id" value="' . esc_attr( (string) $uitdaging_id ) . '" />';
		echo '<p><label for="ink-preview">' . esc_html__( 'Voorskou (redigeerbaar)', 'ink-core' ) . '</label></p>';
		echo '<p><textarea id="ink-preview" name="preview" rows="20" cols="100" class="large-text code">' . esc_textarea( $preview ) . '</textarea></p>';
		echo '<p><label for="ink-recipients">' . esc_html__( 'Beoordelaar-e-posadresse (komma- of reëlgeskei)', 'ink-core' ) . '</label></p>';
		echo '<p><input type="text" id="ink-recipients" name="recipients" class="large-text" /></p>';
		echo '<button type="submit" class="button button-primary">' . esc_html__( 'Stuur', 'ink-core' ) . '</button>';
		echo '</form>';
	}

	/**
	 * The posted action ('collate' | 'send' | ''). Sanctioned superglobal read.
	 *
	 * @return string
	 */
	private function postedAction(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the action only selects a branch; the branch itself verifies its nonce before any write.
		if ( ! isset( $_POST['ink_action'] ) || ! is_scalar( $_POST['ink_action'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_key( wp_unslash( $_POST['ink_action'] ) );
	}

	/**
	 * The posted uitdaging id. Sanctioned superglobal read.
	 *
	 * @return int
	 */
	private function postedUitdagingId(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the per-action nonce is verified in render() before any write; this only pre-reads the selection.
		if ( ! isset( $_POST['uitdaging_id'] ) || ! is_scalar( $_POST['uitdaging_id'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return absint( wp_unslash( $_POST['uitdaging_id'] ) );
	}

	/**
	 * Read + sanitise a single-line posted field. Sanctioned superglobal read.
	 *
	 * @param string $name The field name.
	 * @return string
	 */
	private function postedField( string $name ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- caller verifies the send nonce before this read.
		if ( ! isset( $_POST[ $name ] ) || ! is_scalar( $_POST[ $name ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_text_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Read + sanitise a posted textarea (preserves newlines). Sanctioned superglobal read.
	 *
	 * @param string $name The field name.
	 * @return string
	 */
	private function postedTextarea( string $name ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- caller verifies the send nonce before this read.
		if ( ! isset( $_POST[ $name ] ) || ! is_scalar( $_POST[ $name ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Verify a posted nonce (capability is checked in {@see render()}).
	 *
	 * @param string $nonce_action The nonce action.
	 * @return bool
	 */
	private function verify( string $nonce_action ): bool {
		if ( ! isset( $_POST[ $nonce_action ] ) || ! is_scalar( $_POST[ $nonce_action ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ $nonce_action ] ) );

		return false !== wp_verify_nonce( $nonce, $nonce_action );
	}
}
