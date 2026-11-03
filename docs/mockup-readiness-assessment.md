# Lovable Mockup Readiness Assessment

_Synced to verse-haven `cbd9d3a` on 2026-11-02 (range `71c0847..cbd9d3a`). "Reference-ready" means a design exists; the build state of each delta page is in `design-handoff/page-map.csv` (`status`) and `lovable-delta-2026-10/worklist.md`._

## Summary

The Lovable mockup is sufficiently complete to use as a design template. The design system (tokens, typography, colour palette) is the most valuable output and is directly convertible to `theme.json`. Most core pages have reference-quality layouts. Several gaps exist but are either assembly work or flagged for dedicated follow-up.

**Overall verdict: Reference-ready, with known gaps documented below.**

---

## Design system

| Area | Status | Notes |
|---|---|---|
| Colour tokens | Ready | Full palette defined in `index.css` and `DESIGN_TOKENS.md`. Terracotta, cream, sage, gold, highlight all present. |
| Typography | Ready | Lora (serif) + Inter (sans). Scale documented. Literary prose styling specified. |
| Spacing | Ready | Token scale defined. |
| Dark mode | Ready | Tokens defined for both modes. |
| Component library | Ready | Full shadcn/ui set available. All assembly components present. |

---

## Page-by-page status

| Page | Lovable source | Status | Notes |
|---|---|---|---|
| Tuisblad | `Index.tsx` + `components/home/*` | Reference-ready | Order since 2026-11: hero spotlight, results banner, InkPols section, "Last month's winners", featured works, sponsors, CTA. The winners section shows two cards in the design; INK shows four (D10, disclosed divergence). The hero stats row was removed in the design; INK never had one. |
| Lees (storie) | `ReadStory.tsx` + `components/reading/*` | Reference-ready | Highlightable text, critique panel present. Static story only — no dynamic data. |
| Lees (gedig) | `ReadStory.tsx` + `components/reading/PoetryReader.tsx` | Reference-ready | Stanza-aware poem layout (preserves line breaks; Roman-numeral stanza markers) with per-line "resonance" (heart) taps. Added 2026-06-14; poem body left-aligned 2026-06-20 (dedication and resonance-count footer stay centred). The dedication needs a poem field and the response upvote (`ReadStory.tsx`) needs a vote; both ruled "build" 2026-11-02 (§14 #31). Stacked blank lines collapse to one stanza break (FR-25, spec §9.4). |
| Biblioteek | `Library.tsx` | Partial — see separate doc | Layout pattern (featured strip + category filter + search + card grid) is valid. Gaps: no date/archive browsing, no pagination, no author filter. See [Biblioteek organisasie.md](Biblioteek%20organisasie.md). |
| Opleiding | `Library.tsx` | Usable as layout reference | Same layout pattern as Biblioteek. Content structure differs but the archetype is shared. |
| Uitdagings (single) | `Challenge.tsx` | Reference-ready | Full challenge detail page with resources and submission list. |
| Uitdagings (list) | `Challenges.tsx` | Reference-ready | Designed 2026-11-01 (`3e75fb8`): heading, featured current-round card with countdown, past rounds as rows with "See the winners". The owner keeps INK's standard past-round cards instead of the design's rows (accepted divergence). Replaces the earlier "home section only" status. |
| Uitslae (round results) | `ChallengeResults.tsx` + `data/awards.ts` | Reference-ready | Hero, judges' letter, podium with Gedigte / Stories / Artikels tabs (Gedigte by Gold / Silver / Bronze), award cards with citation, honourable mentions, CTA. New 2026-11. The design has no link from a round to its results; INK adds one (owner). |
| Lees (winner aside) | `ReadStory.tsx` (`reading-award`) | Reference-ready | Gold aside on a placed work: label linking to the results and a citation, shown only if the author opted in (D6). |
| InkPols archive | `Magazine.tsx` | Reference-ready | Current issue, year chips, past issues grid. The design's issue number, season and PDF download buttons are not built (D9: the naam replaces number and season; no downloads). |
| InkPols issue | `MagazineIssue.tsx` | Reference-ready | Top bar, flipbook, "in this issue", prev/next. The flipbook appears only here (D9). |
| Kompetisies | `Competitions.tsx` + `data/competitions.ts` | Reference-ready | Annual-competition dark hero, three categories, gala (details, programme, ticket pills, "Reserve"), "Evenings Past" by year, external directory with search and genre and fee chips, submit-a-listing box. New 2026-11. Tickets are fictional with no payment (D4). INK adds a `/kompetisies/gala/<jaar>/` page with every photo (owner, 2026-11-02). |
| Header (nav + mobile menu) | `layout/Header.tsx` | Reference-ready | Gains Competitions, Magazine and Challenges links and `header-mobile-*` ids. INK adds Kompetisies only (D1), drops "My profiel" from the desktop row and switches to the mobile menu below 1024 (owner, 2026-11-02). Footer links are deferred. |
| Skryf | `Write.tsx` | Reference-ready | Distinguishes between poem, short story, and article content types. Challenge linking present. |
| Skrywerprofiel | `Writer.tsx` | Reference-ready | Cover image, bio, pinned works, accomplishments, follower stats. |
| My Profiel | `Profile.tsx` | Reference-ready | Tabs: Overview, Posts, Reading list, Following, Activity (new work from follows), Notifications, Membership. Friends tab replaced by **Following** (follow decision); activity feed added 2026-06-14. |
| Ontdek/Browse | `Browse.tsx` | Reference-ready | Stories tab and writers tab. Writer tab includes genre filter and sort (Most Read, New Voices). |
| Gemeenskap | `Community.tsx` | Reference-ready (as intended) | Functions as a visitor conversion/marketing page. Community features live on the profile page. Correct approach for this platform. |
| Lidmaatskap | — | Missing | Membership acquisition/pricing page. `Profile.tsx` has renewal UI only. Needs a standalone page. Assembly work — no new design required. |
| Oor INK | — | Missing | Static content page. Assembly work using existing components (typography, SponsorsSection pattern, media-text blocks). |
| Kontak/Contact | — | Missing | Simple form page. Assembly work. |
| Authentication flow | `Auth.tsx` + `ForgotPassword.tsx` + `ResetPassword.tsx` | Reference-ready | Sign-in/sign-up tabs, forgot-password, and reset-password screens on a single centred card, plus Google/Apple social-login buttons — the design confirms the already-decided R6 social login (§14 #22 / feature 3.5). Header also gains logged-in (avatar dropdown: My Profiel / Opleiding / Meld af) vs logged-out (Meld aan / Sluit aan) states. Designed 2026-07-19. Supabase auth in the mock is prototype plumbing only — the build is WordPress-native auth. |

---

## Genuine gaps requiring design decisions

1. **Biblioteek organisation** — archive depth requires date browsing, pagination, and author filtering. See [Biblioteek organisasie.md](Biblioteek%20organisasie.md).

_Resolved 2026-06-14: the **gedig reading layout** (`PoetryReader.tsx`) and the **profile activity feed** (`Profile.tsx` Activity tab) have been designed and are no longer gaps._

_Resolved 2026-11: the **Uitdagings list** (`Challenges.tsx`), **results** (`ChallengeResults.tsx`), **InkPols** (`Magazine.tsx`, `MagazineIssue.tsx`) and **Kompetisies** (`Competitions.tsx`) pages are designed. The **footer** is not part of any design gap: it is known to be wrong and deferred by the owner._

## Assembly-only gaps (no design work needed)

- Lidmaatskap page
- Oor INK page
- Kontak page

_Uitdagings list left this list: it has a design now._

---

## Related documents

- [lovable-block-theme-playbook.md](lovable-block-theme-playbook.md) — conversion approach from Lovable to WordPress block theme
- [design-handoff/page-map.csv](design-handoff/page-map.csv) — per-page implementation targets
- [Biblioteek organisasie.md](Biblioteek%20organisasie.md) — biblioteek content structure planning
- [afrikaans-terms.md](afrikaans-terms.md) — Afrikaans terminology reference
