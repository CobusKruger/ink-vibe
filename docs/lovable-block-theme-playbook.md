# INK Lovable to Block Theme Playbook

## Purpose

This document defines how to convert the existing Lovable design into a production-ready WordPress block theme without losing visual intent.

It answers two core needs:

1. Standardise color, typography, spacing, and component behavior in a single theme system.
2. Build page layouts that match designed pages, then reuse the same principles for new pages not yet designed.

---

## Short answer on compatibility

See [mockup-readiness-assessment.md](mockup-readiness-assessment.md) for a full page-by-page status of the Lovable mockup, known gaps, and assembly-only work.

Yes, the Lovable design is compatible with a WordPress block theme if it is treated as a design source, not as direct production runtime code.

The practical translation model is:

- Lovable design language -> theme tokens in theme.json
- Lovable page compositions -> block patterns and templates
- Lovable interaction patterns -> block styles and lightweight front-end behavior

---

## Implementation model

Use the already confirmed three-layer architecture:

- Theme layer: all visual design decisions and layout structure
- ink-core plugin layer: all INK business rules and workflow logic
- Platform layer: WooCommerce, BuddyPress, SEO, security, and related commodity features

Design consistency is enforced in the theme layer, never in ad hoc plugin CSS.

---

## 1) Standardise design tokens in theme.json

Create one canonical token set in theme.json and do not bypass it.

### Color system

Define a named palette with semantic roles.

- primary
- secondary
- accent
- surface
- surface-alt
- text
- muted-text
- success
- warning
- danger

Rules:

- No hardcoded hex values in templates or block markup.
- Every block color selection must map to a named token.
- Create tokens for both brand and utility states.

### Typography system

Define explicit font families and a scale.

- Display
- Heading
- Body
- UI

Define font sizes as named steps.

- xs, sm, md, lg, xl, 2xl, 3xl

Rules:

- Use fluid typography where appropriate.
- Keep line-height and letter-spacing tokens explicit for readability.
- Afrikaans readability takes priority over decorative choices.

### Spacing and rhythm

Define one spacing scale and apply everywhere.

- 4, 8, 12, 16, 24, 32, 48, 64, 80, 96

Rules:

- All margins, paddings, and gaps come from this scale.
- Section spacing uses larger steps only.
- Avoid one-off spacing values.

### Layout widths

Set layout constraints globally.

- content width for reading comfort
- wide width for feature rows and media modules

Rules:

- Reading templates should remain text-legible first.
- Feature and archive templates may use wide containers, but content hierarchy stays clear.

---

## 2) Convert designed pages into reusable layout primitives

Do not implement each page as a unique one-off template.

Split every designed page into reusable primitives:

- Template parts: header, footer, section shells
- Block patterns: hero, featured grid, archive intro, CTA bands, profile summaries
- Block styles: button variants, card variants, emphasis treatments

### WordPress implementation targets

- templates/*.html for page-level structures
- template-parts/*.html for shared regions
- patterns/*.php for reusable content compositions
- theme.json for all design-system values

### Lock strategy

Use block locking where layout integrity matters.

- Lock structure on critical editorial sections.
- Keep content fields editable by editors.
- Prevent accidental design drift.

---

## 3) Page mapping: designed pages

For each Lovable mock page, create a mapping row before coding.

Required mapping columns:

1. Source design page name
2. WordPress target (template, pattern, or template part)
3. Content source (post type, taxonomy, options, user data)
4. Required blocks and variants
5. Afrikaans terminology checks
6. Responsive behavior notes

If a page cannot be mapped mostly with core blocks and light custom blocks, revisit the layout before implementation.

---

## 4) Page principles: pages not yet designed

When a page has no mock design, build it from approved layout archetypes.

### Archetype A: Editorial landing

- Intro section
- Featured stream
- Secondary content bands
- CTA to deeper navigation

Use for: Tuisblad, top-level discovery pages.

### Archetype B: Archive and discovery

- Context intro
- Filter or taxonomy controls
- Card listing with pagination

Use for: Lees, Opleiding, Biblioteek, Uitdagings lists.

### Archetype C: Detail reading page

- Strong title and metadata
- Main readable body column
- Related items block

Use for: gedig, verhaal, artikel, hulpbronartikel, biblioteekitem.

### Archetype D: Community utility page

- Clear functional heading
- Functional module first
- Secondary explanation after action controls

Use for: profile, notifications, account, member interactions.

All archetypes must reuse the same token system, spacing rhythm, and component variants.

---

## 5) Afrikaans-first enforcement in UI implementation

Every mocked label must be checked against the terminology guide before release.

Required controls:

- UI copy review against afrikaans-terms.md
- Avoid English fallback strings in templates and blocks
- Ensure button labels and status messages match approved terms

---

## 6) Quality gates

Treat these as release gates for every template and pattern.

### Gate A: Design system compliance

- Uses only approved theme tokens
- No hardcoded one-off colors or spacing
- Typography scale matches theme system

### Gate B: Layout consistency

- Matches approved mock intent where a design exists
- Uses archetype rules where no design exists
- Maintains visual hierarchy across desktop and mobile

### Gate C: Platform fit

- Works in Site Editor without fragile custom hacks
- Editor experience is stable for non-technical staff
- Dynamic content integration works with CPT and taxonomy models

### Gate D: Language compliance

- Afrikaans-first terminology is correct
- No English UI leakage

---

## 7) Suggested rollout sequence

1. Build theme token foundation in theme.json
2. Implement global templates and template parts
3. Implement pattern library from designed pages
4. Map and build top-level information architecture pages
5. Build archive and detail templates for each CPT
6. Fill non-designed pages using archetype system
7. Run final Afrikaans terminology and responsive QA pass

---

## Definition of done for design implementation

A page is done only when:

- it uses the shared token system
- it is assembled from reusable templates, parts, and patterns
- it aligns with the Lovable design intent or approved archetype
- it passes Afrikaans terminology checks
- it remains maintainable in the WordPress Site Editor
