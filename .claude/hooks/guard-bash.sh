#!/usr/bin/env bash
# PreToolUse hook for the Bash tool.
#
# Denies two recurring anti-patterns that instructions/memory fail to stop:
#   1. Redundant `cd <dir> && ...` prefixes (the working dir already persists
#      between Bash calls; use absolute paths or a one-time standalone `cd`).
#   2. Reading/writing file CONTENT via the shell (cat/head/tail/less a file,
#      or echo/printf/cat/tee/heredoc INTO a file) instead of the Read / Edit /
#      Write tools.
#
# On a violation it emits a PreToolUse "deny" decision whose reason is fed back
# to the model so it rewrites the call. Otherwise it exits 0 (allow).
#
# Escape hatch: include the marker  #bash-ok  anywhere in the command to bypass
# (for the genuinely-rare case where the shell really is the right tool).

set -euo pipefail

input="$(cat)"
cmd="$(printf '%s' "$input" | jq -r '.tool_input.command // ""')"

[ -z "$cmd" ] && exit 0

# Escape hatch
case "$cmd" in
  *'#bash-ok'*) exit 0 ;;
esac

deny() {
  jq -n --arg reason "$1" '{
    hookSpecificOutput: {
      hookEventName: "PreToolUse",
      permissionDecision: "deny",
      permissionDecisionReason: $reason
    }
  }'
  exit 0
}

# Trim leading whitespace.
trimmed="${cmd#"${cmd%%[![:space:]]*}"}"
# First whitespace-delimited token.
first="${trimmed%%[[:space:]]*}"
# Everything after the first token, left-trimmed.
rest="${trimmed#"$first"}"
rest="${rest#"${rest%%[![:space:]]*}"}"

has_pipe=0
case "$cmd" in *'|'*) has_pipe=1 ;; esac

# ---------------------------------------------------------------------------
# Rule 1: redundant `cd` prefix  ( cd <dir> && cmd   /   cd <dir>; cmd )
# Allows a standalone `cd` (one-time dir change) and subshell `(cd ... && ...)`.
# ---------------------------------------------------------------------------
if [[ "$trimmed" =~ ^cd[[:space:]]+[^\&\;]*(\&\&|\;) ]]; then
  deny "Don't prefix commands with \`cd\`. The Bash tool keeps its working directory between calls, so use an absolute path (or \`<tool> -C <dir>\`, e.g. \`git -C <dir>\`), or run a one-time standalone \`cd\` first. Re-issue without the \`cd ... &&\` prefix."
fi

# ---------------------------------------------------------------------------
# Rule 3 (checked before read so `cat > file` is classed as a write):
# writing file CONTENT via the shell.
# Triggers only when a content producer (echo/printf/cat/tee) redirects into a
# real file. Redirects to /dev/null|/dev/stdout|/dev/stderr and fd dups (2>&1)
# are allowed, as is capturing other commands' output (e.g. `make > build.log`).
# ---------------------------------------------------------------------------
producer=0
case "$first" in echo|printf|cat) producer=1 ;; esac
case " $cmd " in *' tee '*) producer=1 ;; esac

if [ "$producer" -eq 1 ]; then
  wrote_file=0
  # Redirect targets: capture the token after each > or >>.
  while IFS= read -r tgt; do
    [ -z "$tgt" ] && continue
    case "$tgt" in
      /dev/null|/dev/stdout|/dev/stderr|/dev/fd/*) ;;  # safe
      *) wrote_file=1 ;;
    esac
  done < <(printf '%s' "$cmd" | grep -oE '>>?[[:space:]]*[^[:space:];|&)<>]+' | sed -E 's/^>>?[[:space:]]*//')

  # `tee <file>` / `tee -a <file>` (not /dev/null).
  tee_tgt="$(printf '%s' "$cmd" | grep -oE '(^|[[:space:]])tee([[:space:]]+-[aA])?[[:space:]]+[^[:space:];|&)<>]+' | sed -E 's/.*[[:space:]]//' || true)"
  case "$tee_tgt" in
    ''|/dev/null|/dev/stdout|/dev/stderr) ;;
    *) wrote_file=1 ;;
  esac

  # Heredoc into a file: `cat > file <<EOF` (already caught above), or any `<<`
  # combined with a redirect to a file (caught above). Bare heredoc to stdout
  # is fine.

  if [ "$wrote_file" -eq 1 ]; then
    deny "Don't author file content through the shell (\`$first ... >\`/\`tee\`). Use the Write tool to create/overwrite a file or the Edit tool to change part of one. Re-issue with Write/Edit."
  fi
fi

# ---------------------------------------------------------------------------
# Rule 2: reading a file via the shell (no pipe).
# `cat/head/tail/less/more/bat <file>` with no pipe should be the Read tool.
# Allowed: any pipeline (`| ...`), `tail -f/-F/--follow`, bare `cat` (stdin).
# ---------------------------------------------------------------------------
if [ "$has_pipe" -eq 0 ] && [ -n "$rest" ]; then
  case "$first" in
    cat|head|tail|less|more|bat)
      case " $cmd " in
        *' -f '*|*' -F '*|*'--follow'*) : ;;  # tail follow — keep
        *)
          deny "Use the Read tool to view files, not \`$first\`. Read returns line numbers, paginates large files, and renders images/PDFs/notebooks. It supports offset/limit if you only need part of a file. Re-issue with Read."
          ;;
      esac
      ;;
  esac
fi

exit 0
