<?php
/**
 * Per-CPT schema `@type` refinement for Rank Math JSON-LD — Story 18.1 (NFR-4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Seo;

use Ink\Content\PostTypes;

defined( 'ABSPATH' ) || exit;

/**
 * Maps each reader-facing INK CPT to its correct schema.org `@type` and applies
 * it to Rank Math's JSON-LD output.
 *
 * Rank Math defaults every singular post to `Article`. A poem (`gedig`) and a
 * short story (`storie`) are creative works, not articles; only `artikel` is a
 * true `Article`. This collaborator rewrites the `@type` of the rich-snippet
 * node Rank Math emits for an INK singular, reading the reader-facing CPT slugs
 * from the migration-load-bearing single source {@see PostTypes::readableTypes()}
 * — the gedig/storie/artikel literals are never duplicated here.
 *
 * Inert without Rank Math: {@see register()} only adds the filter when
 * {@see rankMathActive()} is true, so the module is a no-op (and never fatals)
 * on a site that has not yet installed Rank Math. The Rank Math presence check
 * and the current-post-type read are protected overridable seams so unit tests
 * vary them by subclass-and-override rather than global stubbing.
 *
 * @package Ink\Core
 */
class SchemaTypes {

	/**
	 * The schema.org `@type` for `gedig` and `storie` — both are creative works.
	 */
	public const TYPE_CREATIVE_WORK = 'CreativeWork';

	/**
	 * The schema.org `@type` for `artikel`.
	 */
	public const TYPE_ARTICLE = 'Article';

	/**
	 * Wire the JSON-LD refinement — only when Rank Math is present.
	 */
	public function register(): void {
		if ( ! $this->rankMathActive() ) {
			return;
		}

		add_filter( 'rank_math/json_ld', array( $this, 'filterJsonLd' ), 99, 2 );
	}

	/**
	 * The schema `@type` for a reader-facing INK CPT, or null for anything else.
	 *
	 * @param string $cpt A post-type slug.
	 */
	public function defaultTypeFor( string $cpt ): ?string {
		return match ( $cpt ) {
			PostTypes::GEDIG, PostTypes::STORIE => self::TYPE_CREATIVE_WORK,
			PostTypes::ARTIKEL                  => self::TYPE_ARTICLE,
			default                             => null,
		};
	}

	/**
	 * Rewrite the rich-snippet node's `@type` for an INK singular.
	 *
	 * Rank Math passes the assembled JSON-LD graph (an associative array keyed by
	 * node name, e.g. `richSnippet`/`article`). For a singular INK CPT we set the
	 * `@type` of each Article-family node to the mapped value; non-INK posts and
	 * non-singular requests are returned untouched.
	 *
	 * @param array<string, mixed> $data   The JSON-LD graph Rank Math will output.
	 * @param mixed                $jsonld The Rank Math JsonLD instance (part of the
	 *                                     filter contract; not read here).
	 * @return array<string, mixed>
	 */
	public function filterJsonLd( array $data, $jsonld ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- $jsonld is required by the rank_math/json_ld filter signature.
		$type = $this->defaultTypeFor( $this->currentPostType() );

		if ( null === $type ) {
			return $data;
		}

		foreach ( $data as $key => $node ) {
			if ( is_array( $node ) && isset( $node['@type'] ) && $this->isArticleType( $node['@type'] ) ) {
				$data[ $key ]['@type'] = $type;
			}
		}

		return $data;
	}

	/**
	 * The schema.org Article-family `@type`s Rank Math may emit for a singular —
	 * the article type is configurable (Article / BlogPosting / NewsArticle).
	 * We refine any of these to the CPT's true creative type.
	 */
	private const ARTICLE_FAMILY = array( 'Article', 'BlogPosting', 'NewsArticle' );

	/**
	 * Whether a Rank Math node `@type` is an Article-family type we should refine.
	 *
	 * @param mixed $nodeType The node's `@type` (string or list of strings).
	 */
	protected function isArticleType( $nodeType ): bool {
		$types = is_array( $nodeType ) ? $nodeType : array( $nodeType );

		foreach ( $types as $candidate ) {
			if ( is_string( $candidate ) && in_array( $candidate, self::ARTICLE_FAMILY, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Whether Rank Math is active. Overridable test seam.
	 */
	protected function rankMathActive(): bool {
		return defined( 'RANK_MATH_VERSION' ) || class_exists( '\RankMath' );
	}

	/**
	 * The post type of the current singular request. Overridable test seam.
	 */
	protected function currentPostType(): string {
		$type = get_post_type();

		return is_string( $type ) ? $type : '';
	}
}
