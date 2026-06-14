# INK Design Handoff Workflow (Lovable -> Project -> Agents)

## Purpose

This workflow explains how to make design-system information and designed pages available inside this project so implementation agents can use them reliably.

It covers:

1. How to export design information from Lovable.
2. Where to store it in this repository.
3. How to point agents to the correct source files during build tasks.

> **Automated path:** the recurring re-sync described here is encoded as a reusable skill. Run `/lovable-design-sync` (or say "sync the Lovable design") to pull `ink-lovable`, diff it against the last-synced commit, and propagate changes through the handoff folder, planning docs, and specs. See `.claude/skills/lovable-design-sync/SKILL.md`.

---

## Direct answer

Yes, you can export the design system and share designed pages with agents.

The safest method is to keep all handoff assets in version control under a single folder with a fixed structure.

Recommended location:

- docs/design-handoff/

---

## Step 1: Export from Lovable

Use the richest export available from Lovable. If a full design-token export is available, use that first. If not, export what exists and normalise it in this repo.

The full `ink-lovable` repo is cloned and is the live reference, so a manual export is normally unnecessary — read tokens and source directly. Priority of sources:

1. Design tokens (`DESIGN_TOKENS.md`, `src/index.css`, `tailwind.config.ts`)
2. Page/component source (`src/pages/*`, `src/components/*`)
3. Font list and asset package

No screenshots: a screenshot is a picture of English placeholder content and invites literal copying. Tokens + source are the precise reference; mockup copy/content must not be lifted (copy ← `ui-copy-translations.md`/`afrikaans-terms.md`, content ← migrated DB).

---

## Step 2: Save into the repo using the standard structure

Use this structure exactly so humans and agents can find files quickly:

- docs/design-handoff/README.md
- docs/design-handoff/tokens/
- docs/design-handoff/assets/
- docs/design-handoff/agent-brief.md
- docs/design-handoff/page-map.csv

Example page slugs:

- tuisblad
- lees
- opleiding
- biblioteek
- uitdagings
- gemeenskap
- lidmaatskap
- oor-ink

---

## Step 3: Convert design tokens to WordPress token names

Maintain one canonical mapping file between design tokens and theme tokens.

Required mapping outputs:

1. color -> settings.color.palette in theme.json
2. typography -> settings.typography in theme.json
3. spacing -> settings.spacing.spacingSizes in theme.json
4. layout widths -> settings.layout in theme.json

Store the mapping in:

- docs/design-handoff/tokens/token-map.md

Important rule:

- theme.json naming becomes the production source of truth, even if Lovable token names differ.

---

## Step 4: Make designed pages actionable for implementation

For each designed page, create one row in page-map.csv with:

1. page slug
2. source mock reference
3. WordPress target (template, pattern, template part)
4. block composition notes
5. dynamic data source (CPT/taxonomy/user/meta)
6. Afrikaans terminology checks

This (with the Lovable source) is what agents should build from.

---

## Step 5: Show agents exactly what to use

When prompting an implementation agent, include:

1. The handoff workflow path
2. The token file path
3. The page-map path
4. The specific page's Lovable source file (from page-map.csv)
5. The expected output files

Suggested prompt pattern:

- Use [docs/design-handoff/tokens/theme-tokens.json](docs/design-handoff/tokens/theme-tokens.json) as the token source.
- Use [docs/design-handoff/page-map.csv](docs/design-handoff/page-map.csv) for template and pattern targets.
- Implement the page from its Lovable source (see `page-map.csv` for the source file) into block-theme files.
- Enforce Afrikaans labels using [docs/afrikaans-terms.md](docs/afrikaans-terms.md).

---

## Step 6: Keep handoff current during design changes

If the Lovable design changes:

1. Update token files first.
2. Update `mockup-readiness-assessment.md` status.
3. Update page-map rows.
4. Add a changelog entry in docs/design-handoff/README.md.

Never allow implementation to proceed against stale mock assets.

---

## Practical notes

- If Lovable supports direct code export, store the raw export under docs/design-handoff/raw-export/ and do not edit it.
- Keep a separate normalised token file for theme consumption.
- Image assets used in mock pages should be copied into docs/design-handoff/assets/ with descriptive names.
- If licensed fonts are used, store only references and licensing notes, not restricted binaries.

---

## Definition of done

Design handoff is complete when:

1. Tokens are present in a machine-readable file.
2. Every designed page is mapped in page-map.csv (no screenshots — tokens + source are the reference).
3. page-map.csv links each page to WordPress implementation targets.
4. agent-brief.md exists and points agents to authoritative paths.
5. Token mapping to theme.json is documented.
