<?php
/**
 * Short-term email send log.
 *
 * @package Ink\Core
 */

declare(strict_types=1);

namespace Ink\Notifications;

defined( 'ABSPATH' ) || exit;

/**
 * A capped ring buffer (last {@see MAX} sends) kept in one option, shown on the
 * admin screen for debugging. Not an audit trail.
 *
 * @package Ink\Core
 */
final class SendLog {

	public const OPTION = 'ink_notifications_log';
	public const MAX    = 100;

	/**
	 * Record one send attempt.
	 *
	 * @param string $key Event key.
	 * @param string $to  Recipient address.
	 * @param bool   $ok  Whether `wp_mail` accepted it.
	 */
	public function record( string $key, string $to, bool $ok ): void {
		$rows = $this->recent();

		array_unshift(
			$rows,
			array(
				'time' => gmdate( 'Y-m-d H:i:s' ),
				'key'  => $key,
				'to'   => $to,
				'ok'   => $ok,
			)
		);

		update_option( self::OPTION, array_slice( $rows, 0, self::MAX ), false );
	}

	/**
	 * Newest-first log rows.
	 *
	 * @return list<array{time:string,key:string,to:string,ok:bool}>
	 */
	public function recent(): array {
		$rows = get_option( self::OPTION, array() );

		return is_array( $rows ) ? array_values( $rows ) : array();
	}
}
