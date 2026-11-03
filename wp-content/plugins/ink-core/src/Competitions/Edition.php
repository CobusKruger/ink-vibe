<?php
/**
 * One yearly edition of the annual competition — WP5 (`05-kompetisies` §3.2).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Competitions;

use Ink\Content\FieldSets;

defined( 'ABSPATH' ) || exit;

/**
 * Read model of a `kompetisie` post. Pure once built: a blank slot or line simply doesn't
 * come back, so the page renders whatever an editor has filled in.
 *
 * @package Ink\Core
 */
final class Edition {

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
	 * Whether this post is a current edition. Imported old competitions have no
	 * description, so one never becomes current (`05-kompetisies` §3.5).
	 *
	 * @return bool
	 */
	public function isCurrentEdition(): bool {
		return '' !== $this->field( 'beskrywing' );
	}

	/**
	 * Whether this post has a gala to show under "Evenings past".
	 *
	 * @return bool
	 */
	public function hasPastGala(): bool {
		return '' !== $this->field( 'gala_hoogtepunt' ) || array() !== $this->photoIds();
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
