<?php
/**
 * From a winner list's category label to a results tab — `01-uitslae` §5.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * For a win with no post, the importer stores the post type this gives it. There is no
 * second category map: the parser's categories are labels, not slugs, so this holds only the
 * label-to-slug step (`migration-plan.md` "Current state"), and
 * {@see PostReclassifier::cptForCategorySlugs()} decides.
 *
 * **The trap this exists for:** the map has `algemeen => storie`, for the `algemeen-verhale`
 * tree, so sending the label "Algemeen" straight in gives Stories, against the ruling.
 *
 * Pure.
 *
 * @package Ink\Core
 */
final class WinCategory {

	/**
	 * Label to category slug.
	 *
	 * @var array<string, string>
	 */
	public const LABEL_SLUGS = array(
		'Gedigte'      => 'gedigte',
		'Verhale'      => 'verhale-kortverhale',
		'Artikels'     => 'artikels',
		'Rubrieke'     => 'rubrieke',
		'Prosa'        => 'prosa',
		'Meningstukke' => 'algemeengorrel',
		'Algemeen'     => 'algemeengorrel',
	);

	/**
	 * The post type for a label, or null when the label isn't known: the importer reports
	 * that instead of letting the map default it to `skryfwerk`.
	 *
	 * @param string $label The category label the parser reads.
	 * @return string|null
	 */
	public static function postTypeFor( string $label ): ?string {
		$slug = self::LABEL_SLUGS[ trim( $label ) ] ?? null;

		return null === $slug ? null : PostReclassifier::cptForCategorySlugs( array( $slug ) );
	}
}
