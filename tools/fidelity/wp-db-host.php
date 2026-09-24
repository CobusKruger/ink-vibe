<?php
/**
 * wp-cli --require shim for the Local by Flywheel site.
 *
 * `wp-config.php` hardcodes DB_HOST as 'localhost', which makes PHP's mysqli look
 * for the default socket (/tmp/mysql.sock) rather than Local's own. wp-cli loads
 * --require files before WordPress bootstraps, so defining the constant here wins:
 * wp-config's later define() becomes a no-op.
 *
 * Local exposes MySQL on 127.0.0.1:10013 for this site. Verify with:
 *   lsof -nP -iTCP -sTCP:LISTEN | grep mysqld
 */

if ( ! defined( 'DB_HOST' ) ) {
	define( 'DB_HOST', '127.0.0.1:10013' );
}
