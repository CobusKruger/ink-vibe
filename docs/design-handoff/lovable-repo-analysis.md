# Lovable Repo Analysis

Date: 2026-11-02 (synced to verse-haven @ cbd9d3a; previous record 2026-07-19 @ 04217f7, with 71c0847 synced by the fidelity rework)
Source repo: verse-haven (renamed from ink-lovable; older sections below keep the old name in paths, read them as `verse-haven/`)

## Summary

The Lovable repo contains enough information to populate most of the canonical token file and to start implementation mapping for selected pages.

As of the 2026-06-14 sync it contains designs for all top-level INK pages (see below). The 2026-11-02 sync (`71c0847..cbd9d3a`, 36 commits) adds six pages and rebuilds two sections; see "Design delta" below.

## What is present

1. Design token reference document with explicit values:
- ink-lovable/DESIGN_TOKENS.md

2. Runtime token values in CSS variables (including dark mode):
- ink-lovable/src/index.css

3. Tailwind-level token wiring and semantic aliases:
- ink-lovable/tailwind.config.ts

4. Implemented page references:
- ink-lovable/src/pages/Index.tsx (Tuisblad)
- ink-lovable/src/pages/ReadStory.tsx (Lees — storie)
- ink-lovable/src/pages/Write.tsx (Skryf)
- ink-lovable/src/pages/Browse.tsx (Ontdek)
- ink-lovable/src/pages/Challenge.tsx (Uitdaging — single)
- ink-lovable/src/pages/Library.tsx (Opleiding / Biblioteek layout)
- ink-lovable/src/pages/Writer.tsx (Skrywerprofiel)
- ink-lovable/src/pages/Profile.tsx (My Profiel — tabs incl. Following + Activity)
- ink-lovable/src/pages/Community.tsx (Gemeenskap)
- ink-lovable/src/pages/Auth.tsx + ForgotPassword.tsx + ResetPassword.tsx (Outentisering — aanmeld/registreer tabs, wagwoord-vergeet, wagwoord-herstel, Google/Apple sosiale aanmelding). Added 2026-07-19; `Header.tsx` also gains logged-in (avatar dropdown) vs logged-out states. Auth uses Supabase in the mock — prototype plumbing only; the build is WordPress-native auth.

5. Reusable section components:
- ink-lovable/src/components/home/*
- ink-lovable/src/components/reading/* (incl. PoetryReader.tsx — gedig layout)
- ink-lovable/src/components/layout/*
- ink-lovable/src/data/* (works, writers mock data) + src/lib/readerStore.ts (reading-list / follow / resonance state)

## Design delta `71c0847..cbd9d3a` (synced 2026-11-02)

Read from the code at `cbd9d3a` (`git log` and the components), not from the handover. The range spans 36 commits and 26 files. **Tokens:** none of `DESIGN_TOKENS.md`, `src/index.css` or `tailwind.config.ts` changed in the range, so there is no `theme.json` regeneration. The new photos (`gala-2025-*.jpg`, `magazine-cover.jpg`) are placeholders and are not shipped. `data-audit-id`s were added to every new page (`c65d970`, `cbd9d3a`; the ids are ours, not Lovable's).

New pages and sections:

| Lovable source | What it is | INK target | State |
|---|---|---|---|
| `pages/ChallengeResults.tsx`, `data/awards.ts` | Per-round results: hero (month, theme, announced date, entries), judges' letter, podium with Gedigte / Stories / Artikels tabs (Gedigte split Gold / Silver / Bronze), award cards with excerpt and judging-panel citation, honourable mentions, closing CTA | `/uitdaging/<slug>/uitslae/` (endpoint, `single-uitdaging-uitslae`) | built, gate passed on test data; real winner data waits for `winner-cleanup.md` |
| `pages/Competitions.tsx`, `data/competitions.ts` | Annual competition dark hero, three categories with guidelines, gala (details, programme, ticket pills, "Reserve Your Seat"), "Evenings Past" gallery by year, external competitions directory (search, genre and fee chips), "submit a listing" box | `/kompetisies/` (SystemPages, adopts prod Page 59192) | built, gate passed |
| `pages/Magazine.tsx`, `pages/MagazineIssue.tsx` | InkPols archive (current issue, year chips, past issues grid) and issue reader (top bar, flipbook, "in this issue", prev/next) | `archive-` / `single-inkpols_uitgawe` | built, gate passed |
| `pages/Challenges.tsx` | Challenges list: heading, featured current-round card, past rounds as rows with "See the winners" | `archive-uitdaging` | built; past rounds keep INK's standard cards (owner, accepted divergence) |
| `components/home/ResultsBanner.tsx`, `MagazineSection.tsx` | Home results banner and latest-issue section | blocks `ink/uitslae-banier`, `ink/inkpols-tuis` | built |
| `components/home/ChallengeSection.tsx` | Rebuilt as "Last month's winners": two gold cards (story, poem). The current-challenge card left this section | block `ink/wenner-kollig`, **four** cards (D10) | built |
| `pages/ReadStory.tsx` (`reading-award*`) | Gold aside on a placed work: label linking to the results, citation | block `ink/lees-wenner` | built |
| `components/layout/Header.tsx` | Competitions and Magazine links, Challenges link, `header-mobile-*` ids | header gains Kompetisies only; Magazine stays out (D1) | built |
| `pages/Library.tsx` | Shelf H2 `text-3xl md:text-4xl font-semibold` | CSS | built |

What the design shows and INK does **not** build: PDF download buttons on InkPols (D9), the issue number and season (the *naam* replaces them), any payment behind "Reserve Your Seat" (D4), the footer links to Competitions and Magazine (footer deferred), the Magazine header link (D1), and the design's rows for past rounds on Challenges (owner).

What INK builds that the design does not show: a link from the round page to its results (owner), the `/kompetisies/gala/<jaar>/` page with every photo of a gala (owner), two more winner cards on Tuis (D10: four, not two), and the response upvote and poem dedication of `ReadStory.tsx` / `PoetryReader.tsx`, which the design had earlier but INK had not built (Lees rulings, 2026-11-02).

Still not designed (assembly-only): Lidmaatskap, Oor INK, Kontak. The footer is untouched by the delta and deferred.

## What is missing for full INK coverage

1. Pages still without a dedicated Lovable design (assembly-only — build from archetypes):
- Lidmaatskap, Oor INK, Kontak
- (Opleiding & Biblioteek reuse the Library.tsx layout; Gemeenskap = Community.tsx; Auth flows designed 2026-07-19; the Uitdagings list is now `Challenges.tsx`, 2026-11 — all now present.)

2. No direct WordPress block mapping artifacts from Lovable:
- No template map
- No block pattern inventory
- No theme.json-ready output

3. Copy is English in component examples; Afrikaans terms will need replacement during implementation.

## Confirmation

Can this repo populate the canonical design-system file?
- Yes, mostly.
- Colors, typography, spacing, radius, shadows, and layout widths are available.

Can this repo alone complete all handoff artifacts for INK page implementation?
- Not fully.
- It supports immediate work for Tuisblad and Lees, partial for Uitdagings/Oor INK, and leaves multiple top-level pages without design references.

## Recommended next capture from Lovable

1. Export or design missing top-level pages listed above.
2. Keep the raw Lovable repo as reference and continue normalizing into docs/design-handoff files. (No screenshots are kept — tokens + source are the reference; mockup copy/content must not be lifted.)
