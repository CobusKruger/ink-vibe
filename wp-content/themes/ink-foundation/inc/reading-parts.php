<?php
/**
 * Reading-page parts shared by the storie and artikel patterns (and the gedig byline):
 * Lovable's `ReadStory.tsx` node for node — header > container > inner column, a byline
 * whose avatar and name are one author link followed by the read time, the body article
 * > container > 672px column, the sticky engagement bar > container > pill, the author
 * band and the responses band. Emitted as raw HTML around the dynamic blocks, so
 * WordPress's group layout adds no wrappers, block-gap margins or positioning
 * (acceptance 2026-11-02, as the header and Tuis).
 *
 * Presentation only: the data comes from core (title, author, content) and from ink-core
 * blocks and helpers (`ReadingTime`), each guarded so the theme degrades without the plugin.
 *
 * @package Ink\Foundation
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ink_foundation_reading_byline' ) ) {
	/**
	 * The byline row: one author link (avatar + name), a bullet, and the read time
	 * (Lovable shows read time, not the date; spec 02 section 8). Impure (post reads).
	 *
	 * @return string
	 */
	function ink_foundation_reading_byline(): string {
		$post_id   = (int) get_queried_object_id();
		$author_id = (int) get_post_field( 'post_author', $post_id );
		$name      = (string) get_the_author_meta( 'display_name', $author_id );
		$avatar    = (string) get_avatar_url( $author_id, array( 'size' => 80 ) );
		$minutes   = class_exists( '\Ink\Discovery\ReadingTime' )
			? \Ink\Discovery\ReadingTime::minutesFromText( wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) ) )
			: 0;
		$read_time = class_exists( '\Ink\Discovery\ReadingTime' ) ? \Ink\Discovery\ReadingTime::label( $minutes ) : '';

		$html  = '<div data-audit-id="reading-byline" class="ink-lees-byline">';
		$html .= '<a data-audit-id="reading-byline-author" class="ink-lees-byline__skrywer" href="' . esc_url( (string) get_author_posts_url( $author_id ) ) . '">';

		if ( '' !== $avatar ) {
			$html .= '<img data-audit-id="reading-byline-avatar" class="ink-avatar ink-avatar--40" src="' . esc_url( $avatar ) . '" alt="' . esc_attr( $name ) . '" width="40" height="40" />';
		}

		$html .= '<span data-audit-id="reading-byline-name" class="ink-lees-byline__naam">' . esc_html( $name ) . '</span></a>';

		if ( '' !== $read_time ) {
			$html .= '<span data-audit-id="reading-byline-sep">•</span>'
				. '<span data-audit-id="reading-byline-readtime" class="ink-lees-byline__leestyd">'
				. '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>'
				. esc_html( $read_time ) . '</span>';
		}

		return $html . '</div>';
	}
}

if ( ! function_exists( 'ink_foundation_reading_share_button' ) ) {
	/**
	 * The engagement bar's share button (Lovable `reading-share-btn`): the Share2 icon.
	 * `assets/js/lees-deel.js` opens the device share sheet, or copies the link. Pure.
	 *
	 * @return string
	 */
	function ink_foundation_reading_share_button(): string {
		return '<button type="button" data-audit-id="reading-share-btn" class="ink-lees-deel" data-ink-deel aria-label="' . esc_attr__( 'Deel', 'ink-foundation' ) . '">'
			. '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>'
			. '</button>';
	}
}

if ( ! function_exists( 'ink_foundation_reading_badge' ) ) {
	/**
	 * The type badge (`storie-badge` / `gedig-badge`). Gedig carries the feather; the badge
	 * tone per type (gedig sage, storie terracotta, artikel grey) is a product-owner call,
	 * disclosed with the home pills (FeaturedStream::metaTopHtml(), home.css). Pure.
	 *
	 * @param string $type The post type: `gedig`, `storie` or `artikel`.
	 * @return string
	 */
	function ink_foundation_reading_badge( string $type ): string {
		$label  = function_exists( 'ink_foundation_term' ) ? ink_foundation_term( $type, ucfirst( $type ) ) : ucfirst( $type );
		$tone   = array(
			'gedig'   => 'ink-tone-accent',
			'storie'  => 'ink-tone-primary',
			'artikel' => 'ink-tone-muted',
		)[ $type ] ?? 'ink-tone-primary';
		$prefix = 'gedig' === $type ? 'gedig' : 'storie';
		$icon   = 'gedig' === $type
			? '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z"/><line x1="16" x2="2" y1="8" y2="22"/><line x1="17.5" x2="9" y1="15" y2="15"/></svg>'
			: '';

		return '<span data-audit-id="' . esc_attr( $prefix ) . '-badge" class="ink-lees-tipe ink-pill ink-pill--tint ' . ( 'gedig' === $type ? 'ink-pill--tint-15 ' : '' ) . esc_attr( $tone ) . '">' . $icon . esc_html( $label ) . '</span>';
	}
}

if ( ! function_exists( 'ink_foundation_reading_hint' ) ) {
	/**
	 * The interaction hint pill. Lovable shows it to everyone; INK to signed-in readers only,
	 * because the highlighter and line reactions load only for them (functions.php).
	 * Owner ruling (2026-11-02): the hint stays members-only. Recorded as `divergence` in
	 * maps/lees-{storie,gedig,artikel}.csv (`storie-hint`, `gedig-hint`, `reading-hint-icon`);
	 * a logged-out MISSING on those ids is this ruling, not a defect. Pure.
	 *
	 * @param string $type The post type: `gedig`, `storie` or `artikel`.
	 * @return string
	 */
	function ink_foundation_reading_hint( string $type ): string {
		if ( 'gedig' === $type ) {
			return '<div data-audit-id="gedig-hint" class="ink-gedig-hint ink-pill ink-pill--hint ink-tone-accent ink-pill--tint-15">'
				. '<svg data-audit-id="reading-hint-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.29 1.51 4.04 3 5.5l7 7Z"/></svg>'
				. '<span>' . esc_html__( 'Merk hierdie reël', 'ink-foundation' ) . '</span></div>';
		}

		return '<div data-audit-id="storie-hint" class="ink-storie-hint ink-pill ink-pill--hint">'
			. '<svg data-audit-id="reading-hint-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="m9 11-6 6v3h9l3-3"/><path d="m22 12-4.6 4.6a2 2 0 0 1-2.8 0l-5.2-5.2a2 2 0 0 1 0-2.8L14 4"/></svg>'
			. '<span>' . esc_html__( 'Kies enige teks om jou gunsteling passasies uit te lig', 'ink-foundation' ) . '</span></div>';
	}
}
