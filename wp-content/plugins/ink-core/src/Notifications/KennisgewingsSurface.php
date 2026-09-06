<?php
/**
 * Kennisgewings read-model — My Profiel rebuild §5.8 (the notifications tab's
 * data + render source; the missing "read side" of Story 9.9).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

use Ink\Content\FieldSets;
use Ink\Kernel\Sast;

defined( 'ABSPATH' ) || exit;

/**
 * Lists the current user's kennisgewings back out of the BuddyPress
 * notifications store, mapped to the four ratified Kennisgewings-tab template
 * strings (`docs/ui-copy-translations.md` "Kennisgewingtemplates", lines
 * ~515-518) and ordered newest-first.
 *
 * {@see Kennisgewings} only ever WRITES (Story 9.9); nothing before this class
 * read a user's notifications back out (confirmed by a full-directory grep —
 * this is genuinely new build, not wiring). Guarded the same way
 * {@see Kennisgewings::add()} degrades gracefully without BuddyPress: a plain
 * `function_exists()` check on a BuddyPress FUNCTION (not a `class_exists()`
 * check on a BuddyPress class) — `bp_notifications_get_notifications_for_user()`
 * rather than calling `BP_Notifications_Notification::get()` directly, because
 * a plain function is what Brain Monkey's `Functions\when()`/`Functions\expect()`
 * can mock in the unit suite (a `class_exists()` guard would need the class to
 * really be defined to ever exercise the "BuddyPress present" branch, which
 * would leak a global class definition across every other test in the process —
 * the same reasoning the Story 17.4 guard doubles in `tests/bootstrap.php`
 * already document for `function_exists()` over class stubs).
 *
 * ## The VolgWerk ambiguity (investigated, not guessed)
 *
 * `docs/ui-copy-translations.md` line 517 ratifies a template for "[Name]
 * started following you" ("[Naam] volg jou nou"). {@see NotificationType} has
 * NO case for that event. `NotificationType::VolgWerk` looks like the obvious
 * candidate by name, but its WIRED semantics (see {@see Events::onTransition()})
 * are the opposite event: "a writer you follow published new work" — the
 * notification fires for the FOLLOWER (recipient) when the FOLLOWEE (actor)
 * publishes, i.e. `Kennisgewings::add( $follower_id, NotificationType::VolgWerk,
 * $post_id, $author_id )`. Rendering that data through "[Naam] volg jou nou"
 * would read backwards — it would tell the recipient that the author they
 * already follow "now follows you", which is not what happened and is not even
 * guaranteed to be true. No other `NotificationType` case fits either
 * (`Mention`/`Reaksie` are comment-shaped, `Uitdaging` is a challenge deadline,
 * `LidmaatskapVerval`/`Ontvangs` are membership/read-receipt). There is
 * currently NO source anywhere in the codebase that emits a "someone started
 * following you" kennisgewing — {@see \Ink\Social\FollowController} writes the
 * follow graph via {@see \Ink\Social\FollowStore} and never touches
 * {@see Kennisgewings} at all.
 *
 * Conclusion: this is a genuine product/dev gap, not a naming coincidence to
 * paper over. {@see self::textFor()} deliberately does NOT map `VolgWerk` (or
 * the two other ratified-copy-less cases, `LidmaatskapVerval`/`Ontvangs`) to
 * the "volg jou nou" string — it renders a `[NEEDS DEV DECISION]`-flagged
 * placeholder instead (mirroring the `[NEEDS HUMAN AFRIKAANS]` marker
 * convention {@see \Ink\Engagement\ReadingList::toHtml()} already uses for
 * unauthored copy), so a real kennisgewing of one of these three types is
 * still visible on the tab rather than silently dropped, while being honest
 * that no correct copy exists for it yet. Resolving this needs a product
 * decision — most likely a NEW `NotificationType` case (e.g. `Volgeling`) wired
 * off a new `ink_follows` insert event — not a code fix in this class.
 *
 * ## Shape
 *
 * {@see self::rows()} is the one query both call sites share (My Profiel
 * rebuild strategy §5.8 point 3): the Kennisgewings tab wants everything,
 * the Oorsig "Onlangse aktiwiteit" card wants only the first
 * {@see self::RECENT_LIMIT} via {@see self::recent()}, and both the Oorsig
 * "Ongelees" stat and the tab's own badge want {@see self::unreadCount()} —
 * all three read the SAME underlying rows rather than running three queries.
 *
 * @package Ink\Core
 */
final class KennisgewingsSurface {

	/**
	 * The Oorsig "Onlangse aktiwiteit" card's row cap (§5.3).
	 */
	public const RECENT_LIMIT = 3;

	/**
	 * The current user's kennisgewings, newest-first, mapped to display rows.
	 *
	 * Empty (never throws, never warns) when `$user_id` is non-positive or
	 * BuddyPress is absent — the same clean-no-op contract as
	 * {@see Kennisgewings::add()}.
	 *
	 * @param int $user_id The user.
	 * @return list<array{type:string, created_gmt:string, unread:bool, text:string}>
	 */
	public static function rows( int $user_id ): array {
		if ( $user_id <= 0 ) {
			return array();
		}

		$boundary = Kennisgewings::boundaryFor( $user_id );
		$enriched = array();

		foreach ( self::rawRows( $user_id ) as $raw ) {
			$type = NotificationType::tryFrom( self::field( $raw, 'component_action' ) );

			if ( null === $type ) {
				continue; // Not one of ours (component_name is already filtered, but never trust it blindly).
			}

			$item_id = (int) self::field( $raw, 'item_id' );

			$enriched[] = array(
				'type'        => $type->value,
				'subject_id'  => self::subjectIdFor( $type, $item_id ),
				'actor_id'    => (int) self::field( $raw, 'secondary_item_id' ),
				'created_gmt' => self::field( $raw, 'date_notified' ),
			);
		}

		$rows = array();

		foreach ( self::groupRows( $enriched ) as $group ) {
			$type = NotificationType::from( $group['type'] );

			$rows[] = array(
				'type'        => $group['type'],
				'created_gmt' => $group['created_gmt'],
				'unread'      => Kennisgewings::isUnread( $group['created_gmt'], $boundary ),
				'text'        => self::textFor(
					$type,
					self::actorNameFor( $group['actor_id'] ),
					self::titleFor( $type, $group['subject_id'] ),
					$group['n_others'],
					self::daysUntilCloseFor( $type, $group['subject_id'] )
				),
			);
		}

		return $rows;
	}

	/**
	 * The first `$limit` rows (the Oorsig "Onlangse aktiwiteit" card, §5.3).
	 *
	 * @param int $user_id The user.
	 * @param int $limit   Rows to keep (default {@see self::RECENT_LIMIT}).
	 * @return list<array{type:string, created_gmt:string, unread:bool, text:string}>
	 */
	public static function recent( int $user_id, int $limit = self::RECENT_LIMIT ): array {
		return array_slice( self::rows( $user_id ), 0, max( 0, $limit ) );
	}

	/**
	 * The user's unread kennisgewing count — shared by the Oorsig "Ongelees"
	 * stat card and the Kennisgewings tab's own badge (§5.3/§5.8), computed
	 * once off the SAME {@see self::rows()} query rather than a second read.
	 *
	 * @param int $user_id The user.
	 * @return int
	 */
	public static function unreadCount( int $user_id ): int {
		return self::countUnreadRows( self::rows( $user_id ) );
	}

	/**
	 * Pure: count the unread rows in an already-mapped row list.
	 *
	 * Split out from {@see self::unreadCount()} so the counting logic itself
	 * unit-tests without mocking BuddyPress.
	 *
	 * @param list<array{unread:bool}> $rows The mapped rows.
	 * @return int
	 */
	public static function countUnreadRows( array $rows ): int {
		$n = 0;

		foreach ( $rows as $row ) {
			if ( ! empty( $row['unread'] ) ) {
				++$n;
			}
		}

		return $n;
	}

	/**
	 * Pure: group already-typed rows, newest-first.
	 *
	 * Only `NotificationType::Reaksie` rows sharing the same `subject_id`
	 * collapse into ONE row — the ratified "[Naam] en nog [N] ander het …
	 * liefgehad" template's aggregation. Every other type passes through one
	 * row per raw notification (no other ratified template aggregates).
	 * The collapsed row keeps the MOST RECENT row's actor/created_gmt as the
	 * displayed actor, and `n_others` is the count of OTHER distinct actors in
	 * the group (so the template's "1 named + N others" always equals the
	 * group's true distinct-actor count).
	 *
	 * @param list<array{type:string, subject_id:int, actor_id:int, created_gmt:string}> $enriched
	 * @return list<array{type:string, subject_id:int, actor_id:int, created_gmt:string, n_others:int}>
	 */
	public static function groupRows( array $enriched ): array {
		$reaksie_groups = array();
		$passthrough    = array();

		foreach ( $enriched as $row ) {
			if ( NotificationType::Reaksie->value === $row['type'] ) {
				$reaksie_groups[ $row['subject_id'] ][] = $row;
				continue;
			}

			$passthrough[] = $row + array( 'n_others' => 0 );
		}

		$grouped = array();

		foreach ( $reaksie_groups as $group ) {
			usort( $group, static fn( array $a, array $b ): int => $b['created_gmt'] <=> $a['created_gmt'] );

			$representative = $group[0];
			$actors         = array_unique( array_column( $group, 'actor_id' ) );

			$grouped[] = $representative + array( 'n_others' => max( 0, count( $actors ) - 1 ) );
		}

		$all = array_merge( $grouped, $passthrough );

		usort( $all, static fn( array $a, array $b ): int => $b['created_gmt'] <=> $a['created_gmt'] );

		return $all;
	}

	/**
	 * Pure: the display text for one row, per the ratified Kennisgewings
	 * templates (`docs/ui-copy-translations.md` lines ~515-518).
	 *
	 * `VolgWerk`/`LidmaatskapVerval`/`Ontvangs` have no ratified template that
	 * correctly fits their wired semantics — see this class's docblock (the
	 * VolgWerk ambiguity) — so they render a flagged placeholder rather than
	 * guessed copy.
	 *
	 * @param NotificationType $type      The kennisgewing type.
	 * @param string           $actorName The triggering user's display name.
	 * @param string           $title     The subject's title (post/challenge; '' when not applicable).
	 * @param int              $nOthers   Other distinct actors in the group (Reaksie only).
	 * @param int              $days      Days until close (Uitdaging only).
	 * @return string
	 */
	public static function textFor( NotificationType $type, string $actorName, string $title, int $nOthers, int $days ): string {
		return match ( $type ) {
			NotificationType::Reaksie => sprintf(
				/* translators: 1: actor display name, 2: number of other people who reacted, 3: work title. */
				__( '%1$s en nog %2$d ander het "%3$s" liefgehad', 'ink-core' ),
				$actorName,
				$nOthers,
				$title
			),
			NotificationType::Mention => sprintf(
				/* translators: 1: actor display name, 2: work title. */
				__( '%1$s het terugvoer gelewer op "%2$s"', 'ink-core' ),
				$actorName,
				$title
			),
			NotificationType::Uitdaging => sprintf(
				/* translators: 1: challenge title, 2: days until the challenge closes. */
				__( '%1$s sluit oor %2$d dae', 'ink-core' ),
				$title,
				$days
			),
			default => __( '[NEEDS DEV DECISION] — geen bekragtigde Kennisgewings-sjabloon pas hierdie tipe nie; sien KennisgewingsSurface se class-docblok (die VolgWerk-dubbelsinnigheid).', 'ink-core' ),
		};
	}

	/**
	 * Render the kennisgewings list + "Merk alles as gelees" button.
	 *
	 * NOT yet wired into a page template — the tab shell embeds this in a later
	 * build step (my-profiel-rebuild-strategy.md §5.8 point 5 explicitly wants
	 * this method to already exist so that step has something to call). Pure —
	 * escaping only.
	 *
	 * `kennisgewings.js`'s assumed DOM contract (documented in full on that
	 * file): `.ink-kennisgewings` wrapper section, `.ink-kennisgewings__merk-alles
	 * [data-ink-kennisgewings-merk-alles]` button, `.ink-kennisgewings__item
	 * [data-ink-kennisgewing-status="unread"|"gelees"]` (+ `is-unread` class)
	 * per row.
	 *
	 * @param list<array{type:string, created_gmt:string, unread:bool, text:string}> $rows The mapped rows.
	 * @return string
	 */
	public static function toHtml( array $rows ): string {
		$heading = '<h2 class="ink-kennisgewings__titel">' . esc_html__( 'Kennisgewings', 'ink-core' ) . '</h2>';
		$button  = '<button type="button" class="ink-kennisgewings__merk-alles" data-ink-kennisgewings-merk-alles>'
			. esc_html__( 'Merk alles as gelees', 'ink-core' ) . '</button>';
		// Same "kop" (header row: heading + one action) shape BydraesSurface already
		// uses (`.ink-bydraes__kop`) — reused, not reinvented, so the tab-shell CSS
		// can style one shared "panel header row" recipe across both.
		$kop = '<div class="ink-kennisgewings__kop">' . $heading . $button . '</div>';

		if ( array() === $rows ) {
			// No ratified copy exists yet for this empty state (ui-copy-translations.md's
			// "Kennisgewings-blad" section only carries the heading/button/templates) —
			// flagged per the standard [[afrikaans-copy-debt-process]] rather than invented.
			$empty = __( '[NEEDS HUMAN AFRIKAANS] — Kennisgewings empty-state copy not yet authored in ui-copy-translations.md.', 'ink-core' );

			return '<section class="ink-kennisgewings">' . $kop
				. '<p class="ink-kennisgewings__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-kennisgewings">' . $kop . '<ul class="ink-kennisgewings__lys">';

		foreach ( $rows as $row ) {
			$status  = ! empty( $row['unread'] ) ? 'unread' : 'gelees';
			$classes = 'ink-kennisgewings__item' . ( 'unread' === $status ? ' is-unread' : '' );

			$html .= '<li class="' . esc_attr( $classes ) . '" data-ink-kennisgewing-status="' . esc_attr( $status ) . '">'
				. '<p class="ink-kennisgewings__teks">' . esc_html( (string) $row['text'] ) . '</p>'
				. '</li>';
		}

		$html .= '</ul></section>';

		return $html;
	}

	/**
	 * Pure: days until a stored `Y-m-d` deadline closes (Uitdaging only),
	 * floored at 0 (never negative for an already-closed challenge).
	 *
	 * Mirrors {@see Sast::endOfDay()}'s "valid through 23:59:59 SAST" boundary
	 * (AD-2/AD-3) — the same boundary {@see \Ink\Challenges\Deadline} uses —
	 * without depending on `Ink\Challenges` (not in this module's deptrac
	 * allowlist; only `Ink\Content`/`Ink\Kernel` are).
	 *
	 * @param string              $deadline_raw The stored `Y-m-d[ T]H:i(:s)` deadline string.
	 * @param \DateTimeImmutable $now          The instant to measure from.
	 * @return int
	 */
	public static function daysUntilClose( string $deadline_raw, \DateTimeImmutable $now ): int {
		if ( '' === $deadline_raw ) {
			return 0;
		}

		try {
			$deadline = new \DateTimeImmutable( $deadline_raw, new \DateTimeZone( Sast::TIMEZONE ) );
		} catch ( \Exception $e ) {
			return 0;
		}

		$seconds = Sast::endOfDay( $deadline )->getTimestamp() - $now->getTimestamp();

		return max( 0, (int) ceil( $seconds / self::DAY_IN_SECONDS ) );
	}

	/**
	 * One day in seconds — a literal rather than WP's `DAY_IN_SECONDS`, which is
	 * undefined when WordPress is not loaded (the mocked unit suite), mirroring
	 * {@see \Ink\Entitlement\LifecycleEmails::WINDOW_TOLERANCE}'s documented reasoning.
	 */
	private const DAY_IN_SECONDS = 86400;

	/**
	 * The raw BuddyPress notification rows for a user. Guarded — `array()` when
	 * BuddyPress is absent (mirrors {@see Kennisgewings::add()}'s guard).
	 *
	 * @param int $user_id The user.
	 * @return list<mixed>
	 */
	private static function rawRows( int $user_id ): array {
		if ( ! function_exists( 'bp_notifications_get_notifications_for_user' ) ) {
			return array();
		}

		$notifications = bp_notifications_get_notifications_for_user( $user_id, 'object' );

		return is_array( $notifications ) ? array_values( $notifications ) : array();
	}

	/**
	 * Defensive field access over a raw BP notification row — tolerates either
	 * an object (the documented `BP_Notifications_Notification::get()` shape)
	 * or an array (what the unit suite's mocked rows use), so this class never
	 * assumes more about BuddyPress's exact return shape than it has to.
	 *
	 * @param mixed  $row The raw row.
	 * @param string $key The field name.
	 * @return string
	 */
	private static function field( $row, string $key ): string {
		if ( is_array( $row ) && isset( $row[ $key ] ) ) {
			return (string) $row[ $key ];
		}

		if ( is_object( $row ) && isset( $row->$key ) ) {
			return (string) $row->$key;
		}

		return '';
	}

	/**
	 * The "subject" a notification is really about, for grouping + title
	 * lookup. `Reaksie`/`Mention` notifications carry a COMMENT id as `item_id`
	 * (see {@see Events::onComment()}) — the subject readers care about is that
	 * comment's POST. Every other type's `item_id` already IS the subject.
	 *
	 * @param NotificationType $type    The kennisgewing type.
	 * @param int              $item_id The raw `item_id`.
	 * @return int
	 */
	private static function subjectIdFor( NotificationType $type, int $item_id ): int {
		if ( NotificationType::Reaksie !== $type && NotificationType::Mention !== $type ) {
			return $item_id;
		}

		if ( $item_id <= 0 || ! function_exists( 'get_comment' ) ) {
			return 0;
		}

		$comment = get_comment( $item_id );

		return $comment ? (int) $comment->comment_post_ID : 0;
	}

	/**
	 * The triggering user's display name ('' when unresolvable — a system
	 * kennisgewing, e.g. `LidmaatskapVerval`, has no actor).
	 *
	 * @param int $actor_id The actor id (0 = system).
	 * @return string
	 */
	private static function actorNameFor( int $actor_id ): string {
		if ( $actor_id <= 0 || ! function_exists( 'get_userdata' ) ) {
			return '';
		}

		$user = get_userdata( $actor_id );

		return $user instanceof \WP_User ? (string) $user->display_name : '';
	}

	/**
	 * The subject's display title. `LidmaatskapVerval`'s subject is a
	 * membership id, not a post — no title concept applies.
	 *
	 * @param NotificationType $type       The kennisgewing type.
	 * @param int              $subject_id The resolved subject id.
	 * @return string
	 */
	private static function titleFor( NotificationType $type, int $subject_id ): string {
		if ( NotificationType::LidmaatskapVerval === $type || $subject_id <= 0 || ! function_exists( 'get_the_title' ) ) {
			return '';
		}

		return (string) get_the_title( $subject_id );
	}

	/**
	 * Days until close, for `Uitdaging` rows only. SPECULATIVE: the Uitdaging
	 * kennisgewing source is not wired yet ({@see Events} docblock lists it as
	 * "deferred — emitter ready, source not built"), so this assumes the
	 * eventual source will pass the challenge post id as `item_id` (reading its
	 * deadline the same way {@see \Ink\Challenges\SinglePage}/{@see
	 * \Ink\Challenges\Archive} already do via `FieldSets::UITDAGING_DEADLINE`).
	 * Whoever wires that source should confirm this assumption still holds.
	 *
	 * @param NotificationType $type       The kennisgewing type.
	 * @param int              $subject_id The resolved subject id (assumed: the challenge post).
	 * @return int
	 */
	private static function daysUntilCloseFor( NotificationType $type, int $subject_id ): int {
		if ( NotificationType::Uitdaging !== $type || $subject_id <= 0 || ! function_exists( 'get_post_meta' ) ) {
			return 0;
		}

		$raw = (string) get_post_meta( $subject_id, FieldSets::UITDAGING_DEADLINE, true );

		return self::daysUntilClose( $raw, Sast::now() );
	}
}
