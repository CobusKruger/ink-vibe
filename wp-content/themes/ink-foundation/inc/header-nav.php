<?php
/**
 * The header's link list: the single source for the desktop nav, the mobile panel
 * and the audit-id filter (build spec 06-chrome §2, §4), so they cannot drift.
 *
 * Presentation only. Order is the spec's; InkPols stays out (D1). Opleiding has no
 * Lovable counterpart on the desktop row (D1). My profiel is off the desktop row,
 * because the avatar menu has it (owner, 2026-11-02). It stays in the mobile panel:
 * the avatar menu sits in `.ink-header-actions`, hidden with the row below 1024, so
 * the panel is the only way to it at those widths.
 *
 * @package Ink\Foundation
 */

declare(strict_types=1);

/**
 * The header links, in display order.
 *
 * @param bool $logged_in Whether to include the logged-in-only links.
 * @return list<array{id: string, label: string, url: string}>
 */
function ink_foundation_header_links( bool $logged_in ): array {
	$links = array(
		array(
			'id'    => 'home',
			'label' => 'Tuis',
			'url'   => '/',
		),
		array(
			'id'    => 'browse',
			'label' => 'Ontdek',
			'url'   => '/ontdek/',
		),
		array(
			'id'    => 'library',
			'label' => 'Biblioteek',
			'url'   => '/biblioteek/',
		),
		array(
			'id'    => 'training',
			'label' => 'Opleiding',
			'url'   => '/opleiding/',
		),
		array(
			'id'    => 'challenges',
			'label' => 'Uitdagings',
			'url'   => '/uitdaging/',
		),
		array(
			'id'    => 'competitions',
			'label' => 'Kompetisies',
			'url'   => '/kompetisies/',
		),
		array(
			'id'    => 'community',
			'label' => 'Gemeenskap',
			'url'   => '/gemeenskap/',
		),
	);

	if ( $logged_in ) {
		$links[] = array(
			'id'    => 'profile',
			'label' => 'My profiel',
			'url'   => '/my-profiel/',
		);
	}

	return $links;
}

/**
 * The panel's order: the design's own (Header.tsx:129-136), where Biblioteek follows
 * Kompetisies. Opleiding, which the design doesn't have, stays next to Biblioteek. Same links.
 *
 * @param bool $logged_in Whether to include the logged-in-only links.
 * @return list<array{id: string, label: string, url: string}>
 */
function ink_foundation_header_mobile_links( bool $logged_in ): array {
	$order = array_flip( array( 'home', 'browse', 'challenges', 'competitions', 'library', 'training', 'community', 'profile' ) );
	$links = ink_foundation_header_links( $logged_in );

	usort( $links, static fn ( array $a, array $b ): int => $order[ $a['id'] ] <=> $order[ $b['id'] ] );

	return $links;
}

/**
 * The desktop audit id for an authored link URL. Desktop links only.
 *
 * @param string $url The link's authored URL.
 * @return string|null Null when the URL is not a header link.
 */
function ink_foundation_header_nav_audit_id( string $url ): ?string {
	foreach ( ink_foundation_header_links( false ) as $link ) {
		if ( $link['url'] === $url ) {
			return 'header-nav-' . $link['id'];
		}
	}

	return null;
}

/**
 * The inner blocks of the desktop core/navigation block: the same for every visitor.
 */
function ink_foundation_header_nav_blocks(): string {
	$blocks = '';

	foreach ( ink_foundation_header_links( false ) as $link ) {
		$blocks .= sprintf( '<!-- wp:navigation-link {"label":"%s","url":"%s"} /-->', $link['label'], $link['url'] ) . "\n";
	}

	return $blocks;
}

/**
 * The inline mobile panel (RG-CH-01; Header.tsx:127-154). It ships `hidden`;
 * `assets/js/header-menu.js` toggles it. The action row sits inside the nav, as in
 * Lovable. Lovable's logged-in buttons carry no audit id, so neither do these.
 *
 * @param bool   $logged_in     Which action row this is.
 * @param string $ghost_label   Escaped here. Teken in / Teken uit.
 * @param string $ghost_url     Escaped here.
 * @param string $primary_label Escaped here. Sluit aan / Begin skryf.
 * @param string $primary_url   Escaped here.
 */
function ink_foundation_header_mobile_menu( bool $logged_in, string $ghost_label, string $ghost_url, string $primary_label, string $primary_url ): string {
	$links = '';

	foreach ( ink_foundation_header_mobile_links( $logged_in ) as $link ) {
		$links .= '<a data-audit-id="header-mobile-nav-' . esc_attr( $link['id'] ) . '" class="ink-nav-link--mobile" href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a>';
	}

	$ghost_id   = $logged_in ? '' : ' data-audit-id="header-mobile-btn-signin"';
	$primary_id = $logged_in ? '' : ' data-audit-id="header-mobile-btn-join"';

	return '<div id="ink-mobile-menu" data-audit-id="header-mobile-panel" class="ink-mobile-menu alignwide" hidden data-ink-mobile-menu>'
		. '<nav data-audit-id="header-mobile-nav" class="ink-mobile-menu__nav">'
		. $links
		. '<div data-audit-id="header-mobile-actions" class="ink-mobile-menu__actions">'
		. '<a' . $ghost_id . ' class="ink-btn ink-btn--ghost" href="' . esc_url( $ghost_url ) . '">' . esc_html( $ghost_label ) . '</a>'
		. '<a' . $primary_id . ' class="ink-btn ink-btn--primary" href="' . esc_url( $primary_url ) . '">' . esc_html( $primary_label ) . '</a>'
		. '</div></nav></div>';
}
