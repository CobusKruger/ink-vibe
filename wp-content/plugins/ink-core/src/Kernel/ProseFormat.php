<?php
/**
 * The single-source inline-prose allowlist for INK light-editor content.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Kernel;

defined( 'ABSPATH' ) || exit;

/**
 * The ONE source of truth for which inline marks may persist in INK prose bodies.
 *
 * The light editor (FR-18) exists so shaped / concrete poetry survives: the only
 * marks allowed in a gedig/storie/artikel body are bold, italic, and a hard line
 * break — no attributes (so no inline `style`/`class` → no font / colour / size
 * controls), and no block/media/link/control tags.
 *
 * This list is consumed at BOTH ends of the content lifecycle:
 *   - write time — {@see \Ink\Submission\ProseSanitizer} sanitises the submitted
 *     body to this allowlist (Story 6.3);
 *   - read time — {@see \Ink\Engagement\GedigBody} re-sanitises the stored body on
 *     output when it renders the stanza-aware poem (Story 7.2).
 *
 * Defining it once here in the Kernel (the shared base every module depends on,
 * which itself depends on nothing — deptrac.yaml) means the write-time and
 * read-time allowlists are provably one set: a future loosening of one cannot
 * silently diverge from the other (the cross-story durability rule applied to the
 * prose-fidelity guarantee). Pure value utility — no WordPress state, no business
 * logic — like {@see Scalar} and the Kernel enums.
 *
 * @package Ink\Core
 */
final class ProseFormat {

	/**
	 * The strict inline allowlist: bold, italic, hard break — NO attributes.
	 *
	 * Empty attribute arrays mean any `style` / `class` / `color` / `size` on an
	 * allowed tag is dropped; any tag NOT listed (heading, table, img, span, a, ul,
	 * blockquote, iframe, …) is reduced to its text content by {@see wp_kses()}.
	 *
	 * @return array<string, array<string, mixed>> The `wp_kses` allowed-HTML map.
	 */
	public static function allowedInlineTags(): array {
		return array(
			'strong' => array(),
			'b'      => array(),
			'em'     => array(),
			'i'      => array(),
			'br'     => array(),
		);
	}
}
