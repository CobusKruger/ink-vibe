# Afrikaans leak-vector inventory & staging translation checklist

**Story 17.2 (NFR-1 / NFR-7).** The runbook a human translator + staging site
follow to author and verify the Afrikaans translations of the surviving
third-party plugins. Owning the UI means owning chrome, layout, and static labels
— **not** the strings plugins generate at runtime or send out of band. Those are
the leak vectors below.

Source of truth for the vector taxonomy: `docs/specs/ink-consolidated-spec.md`
§12 (leak vectors) + §13 / §14.13–14.15 (workflow). Loading home + workflow:
`wp-content/languages/README.md`. Standing detector: the English-leak scan
(Story 17.4).

> **Hard constraint:** human native-speaker Afrikaans only — **no AI translation**
> (LocoAI is retired). Author on staging with Loco → commit `.po/.mo/.json` to
> `wp-content/languages/` → production loads without Loco. Prefer complete w.org
> community language packs; hand-author only the gaps.

## The five §12 leak vectors

1. **Validation / status / error messages** — payment declines, "membership
   expired", login throttling, form validation.
2. **Plugin-composed sentences** — text assembled at runtime inside plugin
   functions (BuddyPress notification sentences, WooCommerce order/membership
   phrasing) — not a static template string.
3. **Transactional emails** — Woo order/renewal/expiry, BuddyPress notifications,
   password reset — often not a template INK rebuilds.
4. **Plugin JavaScript strings** — e.g. Real3D viewer controls — need the plugin's
   **JS `.json` translations, separate from `.mo`**.
5. **Out-of-band outputs** — REST / AJAX payloads, redirect-notice query args,
   feeds — rendered outside the normal template path.

## Surviving plugins × vectors

Front-end-user-facing plugins carry leak risk; admin-only plugins stay English by
decision (§14.14) and are out of scope. Contact Form 7 and Report Content are
**out of scope** — replaced by custom Afrikaans-native `ink-core` forms at launch
(Story 15.4 / 18.4), which removes their string surface entirely.

| Plugin | Risk | Vectors to author | w.org pack? |
|---|---|---|---|
| **BuddyPress** | HIGH | 1 (member/profile validation), 2 (activity/notification sentences), 3 (BP notification emails), 5 (AJAX directory/activity payloads) | Yes (verify completeness) |
| **WooCommerce** | HIGH | 1 (cart/checkout/order status, stock), 2 (order phrasing), 3 (order emails), 5 (Store API / AJAX cart fragments) | Yes (largely complete — verify) |
| **WC Memberships** | HIGH | 1 (access/expiry/renewal messages), 2 (plan phrasing), 3 (membership emails) | Partial — premium; hand-author gaps |
| **WC PayFast Gateway (ZAR)** | HIGH | 1 (payment status/error/redirect text), 3 (gateway-composed notices) | No pack — committed `.mo` is the only defence |
| **Real3D Flipbook** | MEDIUM | 4 (viewer-control JS `.json`, loaded via `Viewer::registerScriptTranslations`) | No — author `.json` |
| **Redirection** | LOW | 1/5 (front-end 404 / redirect notices only; admin stays English) | Yes |

Rank Math, LiteSpeed Cache, Patchstack, Loco Translate are admin/SEO/ops only —
no user-facing front-end strings → out of scope.

## Per-vector staging verification steps

For each surviving plugin above, on a production-like staging site with all
plugins active and migrated data:

- [ ] **Vector 1 — validation/status/error:** trigger each error path (declined
  PayFast sandbox payment, expired membership access, invalid checkout, BP
  registration validation) and confirm the message renders Afrikaans.
- [ ] **Vector 2 — composed sentences:** exercise BP activity/notifications and a
  full Woo order lifecycle; confirm runtime-assembled sentences are Afrikaans
  (these are the easiest to miss — not in any static template).
- [ ] **Vector 3 — transactional emails:** send each email (order, renewal,
  expiry, BP notification, password reset) to a test inbox; confirm subject + body
  Afrikaans. PayFast / Memberships premium emails have no community pack — author.
- [ ] **Vector 4 — plugin JS:** open a Real3D flipbook; confirm viewer controls are
  Afrikaans. Author the Real3D `.json` (NOT `.mo`) and commit to
  `wp-content/languages/`; verify it loads via the already-wired
  `wp_set_script_translations` seam.
- [ ] **Vector 5 — out-of-band:** inspect REST/Store-API/AJAX payloads and feed
  output for English fragments (e.g. Woo cart fragments, BP AJAX, redirect query
  notices); author any leaks.
- [ ] **Commit** all `.po/.mo/.json` to `wp-content/languages/`; redeploy; re-run
  the English-leak scan (Story 17.4) and confirm zero leaks.

## Already-built infrastructure (no staging needed — verified by tests)

- `Ink\Kernel\I18n::load()` — loads the `ink-core` domain; `forceStaffAdminLocale()`
  keeps staff admin English while front end stays `af` (`AdminLanguageTest`, 4 tests).
- `ink_foundation_load_textdomain()` — loads the `ink-foundation` theme domain.
- `Ink\InkPols\Viewer::registerScriptTranslations()` — points Real3D JS at the
  committed `wp-content/languages/` home (`TranslationLoadingTest`).
- `Ink\I18n\Terms` — single-source Afrikaans UI-label registry (`TermsTest`, 11
  tests) — keeps INK-owned custom strings out of the leak surface entirely.

## Status — pre-launch staging + human gate

The `.po/.mo/.json` **content** is the irreducible deliverable that cannot be
produced in-repo (vendor plugins absent, no running site, AI translation forbidden).
It is a **pre-launch gate** owned by a human translator on staging — tracked here
and in the Epic 17 carry-forward, not silently dropped. The loading wiring is done
and guarded; translations load automatically the moment they land.
