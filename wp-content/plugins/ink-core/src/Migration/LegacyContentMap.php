<?php
/**
 * Committed mapping of legacy Biblioteek/Opleiding content — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

use Ink\Content\PostTypes;
use Ink\Content\Taxonomies;

defined( 'ABSPATH' ) || exit;

/**
 * Where each legacy `bibliotekon` / `opleiding` tree goes. Prod keeps this content in
 * two hierarchical legacy CPTs (not core posts); the first path segment (the "root")
 * decides the destination. The table is the single committed record of that human
 * decision, so a re-run on a fresh prod copy needs no manual step.
 *
 * Decisions per root slug:
 *  - `migrate`   → the item becomes `biblioteek_item` / `opleiding_artikel` and gets one
 *                  term (`genre` for the shelf or collection, `vaardigheid` for training);
 *  - `skip`      → deliberately not migrated (winners pages are replaced by the dynamic
 *                  Ontdek tab; old announcements are dropped);
 *  - `container` → an empty menu/section placeholder; its children are migrated, it is not.
 *
 * Some opleiding items live under another root but belong elsewhere (the dictionaries
 * under the old "gedigte" container): {@see ITEM_OVERRIDES} is keyed by the item's own slug.
 *
 * Pure, zero WordPress: unit-tests without a database.
 *
 * @package Ink\Core
 */
final class LegacyContentMap {

	public const LEGACY_LIBRARY  = 'bibliotekon';
	public const LEGACY_TRAINING = 'opleiding';

	public const MIGRATE   = 'migrate';
	public const SKIP      = 'skip';
	public const CONTAINER = 'container';

	/**
	 * The Biblioteek shelf that replaces the dictionaries' old Opleiding home.
	 *
	 * @var string
	 */
	public const WOORDEBOEKE_TERM = 'Woordeboeke';

	/**
	 * Library root slug → destination. `term` is the genre term name.
	 *
	 * @var array<string, array{action:string, term?:string}>
	 */
	private const LIBRARY_ROOTS = array(
		'gedigte'                                  => array(
			'action' => self::MIGRATE,
			'term'   => 'Gedigte',
		),
		'vrystaatstories-deur-henning-van-aswegen' => array(
			'action' => self::MIGRATE,
			'term'   => 'Vrystaatstories deur Henning van Aswegen',
		),
		'oom-pine-se-jagstories'                   => array(
			'action' => self::MIGRATE,
			'term'   => 'Oom Pine se jagstories',
		),
		'biblioteek-liegstories'                   => array(
			'action' => self::MIGRATE,
			'term'   => 'Liegstories',
		),
		'vergete-helde-uit-die-geskiedenis'        => array(
			'action' => self::MIGRATE,
			'term'   => 'Vergete Helde uit die geskiedenis',
		),
		'flipvis-se-verhale'                       => array(
			'action' => self::MIGRATE,
			'term'   => 'Flipvis se verhale',
		),
		'christo-visser-se-vriend-jan'             => array(
			'action' => self::MIGRATE,
			'term'   => 'Christo Visser se vriend Jan',
		),
		'gert-rossouw-se-briewe-aan-celeste'       => array(
			'action' => self::MIGRATE,
			'term'   => 'Gert Rossouw se briewe aan Celeste',
		),
		'fak-elektroniese-sangbundel-kitaardrukke' => array(
			'action' => self::MIGRATE,
			'term'   => 'FAK - Elektroniese sangbundel EN Kitaardrukke',
		),
		'jakkals-en-wolf-ander-kinderstories'      => array(
			'action' => self::MIGRATE,
			'term'   => 'Jakkals en Wolf & ander kinderstories',
		),
		'kinderrympies'                            => array(
			'action' => self::MIGRATE,
			'term'   => 'Kinderrympies',
		),
		'kinderrympies-vingerversies'              => array(
			'action' => self::MIGRATE,
			'term'   => 'Kinderrympies',
		),
		// Replaced by the dynamic Ontdek winners tab; 301s are generated separately.
		'projek-wenners'                           => array( 'action' => self::SKIP ),
		// Belongs with the gala pages (a Page of the same slug already exists).
		'fotos-11-november-2023-8ste-gala-aand'    => array( 'action' => self::SKIP ),
	);

	/**
	 * Training root slug → destination. `term` is the vaardigheid term name.
	 *
	 * @var array<string, array{action:string, term?:string}>
	 */
	private const TRAINING_ROOTS = array(
		// Digkuns.
		'stof-en-tegniek-gert-strydom'                => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'leestekens-in-digkuns'                       => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'poetiese-begrippe'                           => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'pastiche'                                    => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'monorym'                                     => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'riglyne-vir-die-ontleding-van-gedigte-l-w-slegs-riglyne' => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'riglyne-ten-opsigte-van-kommentaarlewering-op-gedigte-deur-milla' => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'wenke-by-digkuns-jopie-koen'                 => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		'wat-maak-van-n-gedig-n-goeie-wengedig-driekie-grobler' => array(
			'action' => self::MIGRATE,
			'term'   => 'Digkuns',
		),
		// Skryfkuns.
		'riglyne-om-n-radiodrama-of-verhaal-te-skryf' => array(
			'action' => self::MIGRATE,
			'term'   => 'Skryfkuns',
		),
		'101-wenke-vir-die-skryf-van-fiksie-deur-elize-parker' => array(
			'action' => self::MIGRATE,
			'term'   => 'Skryfkuns',
		),
		'kortverhale-wenke'                           => array(
			'action' => self::MIGRATE,
			'term'   => 'Skryfkuns',
		),
		'hoe-om-n-grilstorie-te-skryf-de-wet-hugo'    => array(
			'action' => self::MIGRATE,
			'term'   => 'Skryfkuns',
		),
		'4-skryfwenke-annerle-barnard'                => array(
			'action' => self::MIGRATE,
			'term'   => 'Skryfkuns',
		),
		// Taalgidse.
		'idiome-en-gesegdes-in-afrikaans'             => array(
			'action' => self::MIGRATE,
			'term'   => 'Taalgidse',
		),
		'n-kopkrappery-oor-koppeltekens'              => array(
			'action' => self::MIGRATE,
			'term'   => 'Taalgidse',
		),
		'woordsoorte-viva-sophia-kapp'                => array(
			'action' => self::MIGRATE,
			'term'   => 'Taalgidse',
		),
		// Algemene wenke.
		'plagiaat-letterdiefstal'                     => array(
			'action' => self::MIGRATE,
			'term'   => 'Algemene wenke',
		),
		'n-paar-wenke-vir-my-medeskrywers-dirk-loriet-netwerk-24' => array(
			'action' => self::MIGRATE,
			'term'   => 'Algemene wenke',
		),
		// Dictionaries move out of Opleiding to the Biblioteek (handled via ROOT_TARGETS below).
		'woordeboek-wat'                              => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		'e-woordeboeke'                               => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		'drietalige-idoom-woordeboek-pdf'             => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		'meertalige-aanlynwoordeboek'                 => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		'woordelys-en-spelreels-uitgawe-6-3'          => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		// Old announcement: not migrated, not linked.
		'inligtingstuk-stergradering-projekte-en-slypskool' => array( 'action' => self::SKIP ),
		// Empty section / menu placeholders: their children are migrated, they are not.
		'opleiding'                                   => array( 'action' => self::CONTAINER ),
		'gedigte'                                     => array(
			'action' => self::CONTAINER,
			'term'   => 'Digkuns',
		),
		'digkuns'                                     => array( 'action' => self::CONTAINER ),
		'skryfkuns'                                   => array( 'action' => self::CONTAINER ),
		'taalgidse'                                   => array( 'action' => self::CONTAINER ),
		'woordeboeke'                                 => array( 'action' => self::CONTAINER ),
		'algemene-wenke'                              => array( 'action' => self::CONTAINER ),
	);

	/**
	 * Training items that move to the Biblioteek (their own slug, wherever they sit).
	 *
	 * @var list<string>
	 */
	private const MOVES_TO_LIBRARY = array(
		'woordeboek-wat',
		'e-woordeboeke',
		'drietalige-idoom-woordeboek-pdf',
		'meertalige-aanlynwoordeboek',
		'woordelys-en-spelreels-uitgawe-6-3',
		'dignet-woordeboek',
		'letterkundige-terme-woordeboek',
	);

	/**
	 * Item-level overrides for items nested under the wrong root, keyed by the item's slug.
	 *
	 * @var array<string, array{action:string, term?:string}>
	 */
	private const ITEM_OVERRIDES = array(
		'afrikaanse-taalgids'            => array(
			'action' => self::MIGRATE,
			'term'   => 'Taalgidse',
		),
		'dignet-woordeboek'              => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
		'letterkundige-terme-woordeboek' => array(
			'action' => self::MIGRATE,
			'term'   => self::WOORDEBOEKE_TERM,
		),
	);

	/**
	 * Decide what happens to one legacy item. Pure.
	 *
	 * @param string       $legacy_type `bibliotekon` or `opleiding`.
	 * @param list<string> $slugs       The slug chain from the root to the item itself.
	 * @return array{action:string, cpt:?string, taxonomy:?string, term:?string, root:string}
	 */
	public static function decide( string $legacy_type, array $slugs ): array {
		$slugs = array_values( array_map( static fn ( $s ): string => strtolower( trim( (string) $s ) ), $slugs ) );
		$root  = $slugs[0] ?? '';
		$self  = $slugs[ count( $slugs ) - 1 ] ?? '';

		$table = self::LEGACY_LIBRARY === $legacy_type ? self::LIBRARY_ROOTS : self::TRAINING_ROOTS;
		$rule  = self::ITEM_OVERRIDES[ $self ] ?? $table[ $root ] ?? null;

		if ( null === $rule ) {
			// Unknown root: surface it, never guess (the run reports these).
			return self::result( self::SKIP, null, null, null, $root );
		}

		// A container's own row is not migrated, but its children inherit its term.
		if ( self::CONTAINER === $rule['action'] ) {
			if ( count( $slugs ) === 1 ) {
				return self::result( self::CONTAINER, null, null, null, $root );
			}

			$rule = array(
				'action' => self::MIGRATE,
				'term'   => $rule['term'] ?? null,
			);
		}

		if ( self::SKIP === $rule['action'] ) {
			return self::result( self::SKIP, null, null, null, $root );
		}

		$to_library = self::LEGACY_LIBRARY === $legacy_type || in_array( $self, self::MOVES_TO_LIBRARY, true );
		$cpt        = $to_library ? PostTypes::BIBLIOTEEK_ITEM : PostTypes::OPLEIDING_ARTIKEL;
		$taxonomy   = $to_library ? Taxonomies::GENRE : Taxonomies::VAARDIGHEID;

		return self::result( self::MIGRATE, $cpt, $taxonomy, $rule['term'] ?? null, $root );
	}

	/**
	 * The root slugs that have no rule for a legacy type (reported, never guessed). Pure.
	 *
	 * @param string       $legacy_type `bibliotekon` or `opleiding`.
	 * @param list<string> $roots       The root slugs found in the data.
	 * @return list<string>
	 */
	public static function unmappedRoots( string $legacy_type, array $roots ): array {
		$table = self::LEGACY_LIBRARY === $legacy_type ? self::LIBRARY_ROOTS : self::TRAINING_ROOTS;

		return array_values( array_unique( array_filter( $roots, static fn ( string $r ): bool => ! isset( $table[ strtolower( $r ) ] ) ) ) );
	}

	/**
	 * The pre-migration URL of a legacy item (the 301 source), from its slug chain. Pure.
	 *
	 * @param string       $legacy_type `bibliotekon` or `opleiding`.
	 * @param list<string> $slugs       The slug chain from the root to the item.
	 */
	public static function legacyPath( string $legacy_type, array $slugs ): string {
		$prefix = self::LEGACY_LIBRARY === $legacy_type ? 'biblioteek' : 'opleiding';

		return '/' . $prefix . '/' . implode( '/', $slugs ) . '/';
	}

	/**
	 * Build a decision row. Pure.
	 *
	 * @return array{action:string, cpt:?string, taxonomy:?string, term:?string, root:string}
	 */
	private static function result( string $action, ?string $cpt, ?string $taxonomy, ?string $term, string $root ): array {
		return array(
			'action'   => $action,
			'cpt'      => $cpt,
			'taxonomy' => $taxonomy,
			'term'     => $term,
			'root'     => $root,
		);
	}
}
