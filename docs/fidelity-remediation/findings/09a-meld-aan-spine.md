# 09a · meld-aan — spine and structural findings

| | |
|---|---|
| **Page slug** | `meld-aan` |
| **Lovable** | `/auth`, default (sign-in) tab: `src/pages/Auth.tsx`, captured at `http://localhost:4189/auth` |
| **INK** | `https://nuwe-ink.local/meld-aan/`, captured anonymous |
| **Date** | 2026-10-13 |
| **Phases** | 0–4b (Stage 1 audit) |
| **Treatment** | A. Reference diff. One Lovable page serves two INK pages: this row is the sign-in tab; row 09b (`registreer`) is the sign-up tab. |

Header and footer belong to row 00. They were verified there and are not re-reported here. The one exception is F M1, which is about whether this page should carry them at all.

---

## 1. Phase 0 — pairing

Inventory row 09 (`agent-brief.md` §2.1) pairs `/auth` with `/meld-aan` **and** `/registreer`, and the owner ratified it with the note "INK splits it in two". I confirmed the pairing by reading both sides. `Auth.tsx` defaults to `<Tabs defaultValue="signin">` (`Auth.tsx:75`), so the page as it loads is the sign-in surface. `page-meld-aan.html:5` renders `ink-foundation/auth-login`, the sign-in form. **Confirmed. Treatment A.**

## 2. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)

- `src/App.tsx`: route table. `/auth` → `<Auth />` (`App.tsx:40`), with no layout wrapper.
- `src/pages/Auth.tsx`: the whole page.
- `src/components/ui/tabs.tsx`, `src/components/ui/input.tsx`, `src/components/ui/label.tsx`, `src/components/ui/button.tsx`: the shadcn primitives the page uses.
- Not read: `hooks/useAuth`, `integrations/supabase`, `integrations/lovable` and `hooks/use-toast`. They hold behaviour only; none of them emits markup at rest. The toast does render on submit failure, but no form was submitted.

**INK** (`/Users/cobus/Development/ink-vibe`)

- `wp-content/themes/ink-foundation/templates/page-meld-aan.html`: the template.
- `parts/header.html` and `parts/footer.html` were **not** read. They are row 00's scope, and their presence is visible in the template itself (`page-meld-aan.html:1,9`).
- `wp-content/themes/ink-foundation/patterns/auth-login.php`: the only pattern. It contains no `wp:ink/*` dynamic block.
- `wp-content/themes/ink-foundation/assets/css/auth.css`: the page stylesheet. Read for disclosure comments (R6) and attribution.
- `wp-content/themes/ink-foundation/functions.php`, **partial**, for these purposes only:
  - `:1-230`: enqueue gates, including `ink_foundation_enqueue_auth_assets`, `:172-186`.
  - `:1380-1680`: block-style registrations.
  - `:2260-2354`: the social-login bridge and `ink_foundation_icon`.
  - No presence/absence claim rests on the unread ranges.
- `wp-content/plugins/ink-core/src/Accounts/AuthRedirects.php`: login-failure redirect.
- `wp-content/plugins/ink-core/src/Accounts/SocialLogin.php`: the social-section gate.
- The rendered `<main>` of `https://nuwe-ink.local/meld-aan/`, from a cache-busted curl. It confirms that the social branch does not render.
- `theme.json`: `styles.elements` and the extracted `styles.css` were **searched**, not read end to end, for attribution only (Phase 4b).

## 3. Section spine

| # | Lovable (sign-in tab) | INK `/meld-aan` | Status |
|---|---|---|---|
| 0 | No site chrome: `/auth` renders no Header/Footer (`App.tsx:40`, `Auth.tsx:66-143`) | Header + footer template parts (`page-meld-aan.html:1,9`) | **extra** (M1) |
| 1 | Page wrapper, full-height and centred (`Auth.tsx:67-68`) | `<section>` with padding s-64/s-24 and contentSize 480px (`auth-login.php:36-37`) | present (M3) |
| 2 | Brand lock-up: feather + wordmark link (`Auth.tsx:69-72`) | none | **missing** (M2) |
| 3 | Card (`Auth.tsx:74`) | `.is-style-card.ink-auth-card` (`auth-login.php:38-39`) | present |
| 3a | Tabs: list + 2 triggers + panels (`Auth.tsx:75-81, 102, 117-118`) | none; sign-up is a separate page | **missing** (M4, SCOPE) |
| 3b | none | h1 "Teken in" + intro paragraph (`auth-login.php:40-46`) | **extra** (M6, M7) |
| 3c | Sign-in form: e-mail, password + forgot link, submit (`Auth.tsx:82-99`) | `form.ink-auth-form` (`auth-login.php:60-82`) | present (M8, M9, M15) |
| 3d | none | "Onthou my" remember-me row (`auth-login.php:72-77`) | **extra** (M10, DIVERGENCE) |
| 3e | "or continue with" divider + Google/Apple buttons (`Auth.tsx:120-136`) | Social section behind `ink_foundation_social_login_available()` (`auth-login.php:84-119`), off by default, **not rendered** | **missing at runtime** (M11, M12) |
| 3f | none (the sign-up tab does this job) | "Nog nie 'n lid nie? Registreer" switch line (`auth-login.php:121-123`) | **extra** (M5, MECHANISM) |
| 4 | "← Back to Inkwell" line (`Auth.tsx:139-141`) | none | **missing** (M13) |

## 4. Structural findings

| id | class | finding | Lovable | INK |
|---|---|---|---|---|
| M1 | `SCOPE` | INK wraps the sign-in page in the full site header and footer. Lovable's `/auth` is a bare, full-viewport page with no site chrome. | `App.tsx:40`; `Auth.tsx:66-143` (no `<Header/>`/`<Footer/>`) | `templates/page-meld-aan.html:1,9` |
| M2 | `MISSING` | The brand lock-up above the card (feather icon + serif wordmark, linking home) has no INK counterpart. | `Auth.tsx:69-72` | `auth-login.php:36-127` (no counterpart) |
| M3 | `MECHANISM` | Page layout. Lovable centres the card vertically and horizontally in a `min-h-screen` flex wrapper, inside a `max-w-md` column. INK has no column element: the section's constrained layout (`contentSize: 480px`) sets the card's own max-width and auto margins, and the section pads s-64 top and bottom instead of centring vertically. | `Auth.tsx:67-68` | `auth-login.php:36-37` |
| M4 | `SCOPE` | No tabs. Lovable switches sign-in and sign-up in place with a two-trigger TabsList. INK serves them as two pages. The inventory ratifies the split ("INK splits it in two"), so this records an owner-accepted scope difference rather than a defect. `agent-brief.md` §3.4 asked whether the split is SCOPE or STRUCTURE. The ratification answers "pair both", and the owner should confirm SCOPE. | `Auth.tsx:75-81, 102, 117-118` | `page-meld-aan.html:5`; `page-registreer.html:5` |
| M5 | `MECHANISM` | The route to sign-up. Lovable uses the "Sign up" tab trigger at the top of the card. INK uses a text line with an inline link at the bottom of the card. The intent is the same; the position and form differ. | `Auth.tsx:78` | `auth-login.php:121-123` |
| M6 | `EXTRA` | INK card carries an `<h1>` ("Teken in"). Lovable's card has no heading; the active tab label is the only title. The h1's sizing comment cites "Lovable's own heading here (`text-2xl font-semibold`)" (`auth.css:31-34`). `Auth.tsx` has no such heading. Its only `text-2xl font-semibold` node is the wordmark outside the card (`Auth.tsx:71`). | none | `auth-login.php:40-42`; `auth.css:27-39` |
| M7 | `EXTRA` | INK card carries an intro paragraph under the h1. Lovable has none. | none | `auth-login.php:44-46` |
| M8 | `STRUCTURE` | Field markup. Lovable wraps each Label + Input in a `<div class="space-y-2">`, with the password label row as a flex `<div>`. INK uses `<p class="ink-auth-field">` and a `<span>` row. Lovable's spacing comes from the parent (`space-y-*`); INK's comes from the label's own `margin-bottom` and the field's `margin-bottom`. | `Auth.tsx:83-95` | `auth-login.php:61-71`; `auth.css:65-94` |
| M9 | `STRUCTURE` | Submit. INK wraps the button in `<p class="ink-auth-submit">`; Lovable's Button is a direct child of the form. The INK button is a raw `<button class="wp-element-button">` with no `is-style-ink-primary` wrapper, so its recipe comes from `theme.json` `elements.button` plus `auth.css:207`, not from the registered primary style. That is a MECHANISM note, and the primitive consequences are in the primitives file. | `Auth.tsx:96-98` | `auth-login.php:79-81` |
| M10 | `DIVERGENCE (accepted)` | "Onthou my" remember-me checkbox. It has no Lovable counterpart. Disclosure: "no Lovable equivalent … kept as a real, useful WordPress-native feature rather than dropped to match 1:1" (`auth.css:172-174`). **That disclosure is an implementer comment, not a recorded owner ruling. Owner to confirm.** | none | `auth-login.php:72-77`; `auth.css:172-189` |
| M11 | `SCOPE` | "or continue with" divider + Google/Apple OAuth buttons. INK has the section, but it is gated on `SocialLogin::isAvailable()`, which defaults to `false` (`SocialLogin.php:74-76`). On the live site it does not render, so the divider and OAuth buttons are absent from the page. This is a disclosed deploy-time seam (`auth-login.php:6-12`). | `Auth.tsx:120-136` | `auth-login.php:84-119`; `functions.php:2284-2290` |
| M12 | `EXTRA` (latent) | When the social section does render, it differs from Lovable in two ways. It adds a POPIA consent note (`auth-login.php:102-118`), which is EXTRA. It also builds the divider as a full `<hr>` plus a separate centred paragraph, not Lovable's rule with an overlaid label, which is MECHANISM. Neither renders today. | `Auth.tsx:120-127` | `auth-login.php:85-96, 102-118` |
| M13 | `MISSING` | The "← Back to Inkwell" muted link under the card has no INK counterpart. (INK's header logo links home, which may make it redundant: owner call, tied to M1.) | `Auth.tsx:139-141` | `auth-login.php:36-127` (no counterpart) |
| M14 | `MECHANISM` | Failure feedback. Lovable raises a destructive toast. INK redirects back with `?meld_aan=fout` and renders an inline `.ink-auth-notice--fout`. This was not captured, because no form was submitted. | `Auth.tsx:32-35` | `auth-login.php:55-59`; `AuthRedirects.php:75-80` |
| M15 | `SCOPE` | The first field is `type="email"` "Email" on Lovable, and `type="text"` "Gebruikersnaam of e-pos adres" on INK. WordPress login accepts either a username or an e-mail. Paired as the same element. | `Auth.tsx:84-85` | `auth-login.php:61-64` |

### Notes on classification

- **M4 / M5** are one decision seen from two sides. The split is ratified (M4, SCOPE), and the switch line is how INK delivers the tab's job (M5, MECHANISM). Both rows are recorded so that the switch line is not mistaken for invention.
- **M6**: the h1 is EXTRA, not DIVERGENCE. No disclosure says the heading was added deliberately in place of the tabs. The only comment about it (`auth.css:27-36`) concerns its size, and it cites a reference element that `Auth.tsx` does not contain.
- **M10**: R6 makes this DIVERGENCE (accepted), because a disclosure comment exists. As on rows 04 and 08a, the disclosure was written by an implementer and rests on no owner ruling.
- **M11** is SCOPE rather than MISSING: the INK markup exists and is deliberately gated (`SocialLogin.php` docblock, `:14-47`). Whether to enable it is a deploy and owner decision, not a markup port.

Counts by class: SCOPE 4 (M1, M4, M11, M15); MISSING 2 (M2, M13); EXTRA 3 (M6, M7, M12); MECHANISM 3 (M3, M5, M14); STRUCTURE 2 (M8, M9); DIVERGENCE (accepted) 1 (M10). **15 total.**

## 5. Join-key coverage

The full table is in `maps/meld-aan.csv`. Lovable ids use the neutral prefix `auth-`, assigned once for `Auth.tsx` and shared with row 09b.

- **Paired: 12.** `auth`, `auth-card`, `auth-form`, `auth-email-field`, `auth-email-label`, `auth-email-input`, `auth-password-field`, `auth-password-row`, `auth-password-label`, `auth-forgot-link`, `auth-password-input`, `auth-submit`. The generated diff confirms 12 pairs at all three viewports.
- **Lovable-only: 18.** `auth-inner`; `auth-logo`, `auth-logo-icon` and `auth-logo-text` (M2); the 6 tab ids (M4); the 3 divider and 3 OAuth ids (M11); `auth-back` and `auth-back-link` (M13).
- **INK-only, rendered: 7.** `auth-heading`, `auth-intro`, `auth-remember`, `auth-remember-checkbox`, `auth-submit-wrap`, `auth-switch`, `auth-switch-link`.
- **INK-only, latent:** `auth-notice` (`?meld_aan=fout`) and the gated social ids `auth-divider`, `auth-divider-text`, `auth-oauth` and `auth-oauth-consent`. They are stamped in source but not rendered at rest.
- **Duplicate-id check (T5):** `grep -o '"a":"[^"]*"' <capture> | sort | uniq -d` is empty for every capture used. The first Lovable build gave both TabsContent panels the id `auth-tab-panel`. Radix keeps the inactive panel mounted as an empty `hidden` div, so that id appeared twice. I split it into `auth-tab-panel-signin` and `auth-tab-panel-signup` before any capture was kept.
- **Gates.** `determinism-check.sh` (3 runs) and `run.sh verify --sample 30` both **PASSED** at 390, 768 and 1440 for `lovable/auth@*` and `ink/meld-aan@*`.

## 6. Conclusion and limits

Structurally, `/meld-aan` is the sign-in tab's form lifted into its own page. The form itself (two fields, the inline forgot link, a full-width submit) pairs one to one, as does the card. Around it, the page differs from the reference in three ways:

1. **What frames the card.** INK has site chrome, top-aligned padding and its own h1 + intro. Lovable has a bare centred page with a brand lock-up above the card and a back link below it (M1–M3, M6, M7, M13).
2. **How the user moves between modes.** Lovable uses tabs; INK has a separate page with a switch line (M4, M5).
3. **What the live seam suppresses.** The OAuth divider and buttons exist in INK source but never render (M11, M12).

The only INK addition inside the form is the remember-me row (M10).

**Phase 4 output:**

- `findings/09a-meld-aan-drift.md` (1440), `09a-meld-aan-drift@768.md` and `09a-meld-aan-drift@390.md`: generated by `run.sh diff`, not hand-edited.
- 95 per-element deltas across the 12 paired elements at 1440 and 768: box 57, colour 18, type 8, micro 12.
- At 390: box 55, colour 18, type 10, micro 12.

**Phase 4b output:** `maps/_primitive-observations.meld-aan.csv` and `findings/09a-meld-aan-primitives.md`.

**What this document cannot support:**

- §3–§4 make no claim about any computed value (R4). Size, colour and spacing claims live only in the generated drift files and the primitive register.
- Not captured:
  - the failed-login notice (`?meld_aan=fout`);
  - focus and hover states;
  - the toast;
  - a logged-in visit, which redirects;
  - the social section, which is gated off.
- `parts/header.html` and `parts/footer.html` were not read. M1 rests only on the template's own `template-part` lines.
