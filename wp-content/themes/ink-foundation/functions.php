<?php
/**
 * INK Foundation — theme bootstrap (presentation only).
 *
 * Per the architecture (FSE theme tree): this file registers PATTERNS / BLOCK
 * STYLES ONLY — no business logic. All INK business rules, content models,
 * tier/submission/follow logic, and data access live in the `ink-core` plugin
 * (Story 1.7), never in the theme.
 *
 * @package ink-foundation
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

/**
 * Load the `ink-foundation` text domain so the theme's own presentation strings
 * resolve (Story 1.10 — theme half of the i18n scaffolding).
 *
 * The theme's user-facing labels (pattern-category + block-style names below) are
 * authored in Afrikaans as the gettext SOURCE language, sentence case (Gate D),
 * in the `ink-foundation` domain. Like `ink-core`, the theme ships NO English
 * `.mo` — Afrikaans is the source, so gettext returns it directly. This call is
 * the documented theme i18n entry point; it makes the domain loadable for any
 * future Afrikaans/community artifact under `/languages`. Loading the
 * presentation layer's OWN text domain is presentation infrastructure — it adds
 * no business logic to the theme (three-layer separation holds).
 */
function ink_foundation_load_textdomain(): void {
	load_theme_textdomain( 'ink-foundation', get_template_directory() . '/languages' );
}
add_action( 'init', 'ink_foundation_load_textdomain' );

/**
 * Register the INK building-block inserter category so the theme patterns group
 * together in the Site Editor inserter. Label is Afrikaans, sentence case (Gate D).
 */
function ink_foundation_register_pattern_categories(): void {
	register_block_pattern_category(
		'ink-foundation',
		array(
			'label'       => __( 'INK-boublokke', 'ink-foundation' ),
			'description' => __( 'Kern-boublokke vir die samestelling van bladsye.', 'ink-foundation' ),
		)
	);
}
add_action( 'init', 'ink_foundation_register_pattern_categories' );

/**
 * Register the core block style variations (card / button / emphasis).
 *
 * These are token-driven presentation treatments applied to any block instance
 * (the composition patterns consume them). All CSS values resolve to theme.json
 * tokens via the generated `--wp--preset--*` / `--wp--custom--*` custom properties
 * — zero hardcoded colours, spacing, radius, or type sizes (Gate A). Labels are
 * Afrikaans, sentence case (Gate D).
 */
function ink_foundation_register_block_styles(): void {
	// Card: a bordered, soft-shadowed, rounded surface container.
	register_block_style(
		'core/group',
		array(
			'name'         => 'card',
			'label'        => __( 'Kaart', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-card{'
				. 'background-color:var(--wp--preset--color--surface-alt);'
				. 'border:1px solid var(--wp--preset--color--border);'
				. 'border-radius:var(--wp--custom--radius--lg);'
				. 'box-shadow:var(--wp--preset--shadow--sm);'
				. 'padding:var(--wp--preset--spacing--s-24);'
				. '}',
		)
	);

	// Pill: a fully-rounded button variant (distinct from core fill + is-style-outline).
	register_block_style(
		'core/button',
		array(
			'name'         => 'pill',
			'label'        => __( 'Pil', 'ink-foundation' ),
			'inline_style' => '.wp-block-button.is-style-pill .wp-block-button__link{'
				. 'border-radius:var(--wp--custom--radius--full);'
				. '}',
		)
	);

	// Emphasis: an accented call-out treatment (left rule + tinted background).
	register_block_style(
		'core/group',
		array(
			'name'         => 'emphasis',
			'label'        => __( 'Klem', 'ink-foundation' ),
			'inline_style' => '.wp-block-group.is-style-emphasis{'
				. 'background-color:var(--wp--preset--color--secondary);'
				. 'border-left:var(--wp--preset--spacing--s-4) solid var(--wp--preset--color--primary);'
				. 'border-radius:var(--wp--custom--radius--md);'
				. 'padding:var(--wp--preset--spacing--s-24);'
				. '}'
				. '.wp-block-group.is-style-emphasis :where(p){'
				. 'font-family:var(--wp--preset--font-family--body);'
				. '}',
		)
	);
}
add_action( 'init', 'ink_foundation_register_block_styles' );

if ( ! function_exists( 'ink_foundation_term' ) ) {
	/**
	 * Theme-side bridge to the `ink-core` terminology registry (AD-10, Story 2.0).
	 *
	 * PHP patterns (`patterns/*.php`) render glossary labels through this bridge so
	 * they read from the same single source as `ink-core` — without hardcoding the
	 * Afrikaans literal in the theme. It is `function_exists`-guarded so the theme
	 * never fatals when `ink-core` is inactive (it returns the provided fallback).
	 * This is presentation infrastructure — a label lookup with a graceful degrade,
	 * not business logic (three-layer separation holds).
	 *
	 * Static block-template HTML (`templates/*.html`) cannot call PHP — it binds to
	 * the `ink/term` Block Bindings source registered by `ink-core` instead.
	 *
	 * @param string $key      Glossary concept key (e.g. 'gradering').
	 * @param string $fallback Returned when `ink-core` is not active.
	 * @return string The Afrikaans label, or the fallback.
	 */
	function ink_foundation_term( string $key, string $fallback = '' ): string {
		if ( function_exists( 'Ink\\ink_term' ) ) {
			return \Ink\ink_term( $key );
		}

		return $fallback;
	}
}
