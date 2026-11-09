<?php
/**
 * Unit tests for email body rendering.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Notifications;

use Ink\Notifications\Mailer;
use Ink\Notifications\MergeResolver;

test( 'plain-text bodies become escaped paragraphs with line breaks', function (): void {
	$html = Mailer::renderBody( "Hallo {skrywer}\nTweede reël\n\nNuwe paragraaf 1 < 2", array( 'skrywer' => 'Jan' ), new MergeResolver() );

	expect( $html )->toBe( '<p>Hallo Jan<br>' . "\n" . 'Tweede reël</p><p>Nuwe paragraaf 1 &lt; 2</p>' );
} );

test( 'HTML bodies keep their markup but escape merge values', function (): void {
	$html = Mailer::renderBody( '<p>Hallo {skrywer}</p>', array( 'skrywer' => '<script>x</script>' ), new MergeResolver() );

	expect( $html )->toBe( '<p>Hallo &lt;script&gt;x&lt;/script&gt;</p>' );
} );

test( 'wrap adds the frame and the optional footer', function (): void {
	expect( Mailer::wrap( '<p>x</p>' ) )->toContain( '<p>x</p>' )->not->toContain( '<br>' );
	expect( Mailer::wrap( '<p>x</p>', 'uit' ) )->toContain( '<br>uit' );
} );
