#!/usr/bin/env python3
"""PreToolUse hook for the Bash tool.

Pushes the model toward the native Read / Edit / Write / Grep / Glob tools
instead of doing the same work through the shell. Instructions and memory alone
don't stop the habit, so this denies the recurring anti-patterns and feeds a
corrective reason back to the model.

Reads the PreToolUse JSON on stdin; on a violation prints a PreToolUse "deny"
decision and exits 0, otherwise prints nothing (allow).

It denies, per individual command segment (split on ; && || | and newlines):
  1. Redundant `cd <dir> && ...` prefixes (the working dir persists between
     Bash calls; use absolute paths or a one-time standalone `cd`).
  2. Reading file CONTENT via the shell: cat/head/tail/less/more/bat a file,
     or `sed -n 'N,Mp' file` line-range extraction  ->  Read tool.
  3. Searching file content via grep/egrep/fgrep/rg over files  ->  Grep tool.
  4. Locating files via `find` (pure search, no -exec/-delete)  ->  Glob tool.
  5. Listing file names via plain `ls`  ->  Glob tool.
  6. Authoring file content via echo/printf/cat/tee redirected into a file
     ->  Write / Edit tools.

Why per-segment: the old version only inspected the FIRST token of the whole
command and disabled its read check whenever any `|` appeared, so
`echo "==="; cat file`, `python3 -c ...; head file`, and `cat f | head` all
slipped through. Every pipeline stage and statement is now examined on its own,
and a grep/cat that READS FROM A PIPE (stage 2+) is correctly allowed (it's
consuming stdin, not a file).

Deliberately NOT denied (legitimate shell, or no clean native equivalent):
  - any tool reading from a pipe        (git log | grep,  ls | wc)
  - `find ... -exec/-delete/-ok`        (actions Glob can't perform)
  - `ls -l/-t/-S/-h/-d/-i`              (metadata Glob doesn't show)
  - `tail -f/-F/--follow`               (live follow)
  - reads/searches inside $(...) / `...` (scripting)
  - capturing other commands' output    (make > build.log)

Escape hatch: include the marker  #bash-ok  anywhere in the command to bypass.

NOTE: no shell-text guard can be bypass-proof (eval, base64, variable
indirection, command substitution all remain). The goal is to break the casual
habit, not to sandbox a determined bypass.
"""

import json
import re
import shlex
import sys


def deny(reason):
    print(json.dumps({
        "hookSpecificOutput": {
            "hookEventName": "PreToolUse",
            "permissionDecision": "deny",
            "permissionDecisionReason": reason,
        }
    }))
    sys.exit(0)


# --- quote/paren-aware splitter ---------------------------------------------
# Returns a list of statements; each statement is a list of pipeline stages.
# Splits on top-level ; && || newline (into statements) and | (into stages).
# Content inside quotes and inside (...) / $(...) / `...` is kept opaque so
# command substitution and subshells are not torn apart or analysed.
def split_command(s):
    statements, stages, buf = [], [], []
    q = None        # active quote char
    depth = 0       # () nesting (covers subshells and $( ))
    btick = False   # inside backticks
    i, n = 0, len(s)

    def flush_stage():
        seg = "".join(buf).strip()
        buf.clear()
        return seg

    while i < n:
        c = s[i]
        nxt = s[i + 1] if i + 1 < n else ""
        if q:
            buf.append(c)
            if c == q:
                q = None
            i += 1
            continue
        if btick:
            buf.append(c)
            if c == "`":
                btick = False
            i += 1
            continue
        if c in ("'", '"'):
            q = c; buf.append(c); i += 1; continue
        if c == "`":
            btick = True; buf.append(c); i += 1; continue
        if c == "\\" and nxt:
            buf.append(c); buf.append(nxt); i += 2; continue
        if c == "(":
            depth += 1; buf.append(c); i += 1; continue
        if c == ")":
            depth = max(0, depth - 1); buf.append(c); i += 1; continue
        if depth == 0:
            if c == "&" and nxt == "&":
                stages.append(flush_stage()); statements.append(stages); stages = []; i += 2; continue
            if c == "|" and nxt == "|":
                stages.append(flush_stage()); statements.append(stages); stages = []; i += 2; continue
            if c in (";", "\n"):
                stages.append(flush_stage()); statements.append(stages); stages = []; i += 1; continue
            if c == "|":
                stages.append(flush_stage()); i += 1; continue
        buf.append(c); i += 1

    stages.append(flush_stage()); statements.append(stages)
    return [[st for st in stmt if st] for stmt in statements]


def tokens(stage):
    try:
        return shlex.split(stage, posix=True)
    except ValueError:
        return stage.split()


WRAPPERS = {"env", "sudo", "nohup", "time", "command", "exec", "builtin",
            "stdbuf", "nice", "xargs"}


def leading_cmd(toks):
    """Command token of a stage, skipping leading VAR=val assignments and a few
    transparent wrappers. Returns (cmdtoken, argtokens) or (None, [])."""
    i = 0
    while i < len(toks):
        t = toks[i]
        if re.match(r"^[A-Za-z_][A-Za-z0-9_]*=", t):   # VAR=val
            i += 1; continue
        if t in WRAPPERS:                               # peel one wrapper
            i += 1
            while i < len(toks) and toks[i].startswith("-"):
                i += 1
            continue
        return t, toks[i + 1:]
    return None, []


def is_file_operand(t):
    if t in ("-", "/dev/stdin", "/dev/stdout", "/dev/stderr", "/dev/null"):
        return False
    if t.startswith(("-", "<", ">", "`")):
        return False
    if t.startswith("$("):
        return False                      # command substitution output, not a file
    # A plain variable reference ($f / ${f}) is almost always a file path in a
    # reader/grep context, so treat it as a (probable) file operand.
    if t.isdigit():
        return False
    return True


def has_glob(t):
    return any(ch in t for ch in "*?[")


READERS = {"cat", "head", "tail", "less", "more", "bat"}
GREPS = {"grep", "egrep", "fgrep", "rg"}


def check_read(cmdtok, args):
    if cmdtok == "tail" and any(a in ("-f", "-F", "--follow") for a in args):
        return None
    file_ops = [a for a in args if is_file_operand(a)]
    if not file_ops:
        return None                       # reading stdin / heredoc
    if any(has_glob(a) for a in file_ops):
        return None                       # multi-file glob: needs the shell
    return ("Use the Read tool to view files, not `%s`. Read returns line "
            "numbers, paginates large files, supports offset/limit, and renders "
            "images/PDFs/notebooks. Re-issue with Read." % cmdtok)


def check_grep(cmdtok, args):
    # rg with no incoming pipe is always a recursive file search.
    file_search = (cmdtok == "rg")
    operands, skip_next, pattern_via_opt, after_ddash = [], False, False, False
    val_opts = set("efmABCdD")            # short opts that consume the next arg
    for t in args:
        if skip_next:
            skip_next = False
            continue
        if after_ddash:
            operands.append(t); continue
        if t == "--":
            after_ddash = True; continue
        if t.startswith("--"):
            name = t[2:].split("=", 1)[0]
            if name in ("recursive", "files-with-matches"):
                file_search = True
            if name in ("include", "exclude", "exclude-dir", "file", "regexp") and "=" not in t:
                skip_next = True
            if name in ("file", "regexp"):
                pattern_via_opt = True
            continue
        if t.startswith("-") and len(t) > 1:
            cluster = t[1:]
            if "r" in cluster or "R" in cluster or "l" in cluster:
                file_search = True
            if "e" in cluster or "f" in cluster:
                pattern_via_opt = True
            if cluster[-1] in val_opts:
                skip_next = True
            continue
        operands.append(t)
    files = operands if pattern_via_opt else operands[1:]
    files = [f for f in files if is_file_operand(f)]
    if file_search or files:
        return ("Use the Grep tool to search file contents, not `%s`. Grep is "
                "built on ripgrep with output modes (content / files / count), "
                "glob & type filters, and context lines. Re-issue with Grep. "
                "(A grep that reads from a pipe, e.g. `git log | grep`, is fine "
                "and not blocked.)" % cmdtok)
    return None


def check_find(args):
    actions = {"-exec", "-execdir", "-ok", "-okdir", "-delete", "-fprintf",
               "-fprint", "-fls"}
    if any(a in actions for a in args):
        return None                       # performs an action Glob can't
    return ("Use the Glob tool to find files by name/pattern, not `find`. Glob "
            "supports `**` recursion and returns paths sorted by mtime. "
            "Re-issue with Glob. (Keep `find` when it uses -exec/-delete to act "
            "on the results.)")


def check_ls(args):
    metadata = set("ltShdicuG")           # flags Glob can't reproduce
    for a in args:
        if a.startswith("--"):
            return None                   # long opts: assume metadata intent
        if a.startswith("-") and len(a) > 1:
            if any(ch in metadata for ch in a[1:]):
                return None
    return ("Use the Glob tool to list files by pattern, not plain `ls`. Glob "
            "returns matching paths directly. Re-issue with Glob. (Keep `ls` "
            "when you need -l/-t/-S/-h metadata Glob can't show.)")


SED_RANGE = re.compile(r"^'?\"?\s*\$?\d+\s*(,\s*\$?\d+\s*)?p'?\"?$")


def check_sed(args):
    if any(a.startswith("-i") for a in args):
        return None                       # in-place edit: a real transform
    if "-n" not in args:
        return None
    has_range = any(SED_RANGE.match(a) for a in args)
    file_ops = [a for a in args if is_file_operand(a)]
    if has_range and file_ops and not any(has_glob(a) for a in file_ops):
        return ("Use the Read tool with offset/limit to view a line range, not "
                "`sed -n 'N,Mp' file`. Re-issue with Read.")
    return None


WRITERS = {"echo", "printf", "cat"}
REDIR = re.compile(r"(?<![0-9&])>>?\s*([^\s;|&)<>]+)")
TEE = re.compile(r"(?:^|\s)tee\s+(?:-[aA]\s+)?([^\s;|&)<>]+)")
SAFE_TARGETS = ("/dev/null", "/dev/stdout", "/dev/stderr")


def check_write(cmdtok, stage):
    is_writer = cmdtok in WRITERS or re.search(r"(^|\s)tee(\s|$)", stage)
    if not is_writer:
        return None
    for tgt in REDIR.findall(stage):
        if tgt not in SAFE_TARGETS and not tgt.startswith("/dev/fd/"):
            return ("Don't author file content through the shell "
                    "(`%s ... >` / `tee`). Use the Write tool to create/"
                    "overwrite a file or Edit to change part of one. Re-issue "
                    "with Write/Edit." % cmdtok)
    m = TEE.search(stage)
    if m and m.group(1) not in SAFE_TARGETS:
        return ("Don't author file content through the shell (`tee`). Use the "
                "Write/Edit tools. Re-issue with Write/Edit.")
    return None


def analyse(cmd):
    statements = split_command(cmd)
    for stmt in statements:
        for idx, stage in enumerate(stmt):
            toks = tokens(stage)
            if not toks:
                continue
            cmdtok, args = leading_cmd(toks)
            if cmdtok is None:
                continue
            base = cmdtok.rsplit("/", 1)[-1]      # /bin/grep -> grep

            # cd-prefix: a `cd` followed by more in the same compound.
            if base == "cd" and idx == 0:
                others = [s for s in stmt if s is not stage]
                if others or len(statements) > 1:
                    return ("Don't prefix commands with `cd`. The Bash tool "
                            "keeps its working directory between calls, so use "
                            "an absolute path (or `<tool> -C <dir>`, e.g. "
                            "`git -C <dir>`), or run a one-time standalone `cd` "
                            "first. Re-issue without the `cd ... &&` prefix.")

            # write check first (so `cat > file` is a write, not a read)
            r = check_write(base, stage)
            if r:
                return r

            if idx > 0:
                continue          # reading a pipe is legitimate for any tool

            if base in READERS:
                r = check_read(base, args)
            elif base == "sed":
                r = check_sed(args)
            # NOTE: the grep/find/ls -> Grep/Glob redirects are disabled on this
            # machine. The macOS *native* Claude Code build does not register the
            # Grep and Glob tools: they exist in the binary but are dropped from
            # the default tool set (confirmed across 2.1.179/183/185; not a
            # setting, not ENABLE_TOOL_SEARCH, not ripgrep). Redirecting to those
            # absent tools would break shell search outright, so shell grep/find/
            # ls are ALLOWED here. The cat/head/tail -> Read, sed -> Read, and
            # `>file` -> Write/Edit redirects stay active (those tools do exist).
            # To re-enable once on a build that ships Grep/Glob (e.g. the npm
            # install), restore the three branches below:
            #   elif base in GREPS:
            #       r = check_grep(base, args)
            #   elif base == "find":
            #       r = check_find(args)
            #   elif base == "ls":
            #       r = check_ls(args)
            else:
                r = None
            if r:
                return r
    return None


def main():
    try:
        data = json.load(sys.stdin)
    except Exception:
        sys.exit(0)                       # malformed input: allow, don't break
    cmd = (data.get("tool_input") or {}).get("command") or ""
    if not cmd or "#bash-ok" in cmd:
        sys.exit(0)
    reason = analyse(cmd)
    if reason:
        deny(reason)
    sys.exit(0)


if __name__ == "__main__":
    main()
