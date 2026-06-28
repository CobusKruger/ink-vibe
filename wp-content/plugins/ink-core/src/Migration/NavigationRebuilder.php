<?php
/**
 * Once-off fresh navigation rebuild — Story 16.8 (FL 16.8).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Rebuilds the site navigation FRESH to the new IA — a once-off, idempotent
 * migration step (FL 16.8).
 *
 * Creates (or updates) a single canonical `wp_navigation` entity carrying the
 * new information architecture's top-level items — NOT a copy of the old site's
 * menu (bookmarked deep links survive via the Story 16.7 redirects, not by
 * cloning the old menu). With one clean menu entity in place, staff editing the
 * Navigation block in the Site Editor get the correct fresh structure.
 *
 * The item labels are already-authored Afrikaans matching the theme nav +
 * Epic-15 org pages — written as menu *data* (staff-editable content), not
 * gettext UI strings, so no new copy debt is introduced.
 *
 * The OTHER two parts of this story are not scripted here: InkPols rides its own
 * `wp ink migrate-inkpols` command ({@see \Ink\InkPols\Migration}, Story 13.4),
 * and sponsors are entered manually (the `borg` CPT is ready from Epic 14; the
 * volume is too low to script — migration plan).
 *
 * Once-off + guarded ({@see OPTION_DONE}; `--force` re-runs); WP-CLI only
 * (`wp ink rebuild-navigation`) — never a web request. Conflation-clean: creates
 * a `wp_navigation` post via WP core only; zero cross-module coupling.
 *
 * Not `final`: the post methods are overridable seams so the orchestration is
 * unit-testable without the WordPress post API.
 *
 * @package Ink\Core
 */
class NavigationRebuilder {

	/**
	 * The completion flag option — set once the rebuild has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_navigation_done';

	/**
	 * The WP-CLI command name (`wp ink rebuild-navigation`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink rebuild-navigation';

	/**
	 * The canonical navigation entity title (its get-or-create key).
	 *
	 * @var string
	 */
	public const NAV_TITLE = 'Hoofnavigasie';

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function ( array $args, array $assoc ): void {
				$summary = $this->run( isset( $assoc['force'] ) );
				\WP_CLI::success(
					sprintf(
						'Navigasie %s: %d items%s.',
						! empty( $summary['created'] ) ? 'geskep' : 'bygewerk',
						(int) $summary['items'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * The new-IA navigation items, in order. Pure.
	 *
	 * Already-authored Afrikaans labels at their canonical routes (matching the
	 * theme nav + the Epic-15 org pages); written as menu data, not UI strings.
	 *
	 * @return list<array{label:string, url:string}>
	 */
	public static function navItems(): array {
		return array(
			array(
				'label' => 'Tuis',
				'url'   => '/',
			),
			array(
				'label' => 'Ontdek',
				'url'   => '/ontdek',
			),
			array(
				'label' => 'Biblioteek',
				'url'   => '/biblioteek',
			),
			array(
				'label' => 'Opleiding',
				'url'   => '/opleiding',
			),
			array(
				'label' => 'Uitdagings',
				'url'   => '/uitdagings',
			),
			array(
				'label' => 'InkPols',
				'url'   => '/inkpols',
			),
			array(
				'label' => 'Gemeenskap',
				'url'   => '/gemeenskap',
			),
			array(
				'label' => 'Oor INK',
				'url'   => '/oor-ink',
			),
			array(
				'label' => 'Kontak',
				'url'   => '/kontak',
			),
		);
	}

	/**
	 * Serialise IA items to `wp:navigation` block markup. Pure.
	 *
	 * @param array<int, array{label:string, url:string}> $items The IA items.
	 * @return string
	 */
	public static function toNavigationMarkup( array $items ): string {
		$links = array();

		foreach ( $items as $item ) {
			$attrs = array(
				'label' => (string) ( $item['label'] ?? '' ),
				'url'   => (string) ( $item['url'] ?? '' ),
				'kind'  => 'custom',
			);

			// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- pure helper: encodes a fixed, ASCII-safe attribute array into block-comment JSON; kept WP-free so it is unit-testable without WordPress.
			$json    = (string) json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
			$links[] = '<!-- wp:navigation-link ' . $json . ' /-->';
		}

		return "<!-- wp:navigation -->\n" . implode( "\n", $links ) . "\n<!-- /wp:navigation -->";
	}

	/**
	 * Run the once-off rebuild. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, created:bool, nav_id:int, items:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped' => true,
				'created' => false,
				'nav_id'  => 0,
				'items'   => 0,
			);
		}

		$items    = self::navItems();
		$markup   = self::toNavigationMarkup( $items );
		$existing = $this->existingNavId();

		if ( $existing > 0 ) {
			$this->updateNav( $existing, $markup );
			$created = false;
			$nav_id  = $existing;
		} else {
			$nav_id  = $this->createNav( self::NAV_TITLE, $markup );
			$created = $nav_id > 0;
		}

		$this->markDone();

		return array(
			'skipped' => false,
			'created' => $created,
			'nav_id'  => $nav_id,
			'items'   => count( $items ),
		);
	}

	/**
	 * Whether the rebuild has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the rebuild complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The existing canonical `wp_navigation` post id, or 0. Overridable seam.
	 *
	 * @return int
	 */
	protected function existingNavId(): int {
		$ids = get_posts(
			array(
				'post_type'        => 'wp_navigation',
				'post_status'      => 'any',
				'numberposts'      => 1,
				'fields'           => 'ids',
				'title'            => self::NAV_TITLE,
				'suppress_filters' => false,
			)
		);

		return ( is_array( $ids ) && isset( $ids[0] ) ) ? (int) $ids[0] : 0;
	}

	/**
	 * Create the navigation entity. Overridable seam.
	 *
	 * @param string $title   The entity title.
	 * @param string $content The block markup.
	 * @return int The new post id, or 0 on failure.
	 */
	protected function createNav( string $title, string $content ): int {
		$id = wp_insert_post(
			array(
				'post_type'    => 'wp_navigation',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			),
			true
		);

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/**
	 * Update an existing navigation entity's content. Overridable seam.
	 *
	 * @param int    $id      The navigation post id.
	 * @param string $content The block markup.
	 */
	protected function updateNav( int $id, string $content ): void {
		wp_update_post(
			array(
				'ID'           => $id,
				'post_content' => $content,
			)
		);
	}
}
