<?php
/**
 * One winner record of a competition's results — K4 (`05-kompetisies` §14.4).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;

defined( 'ABSPATH' ) || exit;

/**
 * An award and who received it. Not a challenge win: it is never counted for Gradering or shown
 * on a profile by itself. The winner may be a site user, a name only, or both. Pure.
 *
 * @package Ink\Core
 */
final class Winner {

	/**
	 * The stored columns, in form order.
	 *
	 * @var list<string>
	 */
	public const COLUMNS = array( 'toekenning', 'wenner_gebruiker', 'wenner_naam', 'inskrywing', 'foto', 'aantekening' );

	/**
	 * Build a winner.
	 *
	 * @param string $award      The award: a category win, a group prize, a book prize, anything.
	 * @param int    $userId     The linked user, 0 for none (the account may since have been deleted).
	 * @param string $name       The free-text name; may be blank when a user is linked.
	 * @param string $entryTitle The winning entry or work, blank for none.
	 * @param int    $photoId    The attachment of the winner receiving the award, 0 for none.
	 * @param string $note       A citation, blank for none.
	 */
	public function __construct(
		public readonly string $award,
		public readonly int $userId,
		public readonly string $name,
		public readonly string $entryTitle,
		public readonly int $photoId,
		public readonly string $note,
	) {
	}

	/**
	 * The winner a stored row describes, or null when it has no award or nobody to give it to.
	 *
	 * @param array<string, mixed> $row A stored row.
	 * @return self|null
	 */
	public static function fromRow( array $row ): ?self {
		$text = static fn ( string $column ): string => isset( $row[ $column ] ) && is_scalar( $row[ $column ] ) ? trim( (string) $row[ $column ] ) : '';
		$id   = static fn ( string $column ): int => max( 0, (int) $text( $column ) );

		$award = $text( 'toekenning' );
		$user  = $id( 'wenner_gebruiker' );
		$name  = $text( 'wenner_naam' );

		if ( ! FieldSets::isWinnerRecord( $row ) ) {
			return null;
		}

		return new self( $award, $user, $name, $text( 'inskrywing' ), $id( 'foto' ), $text( 'aantekening' ) );
	}

	/**
	 * The stored row of this winner.
	 *
	 * @return array<string, string|int>
	 */
	public function toRow(): array {
		return array(
			'toekenning'       => $this->award,
			'wenner_gebruiker' => $this->userId,
			'wenner_naam'      => $this->name,
			'inskrywing'       => $this->entryTitle,
			'foto'             => $this->photoId,
			'aantekening'      => $this->note,
		);
	}

	/**
	 * Whether the winner is linked to a site account.
	 *
	 * @return bool
	 */
	public function hasUser(): bool {
		return $this->userId > 0;
	}

	/**
	 * Whether there is a photo of the winner receiving the award.
	 *
	 * @return bool
	 */
	public function hasPhoto(): bool {
		return $this->photoId > 0;
	}
}
