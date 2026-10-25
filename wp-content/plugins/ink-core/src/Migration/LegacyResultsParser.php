<?php
/**
 * Legacy challenge result-post winners parser — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the winners out of a prod "uitslae en kommentaar" post (HTML written by hand over
 * ten years, so the layout drifts). Rule set, following the July 2026 extraction spec
 * (docs/data-cleanup/winners/list of winner announcement posts.md):
 *
 *  - Only text BEFORE the judges' commentary is read: the first line that starts with
 *    "kommentaar"/"kommentare" once a block is open (a title line carrying those words
 *    before any block is just the heading).
 *  - A short line naming a category (Gedigte, Prosa, Artikels, Verhale, Rubrieke,
 *    Meningstukke, Algemeen/Gorrel) sets the category; one naming Goud/Silwer/Brons sets
 *    the grading (implying Gedigte when no category is open). A header may carry its
 *    first entry on the same line ("GOUD: 1ste. Title – Writer").
 *  - Entry layouts: `[position] [No. n | n.] Writer met Title`, `Wenner: Title – Writer`
 *    (writer last, title first) and the 2018 `(14) Title – 84% Writer`.
 *  - Position is the explicit one (1/2/3, 1ste/2de/3de, W/Wenner) or the order within the
 *    block. "Geen" (or a bare dash) consumes a position and yields no row.
 *
 * Writer and Title are kept verbatim (trimmed). A line in the winners region that looks
 * like an entry but cannot be parsed with confidence (for example two winners on one line)
 * goes to `unparsed` so a human sees every miss; the parser never guesses. Pure.
 *
 * @package Ink\Core
 */
final class LegacyResultsParser {

	private const CATEGORY_WORDS = array(
		'gedigte'      => 'Gedigte',
		'gedig'        => 'Gedigte',
		'prosa'        => 'Prosa',
		'artikels'     => 'Artikels',
		'artikel'      => 'Artikels',
		'verhale'      => 'Verhale',
		'verhaal'      => 'Verhale',
		'rubrieke'     => 'Rubrieke',
		'rubriek'      => 'Rubrieke',
		'meningstukke' => 'Meningstukke',
		'meningstuk'   => 'Meningstukke',
		'menings'      => 'Meningstukke',
		'mening'       => 'Meningstukke',
		'algemeen'     => 'Algemeen',
		'gorrel'       => 'Algemeen',
		'gorrels'      => 'Algemeen',
	);

	/**
	 * Parse a result post's HTML.
	 *
	 * @param string       $html  The post content.
	 * @param list<string> $known Known writer names (display names, logins, pen names). They
	 *                            settle `A – B` lines where either side could be the writer.
	 * @return array{rows:list<array{writer:string,title:string,position:int,category:string,grading:string}>, unparsed:list<string>}
	 */
	public static function parse( string $html, array $known = array() ): array {
		$known_keys = array();

		foreach ( $known as $name ) {
			$key = self::nameKey( (string) $name );

			if ( '' !== $key ) {
				$known_keys[ $key ] = true;
			}
		}

		$rows     = array();
		$unparsed = array();
		$category = '';
		$grading  = '';
		$counter  = 0;
		$explicit = 0; // The last explicit position in this block (ties inherit it).
		$seen     = array(); // Headings that already produced rows.
		$mentions = false;   // Inside an honourable-mention list.
		$current  = '';
		$all      = self::lines( $html );

		// "(29) Marlene Erasmus met ..." : when a bracketed number of two or more digits leads an
		// entry that has no "No." marker, the brackets hold the ENTRY number everywhere in the
		// post, not the position ("(3)" is then entry 3, not third place).
		$paren_is_entry = false;

		foreach ( $all as $candidate ) {
			if ( 1 === preg_match( '/^\(\d{2,3}\)\s*(?!no\b|nr\b)\p{L}/iu', $candidate ) ) {
				$paren_is_entry = true;
				break;
			}
		}

		foreach ( $all as $raw ) {
			if ( ( '' !== $category || array() !== $rows ) && 1 === preg_match( '/^\W*(kommentaar|kommentare|komentaar|besprekings?|kritiek)\b/iu', $raw ) ) {
				break;
			}

			// An honourable mention is not a winner, and neither are the entries listed under that
			// heading ("Eervolle vermeldings in goud, silwer en brons", "... bogemiddelde persentasie")
			// up to the next heading.
			if ( 1 === preg_match( '/^\W*eervolle\s+vermelding|bogemiddelde\s+persentasie/iu', $raw ) ) {
				$mentions = true;
				continue;
			}

			foreach ( self::splitHeaderAndEntry( $raw ) as $line ) {
				$header = self::header( $line );

				if ( null !== $header ) {
					// The commentary repeats the winners' headings; a heading seen before, once
					// rows exist, means the winners region has ended.
					$key = $header['category'] . '|' . $header['grading'] . '|' . ( '' === $header['category'] ? $category : '' );

					if ( isset( $seen[ $key ] ) ) {
						break 2;
					}

					$current  = $key;
					$mentions = false;

					if ( '' !== $header['category'] ) {
						$category = $header['category'];
						$grading  = '';
						$counter  = 0;
						$explicit = 0;
					}

					if ( '' !== $header['grading'] ) {
						$grading  = $header['grading'];
						$counter  = 0;
						$explicit = 0;

						if ( '' === $category ) {
							$category = 'Gedigte';
						}
					}

					continue;
				}

				if ( '' === $category ) {
					continue; // Preamble text before any block.
				}

				if ( $mentions ) {
					continue;
				}

				if ( self::isNoWinner( $line ) ) {
					++$counter;
					continue;
				}

				$entries = self::entries( $line, $known_keys, $paren_is_entry );

				if ( array() === $entries ) {
					if ( self::looksLikeEntry( $line ) ) {
						$unparsed[] = $line;
					}

					continue;
				}

				// Two winners sharing a position on one line produce one row each.
				foreach ( $entries as $index => $entry ) {
					if ( $index > 0 ) {
						$entry['position'] = $entries[0]['position'] ?? ( $explicit > 0 ? $explicit : $counter + 1 );
					}

					$rows[]           = self::row( $entry, $category, $grading, $explicit, $counter, 0 === $index );
					$seen[ $current ] = true;
				}

				continue;

			}
		}

		return array(
			'rows'     => $rows,
			'unparsed' => $unparsed,
		);
	}

	/**
	 * Build a row and advance the position state. Pure (state by reference).
	 *
	 * @param array{writer:string,title:string,position:?int} $entry    The parsed entry.
	 * @param string                                          $category Current category.
	 * @param string                                          $grading  Current grading.
	 * @param int                                             $explicit Last explicit position (updated).
	 * @param int                                             $counter  Order within the block (updated when $advance).
	 * @param bool                                            $advance  Whether this entry advances the order counter.
	 * @return array{writer:string,title:string,position:int,category:string,grading:string}
	 */
	private static function row( array $entry, string $category, string $grading, int &$explicit, int &$counter, bool $advance ): array {
		if ( $advance ) {
			++$counter;
		}

		if ( null !== $entry['position'] ) {
			$explicit = $entry['position'];
		}

		return array(
			'writer'   => $entry['writer'],
			'title'    => $entry['title'],
			// Explicit, or inherited from the last explicit one (a tie listed on following
			// lines), or else the order within the block.
			'position' => $entry['position'] ?? ( $explicit > 0 ? $explicit : $counter ),
			'category' => $category,
			'grading'  => 'Gedigte' === $category ? $grading : '',
		);
	}

	/**
	 * Split text on a pattern, never failing. Pure.
	 *
	 * @param string $pattern A PCRE pattern.
	 * @param string $text    The text.
	 * @return list<string>
	 */
	private static function splitOn( string $pattern, string $text ): array {
		$parts = preg_split( $pattern, $text );

		return false === $parts ? array() : $parts;
	}

	/**
	 * Plain-text lines of the HTML, trimmed, empties dropped. Pure.
	 *
	 * @param string $html The post content.
	 * @return list<string>
	 */
	public static function lines( string $html ): array {
		$text = preg_replace( '#<\s*(br|/p|/li|/h\d|/div|/ol|/ul)\b[^>]*>#i', "\n", $html ) ?? $html;
		// phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- pure helper, kept WordPress-free so it unit-tests without WordPress loaded.
		$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xC2\xA0", ' ', $text );
		$out  = array();

		foreach ( self::splitOn( '/\R/u', $text ) as $raw ) {
			$line = trim( preg_replace( '/\s+/u', ' ', $raw ) ?? '' );

			if ( '' !== $line ) {
				$out[] = $line;
			}
		}

		return $out;
	}

	/**
	 * Split "GOUD: 1ste. Title – Writer" into the header and the entry. Pure.
	 *
	 * @param string $line A trimmed line.
	 * @return list<string>
	 */
	public static function splitHeaderAndEntry( string $line ): array {
		// "GORREL GEEN": a header with no winner.
		if ( 1 === preg_match( '/^(.+?)\s+geen\b.*$/iu', $line, $g ) && null !== self::header( $g[1] ) ) {
			return array( $g[1], 'Geen' );
		}

		if ( 1 === preg_match( '/^([\p{L}\s\/]{3,24}?)\s*[:]\s*(\S.+)$/u', $line, $m ) && null !== self::header( $m[1] ) && null === self::header( $line ) ) {
			return array( $m[1], $m[2] );
		}

		return array( $line );
	}

	/**
	 * Whether a short line is a category/grading header, and which. Pure.
	 *
	 * @param string $line A trimmed line.
	 * @return array{category:string, grading:string}|null
	 */
	public static function header( string $line ): ?array {
		if ( mb_strlen( $line ) > 40 || 1 === preg_match( '/\bmet\b/iu', $line ) ) {
			return null;
		}

		$bare = mb_strtolower( trim( preg_replace( '/[^\p{L}\s\/]+/u', ' ', $line ) ?? '' ) );
		$bare = trim( preg_replace( '/\b(wenners?|vlak|uitslae|projek|afdeling|klas)\b/u', ' ', $bare ) ?? '' );
		$bare = trim( preg_replace( '/\s+/u', ' ', $bare ) ?? '' );

		if ( '' === $bare ) {
			return null;
		}

		$grading  = '';
		$category = '';

		foreach ( self::splitOn( '#[\s/]+#u', $bare ) as $word ) {
			if ( in_array( $word, array( 'goud', 'goue' ), true ) ) {
				$grading = 'Goud';
			} elseif ( 'silwer' === $word ) {
				$grading = 'Silwer';
			} elseif ( 'brons' === $word ) {
				$grading = 'Brons';
			} elseif ( isset( self::CATEGORY_WORDS[ $word ] ) ) {
				$category = self::CATEGORY_WORDS[ $word ];
			} elseif ( '' !== self::fuzzyCategory( $word ) ) {
				$category = self::fuzzyCategory( $word ); // A one-letter typo in a header.
			} else {
				return null; // A word we do not know: not a header.
			}
		}

		return ( '' === $grading && '' === $category ) ? null : array(
			'category' => $category,
			'grading'  => $grading,
		);
	}

	/**
	 * The category for a header word that is one edit away from a known word, or ''. Pure.
	 *
	 * @param string $word A lowercase header word.
	 */
	private static function fuzzyCategory( string $word ): string {
		if ( mb_strlen( $word ) < 5 ) {
			return '';
		}

		foreach ( self::CATEGORY_WORDS as $known => $category ) {
			if ( mb_strlen( $known ) >= 5 && levenshtein( $word, $known ) <= 1 ) {
				return $category;
			}
		}

		return '';
	}

	/**
	 * "Geen" / "3. -" : a slot with no winner. Pure.
	 *
	 * @param string $line A trimmed line.
	 */
	public static function isNoWinner( string $line ): bool {
		return 1 === preg_match( '/^\W*(?:\d+\s*(?:de|ste|e)?\s*(?:plek)?|wenner|w)?\W*(?:geen\b.*|-+|–+)\s*$/iu', $line );
	}

	/**
	 * Parse a line that may hold one winner or several sharing a position ("A met X & B met Y"). Pure.
	 *
	 * @param string              $line       A trimmed line.
	 * @param array<string, true> $known_keys     Known writers by nameKey.
	 * @param bool                $paren_is_entry Whether "(n)" prefixes are entry numbers in this post.
	 * @return list<array{writer:string,title:string,position:?int}>
	 */
	public static function entries( string $line, array $known_keys = array(), bool $paren_is_entry = false ): array {
		$single = self::entry( $line, $known_keys, $paren_is_entry );

		if ( null !== $single ) {
			return array( $single );
		}

		if ( preg_match_all( '/\smet\s/iu', $line ) < 2 ) {
			return array();
		}

		// Keep any position marker, drop the entry numbers ("No. 14 & 15", "Nrs 21 en 1 (21) -").
		$lead = '';

		if ( 1 === preg_match( '/^(\(?\s*[123](?!\d)\s*\)?\s*(?:de|ste|e)?\s*(?:plek)?\s*[:;.\-–]?\s*|(?:wenner|w)\b\s*[:;.\-–]?\s*)/iu', $line, $m ) ) {
			$lead = $m[1];
			$line = substr( $line, strlen( $lead ) );
		}

		$line  = (string) preg_replace( '/^(?:nrs?|no)\.?\s*\d+(?:\s*(?:&|en)\s*(?:no\.?\s*)?\d+)*\s*(?:\(\d+\))?\s*[:;.\-–]?\s*/iu', '', $line );
		$parts = self::splitOn( '/\s+(?:&|EN|ASOOK)\s+(?=(?:\([^)]*\)\s*)?(?:no\.?\s*\d+\s*)?[^&]*?\smet\s)/u', $line );

		if ( count( $parts ) < 2 ) {
			return array();
		}

		$out = array();

		foreach ( $parts as $i => $part ) {
			$entry = self::entry( ( 0 === $i ? $lead : '' ) . $part, $known_keys, $paren_is_entry, true );

			if ( null === $entry ) {
				return array(); // Never half-parse a line.
			}

			$out[] = $entry;
		}

		return $out;
	}

	/**
	 * Parse one winner line into writer/title/position. Pure.
	 *
	 * @param string              $line           A trimmed line.
	 * @param array<string, true> $known_keys     Known writers by {@see nameKey()}.
	 * @param bool                $paren_is_entry Whether "(n)" prefixes are entry numbers in this post.
	 * @param bool                $marked         Whether the surrounding line already carried a marker (tie parts).
	 * @return array{writer:string,title:string,position:?int}|null
	 */
	public static function entry( string $line, array $known_keys = array(), bool $paren_is_entry = false, bool $marked = false ): ?array {
		if ( mb_strlen( $line ) > 200 ) {
			return null; // Prose (commentary), never a winner line.
		}

		$rest     = preg_replace( '/^gesamentlik\s+/iu', '', $line ) ?? $line;
		$position = null;

		if ( $paren_is_entry ) {
			$rest = (string) preg_replace( '/^\(\d+\)\s*(?=(?!no\b|nr\b)\p{L})/iu', '', $rest );
		}

		// Spelled-out positions (Eerste, Tweede, Derde) and "1.DEEL –".
		if ( 1 === preg_match( '/^(eerste|tweede|derde)\b\s*[:;.\-–]?\s*/iu', $rest, $w ) ) {
			$position = array(
				'eerste' => 1,
				'tweede' => 2,
				'derde'  => 3,
			)[ mb_strtolower( $w[1] ) ];
			$rest     = substr( $rest, strlen( $w[0] ) );
		} elseif ( 1 === preg_match( '/^([123])\.?\s*deel\s*[–-]\s*/iu', $rest, $w ) ) {
			$position = (int) $w[1];
			$rest     = substr( $rest, strlen( $w[0] ) );
		}

		if ( null === $position ) {
			if ( 1 === preg_match( '/^\d{1,3}\.(?=\p{L})/u', $rest ) ) {
				// A glued entry number (number, dot, name) is never a position.
				$rest = (string) preg_replace( '/^\d{1,3}\./u', '', $rest );
			} elseif ( 1 === preg_match( '/^[\(\[]?\s*([123])(?!\d)\s*[\)\]]?\s*(?:de|ste|e)?\s*(?:plek)?\s*[:;.\-–]?\s*(?=\S)/iu', $rest, $m ) ) {
				// Position prefix: bracketed digit, "1ste", "2de plek", "3 DE PLEK :".
				$position = (int) $m[1];
				$rest     = substr( $rest, strlen( $m[0] ) );
			} elseif ( 1 === preg_match( '/^(?:wenner|w)\b\s*[:;.\-–]?\s*/iu', $rest, $m ) ) {
				$position = 1;
				$rest     = substr( $rest, strlen( $m[0] ) );
			}
		}

		$had_marker = $marked || null !== $position || $rest !== $line;

		// Entry-number noise in front of the writer: "No. 19", "NR 3 –", "(14)", "(W) No 19 –",
		// ") No 26 -", "22 -", a bare "15 ".
		for ( $i = 0; $i < 4; $i++ ) {
			$before = $rest;
			$rest   = preg_replace( '/^(?:\(\s*\w{1,2}\s*\)|\))\s*/u', '', $rest ) ?? $rest;
			$rest   = preg_replace( '/^\(\s*\d+\s*%\s*\)\s*[:;.\-–]?\s*/u', '', $rest ) ?? $rest;
			$rest   = preg_replace( '/^(?:n[o0]|nr)\.?\s*\d+\s*[:;.\-–]?\s*/iu', '', $rest ) ?? $rest;
			$rest   = preg_replace( '/^\d{1,3}\s*[–-]\s*(?=\S)/u', '', $rest ) ?? $rest;
			$rest   = preg_replace( '/^\d{1,3}\s+(?=\p{L})/u', '', $rest ) ?? $rest;
			$rest   = ltrim( $rest );

			if ( $rest === $before ) {
				break;
			}

			$had_marker = true; // An entry-number marker was stripped.
		}

		// More than one "met": either two winners on a line (ambiguous: never guess) or a title that
		// contains "met" ("Husse met lang ore"). The known-writer list settles it: accept only when
		// the text before the FIRST "met" is a known writer and no second winner follows a connector.
		if ( preg_match_all( '/\smet\s/iu', $rest ) > 1 ) {
			return self::splitAtFirstMet( $rest, $position, $known_keys );
		}

		// 2018 layout: title, dash, percentage mark, then the writer.
		if ( 1 === preg_match( '/^(.+?)\s+[–-]\s*\d+\s*%\s+(.+)$/u', $rest, $m ) ) {
			return self::build( $m[2], $m[1], $position );
		}

		// "Writer met Title". Without any marker (position, "No.", "(n)") the writer must be a known
		// name, otherwise a prose sentence with "met" in it ("... gedig met nostalgie ...") would pass.
		if ( 1 === preg_match( '/^(.+?)\s+met(?:\s+|(?=[’\'‘]))(.+)$/iu', $rest, $m ) ) {
			if ( ! $had_marker && ! isset( $known_keys[ self::nameKey( $m[1] ) ] ) ) {
				return null;
			}

			return self::build( $m[1], $m[2], $position );
		}

		// "A – B" / "A: B" / "A deur B": which side is the writer is settled by the known-writer
		// list. A trailing mark ("– 91%") is dropped first.
		if ( null !== $position ) {
			$rest = (string) preg_replace( '/\s*[–-]?\s*\d+\s*%\s*$/u', '', $rest );

			return self::splitByKnownWriter( $rest, $position, $known_keys );
		}

		// "NO 8 Andries Fourie Musiek oor my": no separator at all; accept only when exactly
		// one leading run of words is a known writer.
		return self::splitByLeadingWriter( $rest, $known_keys );
	}

	/**
	 * "Writer met Title with met in it": accept when the leading part is a known writer. Pure.
	 *
	 * @param string              $text       Text after the markers.
	 * @param int|null            $position   Explicit position.
	 * @param array<string, true> $known_keys Known writers by nameKey.
	 * @return array{writer:string,title:string,position:?int}|null
	 */
	private static function splitAtFirstMet( string $text, ?int $position, array $known_keys ): ?array {
		if ( 1 !== preg_match( '/^(.+?)\s+met\s+(.+)$/iu', $text, $m ) || ! isset( $known_keys[ self::nameKey( $m[1] ) ] ) ) {
			return null;
		}

		// A second winner after a connector ("... & B met Y") means this is a tie line.
		foreach ( self::splitOn( '/\s+(?:&|EN|ASOOK)\s+/u', $m[2] ) as $i => $segment ) {
			if ( $i > 0 && 1 === preg_match( '/^(.+?)\s+met\s+/iu', $segment, $n ) && isset( $known_keys[ self::nameKey( $n[1] ) ] ) ) {
				return null;
			}
		}

		return self::build( $m[1], $m[2], $position );
	}

	/**
	 * A normalised name key: letters only, lowercase, parenthetical removed. Pure.
	 *
	 * @param string $name A writer name.
	 */
	public static function nameKey( string $name ): string {
		$name = (string) preg_replace( '/\(.*?\)/u', '', $name );

		return (string) preg_replace( '/[^\p{L}\p{N}]+/u', '', mb_strtolower( $name ) );
	}

	/**
	 * Split "Writer Title words" when exactly one 2-4 word prefix is a known writer. Pure.
	 *
	 * @param string              $text       The text after the markers.
	 * @param array<string, true> $known_keys Known writers by nameKey.
	 * @return array{writer:string,title:string,position:?int}|null
	 */
	private static function splitByLeadingWriter( string $text, array $known_keys ): ?array {
		if ( array() === $known_keys ) {
			return null;
		}

		$words = self::splitOn( '/\s+/u', trim( $text ) );
		$hit   = null;

		$count = count( $words );

		for ( $n = 2; $n <= 4 && $n < $count; $n++ ) {
			if ( isset( $known_keys[ self::nameKey( implode( ' ', array_slice( $words, 0, $n ) ) ) ] ) ) {
				if ( null !== $hit ) {
					return null;
				}

				$hit = $n;
			}
		}

		return null === $hit ? null : self::build( implode( ' ', array_slice( $words, 0, $hit ) ), implode( ' ', array_slice( $words, $hit ) ), null );
	}

	/**
	 * Split "A – B" / "A - B" / "A: B" where exactly one side is a known writer. Pure.
	 *
	 * @param string              $text       The text after the position marker.
	 * @param int                 $position   The explicit position.
	 * @param array<string, true> $known_keys Known writers by nameKey.
	 * @return array{writer:string,title:string,position:?int}|null
	 */
	private static function splitByKnownWriter( string $text, int $position, array $known_keys ): ?array {
		$found = null;

		if ( preg_match_all( '/\s[–-]\s|\s?:\s+|\s[–-](?=\S)|(?<=\S)[–-]\s|\sdeur\s/iu', $text, $all, PREG_OFFSET_CAPTURE ) ) {
			foreach ( $all[0] as $hit ) {
				$left  = trim( substr( $text, 0, $hit[1] ) );
				$right = trim( substr( $text, $hit[1] + strlen( $hit[0] ) ) );

				if ( '' === $left || '' === $right ) {
					continue;
				}

				$left_known  = isset( $known_keys[ self::nameKey( $left ) ] );
				$right_known = isset( $known_keys[ self::nameKey( $right ) ] );

				if ( $left_known === $right_known ) {
					continue; // Neither or both: ambiguous.
				}

				if ( null !== $found ) {
					return null; // Two different valid splits: ambiguous.
				}

				$found = $right_known ? self::build( $right, $left, $position ) : self::build( $left, $right, $position );
			}
		}

		return $found;
	}

	/**
	 * Build an entry, trimming separators. Pure.
	 *
	 * @param string   $writer   Raw writer.
	 * @param string   $title    Raw title.
	 * @param int|null $position Explicit position.
	 * @return array{writer:string,title:string,position:?int}|null
	 */
	private static function build( string $writer, string $title, ?int $position ): ?array {
		$writer = trim( $writer, " \t–-:;,+*" );
		$title  = trim( $title, " \t" );
		$title  = trim( preg_replace( '/^[–-]\s*/u', '', $title ) ?? $title );

		if ( 1 === preg_match( '/^["“”\'‘’]\s*(.+?)\s*["“”\'‘’]$/u', $title, $q ) ) {
			$title = $q[1];
		}

		return ( '' === $writer || '' === $title ) ? null : array(
			'writer'   => $writer,
			'title'    => $title,
			'position' => $position,
		);
	}

	/**
	 * Whether a line in the winners region plausibly was meant to be an entry. Pure.
	 *
	 * @param string $line A trimmed line.
	 */
	private static function looksLikeEntry( string $line ): bool {
		return mb_strlen( $line ) < 160 && 1 === preg_match( '/\b(met|no\.?|nr\.?|plek)\b|\d+\s*%|^\W*(?:[123](?!\d)\s*(?:de|ste|e)?|wenner)\b\s*[:;.\-–]/iu', $line );
	}
}
