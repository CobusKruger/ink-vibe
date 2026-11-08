<?php
/**
 * Once-off: link hand-sorted legacy posts to their content-type category.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Prod posts that carry no content-type category get one here, so {@see PostReclassifier}
 * classifies them as gedig / storie / artikel instead of dropping them into `skryfwerk`.
 *
 * The owner sorted these posts by hand. The decision lives in {@see MAP} (category slug →
 * post ids) because it exists nowhere else. Runs as the `link-categories` step of
 * `wp ink migrate-all`, **before `posts`**: every later step (winners, Gradering history,
 * entry Gradering, counts, search) then sees the final post types. Posts stay plain
 * `post`; only a category link is added, and existing categories are kept.
 *
 * Idempotent (linking twice changes nothing). WP-CLI only (`wp ink link-categories`).
 * A post that is gone or no longer a plain `post`, or a missing category, is reported and skipped.
 *
 * @package Ink\Core
 */
class PostCategoryLinker {

	/**
	 * The WP-CLI command name (`wp ink link-categories`).
	 *
	 * @var string
	 */
	public const CLI_COMMAND = 'ink link-categories';

	/**
	 * Category slug → prod post ids to link to it. Slugs must be keys of
	 * {@see PostReclassifier::CATEGORY_CPT_MAP}.
	 *
	 * @var array<string, list<int>>
	 */
	public const MAP = array(
		'gedigte' => array(
			51747, 1041, 1179, 1605, 9589, 11511, 12187, 12857, 43054, 43175,
			3404, 45612, 63293, 66422, 66481, 55437, 15809, 1419, 1449, 20340,
			21360, 54254, 54264, 55125, 58389, 58392, 59700, 59951, 2563, 4433,
			4487, 6393, 10971, 10973, 19005, 20141, 39027, 61159, 61162, 23773,
			24201, 29362, 55388, 58399, 58401, 59250, 59580, 59638, 62331, 62333,
			28169, 47882, 55844, 55846, 57285, 57288, 59939, 59941, 57295, 22732,
			23175, 55204, 55205, 55482, 55483, 55841, 55842, 58394, 58397, 47121,
			50456, 55804, 43023, 63244, 63516, 63983, 65648, 65826, 66599, 45739,
			57302, 58964, 58966, 60427, 54233, 54258, 60141, 60161, 60252, 60333,
			60493, 60750, 60758, 54161, 55060, 57109, 62246, 60159, 57006, 57115,
			57117, 57121, 59170, 59679, 56795, 14838, 15462, 15464, 16075, 57291,
			59944, 60140, 60402, 65575, 65576, 64080, 64085, 64313, 60519, 62248,
			63893, 63685, 65851, 68062, 68432,
		),
		'verhale-kortverhale' => array(
			2803, 14806, 16247, 5527, 6621, 9059, 9563, 12326, 12461, 12648,
			14162, 6244, 64287, 18772, 54203, 63839, 63840, 64270, 65573, 65852,
			67321, 65905, 68001,
		),
		'artikels' => array(
			3787, 62483, 63059,
		),
	);

	/**
	 * Register the once-off WP-CLI trigger — ONLY under WP-CLI (never a web request).
	 */
	public function register(): void {
		if ( ! ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			return;
		}

		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			self::CLI_COMMAND,
			function (): void {
				$summary = $this->run();

				foreach ( $summary['problems'] as $problem ) {
					\WP_CLI::warning( $problem );
				}

				\WP_CLI::success(
					sprintf(
						'Kategorieë gekoppel: %d plasings, %d reeds gekoppel, %d oorgeslaan.',
						$summary['linked'],
						$summary['already'],
						count( $summary['problems'] )
					)
				);
			}
		);
	}

	/**
	 * Every post id in the map. Pure.
	 *
	 * @return list<int>
	 */
	public static function allIds(): array {
		return array_merge( ...array_values( self::MAP ) );
	}

	/**
	 * Slugs in the map that {@see PostReclassifier} would not recognise. Pure.
	 *
	 * @return list<string>
	 */
	public static function unmappedSlugs(): array {
		return array_values( array_diff( array_keys( self::MAP ), array_keys( PostReclassifier::CATEGORY_CPT_MAP ) ) );
	}

	/**
	 * Link the posts.
	 *
	 * @return array{linked:int, already:int, problems:list<string>}
	 */
	public function run(): array {
		$linked   = 0;
		$already  = 0;
		$problems = array();

		foreach ( self::MAP as $slug => $ids ) {
			$term_id = $this->categoryId( $slug );

			if ( $term_id <= 0 ) {
				$problems[] = sprintf( 'Kategorie "%s" bestaan nie; %d plasings oorgeslaan.', $slug, count( $ids ) );
				continue;
			}

			foreach ( $ids as $id ) {
				if ( 'post' !== $this->postType( $id ) ) {
					$problems[] = sprintf( 'Plasing %d is nie (meer) \'n gewone plasing nie; oorgeslaan.', $id );
					continue;
				}

				if ( $this->hasCategory( $id, $term_id ) ) {
					++$already;
					continue;
				}

				$this->addCategory( $id, $term_id );
				++$linked;
			}
		}

		return array(
			'linked'   => $linked,
			'already'  => $already,
			'problems' => $problems,
		);
	}

	/**
	 * A category's term id by slug, or 0. Overridable seam.
	 *
	 * @param string $slug The category slug.
	 */
	protected function categoryId( string $slug ): int {
		$term = get_term_by( 'slug', $slug, 'category' );

		return $term instanceof \WP_Term ? (int) $term->term_id : 0;
	}

	/**
	 * A post's type, or '' when it does not exist. Overridable seam.
	 *
	 * @param int $post_id The post.
	 */
	protected function postType( int $post_id ): string {
		$type = get_post_type( $post_id );

		return is_string( $type ) ? $type : '';
	}

	/**
	 * Whether the post is already in the category. Overridable seam.
	 *
	 * @param int $post_id The post.
	 * @param int $term_id The category.
	 */
	protected function hasCategory( int $post_id, int $term_id ): bool {
		return has_term( $term_id, 'category', $post_id );
	}

	/**
	 * Add the category, keeping the post's others. Overridable seam.
	 *
	 * @param int $post_id The post.
	 * @param int $term_id The category.
	 */
	protected function addCategory( int $post_id, int $term_id ): void {
		wp_set_object_terms( $post_id, array( $term_id ), 'category', true );
	}
}
