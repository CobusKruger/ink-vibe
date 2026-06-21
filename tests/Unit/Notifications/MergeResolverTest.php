<?php
/**
 * Unit tests for the name-merge resolver (AD-9).
 *
 * Target: {@see \Ink\Notifications\MergeResolver} (Story 1.12).
 *
 * Runs against the Story 1.11 harness (Pest + tests/bootstrap.php). Pure logic —
 * no WordPress functions are touched, so no Brain Monkey lifecycle is needed.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\MergeResolver;

test( 'resolves the {skrywer} greeting token', function (): void {
	$resolver = new MergeResolver();

	expect( $resolver->resolve( 'Beste {skrywer}, welkom by INK.', array( 'skrywer' => 'Jan' ) ) )
		->toBe( 'Beste Jan, welkom by INK.' );
} );

test( 'leaves unknown or unprovided tokens literal (signals a misconfigured caller)', function (): void {
	$resolver = new MergeResolver();

	// {skrywer} not supplied, {onbekend} is not a whitelisted token — both stay literal.
	expect( $resolver->resolve( 'Beste {skrywer}, {onbekend}.', array() ) )
		->toBe( 'Beste {skrywer}, {onbekend}.' );
} );

test( 'is not a template engine — non-token braces are untouched', function (): void {
	$resolver = new MergeResolver();

	expect( $resolver->resolve( 'Geen {{if}} of {lus} hier nie.', array( 'skrywer' => 'Jan' ) ) )
		->toBe( 'Geen {{if}} of {lus} hier nie.' );
} );
