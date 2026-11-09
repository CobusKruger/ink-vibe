<?php
/**
 * Uitdaging win email.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Notifications\Api as Notifications;
use Ink\Notifications\Template;

defined( 'ABSPATH' ) || exit;

/**
 * Emails a writer when their entry places in the top 3 of an uitdaging.
 *
 * A system event (always sent once enabled; the writer cannot opt out). It
 * listens to {@see HOOK}, fired by {@see Ingestion::commit()} once per newly
 * recorded win. Gradering promotion is a separate event
 * ({@see \Ink\Tiers\PromotionEmails}).
 *
 * @package Ink\Core
 */
final class WinEmail {

	/**
	 * Fired per recorded win: `( int $uitdaging_id, array{post_id:int, rank:int, author_id:int} $winner )`.
	 */
	public const HOOK = 'ink/uitdaging_wen';

	/**
	 * Notifications template/event key.
	 */
	public const TEMPLATE_KEY = 'ink_uitdaging_wen_email';

	/**
	 * Subscribe the listener and register the template.
	 */
	public function register(): void {
		add_action( self::HOOK, array( $this, 'onWin' ), 10, 2 );

		Notifications::registerTemplate(
			new Template(
				self::TEMPLATE_KEY,
				__( 'Baie geluk met jou plek in die uitdaging!', 'ink-core' ),
				__( '<p>Hallo {skrywer},</p><p>Baie geluk! Jou werk "{werk}" is as {plek} in die uitdaging "{uitdaging}" aangewys.</p><p><a href="{skakel}">Sien die uitslae</a></p>', 'ink-core' ),
				false,
				group: __( 'Uitdagings', 'ink-core' ),
				label: __( 'Wen in \'n uitdaging (top 3)', 'ink-core' ),
				tokens: array( 'skrywer', 'werk', 'plek', 'uitdaging', 'skakel' )
			)
		);
	}

	/**
	 * Send the win email.
	 *
	 * @param int                                         $uitdaging_id The round.
	 * @param array{post_id:int, rank:int, author_id:int} $winner       The winning entry.
	 */
	public function onWin( int $uitdaging_id, array $winner ): void {
		$author_id = (int) ( $winner['author_id'] ?? 0 );
		$post_id   = (int) ( $winner['post_id'] ?? 0 );

		if ( $author_id <= 0 || $post_id <= 0 ) {
			return;
		}

		Notifications::fire(
			self::TEMPLATE_KEY,
			$author_id,
			array(
				'werk'      => wp_strip_all_tags( get_the_title( $post_id ) ),
				'plek'      => strtolower( Placements::placementLabel( (int) ( $winner['rank'] ?? 0 ) ) ),
				'uitdaging' => wp_strip_all_tags( get_the_title( $uitdaging_id ) ),
				'skakel'    => (string) get_permalink( $uitdaging_id ),
			)
		);
	}
}
