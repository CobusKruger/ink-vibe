<?php
/**
 * Sets the fidelity-capture user's password using WordPress's own hasher.
 *
 * The SQL seed writes an MD5 hash, which older WordPress accepted as a legacy
 * fallback. This site is on db_version 61833 (WP 6.8+), where that fallback is
 * gone — so the password has to be written through wp_set_password().
 *
 * Bootstrapping WP needs a working DB connection, and wp-config hardcodes
 * DB_HOST as 'localhost'. PHP's mysqli then looks for its default socket, so run
 * this with that ini set to Local's socket:
 *
 *   php -d mysqli.default_socket="$SOCK" tools/fidelity/set-capture-password.php
 *
 * Local dev site only.
 */

$wp_root = '/Users/cobus/Local Sites/nuwe-ink/app/public';

define( 'WP_USE_THEMES', false );
require_once $wp_root . '/wp-load.php';

$login = 'fidelity-capture';
$pass  = 'fidelity-capture-2026';

$user = get_user_by( 'login', $login );

if ( ! $user ) {
	fwrite( STDERR, "user '$login' not found — run create-capture-user.sql first\n" );
	exit( 1 );
}

wp_set_password( $pass, $user->ID );

// The admin bar shifts the whole page down and would corrupt every bbox.
update_user_meta( $user->ID, 'show_admin_bar_front', 'false' );

$check = wp_check_password( $pass, get_userdata( $user->ID )->user_pass, $user->ID );

printf(
	"id=%d login=%s roles=%s password_verifies=%s\n",
	$user->ID,
	$user->user_login,
	implode( ',', get_userdata( $user->ID )->roles ),
	$check ? 'yes' : 'NO'
);

exit( $check ? 0 : 1 );
