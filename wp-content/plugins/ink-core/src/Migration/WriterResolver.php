<?php
/**
 * Resolves a by-line from an old result post to a WordPress account — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Migration;

defined( 'ABSPATH' ) || exit;

/**
 * Turns the writer name printed on a legacy result post into an account id.
 *
 * Order: strip layout noise ("(60%) -", "Gesamentlik") → apply the committed alias table
 * (misspellings of one by-line) → exact match on the name key (committed by-line→account
 * table first, then the site's display names, nicenames and logins) → a unique fuzzy match
 * (two edits at most, names of ten letters or more, one account only). A fuzzy hit is
 * reported as such so a human can see it; anything ambiguous or unknown is `null`, never
 * guessed. Pure.
 *
 * @package Ink\Core
 */
final class WriterResolver {

	/**
	 * Name key → account id.
	 *
	 * @var array<string, int>
	 */
	private array $users;

	/**
	 * Variant name key → canonical name key.
	 *
	 * @var array<string, string>
	 */
	private array $aliases;

	/**
	 * Build a resolver over the account directory and the alias table.
	 *
	 * @param array<string, int>    $users   Name key → account id.
	 * @param array<string, string> $aliases Variant name key → canonical name key.
	 */
	public function __construct( array $users, array $aliases ) {
		$this->users   = $users;
		$this->aliases = $aliases;
	}

	/**
	 * Strip layout noise that sometimes clings to a by-line. Pure.
	 *
	 * @param string $writer The by-line as parsed.
	 */
	public static function clean( string $writer ): string {
		$writer = (string) preg_replace( '/^(?:\(\s*(?:no\.?\s*)?\d+\s*%?\s*\)\s*[-–]?\s*|(?:no|nr)\.?\s*\d+\s*[-–:]?\s*|gesamentlik\s+)+/iu', '', $writer );

		return trim( $writer, " \t+*" );
	}

	/**
	 * Resolve a by-line.
	 *
	 * @param string $writer The by-line as parsed.
	 * @return array{user:int, how:string, matched:string}|null `how` is exact, alias or fuzzy.
	 */
	public function resolve( string $writer ): ?array {
		$key  = LegacyResultsParser::nameKey( self::clean( $writer ) );
		$how  = 'exact';
		$name = $key;

		if ( '' === $key ) {
			return null;
		}

		if ( isset( $this->aliases[ $key ] ) ) {
			$name = $this->aliases[ $key ];
			$how  = 'alias';
		}

		if ( isset( $this->users[ $name ] ) ) {
			return array(
				'user'    => $this->users[ $name ],
				'how'     => $how,
				'matched' => $name,
			);
		}

		return $this->fuzzy( $name );
	}

	/**
	 * A unique near match, or null. Pure.
	 *
	 * @param string $name A name key.
	 * @return array{user:int, how:string, matched:string}|null
	 */
	private function fuzzy( string $name ): ?array {
		if ( strlen( $name ) < 10 ) {
			return null;
		}

		$hits = array();

		foreach ( $this->users as $candidate => $user ) {
			$candidate = (string) $candidate; // PHP turns numeric-string keys (a login like "1981") into ints.

			if ( abs( strlen( $candidate ) - strlen( $name ) ) <= 2 && levenshtein( $name, $candidate ) <= 2 ) {
				$hits[ $user ] = $candidate;
			}
		}

		if ( 1 !== count( $hits ) ) {
			return null;
		}

		$user = (int) array_key_first( $hits );

		return array(
			'user'    => $user,
			'how'     => 'fuzzy',
			'matched' => $hits[ $user ],
		);
	}
}
