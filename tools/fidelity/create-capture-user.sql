-- Creates the dedicated fidelity-capture user on the LOCAL nuwe-ink site.
--
-- ⚠️ TABLE PREFIX IS `wpjj_`, NOT `wp_`.
--    A stale, unused `wp_users` table also exists in this database. Seeding it
--    looks like it worked — the INSERT succeeds and the row selects back — but
--    WordPress never sees the user. Confirm before editing:
--        grep '^\$table_prefix' "<site>/app/public/wp-config.php"
--        SHOW TABLES LIKE '%users';
--
-- Why a dedicated user rather than reusing `admin`:
--   * the admin bar renders on the frontend for privileged users and shifts the
--     whole page down ~32px, which would corrupt every bbox in a capture. This
--     user has show_admin_bar_front = 'false'.
--   * a subscriber is what a normal logged-in member looks like, which is the
--     state we want to compare against the reference.
--   * `admin`'s password is not touched.
--
-- This seeds the row only. The PASSWORD must then be set through WordPress's own
-- hasher — this site is WP 6.8+ (db_version 61833), which no longer accepts the
-- legacy MD5 fallback:
--     php -d mysqli.default_socket="$SOCK" tools/fidelity/set-capture-password.php
--
-- Idempotent: safe to re-run.

-- Remove the row previously seeded into the stale table, if present.
DELETE FROM wp_users WHERE user_login = 'fidelity-capture';

SET @login := 'fidelity-capture';
SET @email := 'fidelity-capture@nuwe-ink.local';

INSERT INTO wpjj_users (user_login, user_pass, user_nicename, user_email, user_registered, user_status, display_name)
SELECT @login, '', @login, @email, NOW(), 0, 'Fidelity Capture'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM wpjj_users) u WHERE u.user_login = @login);

SET @uid := (SELECT ID FROM wpjj_users WHERE user_login = @login);

INSERT INTO wpjj_usermeta (user_id, meta_key, meta_value)
SELECT @uid, 'wpjj_capabilities', 'a:1:{s:10:"subscriber";b:1;}'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM wpjj_usermeta) m WHERE m.user_id = @uid AND m.meta_key = 'wpjj_capabilities');

INSERT INTO wpjj_usermeta (user_id, meta_key, meta_value)
SELECT @uid, 'wpjj_user_level', '0'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM wpjj_usermeta) m WHERE m.user_id = @uid AND m.meta_key = 'wpjj_user_level');

INSERT INTO wpjj_usermeta (user_id, meta_key, meta_value)
SELECT @uid, 'nickname', 'Fidelity Capture'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM wpjj_usermeta) m WHERE m.user_id = @uid AND m.meta_key = 'nickname');

INSERT INTO wpjj_usermeta (user_id, meta_key, meta_value)
SELECT @uid, 'show_admin_bar_front', 'false'
WHERE NOT EXISTS (SELECT 1 FROM (SELECT * FROM wpjj_usermeta) m WHERE m.user_id = @uid AND m.meta_key = 'show_admin_bar_front');

UPDATE wpjj_usermeta SET meta_value = 'false' WHERE user_id = @uid AND meta_key = 'show_admin_bar_front';

SELECT ID, user_login, user_email, display_name FROM wpjj_users WHERE user_login = @login;
