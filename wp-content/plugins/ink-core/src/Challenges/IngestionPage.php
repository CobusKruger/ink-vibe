<?php
/**
 * Results-ingestion admin screen — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\Kernel\Capabilities;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * The redakteur results-ingestion screen (Story 12A.3, R2).
 *
 * A submenu under the Uitdagings menu where the editor pastes the judges' results as
 * PLAIN TEXT, sees the dekkingsverslag (coverage report) reconciling the parse against
 * the stored EntryIDs, and — only after an explicit confirm, and only when no hard gap
 * blocks it — commits the irreversible result writes via {@see Ingestion}.
 *
 * The parse ({@see ResultsParser}), the coverage ({@see Coverage}), and the commit
 * pipeline ({@see Ingestion}) are all pure/seam-isolated; this shell gathers the round's
 * stored EntryIDs (the overridable {@see entriesFor()} seam, mirroring {@see CollationPage}),
 * resolves the parsed EntryIDs to post ids, renders, and drives the confirm gate. The
 * unit-testable integration point is {@see analyse()}. Every write is nonce +
 * `MANAGE_CHALLENGES` gated; all superglobal reads go through `is_scalar` → `wp_unslash`
 * → sanitise; all output is escaped.
 *
 * @package Ink\Core
 */
class IngestionPage {

	/**
	 * The admin-screen slug (submenu under the uitdaging CPT).
	 *
	 * @var string
	 */
	public const SCREEN_SLUG = 'ink-uitdaging-uitslae';

	/**
	 * Nonce action for the parse + commit writes.
	 */
	private const NONCE = 'ink_uitdaging_uitslae_nonce';

	/**
	 * Register the admin screen. Invoked from {@see Module::register()} (on `init`).
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerScreen' ) );
	}

	/**
	 * Register the ingestion submenu under the Uitdagings CPT menu.
	 */
	public function registerScreen(): void {
		add_submenu_page(
			'edit.php?post_type=' . PostTypes::UITDAGING,
			__( 'Uitslae-invoer', 'ink-core' ),
			__( 'Uitslae-invoer', 'ink-core' ),
			Capabilities::MANAGE_CHALLENGES,
			self::SCREEN_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Analyse pasted results for a round: parse, build the coverage report, and resolve
	 * the matched winners + commentary to post ids. Pure-ish integration over the
	 * {@see entriesFor()} seam — the unit-tested entry point.
	 *
	 * @param int    $uitdaging_id The round.
	 * @param string $text         The pasted plain-text results.
	 * @return array{report:array<string,mixed>, winners:list<array{post_id:int, rank:int, author_id:int}>, commentary:list<array{post_id:int, title:string, text:string}>}
	 */
	public function analyse( int $uitdaging_id, string $text ): array {
		$entries = $this->entriesFor( $uitdaging_id );
		$map     = self::storedMap( $entries );
		$parsed  = ResultsParser::parse( $text );

		// Rank-uniqueness must key on the AUTHORITATIVE pool — the entry's stored
		// Gradering snapshot — NOT the pasted header grade (which a typo'd/omitted header
		// could mis-state, letting two real-pool rank-1s slip past the gate). For every
		// matched winner, override the parsed grade with the stored gradering before
		// reconciliation; unmatched winners keep their parsed grade (flagged unknown
		// anyway). R12A review (readiness flag #1 — the authoritative invariant).
		$authoritative = array();

		foreach ( $parsed['winners'] as $winner ) {
			$entry_id = (string) ( $winner['entry_id'] ?? '' );

			if ( isset( $map[ $entry_id ] ) && '' !== $map[ $entry_id ]['gradering'] ) {
				$winner['grade'] = $map[ $entry_id ]['gradering'];
			}

			$authoritative[] = $winner;
		}

		$report = Coverage::report( $authoritative, $parsed['commentary'], array_keys( $map ) );

		$winners = array();

		foreach ( $parsed['winners'] as $winner ) {
			$id = (string) ( $winner['entry_id'] ?? '' );

			if ( isset( $map[ $id ] ) ) {
				$winners[] = array(
					'post_id'   => $map[ $id ]['post_id'],
					'rank'      => (int) $winner['rank'],
					'author_id' => $map[ $id ]['author_id'],
				);
			}
		}

		$commentary = array();

		foreach ( $parsed['commentary'] as $block ) {
			$id = (string) ( $block['entry_id'] ?? '' );

			if ( isset( $map[ $id ] ) ) {
				$commentary[] = array(
					'post_id' => $map[ $id ]['post_id'],
					'title'   => (string) ( $block['title'] ?? '' ),
					'text'    => (string) ( $block['text'] ?? '' ),
				);
			}
		}

		return array(
			'report'     => $report,
			'winners'    => $winners,
			'commentary' => $commentary,
		);
	}

	/**
	 * Build the EntryID-string → {post_id, author_id, title} map for a round. Pure.
	 *
	 * @param list<array{id:int, entry_id:string, author_id:int, title:string, gradering:string}> $entries The round entries.
	 * @return array<string, array{post_id:int, author_id:int, title:string, gradering:string}>
	 */
	public static function storedMap( array $entries ): array {
		$map = array();

		foreach ( $entries as $entry ) {
			$entry_id = (string) ( $entry['entry_id'] ?? '' );

			if ( '' === $entry_id ) {
				continue; // Unnumbered (un-collated) entries carry no EntryID.
			}

			$map[ $entry_id ] = array(
				'post_id'   => (int) ( $entry['id'] ?? 0 ),
				'author_id' => (int) ( $entry['author_id'] ?? 0 ),
				'title'     => (string) ( $entry['title'] ?? '' ),
				'gradering' => (string) ( $entry['gradering'] ?? '' ),
			);
		}

		return $map;
	}

	/**
	 * The round's numbered entries as ingestion rows. Overridable seam (testability).
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{id:int, entry_id:string, author_id:int, title:string, gradering:string}>
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

			$id = (int) $post->ID;

			$rows[] = array(
				'id'        => $id,
				'entry_id'  => EntryId::entryIdFor( $id ),
				'author_id' => (int) $post->post_author,
				'title'     => (string) get_the_title( $post ),
				'gradering' => Scalar::asString( get_post_meta( $id, Entry::GRADERING_META_KEY, true ) ),
			);
		}

		return $rows;
	}

	/**
	 * The commit pipeline. Overridable seam (testability).
	 *
	 * @return Ingestion
	 */
	protected function ingestion(): Ingestion {
		return new Ingestion();
	}

	/**
	 * Render the ingestion screen and handle the parse / commit POSTs.
	 *
	 * Capability-gated (defence in depth). The unit-testable logic lives in
	 * {@see analyse()} + the pure {@see ResultsParser}/{@see Coverage}/{@see Ingestion};
	 * the full rendered screen is verified by E2E (Story 18.8).
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_CHALLENGES ) ) {
			wp_die( esc_html__( 'Jy het nie toestemming om hierdie bladsy te sien nie.', 'ink-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Uitslae-invoer', 'ink-core' ) . '</h1>';

		$selected  = $this->postedUitdagingId();
		$text      = $this->postedTextarea( 'uitslae' );
		$confirmed = $this->postedConfirmed();
		$do_commit = 'commit' === $this->postedAction();

		$this->renderForm( $selected, $text );

		if ( $selected > 0 && '' !== trim( $text ) && $this->verify() ) {
			$analysis = $this->analyse( $selected, $text );
			$report   = $analysis['report'];
			$blocks   = Coverage::blocksCommit( $report );

			$this->renderReport( $report, $blocks );

			if ( $do_commit && $confirmed && ! $blocks ) {
				$result = $this->ingestion()->commit( $selected, $analysis['winners'], $analysis['commentary'] );
				$this->renderCommitResult( $result );
			}
		}

		echo '</div>';
	}

	/**
	 * Render the select + paste form (with the confirm checkbox).
	 *
	 * @param int    $selected The selected uitdaging id.
	 * @param string $text     The pasted text (preserved on re-render).
	 */
	private function renderForm( int $selected, string $text ): void {
		echo '<form method="post">';
		wp_nonce_field( self::NONCE, self::NONCE );
		echo '<p><label for="ink-uitdaging-uitslae">' . esc_html__( 'Uitdaging-ID', 'ink-core' ) . ': </label>';
		echo '<input type="number" id="ink-uitdaging-uitslae" name="uitdaging_id" value="' . esc_attr( (string) $selected ) . '" /></p>';
		echo '<p><label for="ink-uitslae">' . esc_html__( 'Beoordelaars se uitslae (plak as gewone teks)', 'ink-core' ) . '</label></p>';
		echo '<p><textarea id="ink-uitslae" name="uitslae" rows="18" cols="100" class="large-text code">' . esc_textarea( $text ) . '</textarea></p>';
		echo '<p><label><input type="checkbox" name="bevestig" value="1" /> ' . esc_html__( 'Ek bevestig dat alle kategorieë verantwoord is.', 'ink-core' ) . '</label></p>';
		echo '<p><button type="submit" name="ink_action" value="analyse" class="button">' . esc_html__( 'Ontleed', 'ink-core' ) . '</button> ';
		echo '<button type="submit" name="ink_action" value="commit" class="button button-primary">' . esc_html__( 'Pleeg uitslae', 'ink-core' ) . '</button></p>';
		echo '</form>';
	}

	/**
	 * Render the dekkingsverslag (coverage report).
	 *
	 * @param array<string, mixed> $report The coverage report.
	 * @param bool                 $blocks Whether a hard gap blocks the commit.
	 */
	private function renderReport( array $report, bool $blocks ): void {
		echo '<h2>' . esc_html__( 'Dekkingsverslag', 'ink-core' ) . '</h2>';

		$this->reportLine( __( 'Gepaste wenners', 'ink-core' ), (array) ( $report['matched_winners'] ?? array() ) );
		$this->reportLine( __( 'Onbekende wenner-IDs (pas niks)', 'ink-core' ), (array) ( $report['unknown_winners'] ?? array() ) );
		$this->reportLine( __( 'Duplikate', 'ink-core' ), (array) ( $report['duplicates'] ?? array() ) );
		$this->reportLine( __( 'Inskrywings sonder kommentaar', 'ink-core' ), (array) ( $report['entries_without_commentary'] ?? array() ) );

		if ( $blocks ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Daar is harde gapings (onbekende ID of duplikaat). Pleeg is geblokkeer totdat dit reggestel is.', 'ink-core' ) . '</p></div>';
		}
	}

	/**
	 * Render one coverage-report line (label + the comma-joined ids).
	 *
	 * @param string       $label The line label.
	 * @param list<string> $ids   The ids.
	 */
	private function reportLine( string $label, array $ids ): void {
		$value = array() === $ids ? __( 'Geen', 'ink-core' ) : implode( ', ', array_map( 'strval', $ids ) );

		echo '<p><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( $value ) . '</p>';
	}

	/**
	 * Render the commit-result notice.
	 *
	 * @param array{committed:bool, reason:string, post_id:int, feedback:int, placed:int, promoted:int} $result The commit result.
	 */
	private function renderCommitResult( array $result ): void {
		if ( ! empty( $result['committed'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: placements recorded, 2: promotions. */
						__( 'Uitslae gepleeg. %1$d plasings aangeteken, %2$d skrywers bevorder.', 'ink-core' ),
						(int) $result['placed'],
						(int) $result['promoted']
					)
				)
			);
			return;
		}

		$reason = (string) ( $result['reason'] ?? '' );

		if ( 'reeds_gepleeg' === $reason ) {
			$message = __( 'Hierdie uitdaging se uitslae is reeds gepleeg (niks is weer geskryf nie).', 'ink-core' );
		} elseif ( 'geen_wenners' === $reason ) {
			$message = __( 'Geen geldige wenners is opgespoor nie — niks is gepleeg nie. Die uitdaging bly oop vir \'n regstelling.', 'ink-core' );
		} else {
			$message = __( 'Die uitslae kon nie gepleeg word nie.', 'ink-core' );
		}

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * The posted uitdaging id. Sanctioned superglobal read.
	 *
	 * @return int
	 */
	private function postedUitdagingId(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before any write; this only pre-reads the selection.
		if ( ! isset( $_POST['uitdaging_id'] ) || ! is_scalar( $_POST['uitdaging_id'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return absint( wp_unslash( $_POST['uitdaging_id'] ) );
	}

	/**
	 * The posted action ('analyse' | 'commit' | ''). Sanctioned superglobal read.
	 *
	 * @return string
	 */
	private function postedAction(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- branch selector; the branch verifies its nonce before any write.
		if ( ! isset( $_POST['ink_action'] ) || ! is_scalar( $_POST['ink_action'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_key( wp_unslash( $_POST['ink_action'] ) );
	}

	/**
	 * Whether the editor ticked the confirm checkbox. Sanctioned superglobal read.
	 *
	 * @return bool
	 */
	private function postedConfirmed(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before the commit; this reads the confirm tick.
		if ( ! isset( $_POST['bevestig'] ) || ! is_scalar( $_POST['bevestig'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return '1' === sanitize_key( wp_unslash( $_POST['bevestig'] ) );
	}

	/**
	 * Read + sanitise the posted results textarea (preserves newlines). Sanctioned read.
	 *
	 * @param string $name The field name.
	 * @return string
	 */
	private function postedTextarea( string $name ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before any write.
		if ( ! isset( $_POST[ $name ] ) || ! is_scalar( $_POST[ $name ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Verify the posted nonce (capability is checked in {@see render()}).
	 *
	 * @return bool
	 */
	private function verify(): bool {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! is_scalar( $_POST[ self::NONCE ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) );

		return false !== wp_verify_nonce( $nonce, self::NONCE );
	}
}
