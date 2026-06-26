<?php
/**
 * Receipt-notification trigger (R7) — Story 9.11 (FR-44a).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * The R7 receipt trigger: when a skrywer's work crosses a read-count milestone,
 * send an encouraging in-app kennisgewing.
 *
 * It subscribes to the receipt event {@see self::RECEIPT_EVENT} — fired by Story
 * 9.12 / the 18.9 analytics provider with `( skrywer_id, post_id, count )`. On
 * the event it sends an `Ontvangs` kennisgewing (the 9.9 emitter) whose text is
 * a RANDOMIZED pick from the 1.12 form-letter list, deep-linking to the PRIVATE
 * My Profiel (the read counts it celebrates are private, R8/9.12).
 *
 * Inert without analytics, two layers (the epic's graceful-degradation): (a) the
 * `ink/ontvangs` event has no emitter until 9.12/18.9, so it never fires; (b) the
 * R7 form-letter list is human-authored and empty at launch (ui-copy 654), so
 * {@see \Ink\Notifications\Api::randomMessage()} returns '' and the send is
 * suppressed. Either alone is a clean no-op.
 *
 * Conflation-clean: reuses the 9.9 emitter + the 1.12 form-letter store; zero
 * `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class ReceiptNotification {

	/**
	 * The 1.12 form-letter template key for the randomized receipt messages.
	 */
	public const TEMPLATE_KEY = 'ink_ontvangs_kennisgewing';

	/**
	 * The receipt event (fired by Story 9.12 / 18.9): `( skrywer_id, post_id, count )`.
	 */
	public const RECEIPT_EVENT = 'ink/ontvangs';

	/**
	 * Register the form-letter template + subscribe to the receipt event.
	 */
	public function register(): void {
		// Fail-safe template shell: toggle OFF, EMPTY randomized list. The R7
		// messages are human-authored (ui-copy 654, no AI Afrikaans) and added
		// when the copy lands; no placeholder text ships here (nothing to leak).
		Api::registerTemplate(
			new Template(
				self::TEMPLATE_KEY,
				'',
				'',
				false,
				array()
			)
		);

		add_action( self::RECEIPT_EVENT, array( $this, 'onReceipt' ), 10, 3 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- INK ink/... event-surface convention (AD-6).
	}

	/**
	 * Handle a receipt milestone — send the encouraging kennisgewing.
	 *
	 * @param int $skrywer_id The work's author (recipient).
	 * @param int $post_id    The work that reached the milestone.
	 * @param int $count      The read count at the milestone (part of the payload).
	 */
	public function onReceipt( int $skrywer_id, int $post_id, int $count ): void {
		unset( $count );

		if ( $skrywer_id <= 0 ) {
			return;
		}

		// Inert until the R7 form-letter list is authored — no message, no send.
		if ( '' === Api::randomMessage( self::TEMPLATE_KEY ) ) {
			return;
		}

		Kennisgewings::add( $skrywer_id, NotificationType::Ontvangs, $post_id );
	}

	/**
	 * The kennisgewing deep-link target — the PRIVATE My Profiel (Story 9.4).
	 *
	 * @return string
	 */
	public static function deepLinkUrl(): string {
		return home_url( '/my-profiel' );
	}
}
