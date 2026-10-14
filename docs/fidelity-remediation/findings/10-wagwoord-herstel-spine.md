# 10 · wagwoord-herstel — spine and structural findings

| | |
|---|---|
| Page slug | `wagwoord-herstel` (inventory row 10) |
| Lovable | `/forgot-password` → `src/pages/ForgotPassword.tsx` (captured from the local preview build, `http://localhost:4190/forgot-password`) |
| INK | `https://nuwe-ink.local/wagwoord-herstel/` → `templates/page-wagwoord-herstel.html` |
| Date | 2026-10-13 |
| Phase | 2 (this document), plus Phase 3 join key, Phase 4 drift and Phase 4b register |
| Treatment | **A**, reference diff (`agent-brief.md` §2.1, ✅ owner-ratified) |

Header and footer are out of scope. They were verified on row 00 and are not re-reported here. W1 records only that this page carries them and the reference does not.

---

## 1. Files read end to end

**Lovable** (`/Users/cobus/Development/ink-lovable`)

| File | Why |
|---|---|
| `src/App.tsx` | Route table: `/forgot-password` → `ForgotPassword` (line 41). App-level `<Toaster/>` + `<Sonner/>` (26–27) |
| `src/pages/ForgotPassword.tsx` | The page. It renders no `Header` or `Footer` (neither is imported) |
| `src/components/ui/button.tsx` | `variant="literary"`, `variant="outline"`, default size `h-10 px-4 py-2` |
| `src/components/ui/input.tsx` | `h-10 … px-3 py-2 text-base … md:text-sm` |
| `src/components/ui/label.tsx` | `text-sm font-medium leading-none` |
| `src/pages/Auth.tsx` | Read only for the shared `auth-*` id convention (rows 09a/09b) |

Not read: `hooks/use-toast`, `integrations/supabase/client`. No claim here rests on their contents. W13 relies only on the `toast(…)` call at `ForgotPassword.tsx:23`.

**INK** (`/Users/cobus/Development/ink-vibe/wp-content`)

| File | Link in the §3 chain |
|---|---|
| `themes/ink-foundation/templates/page-wagwoord-herstel.html` | 1. template: header part, `<main>` group, pattern, footer part |
| `themes/ink-foundation/patterns/auth-forgot-password.php` | 3. the only pattern. It uses no `wp:ink/*` dynamic block, so there is no link 4 |
| `themes/ink-foundation/assets/css/auth.css` | Page-scoped stylesheet, including its disclosure comments (R6) |
| `themes/ink-foundation/functions.php` lines 158–186 (auth enqueue), 520–560 (wp-login brand), 1420–1440 (`is-style-card`) | Partial read. Used only for load scope and source attribution. No presence or absence claim rests on it |
| `plugins/ink-core/src/Accounts/AuthRedirects.php` | Sends failed lost-password submissions to `?wagwoord=fout` |
| `themes/ink-foundation/patterns/auth-login.php`, `auth-register.php` | Sibling auth patterns, read for the shared ids and for `used_in` |
| Live HTML of `<main>` (cache-busted `curl`, after deploy) | Confirms the rendered DOM. `do_action('lostpassword_form')` (`:60`) currently emits nothing |

Not re-read: `parts/header.html` and `parts/footer.html` (row 00).

## 2. Section spine

| # | Lovable section | Lovable file:line | INK counterpart | INK file:line | Status |
|---|---|---|---|---|---|
| 0 | *(no site header)* | `ForgotPassword.tsx:30` | site header template part | `page-wagwoord-herstel.html:1` | **extra** (W1) |
| 1 | page wrapper, full-height and flex-centred | `:30` | `<main>` group + `alignfull` `<section>` | `page-wagwoord-herstel.html:3-7`; `auth-forgot-password.php:29-30` | present (W3, W4) |
| 2 | brand lock-up (feather + "Inkwell") | `:32-35` | — | — | **missing** (W2) |
| 3 | card: heading, lede | `:37-41` | `is-style-card ink-auth-card` group: h1, paragraph | `:31-39` | present |
| 4a | form state: field, submit, back link | `:53-66` | `<form>` + switch paragraph | `:54-70` | present (W5–W9, W14) |
| 4b | sent state: message + outline "back" button | `:43-51` | `?wagwoord=gestuur` branch: notice + `.ink-auth-secondary` | `:45-48` | present (W10–W12) |
| 4c | *(error: toast, outside the card)* | `:23` | `?wagwoord=fout` inline notice | `:51-53` | present, different mechanism (W13) |
| 5 | *(no site footer)* | — | site footer template part | `page-wagwoord-herstel.html:9` | **extra** (W1) |

## 3. Structural findings

| ID | Class | Finding | Lovable | INK |
|---|---|---|---|---|
| W1 | `SCOPE` | INK wraps the page in the site header and footer. The reference is a standalone, chrome-less screen. This is the same owner call as row 09a's auth-page chrome question | `ForgotPassword.tsx:30` (no Header/Footer) | `page-wagwoord-herstel.html:1,9` |
| W2 | `MISSING` | The brand lock-up (`auth-logo`, `-icon`, `-text`) has no INK counterpart | `:32-35` | `auth-forgot-password.php` (none) |
| W3 | `MECHANISM` | The page wrapper is `min-h-screen flex items-center justify-center px-4 py-12` on Lovable, which centres the card vertically. On INK it is a block `<section>` with s-64/s-24 padding in a constrained `<main>`, and the card sits at the top | `:30` | `:29-30`; template `:3-7` |
| W4 | `MECHANISM` | Lovable's column is a `max-w-md` (448px) wrapper (`auth-inner`). INK has no wrapper: the section's `contentSize: 480px` sizes the card directly | `:31` | `:29` |
| W5 | `SCOPE` | Lovable's field is `type=email`, labelled e-mail. INK's is `type=text` and accepts e-mail **or** username, as core `retrieve_password` does | `:55-56` | `:56-57` |
| W6 | `EXTRA` | INK has a field hint (`.ink-auth-hint`, curated copy per docblock `:13`) with no reference counterpart | — | `:58` |
| W7 | `STRUCTURE` | Field wrapper is a `<div class="space-y-2">` on Lovable and a `<p class="ink-auth-field">` on INK. Label-to-input spacing comes from `space-y-2` on Lovable and from a label margin on INK | `:54` | `:55` |
| W8 | `STRUCTURE` | Lovable's submit is a direct child of the form (`space-y-4`). INK wraps it in `<p class="ink-auth-submit">`. It is a raw `<button class="wp-element-button">` with no block-style wrapper, and its box comes from page-scoped `auth.css:207` (a `PRIMITIVE`, see the primitives doc) | `:58` | `:62-63` |
| W9 | `STRUCTURE` | The back-to-sign-in line sits **inside** the form on Lovable (`<p class="text-center text-sm">`, a single muted link). On INK it is a `core/paragraph` **after** the form, a sibling in the card's 24px block gap, with lead-in prose and a primary underlined link. Copy is not compared (R2) | `:61-65` | `:68-70` |
| W10 | `MECHANISM` | Sent state: Lovable swaps client state in the same card (`sent`). INK lands on the server-side `?wagwoord=gestuur` branch via the hidden `redirect_to` | `:13, :26, :43-51` | `:45-48, :61` |
| W11 | `STRUCTURE` | Sent-state message: Lovable has a plain paragraph inside a `div.text-sm` wrapper (`auth-sent`). INK has no wrapper and uses a bordered, success-tinted `.ink-auth-notice--ok` box | `:44-47` | `:47` |
| W12 | `MISSING` | Lovable's sent message echoes the submitted address (`<span class="font-medium">{email}</span>`). INK's is generic. Carrying the address across the redirect is an owner call | `:46` | `:47` |
| W13 | `MECHANISM` | Error feedback: Lovable raises a destructive toast. INK redirects to `?wagwoord=fout` (`AuthRedirects.php:92-100`) and renders an inline danger notice. **That branch is also where every invalid or expired reset link lands.** Observed: `wp-login.php?action=rp` with no valid key → `?action=lostpassword&error=invalidkey` → `/wagwoord-herstel/?wagwoord=fout` | `:22-24` | `:51-53` |
| W14 | `MECHANISM` | Busy state: Lovable disables the button and relabels it ("Sending…"). INK posts a native form and has no busy state. Not captured | `:58-60` | `:63` |
| W15 | `DIVERGENCE` (implementer comment; owner to confirm) | h1 weight. `auth.css:27-36` pins the size to 24px but states that the heading weight (700 vs Lovable's 600) is left alone on purpose. The disclosure is an implementer note, not an owner ruling | `:38` | `:34`; `auth.css:27-39` |

**Notes on classification**

- W5 is `SCOPE`, not `STRUCTURE`. Accepting a username is WordPress-native behaviour that the mockup cannot model.
- W6 is `EXTRA` rather than `SCOPE` because nothing discloses it as deliberate apart from the copy curation. The owner may reclassify it.
- W13 matters for row 11. INK's reset journey funnels failures into this page's `fout` state. Row 11's conformance doc records that.
- The sent-state primitives (`auth-notice`, `auth-secondary-btn`) are measured on INK only (captures `wagwoord-herstel-gestuur`, `wagwoord-herstel-fout`, both reached by GET with no submission). Lovable's sent state needs a real submission and was **not** captured.

**For the owner, not classified (copy, R2):** the sent-state button reads "Terug na aanmeld" (`:48`). The docblock (`:24-26`) says "Meld aan" is retired sitewide in favour of `teken_in`, and `auth-register.php:52` uses the term registry for the same button. This one is a hard-coded string.

## 4. Join-key coverage

The full table is in `maps/wagwoord-herstel.csv`. The page reuses the shared `auth-*` ids from rows 09a/09b. Two ids are new: `auth-sent` (here, Lovable only) and `auth-notice-link` (row 11's Lovable page). `auth-notice` and `auth-secondary-btn` gain a Lovable side for the first time.

| | ids |
|---|---|
| Paired and captured (default state) | 11: `auth`, `auth-card`, `auth-heading`, `auth-intro`, `auth-form`, `auth-email-field`, `auth-email-label`, `auth-email-input`, `auth-submit`, `auth-switch`, `auth-switch-link` |
| Paired, INK side captured only (sent/error states) | `auth-notice`, `auth-secondary-btn` |
| Lovable only | `auth-inner`, `auth-logo`, `auth-logo-icon`, `auth-logo-text` (captured); `auth-sent` (not captured) |
| INK only | `auth-field-hint`, `auth-submit-wrap` |

Phase 4 output: `10-wagwoord-herstel-drift.md` (1440), `@768`, `@390`. Determinism (3 runs) and `verify --sample 30` pass on all 12 capture files used for row 10: both sides × 3 viewports for the default state, plus INK `-gestuur` and `-fout` × 3.

## 5. Conclusion and limits

The page is structurally close: one card, one field, one submit and a way back to sign-in on both sides. The divergence is in the **frame** and the **spacing system**, not the content:

- INK sets the card in site chrome at the top of a padded section, where Lovable centres it alone on a full-height screen (W1, W3).
- The brand lock-up is missing (W2).
- The INK card is 32px wider at 1440, because 480 > 448 (W4).
- Card children are spaced by a uniform 24px block gap on INK. Lovable uses `mb-2`/`mb-6`/`space-y-4`. The back link sits outside the form, left-aligned and primary-coloured, where Lovable's is centred and muted (W9).

The sent and error states differ in mechanism (server redirect vs client state and toast) and in treatment (a boxed notice vs plain text).

This document makes **no claim about computed values**. Sizes, colours, spacing and weights come only from the generated drift files (Phase 4) and the primitive register (Phase 4b). It cannot speak to:

- Lovable's sent and error states (they need a submission);
- hover, focus and busy states;
- dark mode;
- the reset e-mail;
- the `lostpassword_form` hook's output if a plugin starts using it.
