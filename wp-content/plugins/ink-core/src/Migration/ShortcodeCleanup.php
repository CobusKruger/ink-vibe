<?php
/**
 * Once-off WPBakery shortcode cleanup — Story 16.12 (FL 16.12).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Strips legacy WPBakery `[vc_*]` shortcodes from migrated content — a once-off,
 * idempotent DB update (FL 16.12).
 *
 * The retired WPBakery/Qode stack is never reactivated, so its shortcodes would
 * otherwise render as raw `[vc_*]` text. This removes every `[vc_*]` / `[/vc_*]`
 * tag (opening, closing, self-closing) while PRESERVING the inner content
 * (`[vc_column_text]Hallo[/vc_column_text]` → `Hallo`). Non-`vc_` shortcodes
 * (`[gallery]`, `[caption]`, …) and ordinary text/markup are left intact. A post
 * is rewritten ONLY when its content actually changed.
 *
 * Once-off + guarded ({@see OPTION_DONE}; `--force` re-runs); WP-CLI only
 * (`wp ink clean-shortcodes`) — never a web request. Conflation-clean: reads/
 * writes post content via WP core only; zero cross-module coupling.
 *
 * Not `final`: the content methods are overridable seams so the strip logic is
 * unit-testable without the WordPress post API.
 *
 * @package Ink\Core
 */
class ShortcodeCleanup {

	/**
	 * The completion flag option — set once the cleanup has run.
	 *
	 * @var string
	 */
	public const OPTION_DONE = 'ink_migration_shortcodes_done';

	/**
	 * The WP-CLI command name (`wp ink clean-shortcodes`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink clean-shortcodes';

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
						'WPBakery-kortkodes opgeruim: %d plasings skoongemaak%s.',
						(int) $summary['cleaned'],
						! empty( $summary['skipped'] ) ? ' (oorgeslaan — reeds gedoen)' : ''
					)
				);
			}
		);
	}

	/**
	 * Strip every WPBakery `[vc_*]` tag, keeping the inner content. Pure.
	 *
	 * Removes opening, closing, and self-closing `[vc_*]` tags only — non-`vc_`
	 * shortcodes and text are untouched.
	 *
	 * @param string $content The post content.
	 * @return string
	 */
	public static function stripVcShortcodes( string $content ): string {
		// Match `[vc_…]` / `[/vc_…]` where the body is any run of: non-`]`/quote
		// chars, OR a "…"/'…' quoted attribute value (which MAY contain `]`). This
		// stops the tag from being truncated at a `]` inside an attribute value
		// (R16 review — a truncated tag would leave orphaned text in persisted
		// content), while still leaving non-`vc_` shortcodes untouched.
		$cleaned = preg_replace( '/\[\/?vc_(?:[^\]"\']|"[^"]*"|\'[^\']*\')*\]/', '', $content );

		return is_string( $cleaned ) ? $cleaned : $content;
	}

	/**
	 * Run the once-off cleanup. Idempotent unless `$force`.
	 *
	 * @param bool $force Re-run even when already completed.
	 * @return array{skipped:bool, cleaned:int}
	 */
	public function run( bool $force = false ): array {
		if ( $this->hasRun() && ! $force ) {
			return array(
				'skipped' => true,
				'cleaned' => 0,
			);
		}

		$cleaned = 0;

		foreach ( $this->contentRecords() as $record ) {
			$post_id = (int) ( $record->id ?? 0 );
			$content = (string) ( $record->content ?? '' );

			if ( $post_id <= 0 ) {
				continue;
			}

			$stripped = self::stripVcShortcodes( $content );

			if ( $stripped === $content ) {
				continue; // no WPBakery tags — leave untouched, no write.
			}

			$this->updatePostContent( $post_id, $stripped );
			++$cleaned;
		}

		$this->markDone();

		return array(
			'skipped' => false,
			'cleaned' => $cleaned,
		);
	}

	/**
	 * Whether the cleanup has already completed.
	 */
	public function hasRun(): bool {
		return (bool) get_option( self::OPTION_DONE, false );
	}

	/**
	 * Mark the cleanup complete (the idempotency flag).
	 */
	protected function markDone(): void {
		update_option( self::OPTION_DONE, true, false );
	}

	/**
	 * The post content to clean, as `{id, content}` rows. Overridable seam.
	 *
	 * Default: every public post/CPT + page whose content carries a `[vc_` tag.
	 *
	 * @return array<int, object>
	 */
	protected function contentRecords(): array {
		$ids = get_posts(
			array(
				'post_type'        => 'any',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => false,
				's'                => '[vc_',
			)
		);

		if ( ! is_array( $ids ) ) {
			return array();
		}

		$records = array();

		foreach ( $ids as $id ) {
			$id      = (int) $id;
			$content = get_post_field( 'post_content', $id );

			$records[] = (object) array(
				'id'      => $id,
				'content' => is_string( $content ) ? $content : '',
			);
		}

		return $records;
	}

	/**
	 * Persist a post's cleaned content. Overridable seam.
	 *
	 * @param int    $post_id The post id.
	 * @param string $content The cleaned content.
	 */
	protected function updatePostContent( int $post_id, string $content ): void {
		wp_update_post(
			array(
				'ID'           => $post_id,
				'post_content' => $content,
			)
		);
	}
}
