<?php
/**
 * One yearly edition of the annual competition — WP5 (`05-kompetisies` §3.2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use DateTimeImmutable;
use Ink\Content\FieldSets;
use Ink\Content\Repeater;
use Ink\Kernel\CompetitionType;

defined( 'ABSPATH' ) || exit;

/**
 * Read model of a `kompetisie` post. Pure once built: a blank slot or line simply doesn't
 * come back, so the page renders whatever an editor has filled in.
 *
 * @package Ink\Core
 */
final class Edition {

	/**
	 * The value of the `standaard` field that marks the default announcement.
	 *
	 * @var string
	 */
	public const DEFAULT_YES = 'ja';

	/**
	 * Build an edition.
	 *
	 * @param int                   $postId The post.
	 * @param string                $title  The post title: the competition's name.
	 * @param array<string, string> $values Field values by key suffix, e.g. `gala_naam`.
	 */
	public function __construct(
		public readonly int $postId,
		public readonly string $title,
		private readonly array $values,
	) {
	}

	/**
	 * One field, trimmed; '' when blank.
	 *
	 * @param string $suffix The key suffix.
	 * @return string
	 */
	public function field( string $suffix ): string {
		return trim( $this->values[ $suffix ] ?? '' );
	}

	/**
	 * The edition's year; 0 when unset.
	 *
	 * @return int
	 */
	public function year(): int {
		return (int) $this->field( 'jaar' );
	}

	/**
	 * The competition type; an edition with none stored is annual.
	 *
	 * @return CompetitionType
	 */
	public function type(): CompetitionType {
		return CompetitionType::fromMeta( $this->field( 'tipe' ) );
	}

	/**
	 * Whether results are published for this edition: the editor set `uitslae_gepubliseer` to
	 * `ja` on the post (K4). The flag alone decides; an old year may publish just an intro.
	 *
	 * @return bool
	 */
	public function hasPublishedResults(): bool {
		return self::DEFAULT_YES === $this->field( 'uitslae_gepubliseer' );
	}

	/**
	 * The results intro: the celebration text above the winners, paragraphs split on blank lines.
	 *
	 * @return list<string>
	 */
	public function resultsIntro(): array {
		$parts = preg_split( '/\R{2,}/u', $this->field( 'uitslae_inleiding' ) );

		return array_values( array_filter( array_map( 'trim', false === $parts ? array() : $parts ), static fn ( string $p ): bool => '' !== $p ) );
	}

	/**
	 * The migrated old results post, as HTML (K6): reused as it is, shown after the intro. '' for a
	 * new edition. Unsanitised here; {@see UitslaePage::toHtml()} filters it on output.
	 *
	 * @return string
	 */
	public function resultsBody(): string {
		return trim( $this->field( 'uitslae_oud' ) );
	}

	/**
	 * The winner records in the editor's order. Valid records only (an award and a user or name);
	 * the records exist whether or not the results are published, so a caller shows them only when
	 * {@see self::hasPublishedResults()}. Competition wins are not challenge wins.
	 *
	 * @return list<Winner>
	 */
	public function winners(): array {
		$out = array();

		foreach ( Repeater::decode( $this->field( 'wenners' ) ) as $row ) {
			$winner = Winner::fromRow( $row );

			if ( null !== $winner ) {
				$out[] = $winner;
			}
		}

		return $out;
	}

	/**
	 * The winners grouped under their award, awards in the order each first appears, winners in
	 * record order within an award (two joint winners share one heading).
	 *
	 * @return list<array{award:string, winners:list<Winner>}>
	 */
	public function winnersByAward(): array {
		$groups = array();

		foreach ( $this->winners() as $winner ) {
			$groups[ $winner->award ]['award']     = $winner->award;
			$groups[ $winner->award ]['winners'][] = $winner;
		}

		return array_values( $groups );
	}

	/**
	 * The derived state (`05-kompetisies` §14.2). Pure; never stored.
	 *
	 * Order: the default announcement is generic info and always `closed`; a newer edition
	 * makes this one `archive`; published results make it `results`; then the dates, compared
	 * as site-local `Y-m-d` days, both ends inclusive. A blank `oopdatum` counts as already
	 * open; a blank or invalid `sluitingsdatum` means no window, so `closed`.
	 *
	 * @param DateTimeImmutable $now         Now, in the site's timezone.
	 * @param bool              $newerExists Whether a later real edition exists; see {@see Data::hasNewer()}.
	 * @return EditionState
	 */
	public function state( DateTimeImmutable $now, bool $newerExists = false ): EditionState {
		if ( $this->isDefault() ) {
			return EditionState::Closed;
		}

		if ( $newerExists ) {
			return EditionState::Archive;
		}

		if ( $this->hasPublishedResults() ) {
			return EditionState::Results;
		}

		$today = $now->format( 'Y-m-d' );
		$open  = $this->day( 'oopdatum' );
		$close = $this->day( 'sluitingsdatum' );

		if ( '' !== $open && $today < $open ) {
			return EditionState::Announced;
		}

		return '' !== $close && $today <= $close ? EditionState::Open : EditionState::Closed;
	}

	/**
	 * A date field as `Y-m-d`; '' when blank or not a date.
	 *
	 * @param string $suffix The key suffix.
	 * @return string
	 */
	private function day( string $suffix ): string {
		$value = $this->field( $suffix );

		return 1 === preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	/**
	 * Whether this post is the default announcement (owner, 2026-11-08): the text the hero and
	 * the categories show while no competition is open. It is edited on the site like any
	 * edition, and a real edition replaces it on the page.
	 *
	 * @return bool
	 */
	public function isDefault(): bool {
		return self::DEFAULT_YES === $this->field( 'standaard' );
	}

	/**
	 * Whether entries can be made: a real edition, not the announcement. The default has no
	 * rules, dates or way to enter yet.
	 *
	 * @return bool
	 */
	public function acceptsEntries(): bool {
		return ! $this->isDefault();
	}

	/**
	 * Whether this post is a current edition. Imported old competitions have no
	 * description, so one never becomes current (`05-kompetisies` §3.5), and the default
	 * announcement never is one.
	 *
	 * @return bool
	 */
	public function isCurrentEdition(): bool {
		return '' !== $this->field( 'beskrywing' ) && ! $this->isDefault();
	}

	/**
	 * The announcement built into the code, for when no default post exists (an editor
	 * deleted it). {@see Module::ensureDefault()} creates the post from the same values, so
	 * the page and the admin screen start identical.
	 *
	 * @return self
	 */
	public static function builtIn(): self {
		return new self(
			0,
			__( 'Ons jaarlikse kompetisie', 'ink-core' ),
			array(
				'standaard'  => self::DEFAULT_YES,
				'uitgawe'    => __( 'Binnekort', 'ink-core' ),
				'beskrywing' => __( 'Elke jaar hou INK \'n groot skryfkompetisie. Hou hierdie bladsy dop: sodra die volgende kompetisie bekend gemaak word, verskyn die reëls, kategorieë en sluitingsdatum hier.', 'ink-core' ),
			)
		);
	}

	/**
	 * Every field value, for writing the built-in announcement as a post.
	 *
	 * @return array<string, string>
	 */
	public function values(): array {
		return $this->values;
	}

	/**
	 * The five gala photos an editor picked for the page, in slot order, with their own
	 * captions. With none picked, the first five of the gallery stand in (their captions come
	 * from the media library). A slot with no photo is skipped; slot 1 is the wide one.
	 *
	 * @return list<array{id:int, caption:string}>
	 */
	public function featuredPhotos(): array {
		$out = array();

		for ( $n = 1; $n <= FieldSets::GALA_FOTO_SLOTS; $n++ ) {
			$id = (int) $this->field( "gala_foto{$n}" );

			if ( $id > 0 ) {
				$out[] = array(
					'id'      => $id,
					'caption' => $this->field( "gala_foto{$n}_onderskrif" ),
				);
			}
		}

		if ( array() !== $out ) {
			return $out;
		}

		foreach ( array_slice( $this->photoIds(), 0, FieldSets::GALA_FOTO_SLOTS ) as $id ) {
			$out[] = array(
				'id'      => $id,
				'caption' => '',
			);
		}

		return $out;
	}

	/**
	 * Whether this post has a gala to show under "Evenings past".
	 *
	 * @return bool
	 */
	public function hasPastGala(): bool {
		return '' !== $this->field( 'gala_hoogtepunt' ) || array() !== $this->featuredPhotos();
	}

	/**
	 * Whether the post has a gala of its own to describe (a name filled in).
	 *
	 * @return bool
	 */
	public function hasGala(): bool {
		return '' !== $this->field( 'gala_naam' );
	}

	/**
	 * The categories with a name, in slot order. A guideline is one line.
	 *
	 * @return list<array{naam:string, ikoon:string, tema:string, beskrywing:string, woordlimiet:string, riglyne:list<string>}>
	 */
	public function categories(): array {
		$out = array();

		for ( $n = 1; $n <= FieldSets::KOMPETISIE_SLOTS; $n++ ) {
			$naam = $this->field( "kat{$n}_naam" );

			if ( '' === $naam ) {
				continue;
			}

			$out[] = array(
				'naam'        => $naam,
				'ikoon'       => FieldSets::sanitizeIcon( $this->field( "kat{$n}_ikoon" ) ),
				'tema'        => $this->field( "kat{$n}_tema" ),
				'beskrywing'  => $this->field( "kat{$n}_beskrywing" ),
				'woordlimiet' => $this->field( "kat{$n}_woordlimiet" ),
				'riglyne'     => self::lines( $this->field( "kat{$n}_riglyne" ) ),
			);
		}

		return $out;
	}

	/**
	 * The gala programme: line order is the order.
	 *
	 * @return list<string>
	 */
	public function programme(): array {
		return self::lines( $this->field( 'gala_program' ) );
	}

	/**
	 * The tickets with a label, in slot order. Prices are fictional (D4).
	 *
	 * @return list<array{label:string, price:string}>
	 */
	public function tickets(): array {
		$out = array();

		for ( $n = 1; $n <= FieldSets::KOMPETISIE_SLOTS; $n++ ) {
			$label = $this->field( "kaartjie{$n}_etiket" );

			if ( '' !== $label ) {
				$out[] = array(
					'label' => $label,
					'price' => $this->field( "kaartjie{$n}_prys" ),
				);
			}
		}

		return $out;
	}

	/**
	 * The gala photos' attachment ids, in gallery order.
	 *
	 * @return list<int>
	 */
	public function photoIds(): array {
		$ids = explode( ',', FieldSets::sanitizeIdList( $this->field( 'gala_fotos' ) ) );

		return array_values( array_filter( array_map( 'intval', $ids ) ) );
	}

	/**
	 * The non-blank lines of a textarea.
	 *
	 * @param string $text The stored text.
	 * @return list<string>
	 */
	private static function lines( string $text ): array {
		$lines = preg_split( '/\R/u', $text );

		return array_values( array_filter( array_map( 'trim', false === $lines ? array() : $lines ), static fn ( string $l ): bool => '' !== $l ) );
	}
}
