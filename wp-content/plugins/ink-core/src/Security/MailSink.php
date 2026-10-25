<?php
/**
 * Non-production mail sink — migration rework.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Stops a copy of the live site emailing real people.
 *
 * A migrated clone holds real member and judge addresses, and the challenge flow
 * emails judges. On every environment except `production`, outgoing mail is
 * rewritten to the address in the `INK_MAIL_SINK` constant (original recipients
 * are listed in the subject and body so the flow can still be checked), or, when no
 * sink is configured, not sent at all. Production is never touched. Opt out with the
 * `ink_security_mail_sink` filter.
 *
 * Decisions are pure; only the environment lookup touches WordPress.
 *
 * @package Ink\Core
 */
class MailSink {

	/**
	 * Wire the sink.
	 */
	public function register(): void {
		if ( ! (bool) apply_filters( 'ink_security_mail_sink', true ) ) {
			return;
		}

		add_filter( 'wp_mail', array( $this, 'filterMail' ), 1 );
		add_filter( 'pre_wp_mail', array( $this, 'blockUnsunk' ), 1, 2 );
	}

	/**
	 * Whether mail is diverted on this environment. Pure.
	 *
	 * @param string $environment The `wp_get_environment_type()` value.
	 */
	public static function isActive( string $environment ): bool {
		return 'production' !== $environment;
	}

	/**
	 * Rewrite mail arguments so they go only to the sink. Pure.
	 *
	 * @param array<string, mixed> $args The `wp_mail` arguments.
	 * @param string               $sink The sink address.
	 * @return array<string, mixed>
	 */
	public static function redirect( array $args, string $sink ): array {
		$to       = $args['to'] ?? '';
		$original = is_array( $to ) ? implode( ', ', array_map( 'strval', $to ) ) : (string) $to;

		$args['to']      = $sink;
		$args['headers'] = array();
		$args['subject'] = '[sink: ' . $original . '] ' . (string) ( $args['subject'] ?? '' );
		$args['message'] = 'Originally to: ' . $original . "\n\n" . (string) ( $args['message'] ?? '' );

		return $args;
	}

	/**
	 * `wp_mail` filter: divert to the sink on non-production.
	 *
	 * @param array<string, mixed> $args The mail arguments.
	 * @return array<string, mixed>
	 */
	public function filterMail( array $args ): array {
		$sink = $this->sinkAddress();

		if ( '' === $sink || ! self::isActive( $this->environment() ) ) {
			return $args;
		}

		return self::redirect( $args, $sink );
	}

	/**
	 * `pre_wp_mail` filter: with no sink configured, short-circuit (nothing is sent).
	 *
	 * @param null|bool            $short_circuit Existing short-circuit value.
	 * @param array<string, mixed> $atts          The mail arguments.
	 * @return null|bool
	 */
	public function blockUnsunk( $short_circuit, array $atts ) {
		unset( $atts );

		if ( self::isActive( $this->environment() ) && '' === $this->sinkAddress() ) {
			return true;
		}

		return $short_circuit;
	}

	/**
	 * The configured sink address, or ''. Overridable seam.
	 */
	protected function sinkAddress(): string {
		return defined( 'INK_MAIL_SINK' ) ? (string) constant( 'INK_MAIL_SINK' ) : '';
	}

	/**
	 * The environment type. Overridable seam.
	 */
	protected function environment(): string {
		return wp_get_environment_type();
	}
}
