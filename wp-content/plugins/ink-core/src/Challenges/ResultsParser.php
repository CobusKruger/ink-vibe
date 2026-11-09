<?php
/**
 * Judges' results plain-text parser — Story 12A.3 (FR-50-R2, R2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

defined( 'ABSPATH' ) || exit;

/**
 * Parses the judges' results, pasted as PLAIN TEXT, into a structured winners block +
 * per-entry commentary (Story 12A.3, R2). No `.docx` upload — plain text only (owner
 * decision; removes the PhpWord/XXE/zip-bomb surface). Pure: no WP, no side effects.
 *
 * Expected (forgiving, documented) format — two sections, headed case-insensitively:
 *
 *   WENNERS
 *   Brons Gedigte
 *   1ste: Gedig 3
 *   2de: Gedig 1
 *   3de: Geen
 *   Silwer Stories
 *   1: Storie 2
 *   Eervolle vermeldings
 *   Gedig 7
 *   Lig op die water | Anna Smit
 *
 *   KOMMENTAAR
 *   Gedig 3: Maanlig
 *   <commentary text, any number of lines>
 *
 *   Gedig 1: Nag
 *   <commentary text>
 *
 * A **pool header** line names the Gradering (brons/silwer/goud) and optionally the
 * category (gedig/storie/artikel); it sets the context for the rank lines beneath it.
 * A **winner** line carries a rank token (1/2/3, optionally 1ste/2de/3de/.) and either an
 * **EntryID** ("Gedig 3") or **"Geen"** (no placement — omitted from the output). The
 * EntryID's own type word is authoritative for the category; the pool header's grade
 * supplies the Gradering. In KOMMENTAAR, a line beginning with an EntryID starts a
 * commentary block (the rest of the line after ":" is the title); following lines are
 * its text until the next EntryID or section.
 *
 * Lines under an honourable-mention heading ("Eervolle vermelding") go to **mentions**,
 * and never become winners. Each is an EntryID for {@see IngestionPage::analyse()} to
 * resolve, or plain `Titel | Skrywer` text as printed. The list ends at the next pool
 * header or rank line.
 *
 * The coverage report ({@see Coverage}) + the explicit confirm gate are the safety net
 * for an imperfect parse — nothing is committed on the parse alone.
 *
 * @package Ink\Core
 */
final class ResultsParser {

	/**
	 * Matches an EntryID token: a bydrae type word + a number (e.g. "Gedig 3"). The
	 * type words mirror the {@see \Ink\I18n\Terms} singular labels (Gedig/Storie/Artikel).
	 *
	 * @var string
	 */
	private const ENTRY_ID_RE = '/\b(Gedig|Storie|Artikel)\s+(\d+)\b/i';

	/**
	 * The Gradering words a pool header may carry.
	 *
	 * @var string
	 */
	private const GRADE_RE = '/\b(brons|silwer|goud)\b/i';

	/**
	 * Parse pasted results text into winners + commentary. Pure.
	 *
	 * @param string $text The pasted plain-text results.
	 * @return array{winners:list<array{grade:string, type:string, rank:int, entry_id:string}>, commentary:list<array{entry_id:string, title:string, text:string, type?:string, grade?:string, number?:int}>, mentions:list<array{entry_id:string, text:string}>, problems:list<string>}
	 */
	public static function parse( string $text ): array {
		$lines = preg_split( '/\R/', $text );

		if ( false === $lines ) {
			$lines = array();
		}

		if ( self::isJudgeFormat( $lines ) ) {
			return self::parseJudgeFormat( $lines );
		}

		$section    = '';
		$pool_grade = '';
		$last_rank  = 0;     // The rank a following unnumbered entry line shares (a tie).
		$mention    = false; // Inside an honourable-mention list: not winners.
		$winners    = array();
		$commentary = array();
		$mentions   = array();
		$current    = null; // The open commentary block.

		foreach ( $lines as $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				continue;
			}

			if ( 1 === preg_match( '/^wenners\b/i', $line ) ) {
				$section = 'wenners';
				$current = null;
				continue;
			}

			if ( 1 === preg_match( '/^kommentaar\b/i', $line ) ) {
				$section = 'kommentaar';
				$current = null;
				continue;
			}

			if ( 'wenners' === $section ) {
				self::parseWinnerLine( $line, $pool_grade, $last_rank, $mention, $winners, $mentions );
				continue;
			}

			if ( 'kommentaar' === $section ) {
				self::parseCommentaryLine( $line, $current, $commentary );
			}
		}

		self::flushCommentary( $current, $commentary );

		$problems = array();

		if ( array() === $winners ) {
			$problems = self::noWinnersProblems( $lines );
		} elseif ( array() === $commentary ) {
			$problems[] = 'Geen kommentaar is gevind nie. Plaas die kommentaar onder ’n reël KOMMENTAAR, of onder die kategorie se naam (BRONS, SILWER, GOUD, PROSA of VERHALE) op ’n reël alleen.';
		}

		return array(
			'winners'    => $winners,
			'commentary' => $commentary,
			'mentions'   => $mentions,
			'problems'   => $problems,
		);
	}

	/**
	 * Parse one WENNERS-section line (pool header, rank line or a tie continuation). Mutates
	 * the running context + the winners list.
	 *
	 * Every EntryID on a rank line takes that rank ("1ste: Gedig 3 & Gedig 5" is a tie), and an
	 * unnumbered line of EntryIDs right after a rank line shares its rank. Both are emitted
	 * as-is so the {@see Coverage} gate blocks the commit loudly on the shared slot; silently
	 * dropping the second entry would lose a winner. An honourable-mention heading ("Eervolle
	 * vermelding") sends what follows it, and any EntryID on the heading itself, to the mentions.
	 *
	 * @param string                                                            $line       The trimmed line.
	 * @param string                                                            $pool_grade The running Gradering context (by ref).
	 * @param int                                                               $last_rank  The last rank seen in this pool (by ref).
	 * @param bool                                                              $mention    Whether an honourable-mention list is open (by ref).
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners   The winners accumulator (by ref).
	 * @param list<array{entry_id:string, text:string}>                         $mentions  The mentions accumulator (by ref).
	 */
	private static function parseWinnerLine( string $line, string &$pool_grade, int &$last_rank, bool &$mention, array &$winners, array &$mentions ): void {
		$has_grade = 1 === preg_match( self::GRADE_RE, $line, $gm );
		$entries   = preg_match_all( self::ENTRY_ID_RE, $line, $ems, PREG_SET_ORDER ) ? $ems : array();
		$has_entry = array() !== $entries;
		$rank      = self::rankIn( $line );

		if ( 1 === preg_match( '/vermelding|bogemiddelde/i', $line ) ) {
			$mention   = true;
			$last_rank = 0;

			foreach ( $entries as $em ) {
				$mentions[] = array(
					'entry_id' => self::normaliseEntryId( $em[1], (int) $em[2] ),
					'text'     => '',
				);
			}

			return;
		}

		// A plain-text mention names a writer after a bar. Its title may hold a grade word
		// ("Goud in die grond | Anna Smit"), so this comes before the pool-header test.
		if ( $mention && 0 === $rank && ! $has_entry && str_contains( $line, '|' ) ) {
			$mentions[] = array(
				'entry_id' => '',
				'text'     => self::mentionText( $line ),
			);
			return;
		}

		// A pool header sets the grade context (a line naming a grade but no winner).
		if ( $has_grade && ( 0 === $rank || ! $has_entry ) ) {
			$pool_grade = strtolower( $gm[1] );
			$last_rank  = 0;
			$mention    = false;
			return;
		}

		// A tie continuation: entries with no rank of their own share the previous rank.
		if ( 0 === $rank && $has_entry && $last_rank > 0 && ! $mention ) {
			$rank = $last_rank;
		}

		// Still inside the mention list and not a new pool or rank: a mention, never a winner.
		if ( $mention && 0 === $rank ) {
			foreach ( $entries as $em ) {
				$mentions[] = array(
					'entry_id' => self::normaliseEntryId( $em[1], (int) $em[2] ),
					'text'     => '',
				);
			}

			if ( ! $has_entry ) {
				$mentions[] = array(
					'entry_id' => '',
					'text'     => self::mentionText( $line ),
				);
			}

			return;
		}

		// A rank line: needs a rank AND an EntryID (a "Geen" line is intentionally dropped).
		if ( $rank > 0 && $has_entry && ! $mention ) {
			// A grade on the same line overrides the running context.
			if ( $has_grade ) {
				$pool_grade = strtolower( $gm[1] );
			}

			$last_rank = $rank;

			foreach ( $entries as $em ) {
				$winners[] = array(
					'grade'    => $pool_grade,
					'type'     => strtolower( $em[1] ),
					'rank'     => $rank,
					'entry_id' => self::normaliseEntryId( $em[1], (int) $em[2] ),
				);
			}

			return;
		}

		// A rank line with no entry ("3de: Geen") still closes the previous tie.
		if ( $rank > 0 ) {
			$last_rank = 0;
			$mention   = false;
		}
	}

	/**
	 * A plain-text mention as stored: any list marker dropped, `Titel | Skrywer` spacing. Pure.
	 *
	 * @param string $line The trimmed line.
	 * @return string
	 */
	private static function mentionText( string $line ): string {
		$parts = array_map( 'trim', explode( '|', (string) preg_replace( '/^[\-\x{2013}\x{2022}*]\s*/u', '', $line ), 2 ) );

		return implode( ' | ', $parts );
	}

	/**
	 * Parse one KOMMENTAAR-section line (block header or body). Mutates the open block.
	 *
	 * @param string                                                  $line       The trimmed line.
	 * @param array{entry_id:string, title:string, text:string}|null  $current    The open block (by ref).
	 * @param list<array{entry_id:string, title:string, text:string}> $commentary The commentary accumulator (by ref).
	 */
	private static function parseCommentaryLine( string $line, ?array &$current, array &$commentary ): void {
		// A line beginning with an EntryID starts a new commentary block.
		if ( 1 === preg_match( '/^(Gedig|Storie|Artikel)\s+(\d+)\s*[:\-–]?\s*(.*)$/i', $line, $m ) ) {
			self::flushCommentary( $current, $commentary );

			$current = array(
				'entry_id' => self::normaliseEntryId( $m[1], (int) $m[2] ),
				'title'    => trim( $m[3] ),
				'text'     => '',
				'type'     => strtolower( $m[1] ),
				'grade'    => '',
				'number'   => (int) $m[2],
			);

			return;
		}

		if ( null !== $current ) {
			$current['text'] = '' === $current['text'] ? $line : $current['text'] . "\n" . $line;
		}
	}

	/**
	 * Append the open commentary block (if any) to the accumulator. The caller owns the
	 * `$current` reset (it either reassigns a new block or discards it at end of parse),
	 * so this takes `$current` by value — no by-ref out type to reason about.
	 *
	 * @param array{entry_id:string, title:string, text:string}|null  $current    The open block (or null).
	 * @param list<array{entry_id:string, title:string, text:string}> $commentary The accumulator (by ref).
	 */
	private static function flushCommentary( ?array $current, array &$commentary ): void {
		if ( null !== $current ) {
			$current['text'] = trim( $current['text'] );
			$commentary[]    = $current;
		}
	}

	/**
	 * The category words the judges head a section with, and what they stand for. Pure data.
	 * Brons, Silwer and Goud are the Gedig pools; Prosa is the Artikel; Verhale are the Stories.
	 *
	 * @var array<string, array{0:string, 1:string}> Word → [type, grade].
	 */
	private const SECTION_WORDS = array(
		'brons'    => array( 'gedig', 'brons' ),
		'silwer'   => array( 'gedig', 'silwer' ),
		'goud'     => array( 'gedig', 'goud' ),
		'gedigte'  => array( 'gedig', '' ),
		'gedig'    => array( 'gedig', '' ),
		'prosa'    => array( 'artikel', '' ),
		'artikel'  => array( 'artikel', '' ),
		'artikels' => array( 'artikel', '' ),
		'verhale'  => array( 'storie', '' ),
		'verhaal'  => array( 'storie', '' ),
		'stories'  => array( 'storie', '' ),
		'storie'   => array( 'storie', '' ),
	);

	/**
	 * A winners line, written as the judges write it. Shown to the editor when a file cannot be read.
	 *
	 * @var string
	 */
	public const WINNERS_EXAMPLE = "WENNERS\nBRONS: (1) No 9 (2) No 12 (3) Geen\nPROSA: (1) No 6 (2) No 3 (3) No 5\n\nBRONS\n9. Titel van die gedig\nDie kommentaar, op een reël.\n\nPROSA\nTitel van die stuk\nDie kommentaar, op een reël.";

	/**
	 * Whether the text is in the judges' own layout: a winners line per category,
	 * `BRONS: (1) No 9 (2) No 12 (3) Geen` (the colon is optional), or a category heading followed
	 * by a line of ranks. Pure.
	 *
	 * @param list<string> $lines The lines.
	 * @return bool
	 */
	private static function isJudgeFormat( array $lines ): bool {
		$previous = null;

		foreach ( $lines as $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				continue;
			}

			if ( null !== self::judgeWinnerLabel( $line ) ) {
				return true;
			}

			if ( null !== $previous && self::isRankLine( $line ) && null !== self::judgeHeading( $previous ) ) {
				return true;
			}

			$previous = $line;
		}

		return false;
	}

	/**
	 * Whether a line is only ranks and entries: `(1) No 9 (2) No 12`. Pure.
	 *
	 * @param string $line The trimmed line.
	 * @return bool
	 */
	private static function isRankLine( string $line ): bool {
		return 1 === preg_match( '/^\(\s*[123]\s*\)/u', $line );
	}

	/**
	 * The `[type, grade, rest]` of a judges' winners line, or null when the line is not one. The
	 * colon after the category is optional, and "Brons gedigte" is as good as "Brons". Pure.
	 *
	 * @param string $line The trimmed line.
	 * @return array{0:string, 1:string, 2:string}|null
	 */
	private static function judgeWinnerLabel( string $line ): ?array {
		if ( 1 !== preg_match( '/^(\p{L}+)(?:\s+\p{L}+)?\s*[:\-–]?\s*(\(\s*[123]\s*\).*)$/u', $line, $m ) ) {
			return null;
		}

		$word = strtolower( $m[1] );

		return isset( self::SECTION_WORDS[ $word ] )
			? array( self::SECTION_WORDS[ $word ][0], self::SECTION_WORDS[ $word ][1], $m[2] )
			: null;
	}

	/**
	 * The `[type, grade]` of a bare category heading ("BRONS", "Prosa:", "Brons gedigte"), or null. Pure.
	 *
	 * @param string $line The trimmed line.
	 * @return array{0:string, 1:string}|null
	 */
	private static function judgeHeading( string $line ): ?array {
		if ( 1 !== preg_match( '/^(\p{L}+)(?:\s+(?:gedigte|stories|artikels|verhale))?\s*:?$/u', $line, $m ) ) {
			return null;
		}

		return self::SECTION_WORDS[ strtolower( $m[1] ) ] ?? null;
	}

	/**
	 * A category as the judges name it: BRONS, SILWER, GOUD, PROSA or VERHALE. Pure.
	 *
	 * @param string $type  The entry type.
	 * @param string $grade The Gradering, or ''.
	 * @return string
	 */
	private static function categoryName( string $type, string $grade ): string {
		if ( '' !== $grade ) {
			return strtoupper( $grade );
		}

		return match ( $type ) {
			'artikel' => 'PROSA',
			'storie'  => 'VERHALE',
			default   => 'GEDIGTE',
		};
	}

	/**
	 * Parse the judges' own layout. Pure.
	 *
	 *     WENNERS
	 *     BRONS: (1) No 9 (2) No 12 (3) Geen
	 *     PROSA: (1) No 6 (2) No 3 (3) No 5
	 *
	 *     BRONS
	 *     1. Gee die hond wind
	 *     <the comment, one paragraph>
	 *     PROSA
	 *     Tussen die lyne
	 *     <the comment>
	 *
	 * The files are typed by hand, so this is forgiving: the colon after a category, "No", "No." or
	 * "Nr", a full stop after a number, "Brons gedigte" for "Brons", and a category on a line of its
	 * own with its ranks on the next, are all read. A number after "No" is the entry's number within
	 * its category. A Gedig carries its number in front of its title; a Prosa or Verhale entry has
	 * none, so its number is its place in its section. Every block keeps the title the judge typed,
	 * which is what {@see EntryMatcher} matches on.
	 *
	 * What cannot be read is not guessed at: it comes back in `problems`, each saying which line, what
	 * was expected and what to change in the file.
	 *
	 * @param list<string> $lines The lines.
	 * @return array{winners:list<array{grade:string, type:string, rank:int, entry_id:string}>, commentary:list<array{entry_id:string, title:string, text:string, type:string, grade:string, number:int}>, mentions:list<array{entry_id:string, text:string}>, problems:list<string>}
	 */
	private static function parseJudgeFormat( array $lines ): array {
		$winners    = array();
		$commentary = array();
		$mentions   = array();
		$strays     = array(); // Lines after the winners that belong to neither winners nor a category.
		$section    = '';
		$type       = '';
		$grade      = '';
		$win_type   = ''; // The category a bare heading over a line of ranks stands for.
		$win_grade  = '';
		$sections   = 0;
		$seen       = array(); // type → entries met so far, the number of an unnumbered entry.
		$current    = null;
		$count      = count( $lines );

		foreach ( $lines as $i => $raw ) {
			$line = trim( $raw );

			if ( '' === $line ) {
				continue;
			}

			if ( 1 === preg_match( '/^wenners\b/i', $line ) ) {
				$section = 'wenners';
				continue;
			}

			$winner_line = self::judgeWinnerLabel( $line );

			if ( null !== $winner_line ) {
				$section = 'wenners' === $section || '' === $section ? 'wenners' : $section;
				self::flushCommentary( $current, $commentary );
				$current = null;
				self::judgeWinners( $winner_line, $winners );
				continue;
			}

			if ( self::isRankLine( $line ) && '' !== $win_type ) {
				self::judgeWinners( array( $win_type, $win_grade, $line ), $winners );
				continue;
			}

			if ( 1 === preg_match( '/^eervolle\s+vermelding|^vermelding/iu', $line ) ) {
				self::flushCommentary( $current, $commentary );
				$current = null;
				$section = 'vermeldings';
				continue;
			}

			$heading = self::judgeHeading( $line );

			if ( null !== $heading ) {
				self::flushCommentary( $current, $commentary );
				$current = null;

				// A heading whose next line is a line of ranks heads the winners, not a comment section.
				if ( self::isRankLine( self::nextLine( $lines, $i, $count ) ) ) {
					$section   = 'wenners';
					$win_type  = $heading[0];
					$win_grade = $heading[1];
					continue;
				}

				$section = 'kommentaar';
				$type    = $heading[0];
				$grade   = $heading[1];
				++$sections;
				continue;
			}

			if ( '' === $section ) {
				continue; // The document title and anything before the winners.
			}

			if ( 'vermeldings' === $section ) {
				$mentions[] = array(
					'entry_id' => '',
					'text'     => self::mentionText( (string) preg_replace( '/^\d+\s*[.):\-–]\s+/u', '', $line ) ),
				);
				continue;
			}

			if ( 'kommentaar' !== $section || '' === $type ) {
				$strays[ $i + 1 ] = $line;
				continue;
			}

			if ( 1 === preg_match( '/^(\d+)\s*[.):\-–]\s+(.+)$/u', $line, $m ) ) {
				self::flushCommentary( $current, $commentary );
				$number        = (int) $m[1];
				$seen[ $type ] = max( $seen[ $type ] ?? 0, $number );
				$current       = self::judgeBlock( $type, $grade, $number, $m[2], $sections, true );
				continue;
			}

			// Unnumbered: a title is a short line that opens the section or follows a finished
			// comment; the line after a title is always its comment.
			$opens_block = null === $current || '' !== $current['text'];

			if ( $opens_block && self::looksLikeTitle( $line ) ) {
				self::flushCommentary( $current, $commentary );
				$seen[ $type ] = ( $seen[ $type ] ?? 0 ) + 1;
				$current       = self::judgeBlock( $type, $grade, $seen[ $type ], $line, $sections, false );
				continue;
			}

			if ( null !== $current ) {
				$current['text'] = '' === $current['text'] ? $line : $current['text'] . "\n" . $line;
			}
		}

		self::flushCommentary( $current, $commentary );

		$problems   = self::judgeProblems( $winners, $commentary, $strays );
		$commentary = self::settleNumbers( $commentary );

		return array(
			'winners'    => $winners,
			'commentary' => $commentary,
			'mentions'   => $mentions,
			'problems'   => $problems,
		);
	}

	/**
	 * The next non-blank line after `$i`, trimmed, or ''. Pure.
	 *
	 * @param list<string> $lines The lines.
	 * @param int          $i     The current index.
	 * @param int          $count The number of lines.
	 * @return string
	 */
	private static function nextLine( array $lines, int $i, int $count ): string {
		for ( $j = $i + 1; $j < $count; $j++ ) {
			if ( '' !== trim( $lines[ $j ] ) ) {
				return trim( $lines[ $j ] );
			}
		}

		return '';
	}

	/**
	 * Read one judges' winners line into winners. A tie ("(1) No 4 & No 5") gives both the rank. Pure.
	 *
	 * @param array{0:string, 1:string, 2:string}                               $line    Type, grade and the rank tokens.
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners The winners (by ref).
	 */
	private static function judgeWinners( array $line, array &$winners ): void {
		if ( 0 === preg_match_all( '/\(\s*([123])\s*\)([^()]*)/u', $line[2], $ranks, PREG_SET_ORDER ) ) {
			return;
		}

		foreach ( $ranks as $rank ) {
			// "Geen" has no number, so it adds nobody.
			preg_match_all( '/\b(\d+)\b/', $rank[2], $numbers );

			foreach ( $numbers[1] as $number ) {
				$winners[] = array(
					'grade'    => $line[1],
					'type'     => $line[0],
					'rank'     => (int) $rank[1],
					'entry_id' => self::normaliseEntryId( $line[0], (int) $number ),
				);
			}
		}
	}

	/**
	 * A new commentary block. Pure.
	 *
	 * @param string $type     The category's entry type.
	 * @param string $grade    The pool's Gradering, or ''.
	 * @param int    $number   The entry's number in its category.
	 * @param string $title    The title as the judge typed it.
	 * @param int    $section  Which category section of the file it sits in.
	 * @param bool   $explicit Whether the judge wrote the number.
	 * @return array{entry_id:string, title:string, text:string, type:string, grade:string, number:int, section:int, explicit:bool}
	 */
	private static function judgeBlock( string $type, string $grade, int $number, string $title, int $section, bool $explicit ): array {
		return array(
			'entry_id' => self::normaliseEntryId( $type, $number ),
			'title'    => trim( $title ),
			'text'     => '',
			'type'     => $type,
			'grade'    => $grade,
			'number'   => $number,
			'section'  => $section,
			'explicit' => $explicit,
		);
	}

	/**
	 * Where a section numbers its entries, an entry without a number has none (0): its place in the
	 * file says nothing about the judges' numbering. In a section with no numbers at all (Prosa,
	 * Verhale) the place is the number. Pure.
	 *
	 * @param list<array<string, mixed>> $commentary The blocks.
	 * @return list<array{entry_id:string, title:string, text:string, type:string, grade:string, number:int}>
	 */
	private static function settleNumbers( array $commentary ): array {
		$numbered = array();

		foreach ( $commentary as $block ) {
			if ( $block['explicit'] ) {
				$numbered[ $block['section'] ] = true;
			}
		}

		$out = array();

		foreach ( $commentary as $block ) {
			if ( ! $block['explicit'] && isset( $numbered[ $block['section'] ] ) ) {
				$block['number']   = 0;
				$block['entry_id'] = '';
			}

			unset( $block['section'], $block['explicit'] );
			$out[] = $block;
		}

		return $out;
	}

	/**
	 * What in a judges'-layout file cannot be used, each as a sentence saying where, what was
	 * expected and what to change. Pure.
	 *
	 * @param list<array{grade:string, type:string, rank:int, entry_id:string}> $winners    The winners read.
	 * @param list<array<string, mixed>>                                        $commentary The blocks read.
	 * @param array<int, string>                                                $strays     Line number → text, lines that are neither.
	 * @return list<string>
	 */
	private static function judgeProblems( array $winners, array $commentary, array $strays ): array {
		$problems = array();

		if ( array() !== $strays ) {
			$line       = (string) array_key_first( $strays );
			$problems[] = sprintf(
				'Reël %1$s (“%2$s”) is nie verstaan nie. Na die wenners moet elke kategorie se kommentaar begin met die kategorie se naam op ’n reël alleen: BRONS, SILWER, GOUD, PROSA of VERHALE.%3$s',
				$line,
				self::excerpt( $strays[ (int) $line ] ),
				count( $strays ) > 1 ? sprintf( ' (%d reëls is so oorgeslaan.)', count( $strays ) ) : ''
			);
		}

		$win_cats = array();
		$com_cats = array();

		foreach ( $winners as $winner ) {
			$win_cats[ self::categoryName( $winner['type'], $winner['grade'] ) ] = true;
		}

		foreach ( $commentary as $block ) {
			$com_cats[ self::categoryName( (string) $block['type'], (string) $block['grade'] ) ] = true;
		}

		foreach ( array_diff_key( $win_cats, $com_cats ) as $name => $unused ) {
			$problems[] = sprintf( 'Daar is wenners vir %1$s, maar geen kommentaar onder ’n opskrif %1$s nie. Sit die woord %1$s op ’n reël alleen bo daardie kategorie se kommentaar.', $name );
		}

		foreach ( array_diff_key( $com_cats, $win_cats ) as $name => $unused ) {
			$problems[] = sprintf( 'Daar is kommentaar onder %1$s, maar geen wenners-reël vir %1$s nie. Voeg onder WENNERS ’n reël by soos “%1$s: (1) No 3 (2) No 5 (3) Geen”.', $name );
		}

		$numbers = array();

		foreach ( $commentary as $block ) {
			$name = self::categoryName( (string) $block['type'], (string) $block['grade'] );

			if ( '' === trim( (string) $block['text'] ) ) {
				$problems[] = sprintf( 'Onder %1$s het “%2$s” geen kommentaar nie. Die kommentaar moet op die reël direk ná die titel staan.', $name, self::excerpt( (string) $block['title'] ) );
			}

			if ( $block['explicit'] ) {
				$key = $block['type'] . '|' . $block['number'];

				if ( isset( $numbers[ $key ] ) ) {
					$problems[] = sprintf( 'Nommer %1$d kom twee keer voor (onder %2$s: “%3$s”). Elke nommer moet net een inskrywing hê.', $block['number'], $name, self::excerpt( (string) $block['title'] ) );
				}

				$numbers[ $key ] = true;
			}
		}

		$numbered_sections = array();

		foreach ( $commentary as $block ) {
			if ( $block['explicit'] ) {
				$numbered_sections[ $block['section'] ] = true;
			}
		}

		foreach ( $commentary as $block ) {
			if ( ! $block['explicit'] && isset( $numbered_sections[ $block['section'] ] ) ) {
				$problems[] = sprintf(
					'Onder %1$s het “%2$s” geen nommer nie. Dit is op sy titel gepas, maar dit kan nie as wenner genoem word nie. Sit sy nommer voor die titel, soos “7. %2$s”.',
					self::categoryName( (string) $block['type'], (string) $block['grade'] ),
					self::excerpt( (string) $block['title'] )
				);
			}
		}

		return $problems;
	}

	/**
	 * What to say about a file in which no winners could be read. Pure.
	 *
	 * @param list<string> $lines The lines.
	 * @return list<string>
	 */
	private static function noWinnersProblems( array $lines ): array {
		$at = null;

		foreach ( $lines as $i => $raw ) {
			if ( 1 === preg_match( '/^wenners\b/i', trim( $raw ) ) ) {
				$at = $i;
				break;
			}
		}

		if ( null === $at ) {
			return array( 'Die teks het nie ’n reël WENNERS nie. Sit WENNERS op ’n reël alleen bo die lys van wenners, en gebruik dan een reël per kategorie, soos in die voorbeeld hieronder.' );
		}

		$next = self::nextLine( $lines, $at, count( $lines ) );

		if ( '' === $next ) {
			return array( 'Daar is niks onder WENNERS nie. Plak een reël per kategorie daaronder, soos in die voorbeeld hieronder.' );
		}

		$shown = array();

		foreach ( array_slice( $lines, $at + 1, 8, true ) as $i => $raw ) {
			if ( '' !== trim( $raw ) && count( $shown ) < 3 ) {
				$shown[] = sprintf( 'Reël %d (“%s”)', $i + 1, self::excerpt( trim( $raw ) ) );
			}
		}

		return array(
			sprintf(
				'Onder WENNERS is geen wenners-reël verstaan nie. Gelees: %s. Elke kategorie moet een reël hê wat met sy naam begin (BRONS, SILWER, GOUD, PROSA of VERHALE) en dan die plekke gee, soos “BRONS: (1) No 9 (2) No 12 (3) Geen”.',
				implode( '; ', $shown )
			),
		);
	}

	/**
	 * A line shortened for a message. Pure.
	 *
	 * @param string $text The text.
	 * @return string
	 */
	private static function excerpt( string $text ): string {
		return mb_strlen( $text ) > 60 ? mb_substr( $text, 0, 57 ) . '…' : $text;
	}

	/**
	 * Whether an unnumbered line is a title rather than a line of comment: short, and not a
	 * sentence (no full stop at the end, bar an ellipsis). Pure.
	 *
	 * @param string $line The trimmed line.
	 * @return bool
	 */
	private static function looksLikeTitle( string $line ): bool {
		if ( mb_strlen( $line ) > 110 ) {
			return false;
		}

		return ! str_ends_with( $line, '.' ) || str_ends_with( $line, '..' );
	}

	/**
	 * The placement rank named in a line (1/2/3), or 0 if none. Pure.
	 *
	 * Accepts "1", "1:", "1.", "1ste", "2de", "3de" near the line start.
	 *
	 * @param string $line The line.
	 * @return int
	 */
	private static function rankIn( string $line ): int {
		// Accept "1:" / "1." / "1)" / "(1)" (punctuation) OR an ordinal form "1ste" / "2de" /
		// "3 de plek" even WITHOUT trailing punctuation (R12A review: "1ste Gedig 3" was silently
		// dropped), OR "Wenner" / "W" for first place. A bare "1 Gedig" (no ordinal, no
		// punctuation) is still NOT a rank line, so a prose line like "3 gedigte is ingedien"
		// never false-matches.
		if ( 1 === preg_match( '/^\s*\(?([123])(?:\s*(?:ste|de|e)\s*(?:plek)?\s*[:.\)]?|\s*[:.\)])/i', $line, $m ) ) {
			return (int) $m[1];
		}

		if ( 1 === preg_match( '/^\s*(?:wenner|w)\b\s*[:.\-–]/i', $line ) ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Normalise an EntryID to its canonical "{Ucfirst type} {number}" form. Pure.
	 *
	 * @param string $type_word The matched type word (any case).
	 * @param int    $number    The matched number.
	 * @return string
	 */
	private static function normaliseEntryId( string $type_word, int $number ): string {
		return ucfirst( strtolower( $type_word ) ) . ' ' . $number;
	}
}
