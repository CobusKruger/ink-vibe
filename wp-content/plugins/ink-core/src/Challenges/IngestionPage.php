<?php
/**
 * Results-ingestion admin screen — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\PostTypes;
use Ink\Kernel\Capabilities;
use Ink\Kernel\Scalar;
use Ink\Kernel\Tier;

defined( 'ABSPATH' ) || exit;

/**
 * The redakteur results-ingestion screen (Story 12A.3, R2).
 *
 * A submenu under the Uitdagings menu where the editor pastes the judges' results as
 * PLAIN TEXT, sees the dekkingsverslag (coverage report) reconciling the parse against
 * the stored EntryIDs, and — only after an explicit confirm, and only when no hard gap
 * blocks it — commits the irreversible result writes via {@see Ingestion}.
 *
 * The parse ({@see ResultsParser}), the coverage ({@see Coverage}), and the commit
 * pipeline ({@see Ingestion}) are all pure/seam-isolated; this shell gathers the round's
 * stored EntryIDs (the overridable {@see entriesFor()} seam, mirroring {@see CollationPage}),
 * resolves the parsed EntryIDs to post ids, renders, and drives the confirm gate. The
 * unit-testable integration point is {@see analyse()}. Every write is nonce +
 * `MANAGE_CHALLENGES` gated; all superglobal reads go through `is_scalar` → `wp_unslash`
 * → sanitise; all output is escaped.
 *
 * @package Ink\Core
 */
class IngestionPage {

	/**
	 * The admin-screen slug (submenu under the uitdaging CPT).
	 *
	 * @var string
	 */
	public const SCREEN_SLUG = 'ink-uitdaging-uitslae';

	/**
	 * Nonce action for the parse + commit writes.
	 */
	private const NONCE = 'ink_uitdaging_uitslae_nonce';

	/**
	 * Register the admin screen. Invoked from {@see Module::register()} (on `init`).
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'registerScreen' ) );
	}

	/**
	 * Register the ingestion submenu under the Uitdagings CPT menu.
	 */
	public function registerScreen(): void {
		add_submenu_page(
			'edit.php?post_type=' . PostTypes::UITDAGING,
			__( 'Uitslae-invoer', 'ink-core' ),
			__( 'Uitslae-invoer', 'ink-core' ),
			Capabilities::MANAGE_CHALLENGES,
			self::SCREEN_SLUG,
			array( $this, 'render' )
		);
	}

	/**
	 * Analyse pasted results for a round: parse them, match every entry the judges wrote about to
	 * an entry of the round, build the coverage report, and resolve the winners, commentary and
	 * mentions to post ids. The unit-tested entry point.
	 *
	 * The judges' document is read as a list of **entries** (each commented entry, plus any winner
	 * the judges placed without a comment). Each is matched by title first, then by number
	 * ({@see EntryMatcher}); a winner follows the entry it names. `$corrections` carry the editor's
	 * changes to those matches (entry index → post id; 0 = none chosen, -1 = leave it out), so the
	 * report, the winners and the commentary all reflect what will really be written.
	 *
	 * @param int                     $uitdaging_id The round.
	 * @param string                  $text         The pasted plain-text results.
	 * @param array<int, int>|null    $corrections  The editor's match corrections.
	 * @return array{report:array<string,mixed>, winners:list<array{post_id:int, rank:int, author_id:int}>, commentary:list<array{post_id:int, title:string, text:string}>, mentions:list<string>, problems:list<string>, winner_rows:list<array<string,mixed>>, doc_rows:list<array<string,mixed>>, without:list<string>, options:array<int,string>}
	 */
	public function analyse( int $uitdaging_id, string $text, ?array $corrections = null ): array {
		$entries = $this->entriesFor( $uitdaging_id );
		$map     = self::storedMap( $entries );
		$parsed  = ResultsParser::parse( $text );

		$by_post  = self::entriesByPost( $entries );
		$by_key   = array();
		$matcher  = array();

		foreach ( $by_post as $entry ) {
			$by_key[ self::keyOf( $entry ) ] = $entry;
			$matcher[]                       = array(
				'id'     => $entry['post_id'],
				'type'   => $entry['unsorted'] ? '' : $entry['type'], // Unclassified: any type may be meant.
				'pool'   => $entry['pool'],
				'number' => $entry['number'],
				'title'  => $entry['title'],
			);
		}

		// The entries the judges wrote about: every comment block, then any winner placed with no comment.
		$docs  = array();
		$index = array();

		foreach ( $parsed['commentary'] as $block ) {
			$parsed_id = (string) ( $block['entry_id'] ?? '' );
			$type      = (string) ( $block['type'] ?? self::typeOfEntryId( $parsed_id ) );
			$number    = (int) ( $block['number'] ?? self::numberOfEntryId( $parsed_id ) );

			// An entry without a number (0) cannot be named by a winner, so it is not indexed.
			if ( $number > 0 ) {
				$index[ $type . '|' . $number ] ??= count( $docs );
			}

			$docs[] = array(
				'type'      => $type,
				'grade'     => (string) ( $block['grade'] ?? '' ),
				'number'    => $number,
				'title'     => trim( (string) ( $block['title'] ?? '' ) ),
				'text'      => (string) ( $block['text'] ?? '' ),
				'parsed_id' => $parsed_id,
				'block'     => true,
			);
		}

		foreach ( $parsed['winners'] as $winner ) {
			$parsed_id = (string) ( $winner['entry_id'] ?? '' );
			$type      = (string) ( $winner['type'] ?? '' );
			$number    = self::numberOfEntryId( $parsed_id );

			if ( ! isset( $index[ $type . '|' . $number ] ) ) {
				$index[ $type . '|' . $number ] = count( $docs );
				$docs[]                         = array(
					'type'      => $type,
					'grade'     => (string) ( $winner['grade'] ?? '' ),
					'number'    => $number,
					'title'     => '',
					'text'      => '',
					'parsed_id' => $parsed_id,
					'block'     => false,
				);
			}
		}

		$auto = EntryMatcher::match(
			array_map(
				static fn( array $d ): array => array(
					'type'   => $d['type'],
					'grade'  => $d['grade'],
					'number' => $d['number'],
					'title'  => $d['title'],
				),
				$docs
			),
			$matcher
		);

		// Resolve each entry: the editor's choice, else the automatic match.
		$doc_rows = array();
		$resolved = array();
		$taken    = array();

		foreach ( $docs as $k => $doc ) {
			$auto_post = $auto[ $k ]['post_id'] ?? 0;
			$post_id   = self::resolve( $corrections, $k, $auto_post, $by_post );
			$entry     = $by_post[ $post_id ] ?? null;

			$resolved[ $k ] = $post_id;

			if ( $post_id > 0 ) {
				$taken[ $post_id ] = ( $taken[ $post_id ] ?? 0 ) + 1;
			}

			$doc_rows[] = array(
				'index'     => $k,
				'type'      => $doc['type'],
				'grade'     => $doc['grade'],
				'number'    => $doc['number'],
				'typed'     => $doc['title'],
				'parsed_id' => $doc['parsed_id'],
				'block'     => $doc['block'],
				'post_id'   => $post_id,
				'status'    => self::status( $post_id, $auto_post, $auto[ $k ]['how'] ?? '' ),
				'title'     => null === $entry ? '' : $entry['title'],
				'author'    => null === $entry ? '' : $this->authorName( $entry['author_id'] ),
				'shared'    => false,
				'unsorted'  => null !== $entry && $entry['unsorted'],
			);
		}

		foreach ( $doc_rows as $n => $row ) {
			$doc_rows[ $n ]['shared'] = $row['post_id'] > 0 && $taken[ $row['post_id'] ] > 1;
		}

		// Winners follow the entry they name. The coverage gate keys on the entry's stored pool,
		// not the pasted heading (R12A flag #1); an entry nobody matched keeps a key no stored
		// entry can have, so the gate blocks on it.
		$authoritative = array();
		$winners       = array();
		$winner_rows   = array();

		foreach ( $parsed['winners'] as $j => $winner ) {
			$parsed_id = (string) ( $winner['entry_id'] ?? '' );
			$type      = (string) ( $winner['type'] ?? '' );
			$k         = $index[ $type . '|' . self::numberOfEntryId( $parsed_id ) ];
			$post_id   = $resolved[ $k ];
			$entry     = $by_post[ $post_id ] ?? null;

			$winner_rows[] = array(
				'index'     => $j,
				'doc'       => $k,
				'grade'     => (string) ( $winner['grade'] ?? '' ),
				'type'      => $type,
				'rank'      => (int) $winner['rank'],
				'parsed_id' => $parsed_id,
				'post_id'   => $post_id,
				'status'    => $doc_rows[ $k ]['status'],
				'title'     => $doc_rows[ $k ]['title'],
				'author'    => $doc_rows[ $k ]['author'],
				'typed'     => $docs[ $k ]['title'],
			);

			if ( $post_id < 0 ) {
				continue; // Left out by the editor: not a placement.
			}

			if ( null === $entry ) {
				$winner['entry_id'] = '?' . $parsed_id;
				$authoritative[]    = $winner;
				continue;
			}

			$winner['entry_id'] = self::keyOf( $entry );

			if ( '' !== $entry['gradering'] ) {
				$winner['grade'] = $entry['gradering'];
			}

			$authoritative[] = $winner;
			$winners[]       = array(
				'post_id'   => $entry['post_id'],
				'rank'      => (int) $winner['rank'],
				'author_id' => $entry['author_id'],
			);
		}

		$commentary = array();
		$effective  = array();

		foreach ( $docs as $k => $doc ) {
			if ( ! $doc['block'] || $resolved[ $k ] < 0 ) {
				continue;
			}

			$entry       = $by_post[ $resolved[ $k ] ] ?? null;
			$effective[] = array(
				'entry_id' => null === $entry ? '?' . $doc['parsed_id'] : self::keyOf( $entry ),
				'title'    => $doc['title'],
				'text'     => $doc['text'],
			);

			if ( null !== $entry ) {
				$commentary[] = array(
					'post_id' => $entry['post_id'],
					'title'   => $doc['title'],
					'text'    => $doc['text'],
				);
			}
		}

		$report  = Coverage::report( $authoritative, $effective, array_keys( $by_key ) );
		$without = array();

		// With no comment read at all, "every entry lacks one" says nothing the problems do not.
		foreach ( array() === $effective ? array() : $report['entries_without_commentary'] as $key ) {
			$entry     = $by_key[ $key ];
			$name      = $this->authorName( $entry['author_id'] );
			$without[] = $entry['title'] . ( '' === $name ? '' : ' (' . $name . ')' );
		}

		return array(
			'report'      => $report,
			'winners'     => $winners,
			'commentary'  => $commentary,
			'mentions'    => $this->mentionLines( $parsed['mentions'], $map ),
			'problems'    => $parsed['problems'] ?? array(),
			'winner_rows' => $winner_rows,
			'doc_rows'    => $doc_rows,
			'without'     => $without,
			'options'     => self::options( $by_post, fn( int $author_id ): string => $this->authorName( $author_id ) ),
		);
	}

	/**
	 * The round's entries by post id, with the type, the pool they are judged in and their number.
	 * An entry may be unnumbered (a round that never had its email made on the site). Pure.
	 *
	 * @param list<array<string, mixed>> $entries The entries ({@see entriesFor()}).
	 * @return array<int, array{unsorted:bool, post_id:int, entry_id:string, author_id:int, title:string, gradering:string, type:string, pool:string, number:int}>
	 */
	private static function entriesByPost( array $entries ): array {
		$by_post = array();

		foreach ( $entries as $entry ) {
			$post_id = (int) ( $entry['id'] ?? 0 );

			if ( $post_id <= 0 ) {
				continue;
			}

			$entry_id  = (string) ( $entry['entry_id'] ?? '' );
			$gradering = (string) ( $entry['gradering'] ?? '' );
			$tier      = Tier::tryFrom( $gradering );
			$type      = (string) ( $entry['type'] ?? '' );

			$by_post[ $post_id ] = array(
				'unsorted'  => PostTypes::SKRYFWERK === $type,
				'post_id'   => $post_id,
				'entry_id'  => $entry_id,
				'author_id' => (int) ( $entry['author_id'] ?? 0 ),
				'title'     => (string) ( $entry['title'] ?? '' ),
				'gradering' => $gradering,
				'type'      => '' !== $type ? $type : self::typeOfEntryId( $entry_id ),
				'pool'      => null === $tier ? '' : $tier->competitionTier()->value,
				'number'    => self::numberOfEntryId( $entry_id ),
			);
		}

		return $by_post;
	}

	/**
	 * The coverage key of an entry: its EntryID, else one that cannot clash with an EntryID. Pure.
	 *
	 * @param array{post_id:int, entry_id:string} $entry The entry.
	 * @return string
	 */
	private static function keyOf( array $entry ): string {
		return '' !== $entry['entry_id'] ? $entry['entry_id'] : 'p' . $entry['post_id'];
	}

	/**
	 * The type word of an EntryID ("Gedig 3" → "gedig"), or ''. Pure.
	 *
	 * @param string $entry_id The EntryID.
	 * @return string
	 */
	private static function typeOfEntryId( string $entry_id ): string {
		return 1 === preg_match( '/^(\p{L}+)\s+\d+$/u', trim( $entry_id ), $m ) ? strtolower( $m[1] ) : '';
	}

	/**
	 * The number of an EntryID ("Gedig 3" → 3), or 0. Pure.
	 *
	 * @param string $entry_id The EntryID.
	 * @return int
	 */
	private static function numberOfEntryId( string $entry_id ): int {
		return 1 === preg_match( '/(\d+)$/', trim( $entry_id ), $m ) ? (int) $m[1] : 0;
	}

	/**
	 * The entry the editor's corrections leave a line pointing at. Pure.
	 *
	 * @param array<int, int>|null             $corrections Entry index → chosen post id (0 none chosen, -1 left out); null = no corrections.
	 * @param int                              $index       The entry's index.
	 * @param int                              $auto        The post it matched on its own (0 if none).
	 * @param array<int, array<string, mixed>> $by_post     The round's entries by post id.
	 * @return int A post id of the round, 0 for unresolved, -1 for left out.
	 */
	private static function resolve( ?array $corrections, int $index, int $auto, array $by_post ): int {
		if ( null === $corrections || ! isset( $corrections[ $index ] ) ) {
			return $auto;
		}

		$chosen = $corrections[ $index ];

		if ( $chosen < 0 ) {
			return -1;
		}

		return isset( $by_post[ $chosen ] ) ? $chosen : 0;
	}

	/**
	 * A line's match state. Pure.
	 *
	 * @param int    $post_id The resolved post (0 none, -1 left out).
	 * @param int    $auto    The post the matcher found on its own.
	 * @param string $how     How the matcher found it ({@see EntryMatcher}).
	 * @return string 'gepas' | 'nommer' | 'ooreenkoms' | 'twyfel' | 'gewysig' | 'ongepas' | 'uitgelaat'
	 */
	private static function status( int $post_id, int $auto, string $how ): string {
		if ( $post_id < 0 ) {
			return 'uitgelaat';
		}

		if ( 0 === $post_id ) {
			return 'ongepas';
		}

		if ( $post_id !== $auto ) {
			return 'gewysig';
		}

		return match ( $how ) {
			'nommer'           => 'nommer',
			'ooreenkoms'       => 'ooreenkoms',
			'titel_ander_poel' => 'twyfel',
			default            => 'gepas',
		};
	}

	/**
	 * The round's entries as dropdown options, `Gedig Silwer — Titel (Skrywer)`, by type, pool, title. Pure.
	 *
	 * @param array<int, array{post_id:int, author_id:int, title:string, type:string, pool:string}> $by_post The round's entries.
	 * @param callable(int):string                                                                  $author  Author-name lookup.
	 * @return array<int, string> Post id → label.
	 */
	private static function options( array $by_post, callable $author ): array {
		$pools = array_flip( array( '', 'brons', 'silwer', 'goud' ) );
		$rows  = array_values( $by_post );

		usort(
			$rows,
			static fn( array $a, array $b ): int => array( $a['type'], $pools[ $a['pool'] ] ?? 9, mb_strtolower( $a['title'] ) )
				<=> array( $b['type'], $pools[ $b['pool'] ] ?? 9, mb_strtolower( $b['title'] ) )
		);

		$options = array();

		foreach ( $rows as $row ) {
			$name                      = $author( $row['author_id'] );
			$kind                      = ucfirst( $row['type'] ) . ( '' === $row['pool'] || ! in_array( $row['type'], array( 'gedig', PostTypes::SKRYFWERK ), true ) ? '' : ' ' . ucfirst( $row['pool'] ) );
			$options[ $row['post_id'] ] = trim( $kind . ' — ' . $row['title'] . ( '' === $name ? '' : ' (' . $name . ')' ), ' —' );
		}

		return $options;
	}

	/**
	 * Order rounds by close date, newest first; a round with no close goes last. Pure.
	 *
	 * @param list<array{id:int, title:string, close:string, committed:bool}> $rounds The rounds.
	 * @return list<array{id:int, title:string, close:string, committed:bool}>
	 */
	public static function sortByClose( array $rounds ): array {
		usort(
			$rounds,
			static function ( array $a, array $b ): int {
				if ( $a['close'] === $b['close'] ) {
					return $b['id'] <=> $a['id'];
				}

				if ( '' === $a['close'] || '' === $b['close'] ) {
					return '' === $a['close'] ? 1 : -1;
				}

				return strcmp( $b['close'], $a['close'] );
			}
		);

		return $rounds;
	}

	/**
	 * The mentions as field lines, `Titel | Skrywer`: an EntryID resolves to its entry, and
	 * one with no entry in this round is left out, as unmatched commentary is.
	 *
	 * @param list<array{entry_id:string, text:string}>                                        $mentions The parsed mentions.
	 * @param array<string, array{post_id:int, author_id:int, title:string, gradering:string}> $map      The stored entries.
	 * @return list<string>
	 */
	private function mentionLines( array $mentions, array $map ): array {
		$lines = array();

		foreach ( $mentions as $mention ) {
			if ( '' === $mention['entry_id'] ) {
				$lines[] = $mention['text'];
			} elseif ( isset( $map[ $mention['entry_id'] ] ) ) {
				$entry   = $map[ $mention['entry_id'] ];
				$lines[] = $entry['title'] . ' | ' . $this->authorName( $entry['author_id'] );
			}
		}

		return $lines;
	}

	/**
	 * A writer's display name. Overridable seam.
	 *
	 * @param int $author_id The writer.
	 * @return string
	 */
	protected function authorName( int $author_id ): string {
		return (string) get_the_author_meta( 'display_name', $author_id );
	}

	/**
	 * Build the EntryID-string → {post_id, author_id, title} map for a round. Pure.
	 *
	 * @param list<array{id:int, entry_id:string, author_id:int, title:string, gradering:string}> $entries The round entries.
	 * @return array<string, array{post_id:int, author_id:int, title:string, gradering:string}>
	 */
	public static function storedMap( array $entries ): array {
		$map = array();

		foreach ( $entries as $entry ) {
			$entry_id = (string) ( $entry['entry_id'] ?? '' );

			if ( '' === $entry_id ) {
				continue; // Unnumbered (un-collated) entries carry no EntryID.
			}

			$map[ $entry_id ] = array(
				'post_id'   => (int) ( $entry['id'] ?? 0 ),
				'author_id' => (int) ( $entry['author_id'] ?? 0 ),
				'title'     => (string) ( $entry['title'] ?? '' ),
				'gradering' => (string) ( $entry['gradering'] ?? '' ),
			);
		}

		return $map;
	}

	/**
	 * The round's numbered entries as ingestion rows. Overridable seam (testability).
	 *
	 * @param int $uitdaging_id The round.
	 * @return list<array{id:int, entry_id:string, author_id:int, title:string, gradering:string}>
	 */
	protected function entriesFor( int $uitdaging_id ): array {
		if ( $uitdaging_id <= 0 ) {
			return array();
		}

		// Entries the migration could not classify (skryfwerk) took part all the same, and the judges
		// may have placed one: they are listed, and flagged where they are matched.
		$args              = SinglePage::entriesQueryArgs( $uitdaging_id );
		$args['post_type'] = array_values( array_unique( array_merge( (array) $args['post_type'], array( PostTypes::SKRYFWERK ) ) ) );
		$query             = new \WP_Query( $args );
		$rows              = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$id = (int) $post->ID;

			$rows[] = array(
				'id'        => $id,
				'entry_id'  => EntryId::entryIdFor( $id ),
				'author_id' => (int) $post->post_author,
				'type'      => (string) $post->post_type,
				'title'     => html_entity_decode( (string) get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), // plain text: get_the_title texturizes quotes and dashes into entities.
				'gradering' => Scalar::asString( get_post_meta( $id, Entry::GRADERING_META_KEY, true ) ),
			);
		}

		return $rows;
	}

	/**
	 * The commit pipeline. Overridable seam (testability).
	 *
	 * @return Ingestion
	 */
	protected function ingestion(): Ingestion {
		return new Ingestion();
	}

	/**
	 * The published rounds with their close and whether their results are in, newest close first.
	 * Overridable seam.
	 *
	 * @return list<array{id:int, title:string, close:string, committed:bool}>
	 */
	protected function rounds(): array {
		$posts = get_posts(
			array(
				'post_type'        => PostTypes::UITDAGING,
				'post_status'      => 'publish',
				'orderby'          => 'date',
				'order'            => 'DESC',
				'numberposts'      => 200,
				'suppress_filters' => false,
			)
		);

		$rounds = array();

		foreach ( is_array( $posts ) ? $posts : array() as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$close = Cadence::closeFor( (int) $post->ID );

			$rounds[] = array(
				'id'        => (int) $post->ID,
				'title'     => html_entity_decode( (string) get_the_title( $post ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'close'     => null === $close ? '' : $close->format( 'Y-m-d' ),
				'committed' => $this->ingestion()->isCommitted( (int) $post->ID ),
			);
		}

		return self::sortByClose( $rounds );
	}

	/**
	 * The round to preselect: the newest one that has closed and has no results yet, else the newest.
	 *
	 * @param list<array{id:int, title:string, close:string, committed:bool}> $rounds The rounds, newest close first.
	 * @param string                                                          $today  Today, `Y-m-d`.
	 * @return int
	 */
	public static function defaultRound( array $rounds, string $today ): int {
		foreach ( $rounds as $round ) {
			if ( ! $round['committed'] && '' !== $round['close'] && $round['close'] <= $today ) {
				return $round['id'];
			}
		}

		return array() === $rounds ? 0 : $rounds[0]['id'];
	}

	/**
	 * Render the ingestion screen and handle the analyse / correct / commit POSTs.
	 *
	 * Three steps, one form. "Ontleed" parses the paste and shows how every winner and every
	 * comment was matched to an entry; the editor corrects any wrong match in place and presses
	 * "Herbereken"; "Publiseer uitslae" then writes exactly what is on screen. The publish button is
	 * the confirmation: it only appears once the matches are shown, and it is disabled only by
	 * a gap the editor can fix there (an unmatched winner, an entry placed twice).
	 */
	public function render(): void {
		if ( ! current_user_can( Capabilities::MANAGE_CHALLENGES ) ) {
			wp_die( esc_html__( 'Jy het nie toestemming om hierdie bladsy te sien nie.', 'ink-core' ) );
		}

		echo '<div class="wrap">';
		echo '<h1>' . esc_html__( 'Uitslae-invoer', 'ink-core' ) . '</h1>';

		$rounds   = $this->rounds();
		$posted   = $this->postedUitdagingId();
		$selected = $posted > 0 ? $posted : self::defaultRound( $rounds, gmdate( 'Y-m-d', time() + 2 * HOUR_IN_SECONDS ) );
		$text     = $this->postedTextarea( 'uitslae' );
		$action   = $this->postedAction();
		$analyse  = $selected > 0 && '' !== trim( $text ) && $this->verify();

		$this->renderStyles();
		echo '<form method="post">';
		wp_nonce_field( self::NONCE, self::NONCE );
		$this->renderInputs( $rounds, $selected, $text );

		if ( $analyse ) {
			$corrections = 'analyse' === $action ? null : $this->postedIntMap( 'kom' );
			$analysis    = $this->analyse( $selected, $text, $corrections );
			$committed   = $this->ingestion()->isCommitted( $selected );
			$blocks      = Coverage::blocksCommit( $analysis['report'] );

			if ( 'commit' === $action && ! $blocks && ! $committed ) {
				$result = $this->ingestion()->commit( $selected, $analysis['winners'], $analysis['commentary'], $analysis['mentions'] );
				$this->renderCommitResult( $result );
				$committed = $this->ingestion()->isCommitted( $selected );
			}

			$this->renderReview( $analysis, $blocks, $committed );
		}

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the round dropdown and the paste box.
	 *
	 * @param list<array{id:int, title:string, close:string, committed:bool}> $rounds   The rounds.
	 * @param int                                                             $selected The selected round.
	 * @param string                                                          $text     The pasted text (kept on re-render).
	 */
	private function renderInputs( array $rounds, int $selected, string $text ): void {
		echo '<p><label for="ink-uitdaging-uitslae"><strong>' . esc_html__( 'Uitdaging', 'ink-core' ) . '</strong></label><br />';
		echo '<select id="ink-uitdaging-uitslae" name="uitdaging_id">';

		foreach ( $rounds as $round ) {
			$close = Deadline::parse( $round['close'] );
			$label = $round['title'] . ( null === $close ? '' : ' — ' . sprintf(
				/* translators: %s: the closing date. */
				__( 'sluit %s', 'ink-core' ),
				Deadline::format( $close )
			) ) . ( $round['committed'] ? ' — ' . __( 'uitslae reeds gepubliseer', 'ink-core' ) : '' );

			printf(
				'<option value="%1$d"%2$s>%3$s</option>',
				(int) $round['id'],
				selected( $selected, $round['id'], false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns a fixed attribute.
				esc_html( $label )
			);
		}

		echo '</select></p>';
		echo '<p><label for="ink-uitslae"><strong>' . esc_html__( 'Beoordelaars se uitslae (plak as gewone teks)', 'ink-core' ) . '</strong></label></p>';
		echo '<p><textarea id="ink-uitslae" name="uitslae" rows="14" cols="100" class="large-text code">' . esc_textarea( $text ) . '</textarea></p>';
		echo '<p><button type="submit" name="ink_action" value="analyse" class="button button-primary">' . esc_html__( 'Ontleed', 'ink-core' ) . '</button> ';
		echo '<span class="description">' . esc_html__( 'Niks word gestoor nie voordat jy hieronder “Publiseer uitslae” kies.', 'ink-core' ) . '</span></p>';
	}

	/**
	 * Render the match review: the winners as they will be placed, and every entry the judges
	 * wrote about with the entry of the round it was matched to, a dropdown to correct that, the
	 * gaps that matter, and the publish button.
	 *
	 * @param array<string, mixed> $analysis  The analysis.
	 * @param bool                 $blocks    Whether a hard gap blocks publishing.
	 * @param bool                 $committed Whether the round's results are already published.
	 */
	private function renderReview( array $analysis, bool $blocks, bool $committed ): void {
		/** @var array<string, mixed> $report */
		$report  = $analysis['report'];
		$options = (array) $analysis['options'];

		echo '<hr />';

		$this->renderProblems( (array) $analysis['problems'] );

		// Nothing could be read: the problems above say why. A table of nothing, and a list of every
		// entry "without a comment", would only bury them.
		if ( array() === $analysis['winner_rows'] && array() === $analysis['doc_rows'] ) {
			$this->renderFormatHelp();

			return;
		}

		if ( $committed ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Hierdie uitdaging se uitslae is reeds gepubliseer. Dit kan nie weer gepubliseer word nie.', 'ink-core' ) . '</p></div>';
		}

		echo '<h2>' . esc_html__( 'Wenners', 'ink-core' ) . '</h2>';

		if ( array() === $analysis['winner_rows'] ) {
			echo '<p>' . esc_html__( 'Geen wenners is in die teks gevind nie.', 'ink-core' ) . '</p>';
			$this->renderFormatHelp();
		} else {
			$this->renderWinnersTable( (array) $analysis['winner_rows'] );
		}

		echo '<h2>' . esc_html__( 'Inskrywings in die uitslae', 'ink-core' ) . '</h2>';
		echo '<p class="description">' . esc_html__( 'Elke inskrywing waaroor die beoordelaars geskryf het, gepas met ’n inskrywing van hierdie uitdaging. Pas eers op titel, dan op nommer. Is ’n pas verkeerd, kies die regte inskrywing en kies “Herbereken”.', 'ink-core' ) . '</p>';

		if ( array() === $analysis['doc_rows'] ) {
			echo '<p>' . esc_html__( 'Geen inskrywings is in die teks gevind nie.', 'ink-core' ) . '</p>';
		} else {
			$this->renderDocTable( (array) $analysis['doc_rows'], $options, $committed );
		}

		$this->renderGaps( $report, (array) $analysis['without'], (array) $analysis['mentions'] );

		if ( $blocks && ! $committed ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html__( 'Publiseer is geblokkeer: kies hierbo ’n inskrywing vir elke wenner wat nie gepas is nie, en laat nie een inskrywing twee plekke kry nie. Kies dan “Herbereken”.', 'ink-core' ) . '</p></div>';
		}

		echo '<p>';

		if ( ! $committed ) {
			echo '<button type="submit" name="ink_action" value="herbereken" class="button">' . esc_html__( 'Herbereken', 'ink-core' ) . '</button> ';
			printf(
				'<button type="submit" name="ink_action" value="commit" class="button button-primary"%s>%s</button>',
				$blocks ? ' disabled' : '',
				esc_html__( 'Publiseer uitslae', 'ink-core' )
			);
		}

		echo '</p>';
	}

	/**
	 * Render what in the pasted file could not be used, each with what to change.
	 *
	 * @param list<string> $problems The problems ({@see ResultsParser}).
	 */
	private function renderProblems( array $problems ): void {
		if ( array() === $problems ) {
			return;
		}

		echo '<div class="notice notice-warning inline"><p><strong>' . esc_html__( 'Dinge in die teks om na te gaan:', 'ink-core' ) . '</strong></p><ul style="list-style:disc;margin-left:1.5em">';

		foreach ( $problems as $problem ) {
			echo '<li>' . esc_html( $problem ) . '</li>';
		}

		echo '</ul></div>';
	}

	/**
	 * Render the layout the parser reads, for an editor whose file it could not read.
	 */
	private function renderFormatHelp(): void {
		echo '<p>' . esc_html__( 'Pas die teks aan soos in hierdie voorbeeld, en kies dan weer “Ontleed”. Die dubbelpunt na die kategorie, “No” of “No.”, en die titels se spelling maak nie saak nie.', 'ink-core' ) . '</p>';
		echo '<pre style="background:#f6f7f7;padding:12px;max-width:50em;overflow:auto">' . esc_html( ResultsParser::WINNERS_EXAMPLE ) . '</pre>';
	}

	/**
	 * The status of a match as a symbol, a word and a class: never colour alone.
	 *
	 * @return array<string, array{0:string, 1:string, 2:string}>
	 */
	private function statusLabels(): array {
		return array(
			'gepas'      => array( '✔', __( 'Gepas op titel', 'ink-core' ), 'ink-m-ok' ),
			'nommer'     => array( '✔', __( 'Gepas op nommer', 'ink-core' ), 'ink-m-ok' ),
			'ooreenkoms' => array( '⚠', __( 'Titel verskil effens: kontroleer', 'ink-core' ), 'ink-m-warn' ),
			'twyfel'     => array( '⚠', __( 'Dieselfde titel, maar ’n ander klas: kontroleer', 'ink-core' ), 'ink-m-warn' ),
			'gewysig'    => array( '✎', __( 'Reggestel', 'ink-core' ), 'ink-m-ok' ),
			'ongepas'    => array( '✖', __( 'Nie gepas nie', 'ink-core' ), 'ink-m-bad' ),
			'uitgelaat'  => array( '–', __( 'Uitgelaat', 'ink-core' ), 'ink-m-skip' ),
		);
	}

	/**
	 * Render the winners as they will be placed. Read-only: a wrong match is corrected on the entry below.
	 *
	 * @param list<array<string, mixed>> $rows The winner rows.
	 */
	private function renderWinnersTable( array $rows ): void {
		$labels = $this->statusLabels();

		echo '<table class="widefat striped ink-match"><thead><tr>';
		echo '<th>' . esc_html__( 'Plek', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'In die uitslae', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Inskrywing', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'ink-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$status = (string) $row['status'];
			$place  = trim( ucfirst( (string) $row['grade'] ) . ' ' . ucfirst( (string) $row['type'] ) ) . ' · ' . self::rankLabel( (int) $row['rank'] );
			$typed  = 'No ' . self::numberOfEntryId( (string) $row['parsed_id'] ) . ( '' === (string) $row['typed'] ? '' : ' — ' . (string) $row['typed'] );
			$entry  = (int) $row['post_id'] > 0 ? (string) $row['title'] . ( '' === (string) $row['author'] ? '' : ' (' . (string) $row['author'] . ')' ) : '—';

			echo '<tr class="' . esc_attr( 'ink-r-' . $status ) . '">';
			echo '<td>' . esc_html( $place ) . '</td><td>' . esc_html( $typed ) . '</td><td>' . esc_html( $entry ) . '</td>';
			echo '<td class="' . esc_attr( $labels[ $status ][2] ) . '">' . esc_html( $labels[ $status ][0] . ' ' . $labels[ $status ][1] ) . '</td>';
			echo '</tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the entries the judges wrote about, each with a dropdown of the round's entries set to its match.
	 *
	 * @param list<array<string, mixed>> $rows    The entry rows.
	 * @param array<int, string>         $options Post id → entry label.
	 * @param bool                       $locked  Whether the round is already published (read-only).
	 */
	private function renderDocTable( array $rows, array $options, bool $locked ): void {
		$labels = $this->statusLabels();

		echo '<table class="widefat striped ink-match"><thead><tr>';
		echo '<th>' . esc_html__( 'In die uitslae', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Gepas met inskrywing', 'ink-core' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'ink-core' ) . '</th>';
		echo '</tr></thead><tbody>';

		foreach ( $rows as $row ) {
			$status = (string) $row['status'];
			$post   = (int) $row['post_id'];
			$first  = trim( ucfirst( (string) $row['grade'] ) . ' ' . ucfirst( (string) $row['type'] ) ) . ' ' . (int) $row['number'];
			$first .= '' === (string) $row['typed'] ? ' (' . __( 'net as wenner genoem, sonder kommentaar', 'ink-core' ) . ')' : ' — ' . (string) $row['typed'];

			echo '<tr class="' . esc_attr( 'ink-r-' . $status ) . '">';
			echo '<td>' . esc_html( $first ) . '</td><td>';

			if ( $locked ) {
				echo esc_html( $post > 0 ? ( $options[ $post ] ?? '' ) : '—' );
			} else {
				echo '<select name="' . esc_attr( 'kom[' . (int) $row['index'] . ']' ) . '">';
				echo '<option value="0"' . selected( $post, 0, false ) . '>' . esc_html__( '— kies ’n inskrywing —', 'ink-core' ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns a fixed attribute.
				echo '<option value="-1"' . selected( $post, -1, false ) . '>' . esc_html__( '— los hierdie een uit —', 'ink-core' ) . '</option>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns a fixed attribute.

				foreach ( $options as $post_id => $label ) {
					printf(
						'<option value="%1$d"%2$s>%3$s</option>',
						(int) $post_id,
						selected( $post, (int) $post_id, false ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- selected() returns a fixed attribute.
						esc_html( $label )
					);
				}

				echo '</select>';
			}

			echo '</td><td class="' . esc_attr( $labels[ $status ][2] ) . '">' . esc_html( $labels[ $status ][0] . ' ' . $labels[ $status ][1] );

			if ( ! empty( $row['unsorted'] ) ) {
				echo '<br /><span class="ink-m-warn">⚠ ' . esc_html__( 'Hierdie inskrywing is nie as Gedig, Storie of Artikel geklassifiseer nie (Skryfwerk). Sy plasing word aangeteken, maar dit tel nie vir Gradering nie. Klassifiseer dit eers as dit ’n gedig is.', 'ink-core' ) . '</span>';
			}

			if ( ! empty( $row['shared'] ) ) {
				echo '<br /><span class="ink-m-warn">⚠ ' . esc_html__( 'Dieselfde inskrywing is aan nog ’n reël gekoppel.', 'ink-core' ) . '</span>';
			}

			echo '</td></tr>';
		}

		echo '</tbody></table>';
	}

	/**
	 * Render the gaps worth a look: ties, entries without a comment, and the mentions to be saved.
	 *
	 * @param array<string, mixed> $report   The coverage report.
	 * @param list<string>         $without  Entries with no comment, `ID — Titel`.
	 * @param list<string>         $mentions The honourable mentions that will be saved.
	 */
	private function renderGaps( array $report, array $without, array $mentions ): void {
		$duplicates = (array) ( $report['duplicates'] ?? array() );
		$ties       = (array) ( $report['ties'] ?? array() );

		if ( array() !== $duplicates ) {
			echo '<div class="notice notice-error inline"><p>' . esc_html(
				sprintf(
					/* translators: %s: entry ids. */
					__( 'Dieselfde inskrywing kry meer as een plek: %s.', 'ink-core' ),
					implode( ', ', array_map( 'strval', $duplicates ) )
				)
			) . '</p></div>';
		}

		if ( array() !== $ties ) {
			echo '<div class="notice notice-info inline"><p>' . esc_html__( 'Gelykop: meer as een inskrywing het dieselfde plek gekry. Albei skrywers word as wenners aangeteken.', 'ink-core' ) . '</p></div>';
		}

		if ( array() !== $without ) {
			echo '<p><strong>' . esc_html__( 'Inskrywings sonder kommentaar', 'ink-core' ) . ':</strong> ' . esc_html( implode( '; ', array_map( 'strval', $without ) ) ) . '</p>';
		}

		if ( array() !== $mentions ) {
			echo '<p><strong>' . esc_html__( 'Eervolle vermeldings wat gestoor word', 'ink-core' ) . ':</strong> ' . esc_html( implode( '; ', array_map( 'strval', $mentions ) ) ) . '</p>';
		}
	}

	/**
	 * A rank as the judges write it. Pure.
	 *
	 * @param int $rank The rank.
	 * @return string
	 */
	private static function rankLabel( int $rank ): string {
		return 1 === $rank ? '1ste' : ( 2 === $rank ? '2de' : $rank . 'de' );
	}

	/**
	 * The few admin styles the review table needs. Status is never colour alone: it carries a symbol and a word.
	 */
	private function renderStyles(): void {
		echo '<style>.ink-match select{max-width:100%}.ink-m-ok{color:#1a6b2e}.ink-m-bad{color:#b32d2e;font-weight:600}.ink-m-skip{color:#646970}.ink-m-warn{color:#8a5a00}.ink-r-ongepas td{background:#fcf0f1!important}</style>';
	}

	/**
	 * Render the commit-result notice.
	 *
	 * @param array{committed:bool, reason:string, post_id:int, feedback:int, placed:int, promoted:int} $result The commit result.
	 */
	private function renderCommitResult( array $result ): void {
		if ( ! empty( $result['committed'] ) ) {
			printf(
				'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: placements recorded, 2: promotions. */
						__( 'Uitslae gepubliseer. %1$d plasings aangeteken, %2$d skrywers bevorder.', 'ink-core' ),
						(int) $result['placed'],
						(int) $result['promoted']
					)
				)
			);
			return;
		}

		$reason = (string) ( $result['reason'] ?? '' );

		if ( 'reeds_gepleeg' === $reason ) {
			$message = __( 'Hierdie uitdaging se uitslae is reeds gepubliseer (niks is weer geskryf nie).', 'ink-core' );
		} elseif ( 'geen_wenners' === $reason ) {
			$message = __( 'Geen geldige wenners is opgespoor nie — niks is gepubliseer nie. Die uitdaging bly oop vir \'n regstelling.', 'ink-core' );
		} else {
			$message = __( 'Die uitslae kon nie gepubliseer word nie.', 'ink-core' );
		}

		echo '<div class="notice notice-warning"><p>' . esc_html( $message ) . '</p></div>';
	}

	/**
	 * The posted uitdaging id. Sanctioned superglobal read.
	 *
	 * @return int
	 */
	private function postedUitdagingId(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before any write; this only pre-reads the selection.
		if ( ! isset( $_POST['uitdaging_id'] ) || ! is_scalar( $_POST['uitdaging_id'] ) ) {
			return 0;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return absint( wp_unslash( $_POST['uitdaging_id'] ) );
	}

	/**
	 * The posted action ('analyse' | 'herbereken' | 'commit' | ''). Sanctioned superglobal read.
	 *
	 * @return string
	 */
	private function postedAction(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- branch selector; the branch verifies its nonce before any write.
		if ( ! isset( $_POST['ink_action'] ) || ! is_scalar( $_POST['ink_action'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_key( wp_unslash( $_POST['ink_action'] ) );
	}

	/**
	 * A posted `name[index] = post id` map (the editor's match corrections). Sanctioned superglobal read.
	 *
	 * @param string $name The field name.
	 * @return array<int, int> Line index → chosen post id (0 none, -1 left out).
	 */
	private function postedIntMap( string $name ): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before the commit; this reads the match choices.
		if ( ! isset( $_POST[ $name ] ) || ! is_array( $_POST[ $name ] ) ) {
			return array();
		}

		$map = array();

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- see above; every key and value is cast to int.
		foreach ( wp_unslash( $_POST[ $name ] ) as $index => $value ) {
			if ( is_numeric( $index ) && is_scalar( $value ) ) {
				$map[ (int) $index ] = (int) $value;
			}
		}

		return $map;
	}

	/**
	 * Read + sanitise the posted results textarea (preserves newlines). Sanctioned read.
	 *
	 * @param string $name The field name.
	 * @return string
	 */
	private function postedTextarea( string $name ): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- the nonce is verified in render() before any write.
		if ( ! isset( $_POST[ $name ] ) || ! is_scalar( $_POST[ $name ] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- see above.
		return sanitize_textarea_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Verify the posted nonce (capability is checked in {@see render()}).
	 *
	 * @return bool
	 */
	private function verify(): bool {
		if ( ! isset( $_POST[ self::NONCE ] ) || ! is_scalar( $_POST[ self::NONCE ] ) ) {
			return false;
		}

		$nonce = sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) );

		return false !== wp_verify_nonce( $nonce, self::NONCE );
	}
}
