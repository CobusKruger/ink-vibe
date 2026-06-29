# SEO / Rank Math runbook (Story 18.1, NFR-4)

Rank Math is a **configured platform plugin** — INK does not reimplement SEO. This
runbook is the staging/production checklist for the parts that need a running
site (admin configuration, importer, OG verification, Yoast handover). The one
piece that lives in code is the per-CPT schema `@type` refinement, supplied by
the `Ink\Seo` module (see "What the code does" below).

> Source of record: `_bmad-output/planning-artifacts/epics.md` Story 18.1;
> `_bmad-output/project-context.md` (Rank Math = configured platform plugin,
> Yoast retired; "Don't clone `wp_options` wholesale — SEO config is set up fresh
> in Rank Math").

## What the code does (`Ink\Seo`)

- `Ink\Seo\SchemaTypes` hooks Rank Math's documented `rank_math/json_ld` filter
  and sets the schema `@type` for the three reader-facing INK CPTs that Rank Math
  cannot infer from a custom post type (it defaults every singular to `Article`):
  - `gedig` (poem) → `CreativeWork`
  - `storie` (short story) → `CreativeWork`
  - `artikel` (article) → `Article`
- It reads the CPT slugs from `Ink\Content\PostTypes::readableTypes()` (single
  source) and is **inert when Rank Math is absent** (no fatal, no schema emitted
  by ink-core itself). Nothing else about SEO is in code.

## One-time configuration (fresh — do NOT carry Yoast options forward)

1. **Install + activate Rank Math** on staging. Run the **Setup Wizard**:
   - Site type, organisation/logo, site locale `af`.
   - Titles & Meta: enable for `gedig`, `storie`, `artikel`, `biblioteek_item`,
     `opleiding_artikel`, `uitdaging`, `inkpols_uitgawe`. Keep `skryfwerk`
     (the unclassifiable bucket) `noindex` unless editorially promoted.
2. **Sitemaps**: enable the XML sitemap; confirm the public INK CPTs are
   **included** (they register `public => true` + `has_archive`, so Rank Math
   lists them automatically — verify, don't assume). Exclude `skryfwerk` if it
   is kept `noindex`.
3. **Breadcrumbs**: enable Rank Math breadcrumbs; confirm the theme renders the
   breadcrumb block/function on CPT singles and archives.
4. **Schema**: leave Rank Math's default Article schema on; the `Ink\Seo` seam
   refines the `@type` per CPT automatically. Spot-check a published `gedig`,
   `storie` and `artikel` with the Rich Results / Schema test — `gedig`/`storie`
   should report `CreativeWork`, `artikel` `Article`.

## Importer as a safety net

5. Run the **Rank Math importer** ("Import from Yoast SEO") **once** as a safety
   net to carry over any per-post SEO titles/descriptions/`noindex` flags that
   editors set under Yoast. This is a one-shot reconciliation, not an ongoing
   dependency — global config is set fresh (step 1), the importer only rescues
   per-post overrides.

## InkPols OG-image verification

6. Before retiring Yoast, **verify InkPols (`inkpols_uitgawe`) OG images** render
   from Rank Math: open a published issue, view source / use a social debugger,
   confirm `og:image` resolves to the issue cover (the InkPols cover meta key,
   Story 13.1) and not a Yoast fallback. InkPols is called out explicitly because
   its OG image is editorially important for sharing.

## Yoast deactivation — deliberate §14.11 override

7. **Deactivate Yoast** only after steps 1–6 verify. This is a **deliberate
   override** of the plugin transition guide (§14.11), which kept Yoast as the
   default during transition. Rank Math is now the single SEO plugin; Yoast is
   retired (do not reactivate — project-context "Never reactivate retired
   plugins … Yoast").
8. Re-run the **English-leak gate** (Story 17.4 / 18.8 Layer 2) after the SEO
   swap — Rank Math adds front-end output (breadcrumbs, meta) that must stay
   Afrikaans where user-visible.

## Notes

- Redirect integrity (old URLs → 301) is **Story 18.2**, not here.
- Production hygiene (no diagnostic SEO debug plugins on prod) is **Story 18.6**.
- Update governance for Rank Math (major-version staging gate) is **Story 18.7**.
