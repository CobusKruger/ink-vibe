<?php
/**
 * Shared "is this a readable bydrae?" guard for engagement write paths.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Engagement;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * The single source for "may engagement be written against this post?".
 *
 * Engagement write paths (line reactions, Gemeenskapsreaksies, leeslys) all target
 * a published bydrae — gedig / storie / artikel. This concentrates the gate so a
 * crafted REST call cannot attach engagement to a Page, the `skryfwerk` migration
 * bucket, an attachment, or any other published object, and so the rule is defined
 * (and tested) ONCE rather than copied across three controllers.
 *
 * @package Ink\Core
 */
final class Readable {

	/**
	 * Whether a post is a published, readable bydrae (gedig/storie/artikel).
	 *
	 * @param int $post_id The post.
	 * @return bool
	 */
	public static function isBydrae( int $post_id ): bool {
		return $post_id > 0
			&& 'publish' === get_post_status( $post_id )
			&& in_array(
				get_post_type( $post_id ),
				array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL ),
				true
			);
	}
}
