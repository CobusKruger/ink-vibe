# Lovable Repo Analysis

Date: 2026-07-19 (synced to ink-lovable @ 04217f7)
Source repo: ink-lovable

## Summary

The Lovable repo contains enough information to populate most of the canonical token file and to start implementation mapping for selected pages.

As of the 2026-06-14 sync it contains designs for all top-level INK pages (see below).

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

## What is missing for full INK coverage

1. Pages still without a dedicated Lovable design (assembly-only — build from archetypes):
- Lidmaatskap, Oor INK, Kontak
- (Opleiding & Biblioteek reuse the Library.tsx layout; Gemeenskap = Community.tsx; Auth flows designed 2026-07-19 — all now present.)

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
