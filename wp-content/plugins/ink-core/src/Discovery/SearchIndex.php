<?php
/**
 * Folded search index maintenance — Story 8.4 (FR-35, AD-7).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Discovery;

use Ink\Content\PostTypes;
use Ink\I18n\Terms;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the accent-folded search indexes the {@see Search} block matches on.
 *
 * AD-7's normalized-index fallback: a work's title + body and a writer's name +
 * bio + published-form labels are folded ({@see Diacritics::fold()}) on save into
 * `_ink_soek_indeks` (post) / `ink_skrywer_soek_indeks` (user). Search folds the
 * query the same way and matches `LIKE`, so accents never matter. The assembly is
 * pure (testable); the hooks are thin glue.
 *
 * Conflation-clean: `PostTypes` + the 8.3 `SkrywerIndex` flags + WP core only;
 * zero `Ink\Tiers`/`Ink\Entitlement`.
 *
 * @package Ink\Core
 */
final class SearchIndex {

	/**
	 * Post-meta: a work's folded search index (title + body).
	 *
	 * @var string
	 */
	public const WORKS_META = '_ink_soek_indeks';

	/**
	 * User-meta: a writer's folded search index (name + bio + genre labels).
	 *
	 * @var string
	 */
	public const SKRYWER_META = 'ink_skrywer_soek_indeks';

	/**
	 * Hook the work + writer index maintenance.
	 */
	public function register(): void {
		add_action( 'save_post', array( self::class, 'onSavePost' ), 20, 2 );
		add_action( 'profile_update', array( self::class, 'onProfileChange' ) );
		add_action( 'user_register', array( self::class, 'onProfileChange' ) );
		// A new publication can add a form ("genre") to the writer's index.
		add_action( 'transition_post_status', array( self::class, 'onTransition' ), 20, 3 );
	}

	/**
	 * The readable bydrae types whose body feeds the works index.
	 *
	 * @return list<string>
	 */
	public static function readableTypes(): array {
		return array( PostTypes::GEDIG, PostTypes::STORIE, PostTypes::ARTIKEL );
	}

	/**
	 * Assemble a work's folded search index. Pure.
	 *
	 * @param string $title The work title.
	 * @param string $body  The work body (HTML — tags are stripped).
	 * @return string
	 */
	public static function worksIndexFor( string $title, string $body ): string {
		return Diacritics::fold( $title . ' ' . wp_strip_all_tags( $body ) );
	}

	/**
	 * Assemble a writer's folded search index from its parts. Pure.
	 *
	 * @param string       $name         Display name.
	 * @param string       $bio          Profile bio/description.
	 * @param list<string> $genre_labels The Afrikaans labels of the writer's forms.
	 * @return string
	 */
	public static function skrywerIndexValue( string $name, string $bio, array $genre_labels ): string {
		return Diacritics::fold( $name . ' ' . $bio . ' ' . implode( ' ', $genre_labels ) );
	}

	/**
	 * Maintain a work's index on save (skip autosave/revision; readable bydrae only).
	 *
	 * @param int    $post_id The post id.
	 * @param object $post    The post (a `WP_Post` at runtime).
	 */
	public static function onSavePost( int $post_id, $post ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		if ( ! is_object( $post ) || ! isset( $post->post_type ) || ! in_array( (string) $post->post_type, self::readableTypes(), true ) ) {
			return;
		}

		$title = isset( $post->post_title ) ? (string) $post->post_title : '';
		$body  = isset( $post->post_content ) ? (string) $post->post_content : '';

		update_post_meta( $post_id, self::WORKS_META, self::worksIndexFor( $title, $body ) );
	}

	/**
	 * Rebuild a writer's index on profile change / registration.
	 *
	 * @param int $user_id The user id.
	 */
	public static function onProfileChange( $user_id ): void {
		self::rebuildSkrywer( (int) $user_id );
	}

	/**
	 * Rebuild the author's index when a publication adds/refreshes a form.
	 *
	 * @param string $new_status The new post status.
	 * @param string $old_status The previous post status.
	 * @param object $post       The post (a `WP_Post` at runtime).
	 */
	public static function onTransition( string $new_status, string $old_status, $post ): void {
		if ( 'publish' !== $new_status || ! is_object( $post ) || ! isset( $post->post_type, $post->post_author ) ) {
			return;
		}

		if ( ! in_array( (string) $post->post_type, self::readableTypes(), true ) ) {
			return;
		}

		self::rebuildSkrywer( (int) $post->post_author );
	}

	/**
	 * Rebuild + persist a writer's folded search index.
	 *
	 * @param int $user_id The writer.
	 */
	public static function rebuildSkrywer( int $user_id ): void {
		if ( $user_id <= 0 ) {
			return;
		}

		$name = (string) get_the_author_meta( 'display_name', $user_id );
		$bio  = (string) get_the_author_meta( 'description', $user_id );

		$genre_labels = array();

		foreach ( self::readableTypes() as $type ) {
			if ( '1' === (string) get_user_meta( $user_id, SkrywerIndex::formFlagKey( $type ), true ) ) {
				$genre = array_search(
					$type,
					array(
						'digkuns'  => PostTypes::GEDIG,
						'prosa'    => PostTypes::STORIE,
						'artikels' => PostTypes::ARTIKEL,
					),
					true
				);

				if ( is_string( $genre ) ) {
					$genre_labels[] = Terms::label( 'skrywer_genre_' . $genre );
				}
			}
		}

		update_user_meta( $user_id, self::SKRYWER_META, self::skrywerIndexValue( $name, $bio, $genre_labels ) );
	}
}
