<?php
/**
 * Kennisgewing (notification) type enum — Story 9.9 (FR-44).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * The INK kennisgewing categories (the typed `component_action` for the single
 * BuddyPress `ink` notification component, AD-5).
 *
 * A closed value set — the persisted DB value is the lowercase string used as
 * the BP `component_action`; never duplicate these literals across the codebase.
 *
 * @package Ink\Core
 */
enum NotificationType: string {

	/** A new Gemeenskapsreaksie on your work. */
	case Reaksie = 'reaksie';

	/** You were @mentioned in a reaksie. */
	case Mention = 'mention';

	/** A skrywer you follow published new work. */
	case VolgWerk = 'volg_werk';

	/** An uitdaging announcement / deadline (source: Epic 12). */
	case Uitdaging = 'uitdaging';

	/** Your lidmaatskap is expiring (shares the 4.8 lifecycle anchor). */
	case LidmaatskapVerval = 'lidmaatskap_verval';

	/** A read-receipt milestone on your work (R7, source: Story 9.11). */
	case Ontvangs = 'ontvangs';

	/**
	 * The BuddyPress notification component INK registers all types under.
	 */
	public const COMPONENT = 'ink';
}
