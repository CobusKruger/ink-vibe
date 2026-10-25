#!/usr/bin/env bash
#
# rehearsal-prep.sh — put a fresh copy of the prod site onto the Local site
# nuwe-ink.local, ready for `wp ink migrate-all`. Everything up to the prod
# export itself is automated; the export (a database dump and the uploads
# folder) is the only thing you have to bring; leave --uploads out to keep the
# uploads already on the Local site (media added to prod since then will be
# missing: `wp ink verify-media` shows it).
#
# Usage:
#   tools/rehearsal-prep.sh --sql <prod.sql|prod.sql.gz> [--uploads <dir|.zip|.tar.gz>]
#                           [--mail-sink sink@nuwe-ink.local] [--old-url https://ink.org.za]
#                           [--apply]
#
# Without --apply it is a DRY RUN: it checks the inputs and the Local site and
# prints what it would do. With --apply it DESTROYS the current nuwe-ink
# database (an old test copy, replaced on purpose; nothing is kept).
#
# What it does, in order:
#   1. safety checks (the target really is the Local nuwe-ink site)
#   2. drops and recreates the database, then imports the prod dump
#   3. replaces the prod URL with https://nuwe-ink.local using `wp search-replace`
#      (serialization-safe; editing the SQL text would corrupt serialized data).
#      Only URLs are replaced, never the bare domain, so addresses such as
#      moderator@ink.org.za in the data are left alone.
#   4. copies the uploads in, deploys ink-core + ink-foundation (deploy-to-local.sh)
#   5. activates them, sets INK_MAIL_SINK, flushes rewrites and caches
#
# Mail: Local runs Mailpit for this site. With INK_MAIL_SINK set, ink-core
# rewrites every outgoing message to that address instead of the real one, and
# Mailpit catches it (open it from the Local app). With no sink set, ink-core
# sends nothing at all.
#
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEST_ROOT="/Users/cobus/Local Sites/nuwe-ink/app/public"
LOCAL_HOME="$HOME/Library/Application Support/Local"
TARGET_URL="https://nuwe-ink.local"
TMP_DIR="${REPO_ROOT}/tmp"

SQL=""
UPLOADS=""
OLD_URL=""
MAIL_SINK="sink@nuwe-ink.local"
APPLY=false

while [[ $# -gt 0 ]]; do
	case "$1" in
		--sql) SQL="$2"; shift 2 ;;
		--uploads) UPLOADS="$2"; shift 2 ;;
		--old-url) OLD_URL="$2"; shift 2 ;;
		--mail-sink) MAIL_SINK="$2"; shift 2 ;;
		--apply) APPLY=true; shift ;;
		*) echo "Unknown argument: $1" >&2; exit 2 ;;
	esac
done

die() { echo "ERROR: $*" >&2; exit 1; }
say() { echo "== $* =="; }

[[ -n "$SQL" && -f "$SQL" ]] || die "--sql <file> is required and must exist."
[[ -z "$UPLOADS" || -e "$UPLOADS" ]] || die "--uploads path does not exist: $UPLOADS"
[[ -d "$DEST_ROOT" ]] || die "Local site not found at: $DEST_ROOT"

export PATH="/usr/local/bin:$PATH"
command -v php >/dev/null || die "php not found on PATH."
command -v wp >/dev/null || die "wp (WP-CLI) not found on PATH."

# --- Locate the Local site's MySQL socket and client from Local's own registry.
SITE_INFO="$(python3 - "$LOCAL_HOME/sites.json" "$DEST_ROOT" <<'PY'
import json, os, sys
sites = json.load(open(sys.argv[1]))
want = os.path.realpath(sys.argv[2]).rsplit('/app/public', 1)[0]
for sid, s in sites.items():
    if os.path.realpath(os.path.expanduser(s.get('path', ''))) == want:
        db = s.get('mysql', {})
        ver = s.get('services', {}).get('mysql', {}).get('version', '')
        print(sid, db.get('database', 'local'), db.get('user', 'root'), db.get('password', 'root'), ver)
        break
PY
)"
[[ -n "$SITE_INFO" ]] || die "Could not find this site in Local's sites.json."
read -r SITE_ID DB_NAME DB_USER DB_PASS MYSQL_VER <<<"$SITE_INFO"

SOCKET="${LOCAL_HOME}/run/${SITE_ID}/mysql/mysqld.sock"
[[ -S "$SOCKET" ]] || die "MySQL socket not found ($SOCKET). Start the site in the Local app first."

MYSQL_BIN=""
for candidate in "$LOCAL_HOME"/lightning-services/mysql-"${MYSQL_VER}"*/bin/darwin/bin/mysql; do
	[[ -x "$candidate" ]] && { MYSQL_BIN="$candidate"; break; }
done
[[ -n "$MYSQL_BIN" ]] || die "Local's mysql client for version ${MYSQL_VER} not found."

export MYSQL_PWD="$DB_PASS"
mysql_cmd() { "$MYSQL_BIN" --socket="$SOCKET" -u"$DB_USER" "$@"; }
WP() { php -d error_reporting=0 -d "mysqli.default_socket=$SOCKET" "$(command -v wp)" --path="$DEST_ROOT" --skip-plugins --skip-themes "$@"; }

# --- Safety: this must be the Local copy, never anything pointing at prod.
CURRENT_HOME="$(WP option get home 2>/dev/null || true)"
case "$CURRENT_HOME" in
	*nuwe-ink.local*|"") ;;  # fine (or empty database: nothing to protect)
	*) die "The current site URL is '$CURRENT_HOME', not nuwe-ink.local. Refusing to continue." ;;
esac
[[ "$(WP config get WP_ENVIRONMENT_TYPE 2>/dev/null || true)" != "production" ]] || die "WP_ENVIRONMENT_TYPE is production."

say "Plan"
echo "  site:        $DEST_ROOT"
echo "  database:    $DB_NAME via $SOCKET"
echo "  prod dump:   $SQL"
echo "  uploads:     ${UPLOADS:-(none given: keeping the uploads already on the site)}"
echo "  old URL:     ${OLD_URL:-(read from the imported database)}"
echo "  new URL:     $TARGET_URL"
echo "  mail sink:   $MAIL_SINK"

if [[ "$APPLY" != true ]]; then
	say "DRY RUN: nothing was changed. Re-run with --apply to do it."
	exit 0
fi

mkdir -p "$TMP_DIR"
say "1/5 Resetting the database and importing the prod dump"
mysql_cmd -e "DROP DATABASE IF EXISTS \`${DB_NAME}\`; CREATE DATABASE \`${DB_NAME}\` CHARACTER SET utf8mb4;"
if [[ "$SQL" == *.gz ]]; then
	gunzip -c "$SQL" | mysql_cmd "$DB_NAME"
else
	mysql_cmd "$DB_NAME" < "$SQL"
fi

TABLE_PREFIX="$(WP config get table_prefix)"
mysql_cmd -N -e "SELECT COUNT(*) FROM \`${DB_NAME}\`.\`${TABLE_PREFIX}options\`" >/dev/null \
	|| die "Imported database has no '${TABLE_PREFIX}options' table. Does prod use another table prefix? Change \$table_prefix in wp-config.php."

say "2/5 Pointing the site at $TARGET_URL"
if [[ -z "$OLD_URL" ]]; then
	OLD_URL="$(mysql_cmd -N -e "SELECT option_value FROM \`${DB_NAME}\`.\`${TABLE_PREFIX}options\` WHERE option_name='home'" | tr -d '\r')"
fi
OLD_HOST="${OLD_URL#*://}"; OLD_HOST="${OLD_HOST%%/*}"
echo "  replacing URLs on host: $OLD_HOST"
for from in "https://${OLD_HOST}" "http://${OLD_HOST}" "https://www.${OLD_HOST#www.}" "http://www.${OLD_HOST#www.}"; do
	WP search-replace "$from" "$TARGET_URL" --all-tables --skip-columns=guid --quiet || true
done

say "3/5 Copying the uploads"
mkdir -p "$DEST_ROOT/wp-content/uploads"
if [[ -z "$UPLOADS" ]]; then
	echo "  skipped: keeping the existing uploads"
elif [[ -d "$UPLOADS" ]]; then
	rsync -a --exclude=.DS_Store "${UPLOADS%/}/" "$DEST_ROOT/wp-content/uploads/"
else
	EXTRACT="$(mktemp -d "$TMP_DIR/uploads-XXXXXX")"
	case "$UPLOADS" in
		*.zip) unzip -q "$UPLOADS" -d "$EXTRACT" ;;
		*.tar.gz|*.tgz) tar -xzf "$UPLOADS" -C "$EXTRACT" ;;
		*) die "Unsupported uploads archive: $UPLOADS" ;;
	esac
	# An archive may contain the folder itself ("uploads/") or its content.
	[[ -d "$EXTRACT/uploads" ]] && EXTRACT="$EXTRACT/uploads"
	[[ -d "$EXTRACT/wp-content/uploads" ]] && EXTRACT="$EXTRACT/wp-content/uploads"
	rsync -a --exclude=.DS_Store "${EXTRACT%/}/" "$DEST_ROOT/wp-content/uploads/"
fi

say "4/5 Deploying this repo's code to the site"
"$REPO_ROOT/tools/deploy-to-local.sh" --apply

say "5/5 Activating, mail sink, rewrite rules"
WP plugin activate ink-core
WP theme activate ink-foundation
WP config set WP_ENVIRONMENT_TYPE local --type=constant --quiet
WP config set INK_MAIL_SINK "$MAIL_SINK" --type=constant --quiet
WP rewrite flush --quiet || true
WP cache flush --quiet || true

say "Done. Checks"
echo "  home:        $(WP option get home)"
echo "  environment: $(WP eval 'echo wp_get_environment_type();')"
echo "  ink-core:    $(WP plugin get ink-core --field=status)"
echo "  theme:       $(WP theme get ink-foundation --field=status)"
echo "  posts:       $(WP eval 'global $wpdb; echo $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status=\"publish\"");')"
echo
echo "Next: log in at $TARGET_URL/wp-admin/, then hand over to the implementing agent."
