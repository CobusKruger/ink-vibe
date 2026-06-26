<?php
/**
 * Denormalized reaction-total initialiser — Story 8.2 (FR-33, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

defined( 'ABSPATH' ) || exit;

/**
 * Initialises a bydrae's denormalized reaction total to `0` when it is published.
 *
 * The "Mees geliefd" discovery sort orders by the `ink_reaksie_telling` post-meta
 * (AD-7). A WordPress meta-ordered `WP_Query` (`orderby=meta_value_num` +
 * `meta_key`) silently DROPS posts that lack the key — so a zero-reaction work
 * would vanish from the ordering. Seeding the meta to `0` at publish keeps every
 * readable bydrae in the sort; live writes ({@see ReactionStore::syncTotal()})
 * keep it current thereafter. Never clobbers an existing count.
 *
 * @package Ink\Core
 */
final class ReactionTotalInit {

	/**
	 * Hook the publish transition.
	 */
	public function register(): void {
		add_action( 'transition_post_status', array( self::class, 'onTransition' ), 10, 3 );
	}

	/**
	 * Seed the denormalized total to `0` on a bydrae's first publish.
	 *
	 * @param string $new_status The new post status.
	 * @param string $old_status The previous post status.
	 * @param object $post       The post (a `WP_Post` at runtime).
	 */
	public static function onTransition( string $new_status, string $old_status, $post ): void {
		if ( 'publish' !== $new_status || ! is_object( $post ) || ! isset( $post->ID ) ) {
			return;
		}

		$post_id = (int) $post->ID;

		if ( ! Readable::isBydrae( $post_id ) ) {
			return;
		}

		// Seed once — never overwrite a live count maintained by syncTotal().
		if ( '' === (string) get_post_meta( $post_id, ReactionStore::TOTAL_META_KEY, true ) ) {
			update_post_meta( $post_id, ReactionStore::TOTAL_META_KEY, 0 );
		}
	}
}
