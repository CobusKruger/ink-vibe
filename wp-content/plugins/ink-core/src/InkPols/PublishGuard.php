<?php
/**
 * Publishing an issue on the site — WP4 (`04-inkpols` §4a, `inkpols.md` §2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\InkPols;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\Kernel\Scalar;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * After go-live an editor adds every issue in admin. This holds that path to what the
 * migration enforces for an imported one: a publish with a required field blank goes back
 * to draft, with a notice listing what's missing, and the cover becomes the featured image,
 * so a new issue has a social image.
 *
 * On `save_post` after {@see FieldSets::save()} (10), so it reads the values just saved.
 * Skipped under WP-CLI: {@see Migration} inserts first and writes meta after, and has its
 * own rule for the same fields.
 *
 * @package Ink\Core
 */
class PublishGuard {

	/**
	 * Transient (per user) carrying the missing labels across the post-save redirect.
	 *
	 * @var string
	 */
	public const NOTICE_TRANSIENT = 'ink_inkpols_onvolledig_';

	/**
	 * The meta required to publish, with the post title (the naam) checked separately.
	 *
	 * @var list<string>
	 */
	public const REQUIRED = array(
		FieldSets::INKPOLS_ISSUE_DATE,
		FieldSets::INKPOLS_PDF_ID,
		FieldSets::INKPOLS_COVER_ID,
		FieldSets::INKPOLS_TITEL,
		FieldSets::INKPOLS_TEASER,
		FieldSets::INKPOLS_GLANS_1_TITEL,
		FieldSets::INKPOLS_GLANS_1_SKRYWER,
		FieldSets::INKPOLS_GLANS_1_TIPE,
		FieldSets::INKPOLS_GLANS_2_TITEL,
		FieldSets::INKPOLS_GLANS_2_SKRYWER,
		FieldSets::INKPOLS_GLANS_2_TIPE,
		FieldSets::INKPOLS_GLANS_3_TITEL,
		FieldSets::INKPOLS_GLANS_3_SKRYWER,
		FieldSets::INKPOLS_GLANS_3_TIPE,
	);

	/**
	 * Wire the handlers.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) {
			return;
		}

		// The generic `save_post`, not `save_post_{type}`: core fires the typed hook FIRST, and
		// `FieldSets::save()` is on `save_post` (10), so the typed hook would judge the previous
		// save's meta and a new issue would need three saves to publish (found in the browser,
		// 2026-11-02). Priority 20 runs after the field box has written this request's values.
		add_action( 'save_post', array( $this, 'onSave' ), 20, 2 );
		add_action( 'admin_notices', array( $this, 'renderNotice' ) );
	}

	/**
	 * The required fields blank in these values: meta key → value. Pure.
	 *
	 * @param array<string, mixed> $values The saved values.
	 * @return list<string> The blank keys, in {@see REQUIRED} order.
	 */
	public static function missing( array $values ): array {
		$missing = array();

		foreach ( self::REQUIRED as $key ) {
			$value = trim( Scalar::asString( $values[ $key ] ?? '' ) );

			if ( '' === $value || '0' === $value ) {
				$missing[] = $key;
			}
		}

		return $missing;
	}

	/**
	 * The save handler.
	 *
	 * @param int     $post_id The issue.
	 * @param WP_Post $post    The issue post.
	 */
	public function onSave( int $post_id, WP_Post $post ): void {
		if ( PostTypes::INKPOLS_UITGAWE !== $post->post_type || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$values = array();

		foreach ( self::REQUIRED as $key ) {
			$values[ $key ] = get_post_meta( $post_id, $key, true );
		}

		$cover = Scalar::asNonNegativeInt( $values[ FieldSets::INKPOLS_COVER_ID ] );

		if ( $cover > 0 && get_post_thumbnail_id( $post_id ) !== $cover ) {
			set_post_thumbnail( $post_id, $cover );
		}

		if ( 'publish' !== $post->post_status ) {
			return;
		}

		$labels = array_map( array( FieldSets::class, 'labelFor' ), self::missing( $values ) );

		if ( '' === trim( (string) $post->post_title ) ) {
			array_unshift( $labels, __( 'Naam', 'ink-core' ) );
		}

		if ( array() === $labels ) {
			return;
		}

		$this->demote( $post_id, $labels );
	}

	/**
	 * Back to draft, and leave the notice for the redirect. Overridable seam.
	 *
	 * @param int          $post_id The issue.
	 * @param list<string> $labels  The missing fields' labels.
	 */
	protected function demote( int $post_id, array $labels ): void {
		$hook = 'save_post';

		remove_action( $hook, array( $this, 'onSave' ), 20 );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'draft',
			)
		);
		add_action( $hook, array( $this, 'onSave' ), 20, 2 );

		set_transient( self::NOTICE_TRANSIENT . get_current_user_id(), $labels, MINUTE_IN_SECONDS );

		// Core's "published" message would otherwise show above the notice.
		add_filter( 'redirect_post_location', static fn ( string $location ): string => (string) remove_query_arg( 'message', $location ) );
	}

	/**
	 * Show the notice left by {@see demote()}, once.
	 */
	public function renderNotice(): void {
		$key    = self::NOTICE_TRANSIENT . get_current_user_id();
		$labels = get_transient( $key );

		if ( ! is_array( $labels ) || array() === $labels ) {
			return;
		}

		delete_transient( $key );

		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Hierdie uitgawe is nie geplaas nie. Van die velde is steeds leeg.', 'ink-core' )
			. ': ' . esc_html( implode( ', ', array_map( 'strval', $labels ) ) )
			. '</p></div>';
	}
}
