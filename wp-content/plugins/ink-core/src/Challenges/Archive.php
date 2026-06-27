<?php
/**
 * Uitdagings list page — Story 12.2 (FR-46).
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Challenges;

use Ink\Content\FieldSets;
use Ink\Content\PostTypes;
use Ink\I18n\Terms;
use Ink\Kernel\ArchiveRender;
use Ink\Kernel\Sast;
use Ink\Kernel\Scalar;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the `ink/uitdaging-argief` block: the challenges list page (Archetype B).
 *
 * Lists published `uitdaging` posts newest-first, paginated, each as a card with the
 * tema, the sluitingsdatum and a server-computed **countdown** ("Nog N dae" while
 * open, "Sluit vandag" on the deadline day, "Gesluit" once closed) derived from the
 * inclusive end-of-day-SAST deadline ({@see Sast}). Reads stay SERVER-RENDERED via
 * `WP_Query` (AD-7), mirroring the {@see \Ink\Library\Archive} house style: pure
 * {@see self::queryArgs()} + pure {@see self::countdownLabel()}/{@see self::toHtml()}
 * + a thin {@see self::render()}.
 *
 * Conflation-clean: references only `Ink\Content` (CPT + deadline/theme meta keys) +
 * the `Kernel` helpers + the `Terms` registry + WP core — zero `Ink\Tiers`/
 * `Ink\Entitlement`. Browsing published challenges is open (never gated).
 *
 * @package Ink\Core
 */
final class Archive {

	/**
	 * The block name — single source for the renderer + the theme pattern embed.
	 *
	 * @var string
	 */
	public const BLOCK = 'ink/uitdaging-argief';

	/**
	 * Challenges per page.
	 *
	 * @var int
	 */
	public const PER_PAGE = 12;

	/**
	 * Custom paged query var — avoids colliding with WP page pagination.
	 *
	 * @var string
	 */
	public const PAGED_VAR = 'uitdaging_bladsy';

	/**
	 * Register the server-rendered block on `init`.
	 */
	public function register(): void {
		add_action( 'init', array( self::class, 'registerBlock' ) );
	}

	/**
	 * Register the `ink/uitdaging-argief` dynamic block.
	 */
	public static function registerBlock(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			self::BLOCK,
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);
	}

	/**
	 * Build the `WP_Query` args for the newest-first challenges list. Pure.
	 *
	 * @param int $paged    The requested page (clamped to >= 1).
	 * @param int $per_page Challenges per page.
	 * @return array<string, mixed>
	 */
	public static function queryArgs( int $paged, int $per_page ): array {
		return array(
			'post_type'           => PostTypes::UITDAGING,
			'post_status'         => 'publish',
			'posts_per_page'      => $per_page,
			'paged'               => max( 1, $paged ),
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
		);
	}

	/**
	 * The countdown label for a deadline relative to `now`. Pure.
	 *
	 * Empty without a deadline; "Gesluit" once past the inclusive end-of-day-SAST
	 * boundary; "Sluit vandag" on the deadline's own SAST day; otherwise "Nog N dae"
	 * (singular "Nog 1 dag"), counting whole SAST calendar days.
	 *
	 * @param \DateTimeImmutable|null $deadline The deadline instant, or null.
	 * @param \DateTimeInterface      $now      The instant to measure from.
	 * @return string
	 */
	public static function countdownLabel( ?\DateTimeImmutable $deadline, \DateTimeInterface $now ): string {
		if ( null === $deadline ) {
			return '';
		}

		if ( ! Sast::isThroughEndOfDay( $deadline, $now ) ) {
			return Terms::label( 'uitdaging_gesluit' );
		}

		$sast         = new \DateTimeZone( Sast::TIMEZONE );
		$deadline_day = \DateTimeImmutable::createFromInterface( $deadline )->setTimezone( $sast )->setTime( 0, 0, 0 );
		$now_day      = \DateTimeImmutable::createFromInterface( $now )->setTimezone( $sast )->setTime( 0, 0, 0 );

		$days = (int) $now_day->diff( $deadline_day )->format( '%r%a' );

		if ( $days <= 0 ) {
			return Terms::label( 'uitdaging_sluit_vandag' );
		}

		if ( 1 === $days ) {
			return __( 'Nog 1 dag', 'ink-core' );
		}

		/* translators: %d: whole days remaining until the challenge deadline. */
		return sprintf( __( 'Nog %d dae', 'ink-core' ), $days );
	}

	/**
	 * Block render callback. Reads the page input, queries, builds cards, renders.
	 *
	 * @return string
	 */
	public static function render(): string {
		$paged = ArchiveRender::requestInt( self::PAGED_VAR, 1 );
		$query = new \WP_Query( self::queryArgs( $paged, self::PER_PAGE ) );
		$now   = Sast::now();

		$cards = array();

		foreach ( $query->posts as $post ) {
			if ( ! $post instanceof \WP_Post ) {
				continue;
			}

			$deadline = Deadline::parse(
				Scalar::asString( get_post_meta( (int) $post->ID, FieldSets::UITDAGING_DEADLINE, true ) )
			);

			$cards[] = self::cardHtml(
				array(
					'title'     => get_the_title( $post ),
					'permalink' => (string) get_permalink( $post ),
					'tema'      => Scalar::asString( get_post_meta( (int) $post->ID, FieldSets::UITDAGING_THEME, true ) ),
					'deadline'  => null !== $deadline ? Deadline::format( $deadline ) : '',
					'countdown' => self::countdownLabel( $deadline, $now ),
					'is_open'   => null !== $deadline && Sast::isThroughEndOfDay( $deadline, $now ),
				)
			);
		}

		return self::toHtml(
			$cards,
			array(
				'paged'     => max( 1, $paged ),
				'max_pages' => (int) $query->max_num_pages,
			)
		);
	}

	/**
	 * One challenge card. Pure — Terms + escaping only.
	 *
	 * @param array{title:string, permalink:string, tema?:string, deadline?:string, countdown?:string, is_open?:bool} $card The challenge.
	 * @return string
	 */
	public static function cardHtml( array $card ): string {
		$is_open   = ! empty( $card['is_open'] );
		$state     = $is_open ? 'is-oop' : 'is-gesluit';
		$tema      = isset( $card['tema'] ) ? (string) $card['tema'] : '';
		$deadline  = isset( $card['deadline'] ) ? (string) $card['deadline'] : '';
		$countdown = isset( $card['countdown'] ) ? (string) $card['countdown'] : '';

		$tema_html = '' !== $tema
			? '<span class="ink-uitdagings__tema">' . esc_html( Terms::label( 'tema' ) ) . ': ' . esc_html( $tema ) . '</span>'
			: '';

		$deadline_html = '' !== $deadline
			? '<span class="ink-uitdagings__sluitingsdatum">' . esc_html( Terms::label( 'sluitingsdatum' ) ) . ': '
				. '<time>' . esc_html( $deadline ) . '</time></span>'
			: '';

		$countdown_html = '' !== $countdown
			? '<span class="ink-uitdagings__aftel">' . esc_html( $countdown ) . '</span>'
			: '';

		return '<li class="ink-uitdagings__item is-style-card ' . esc_attr( $state ) . '">'
			. '<a class="ink-uitdagings__titel" href="' . esc_url( $card['permalink'] ) . '">' . esc_html( $card['title'] ) . '</a>'
			. $tema_html
			. $deadline_html
			. $countdown_html
			. '</li>';
	}

	/**
	 * Build the archive HTML. Pure — Terms + escaping only.
	 *
	 * @param list<string>                    $cards The pre-rendered card markup.
	 * @param array{paged:int, max_pages:int} $nav The render context.
	 * @return string
	 */
	public static function toHtml( array $cards, array $nav ): string {
		$heading = '<h1 class="ink-uitdagings__heading">' . esc_html( Terms::label( 'uitdaging_plural' ) ) . '</h1>';

		if ( array() === $cards ) {
			/* translators: %s: the challenges (uitdagings) label. */
			$empty = sprintf( __( 'Geen %s gevind nie.', 'ink-core' ), Terms::label( 'uitdaging_plural' ) );

			return '<section class="ink-uitdagings">' . $heading
				. '<p class="ink-uitdagings__leeg">' . esc_html( $empty ) . '</p></section>';
		}

		$html = '<section class="ink-uitdagings">' . $heading . '<ul class="ink-uitdagings__list">' . implode( '', $cards ) . '</ul>';

		$paged     = isset( $nav['paged'] ) ? (int) $nav['paged'] : 1;
		$max_pages = isset( $nav['max_pages'] ) ? (int) $nav['max_pages'] : 0;

		return $html . ArchiveRender::pagination( $paged, $max_pages, 'ink-uitdagings', self::PAGED_VAR ) . '</section>';
	}
}
