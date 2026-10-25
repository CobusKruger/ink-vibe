<?php
/**
 * Unit tests for the non-production mail sink.
 *
 * Target: {@see \Ink\Security\MailSink}. Pure decisions + seams; no WordPress.
 *
 * @package Ink\Tests
 */

declare(strict_types=1);

namespace Ink\Tests\Unit\Security;

use Ink\Security\MailSink;

function sinkDouble( string $env, string $sink ): MailSink {
	return new class( $env, $sink ) extends MailSink {
		public function __construct( private string $env, private string $sink ) {}

		protected function sinkAddress(): string {
			return $this->sink;
		}
		protected function environment(): string {
			return $this->env;
		}
	};
}

test( 'only production is left alone', function (): void {
	expect( MailSink::isActive( 'production' ) )->toBeFalse();
	foreach ( array( 'local', 'staging', 'development' ) as $env ) {
		expect( MailSink::isActive( $env ) )->toBeTrue();
	}
} );

test( 'redirect sends only to the sink and records the original recipients', function (): void {
	$out = MailSink::redirect(
		array(
			'to'      => array( 'judge@example.com', 'b@example.com' ),
			'subject' => 'Inskrywings',
			'message' => 'Hallo',
			'headers' => array( 'Cc: other@example.com' ),
		),
		'sink@local.test'
	);

	expect( $out['to'] )->toBe( 'sink@local.test' );
	expect( $out['headers'] )->toBe( array() ); // no Cc/Bcc leak
	expect( $out['subject'] )->toContain( 'judge@example.com' );
	expect( $out['message'] )->toContain( 'Originally to: judge@example.com, b@example.com' );
} );

test( 'filterMail diverts on staging but never on production', function (): void {
	$args = array( 'to' => 'x@example.com', 'subject' => 's', 'message' => 'm' );

	expect( sinkDouble( 'staging', 'sink@local.test' )->filterMail( $args )['to'] )->toBe( 'sink@local.test' );
	expect( sinkDouble( 'production', 'sink@local.test' )->filterMail( $args )['to'] )->toBe( 'x@example.com' );
} );

test( 'with no sink configured, non-production mail is short-circuited and production is not', function (): void {
	expect( sinkDouble( 'staging', '' )->blockUnsunk( null, array() ) )->toBeTrue();
	expect( sinkDouble( 'production', '' )->blockUnsunk( null, array() ) )->toBeNull();
	expect( sinkDouble( 'staging', 'sink@local.test' )->blockUnsunk( null, array() ) )->toBeNull();
} );
