# `wp-content/languages/` — committed third-party translation home

This directory is the **load home** for the Afrikaans `.po` / `.mo` / `.json`
translations of the **surviving third-party plugins** (Story 17.2, NFR-1 / NFR-7).
WordPress loads translations placed here ahead of a plugin's own `/languages`
directory, so committing them here makes production Afrikaans **without Loco
Translate installed on production**.

## What loads from here

| Loader | Wired in | Resolves to |
|---|---|---|
| Real3D Flipbook viewer JS (`.json`) | `Ink\InkPols\Viewer::registerScriptTranslations()` → `wp_set_script_translations( …, 'ink-core', WP_LANG_DIR )` | `WP_LANG_DIR` = this dir |
| Surviving plugin `.mo` (BuddyPress, WooCommerce, Memberships, PayFast, Redirection) | WordPress core `load_*_textdomain` resolution | this dir overrides the plugin's own `/languages` |
| w.org language packs | WP auto-update | `wp-content/languages/plugins/` (where a complete community pack exists — preferred) |

> `ink-core` and `ink-foundation` load their OWN domains from their plugin/theme
> `/languages` dirs (`Ink\Kernel\I18n::load()`, `ink_foundation_load_textdomain()`).
> Those are Afrikaans-SOURCE (no English `.mo` — §14.15); this dir is for the
> **third-party** plugins whose source strings are English.

## Workflow (the standing process — §14.13)

1. **Author on staging** with Loco Translate (the only authoring tool; Loco is
   **never** installed/active on production). Prefer a complete **w.org community
   language pack** where one exists — only hand-author the gaps.
2. **Commit** the resulting `.po/.mo` (and Real3D `.json`) to this directory in
   version control.
3. **Production loads** them from here at boot — no Loco, no hand-editing on prod.
4. New strings from ungated plugin/core updates are caught by the standing
   **English-leak scan** (Story 17.4), then re-authored on staging, committed,
   redeployed.

## Rules

- **No AI-generated Afrikaans.** Human native-speaker authoring only (LocoAI is
  retired). See `docs/i18n-leak-vectors.md` for the per-plugin × per-vector
  authoring + QA checklist.
- **Cover all §12 leak vectors**, not just static labels: validation/status/error
  messages, plugin-composed sentences, **transactional emails**, **plugin JS**
  (Real3D `.json`), and out-of-band outputs (REST/AJAX/feeds).
- **Never commit an English `ink-core` `.mo` anywhere** — it would defeat the
  Afrikaans-source admin-language split (§14.15; see
  `wp-content/plugins/ink-core/languages/.gitkeep`).

## Status

The translation **infrastructure** is complete and wired (Epics 1.10, 13.3, 2.0).
The actual third-party `.po/.mo/.json` **content** is a **pre-launch staging +
human-translator gate** — it cannot be produced in-repo (vendor plugins are not in
the repo and there is no running site here; AI translation is forbidden). Once a
`.mo`/`.json` lands here it loads automatically via the wiring above.
