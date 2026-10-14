# Primitive synthesis: type and layout (heading, section-band, eyebrow)

Stage 2 synthesis for one group (README §4.7). The input is `maps/_primitive-observations.csv`,
627 of its 1,896 rows. The output is `maps/_recipes.type-layout.csv` (153 rows). The scripts are
re-runnable and live in `tmp/stage2/type-layout/`:
- `synth.py` writes the counts, the per-role Lovable recipe and the INK join (`roles.md`).
- `recipes.py` writes the CSV.
- `dump.py` writes the raw variant dumps.

The method works like this. Each Lovable instance is assigned to a **role** by its `audit_id`. The
role list is in `synth.py`, as `HEADING_ROLES`, `BAND_ROLES` and `EYEBROW_ROLES`. An INK instance
joins its role through the same page and `audit_id` (R3). Within a role, the Lovable values are the
canonical recipe. Every value here is a computed value from the register (R4).

Some colour differences are token-value deltas, not recipe drift: the same token has a different
hex on each side. They are reported once here and not repeated below:

| Token | Lovable | INK |
|---|---|---|
| ink-text | `rgb(24,29,37)` | `rgb(26,29,33)` |
| border | `rgb(231,225,218)` | `rgb(232,228,220)` |
| muted-text | `rgb(103,111,126)` | `rgb(107,114,128)` |
| surface (cream) | `rgb(250,248,245)` | `rgb(248,246,242)` |
| secondary | `rgb(240,235,230)` | `rgb(237,233,224)` |

They belong to the token owner, not to this group.

## Summary

| Kind | Rows | M (instances) | N (variants, joint) | INK variants | Lovable variants | Source definitions (distinct) | Status |
|---|---|---|---|---|---|---|---|
| heading | 406 | 316 | 77 | 51 | 26 | 90 (74) | 22 roles. 12 are canonical. HR-1 to HR-4 need a ruling |
| section-band | 162 | 122 | 65 | 39 | 26 | 40 (35) | Surfaces are canonical. The vertical rhythm (SB-1), the cream outlier (SB-3) and the gradient capture need a ruling. Vocabulary split proposed (V-2, V-3) |
| eyebrow | 59 | 47 | 21 | 11 | 12 | 12 (12) | 5 of 7 variants are canonical. EB-1 and EB-2 need a ruling. The `stat-label` kind is proposed (V-4) |

N is large because the type values are viewport-dependent. Each width of a responsive heading is
its own `declarations` string. Grouping the 26 Lovable heading variants by role gives 22 roles.
Grouping the 51 INK variants gives the same roles plus fluid in-between sizes (V-1).

---

## 1. heading

### 1.1 Root causes on the INK side

These four causes explain almost every row in §1.3. They live in `theme.json` and the size presets,
not in the page sheets.

| # | Cause | Effect (measured) |
|---|---|---|
| T-1 | `theme.json styles.elements.heading` sets **no `fontWeight`**. | Every heading without a class rule renders at the UA weight of **700**. This covers the auth h1, reading responses h2, footer h3 and every Treatment C heading (oor-ink, kontak, lidmaatskap, ledegids, inkpols). Lovable's effective default is 400 (preflight), plus an explicit `font-semibold` on 88 of 144 instances. |
| T-2 | `elements.heading` sets `lineHeight: custom.tight` (1.2) on every step. | Tailwind pairs a line height with each size: 16/24, 18/28, 20/28, 24/32, 30/36, 36/40, and 48+ at 1. Only 30px matches 1.2. So the h3 is 20/24 against Lovable's 20/28, and the h2-block is 24/28.8 against 24/32. The theme has no line-height token for each step. |
| T-3 | The `xxl` (20–24) and `xxxl` (24–32) presets are **fluid**. `hero` is `clamp(1.875rem, …, 3rem)`. | They render off-scale sizes at 390 and 768: 20.27, 21.71, 24.53, 27.42, 30.9 and 42.24. Lovable steps at the md and lg breakpoints, and 32px is never a Lovable size. |
| T-4 | Size is set in up to four layers on one element. | The home hero h1 has the element h1, the hero preset and three `!important` steps in `home.css:262/267/273`. On `profiel-identity-name`, the `has-xxl-font-size !important` rule beats `profiel.css:60`, which leaves that rule dead. |

### 1.2 Canonical roles (from Lovable measurements)

The base for every role is Lora, the ink-text colour, `font-weight 600` and `normal` style. The
format below is font-size/line-height at 390 · 768 · 1440.

| Role (variant) | 390 | 768 | 1440 | Weight | Lovable evidence | Status |
|---|---|---|---|---|---|---|
| h1-index | 36/45 | 48/48 | 60/60 | 600 | ontdek `intro-h1`, biblioteek `library-h1` | canonical |
| h1-entity | 30/37.5 | 48/48 | 48/48 | 600 | uitdaging-enkel `uitdaging-title`, skrywer `skrywer-name` | canonical |
| h1-hero | 30/37.5 | 36/40 | 48/48 | 600 | tuisblad `hero-h1` | **HR-3** |
| h1-hero-landing | 36/39.6 | 60/60 | 60/60 | 600 | gemeenskap `gemeenskap-hero-h1` | **HR-3** |
| h1-reading | 30/36 | 36/40 | 48/48 | 600 | lees-storie `storie-title` | canonical |
| h1-reading-poem | 30/36 | 36/40 | 36/40 | 600, italic, −0.025em | lees-gedig `gedig-title` | canonical |
| h2-section | 30/36 | 36/40 | 36/40 | 600 | 8 ids on 4 pages (featured, gemeenskap ×3, skryf intro, uitdaging ×3) | canonical |
| h2-sub | 24/32 | 30/36 | 30/36 | 600 | challenge-card-title ×2, sponsors-h2 ×2, uitdaging-prompt-h2 | canonical |
| h2-block | 24/32 | 24/32 | 24/32 | 600 | winner, featured-lead, card-writers-h2, reading-responses ×2, auth-heading ×2 | canonical |
| h2-on-dark | 30/36 | 36/40 **or** 48/48 | 48/48 | 600, white **or** cream | tuisblad `cta-h2`, gemeenskap `gemeenskap-cta-h2` | **HR-4** |
| h3-spotlight | 20/27.5 | 24/32 | 24/32 | 600 | tuisblad `hero-spotlight-title` | canonical |
| h3 | 20/28 | 20/28 | 20/28 | 600 | hiw-col-h3, principle-title, skryf type label, devices-h3, author-name ×2 | canonical |
| h3-card | 20/28 **or** 20/27.5 | ← | ← | 600 **or** 400 | featured-card-title + uitdaging-entry-title, against stories/workcard/library card titles | **HR-2** |
| h4 | 18/28 | 18/28 | 18/28 | 600 | uitdaging `rules-h3`, `resource-title` | canonical |
| h5 | 16/24 | 16/24 | 16/24 | 600 | footer-col-heading, highlights-panel h3 | canonical |
| h5-list-item | 16/24 | 16/24 | 16/24 | 500 | gemeenskap `card-writers-item-title` | canonical |
| *-regular (h2-section, h2-sub, h2-block, h3, h4) | same as the 600 role | | | **400** | my-profiel, skrywer, biblioteek | **HR-1** |

### 1.3 Property-level diff: INK against Lovable

The table lists only the properties that differ. The ink-text colour delta, which appears on every
row, is omitted. The full per-instance listing is in `tmp/stage2/type-layout/roles.md`.

| Role | INK instance (selector) | Differs |
|---|---|---|
| h1-index | biblioteek/opleiding `__heading` | line-height 60/75 at 768/1440 (leading 1.25 kept; Lovable 1.0) |
| h1-entity | `uitdaging-title`, `skrywer-name` | line-height 36 at 390 (Lovable 37.5); uitdaging 57.6 at 768/1440 (Lovable 48) |
| h1-hero | `hero-h1` | line-height 36 / 43.2 / 57.6 (Lovable 37.5 / 40 / 48) |
| h1-hero-landing | `gemeenskap-hero-h1` | line-height 36 at 390 (Lovable 39.6) |
| h1-reading | `storie-title` (`has-xxxxxl` preset) | **48px at every width** (Lovable 30 / 36 / 48); line-height 57.6 |
| h1-reading-poem | `gedig-title` | 36px at 390 (Lovable 30) |
| h2-section | `featured-h2` | 24.53/29.4 at 390 (fluid); line-height 43.2 at 768+ (also skryf, uitdaging ×2) |
| h2-section-regular | `profiel-identity-name` | **20.27 / 21.71 / 24, weight 700** (Lovable 30 / 36 / 36, weight 400). The preset beats `profiel.css` |
| h2-section-regular | opleiding `library-cta-h2` | 36 at 390 (Lovable 30) |
| h2-sub | `challenge-card-title`, `sponsors-h2` (home) | fluid 24.53 / 27.42 / 32 and 20.27 / 27.42 / 32 (Lovable 24 / 30 / 30) |
| h2-sub | oor-ink `sponsors-h2` | 32 at 768+ (Lovable 30) |
| h2-sub | uitdagings `challenge-card-title` | **20/28 at every width** (Lovable 24 / 30 / 30). Row 14 S-U |
| h2-sub-regular | `library-shelf-h2` | fluid 20.27 / 21.71 / 24 (Lovable 24 / 30 / 30). Opleiding is also at weight 600 |
| h2-sub-regular | `skrywer-oor-title` | 30 at 390 (Lovable 24) |
| h2-on-dark | tuisblad `cta-h2` | fluid 30.9 / 42.24 / 48, weight 700, line-height 1.0, surface-alt colour |
| h2-on-dark | gemeenskap `gemeenskap-cta-h2` | line-height 30 at 390, surface-alt colour |
| h2-block | `reading-responses-h2`, `auth-heading` ×3 | **weight 700** (T-1), fluid size, line-height 28.8 |
| h2-block | `winner-title`, `featured-lead-title`, `card-writers-h2` | fluid 20.27 / 21.71 at 390/768; line-height 24–29 |
| h2-block-regular | `profiel-activity-title` | 20/24, weight 700 (not in the `profiel.css:214` group) |
| h3 | hiw-col-h3, principle-title, author-name ×2 | line-height 24 (Lovable 28) |
| h3 | skryf type label | line-height 30 |
| h3-card | `featured-card-title` | line-height 24 |
| h3-card-regular | ontdek `stories-card-title` | **18/23.4** (Lovable 20/27.5). Biblioteek and opleiding match |
| h3-regular | profiel about/glance/recent | 18/21.6 (Lovable 20/28) |
| h3-regular | profiel edit/membership-renew | weight 700; renew is 24px |
| h5 | `footer-col-heading` | weight 700, line-height 19.2 |
| h5-list-item | `card-writers-item-title` | **Inter** (Lovable Lora) |

INK-only headings have no Lovable instance. Their proposed roles are input for the Stage 4
OFF-RECIPE check, not canonical evidence:

| Proposed role | INK ids | What they render today |
|---|---|---|
| h1-index | oor-ink-hero-h1, kontak-h1, lidmaatskap-h1, inkpols-heading | 32/38.4 at weight 700, pinned with `!important` (inkpols is fluid) |
| h1-index | ledegids-h1 | xxl fluid at weight 700 |
| h1-index | uitdagings-h1 | 36/43.2 · 48/57.6 · 60/72 |
| h2-section | oor-ink org/kontak/meer, lidmaatskap benefits/faq | 24 at weight 700 |
| h2-section | lidmaatskap-cta | 32 at weight 700 |
| h2-block | ontdek stories-heading, writers-heading; ledegids writers-heading | fluid xxl at weight 700 |
| h3 | surfaces-title, lesergradering-title, plan-term, membership-plan-label | 20/24 at weight 700 |
| h3-card-regular | writers-card-name ×2 | 18/27 |
| *(cross-kind)* | skrywer-prestasies-title | INK renders an h2 at 20/24/700. Lovable renders an uppercase muted label (`eyebrow` → `stat-label`) |

### 1.4 Duplication: definitions Stage 3 would delete

Once the role classes exist in `primitives.css`, Stage 3 would remove:

- **`theme.json` styles.css page rules** (66 source rows), all restating a role:
  - `.ink-gemeenskap-{hiw,principles,cta} h2` (three identical bodies), `.ink-gemeenskap-hero h1`, `.ink-gemeenskap-card__head h2`, `.ink-gemeenskap-hiw h3`, `.ink-gemeenskap-principle h3`
  - `.ink-uitdaging__hero h1`, `.ink-uitdaging__inskrywings-titel`, `.ink-uitdaging-cta__titel`
  - `.ink-skrywerprofiel__{naam,oor-titel,vasgespel-titel,cta-titel}`, each with a 768 media copy. `vasgespel-titel` and `cta-titel` are identical. `__lesergradering-titel` is defined twice
  - `.ink-{biblioteek,opleiding}__heading` (3 breakpoints each, identical), `__titel` (identical pair), `__uitgelig-titel` against `__rak-titel` (they differ only in weight, 400 against 600), `.ink-opleiding-bydra__titel`
  - `.ink-uitdagings__heading` (3 breakpoints), `__titel`
  - `.ink-ontdek-intro h1`, `.ink-ontdek-{werke,skrywers}__heading` (identical), `__title`, `__naam`
  - `.ink-outeur-kaart__naam`, `.ink-highlights-panel__title`, `.ink-volg-voer__heading`
  - the `!important` pins: `.ink-{oor-ink-hero,kontak-hero,lidmaatskap-hero} h1` and `.ink-lidmaatskap-cta h2` at 32px (4 copies); `.ink-oor-ink-{org,kontak,meer} h2`, `.ink-lidmaatskap-{benefits,faq} h2` and `auth.css:37` at 24px
- **`home.css`** (14 rows, front page only, LATENT on globally registered blocks):
  - `:262/267/273` (hero steps), `:388/461/561` (challenge title), `:693`, `:824/833`, `:988/1022`
  - `:1123/1133` `.ink-borg-strook__titel`, which **duplicates** `.ink-borg-erkenning__titel` (theme.json) with different values: tokens against hard-coded 24/32 `!important`
  - `:1353`
- **`profiel.css`**: `:60` (dead), `:214` (panel group), `:236`, `:959`, `:1079`.
- **`skryf.css`**: `:29` and `:35` (two sizes for one selector), `:184`.
- **`patterns/hero.php:36`** and **`gemeenskap.php:59`**: inline weight and line-height attributes.
- `wp-login-brand.css:31` stays. It styles the core login screen (row 11) and is outside the theme heading system.

---

## 2. section-band

### 2.1 What the kind actually holds (V-2, V-3)

In Lovable, a section is **two layers**. The outer `<section>` carries the surface: background,
border and sometimes `py`. An inner `.container px-4` carries the gutter, the max-width and,
for every cream band, the `py`. The register captured only the outer layer. INK puts everything
on one `alignfull` group, with a 24px gutter on the band itself. So:

- On both sides, the outer-layer padding-x is not comparable to the other side's gutter. The
  canonical gutter is **16px** on the container (9 Lovable instances), against INK's 24px.
- The padding-y of cream bands is **unmeasured** on Lovable, because it sits on the container. It
  needs a capture before Stage 3.
- 16 of the 51 Lovable rows and about 35 of the 71 INK rows are **not bands**. They are listed
  in V-3.

### 2.2 Canonical recipe

The base for every band is `display:block`, padding-x 0 on the band, radius 0, the border token
for the border colour, and a transparent background.

| Variant | Background | Border-top | Padding-y | Lovable evidence | Status |
|---|---|---|---|---|---|
| base | transparent | 0 | 64 (default) | 17 content sections | padding: **SB-1** |
| sm | — | — | 48 | reading-body, responses, author-band, header (lees-storie and lees-gedig) | **SB-1** |
| lg | — | — | 80 | gemeenskap value/spotlight/principles, tuisblad cta, hero at 768+ | **SB-1** |
| tint-cream | cream at 0.4 | — | on the container (unmeasured) | 9 instances on 6 pages; skrywer-werk is at 0.3 | **SB-3** |
| intro | tint-cream | 0, plus a bottom rule (not captured) | — | gemeenskap-hero, profiel-identity, ontdek intro, library-intro | canonical |
| strip | tint-cream | 1px (border-y) | — | gemeenskap stats + hiw, skrywer stats, library-cta, skrywer-werk | canonical |
| tint-stone | secondary at 0.3 | per use: 1px or 0 | 48 or 64 | challenge, resources, author-band ×2, reading-header | canonical |
| tint-stone-soft | secondary at 0.2 | 0 | 64 | sponsors (tuisblad, oor-ink) | canonical |
| dark | ink-text | 1px | on the container | gemeenskap-cta | canonical |
| gradient | gradient from primary/10 through background to accent/10 | 1px | 64 | uitdaging-cta | gradient value: needs a capture |
| *(container, proposed)* | — | — | gutter **16px** | 9 `.container px-4` sections | canonical |

Lovable's padding-y histogram, counted over content and tint bands at each viewport:

| Viewport | 40 | 48 | 56 | 64 | 80 |
|---|---|---|---|---|---|
| 390 | 2 | 10 | 0 | 9 | 4 |
| 768 | 1 | 6 | 1 | 11 | 6 |
| 1440 | 1 | 6 | 1 | 11 | 6 |

### 2.3 Property-level diff: INK against Lovable

These rows exclude padding-x 24 against 0/16 (§2.1) and the token deltas.

| Band | INK (defined in) | Differs |
|---|---|---|
| ontdek `intro` | `archive-intro.php:9` inline | **no cream tint and no bottom rule**. Its padding sits on the band |
| gemeenskap `gemeenskap-hero` | theme.json + inline | no cream tint. 80/112 on the band |
| profiel `profiel-identity` | `profiel.css:30` | matches the surface at 0.4 (token delta). Padding 40 on the band |
| gemeenskap `gemeenskap-hiw` | theme.json:162 | no tint, no border-top. 80 on the band |
| skrywer `skrywer-stats` / `skrywer-werk` | theme.json | stats matches (a grid). werk has no tint and is a flex div |
| tuisblad `hero` | `hero.php:26`, `home.css:68` | 64 at every width (Lovable 48 / 80 / 80) |
| tuisblad `cta` | `cta-band.php:23` | bottom 64. This is **DIVERGENCE (accepted)**, `cta-band.php:21` |
| uitdaging `prompt` / `entries` | `reading-uitdaging.php:107/117` inline | 24/24 and 24/64 (Lovable 64/64) |
| lees-* `reading-body` / `responses` | pattern inline | 24/64 and 48/64 (Lovable 48/48) |
| lees-gedig `reading-header` | `reading-gedig.php:85` | **surface at 0.5**, where `reading-storie.php` uses secondary at 0.3 (two inline recipes) |
| ontdek `stories` | inline | 16/64 (Lovable 40/40, then 56/56) |
| profiel `tabs-section` | `my-profiel.php:195` | 48 (Lovable 40) |
| gemeenskap `value` | `gemeenskap.php:83` | **0** vertical padding (no rule), plus a surface-alt fill |
| gemeenskap `principles` | theme.json | adds a surface-alt fill (Lovable transparent) |
| gemeenskap `cta` (dark) | theme.json + preset | no border-top |
| oor-ink `sponsors` | inline | **transparent** (Lovable secondary at 0.2); the home band has the tint |
| uitdaging `cta` (gradient) | theme.json | a surface fill under the gradient (Lovable transparent plus gradient) |

**Duplication and deletion targets:**
- `theme.json .ink-gemeenskap-{hiw,principles,cta}` padding: three identical bodies.
- `home.css:509` `.ink-feature-band`, `:793`, `:1090` `.ink-borg-band`.
- `profiel.css:30`.
- `theme.json .ink-uitdaging-cta`, and `.ink-skrywerprofiel__{statistieke,vasgespel,cta}` with its media copy.
- `.ink-opleiding-bydra`.
- About 20 per-pattern inline `style` padding attributes (hero, front-page, borg-strook, biblioteek, opleiding, inkpols, ledegids, kontak, lidmaatskap ×5, my-profiel, reading-* ×6, skryf, auth ×3, oor-ink ×4). Each one restates a rhythm step.

---

## 3. eyebrow

| Variant | Family | Size/LH | Weight | Tracking | Case | Colour | Lovable evidence | Status |
|---|---|---|---|---|---|---|---|---|
| base (section) | Inter | 14/20 | 500 | 0.05em | upper | primary | featured, resources, entries | canonical |
| accent | | | | | | accent (sage) | sponsors-eyebrow ×2 | canonical |
| tracked | Inter | 12/16 | 500 | **0.2em** | upper | primary | gemeenskap hero, spotlight, principles | **EB-1** |
| muted | Inter | 12/16 | 400 | 0.05em | upper | muted-text | profiel-identity-eyebrow, spotlight-card-label | canonical |
| kicker-serif | Lora *italic* | 14/20 (row 16/24, icon 16, gap 8) | 400 | 0.025em | none | primary | skrywer werk-kicker, library-eyebrow, ontdek intro-eyebrow | canonical |
| card | Inter | 14/20 | 500 | normal | none | primary, or gold for the winner | challenge-card-eyebrow ×2, winner-eyebrow-text | canonical |
| stat-label *(proposed kind)* | Inter | 12/16 | 400 | 0.025em | upper | muted-text | 6 of 8 stat labels | canonical |
| stat-label-lg | Inter | 14/20 | 400 | 0.05em | upper | muted-text | gemeenskap stat-label (section MISSING on INK) | **EB-2** |

**INK against Lovable:**

| Variant | INK instance | Differs |
|---|---|---|
| base | featured-eyebrow (`home.css:815`) | line-height 21, tracking **0.08em** |
| base | sponsors (`home.css:1114`, theme.json `.ink-borg-erkenning__etiket`) | line-height 21, tracking **0.08em**. The two bodies are **identical duplicates** |
| tracked | gemeenskap ×2 | line-height 18 |
| tracked | oor-ink and kontak (proposed role) | line-height 18 |
| muted | profiel-identity-eyebrow (`profiel.css:53`) | line-height 18, tracking 0.08em |
| kicker-serif | ontdek intro-eyebrow | line-height 21, no tracking |
| kicker-serif | skrywer kicker | matches |
| kicker-serif | opleiding `.ink-opleiding__eyebrow` | 16/24 (Lovable 14/20) |
| card | challenge-card-eyebrow (`home.css:555`) | line-height 21 |
| card | winner (`home.css:664`) | **Lora 600** (Lovable Inter 500) |
| stat-label | skrywer `__stat-etiket` | **matches** |
| stat-label | profiel `dt` (`profiel.css:303`) | line-height 18, tracking 0.05em |

The 18px and 21px line heights all have the same cause: INK body line-height 1.5 is inherited,
while Tailwind's `text-xs` and `text-sm` set 16 and 20.

**Duplication and deletion targets:**
- `home.css:555`, `:664`, `:815` and `:1114`, plus theme.json `.ink-borg-erkenning__etiket`, which is identical to `:1114`.
- `profiel.css:53`, `:303` and `:826`. `:826` is identical to `:53` apart from the colour.
- theme.json `.ink-ontdek-intro__boskrif`, `.ink-skrywerprofiel__{stat-etiket,vasgespel-kicker}` and `.ink-opleiding__eyebrow`.
- The eyebrow recipe is retyped as inline block attributes in `gemeenskap.php:55` and `:197`, `kontak.php:32` and the oor-ink hero. That is 4 copies with no class.

---

## 4. Needs-ruling items

These are new. None of them repeats an item already in stage-1-progress "Owner decisions needed".

| ID | Question | Options (measured) | Recommendation |
|---|---|---|---|
| **HR-1** | Lovable sets the same heading size at **400** on my-profiel, skrywer and biblioteek, and at **600** elsewhere. This covers h2-section, h2-sub, h2-block, h3 and h4. | (a) keep both, as named `-regular` variants by surface; (b) unify on 600 (88 of 144 instances, every h1); (c) unify on 400 | (a) or (b). This is a design-language call. |
| **HR-2** | The work-card title is one component with two treatments. | (a) 20/28 at 600 (tuisblad featured, uitdaging entries, 18 instances); (b) 20/27.5 at 400 (ontdek, skrywer, biblioteek, 24 instances) | Needs an owner call. (b) is the larger set. |
| **HR-3** | The two landing-hero h1 scales differ. | home 30/36/48 against gemeenskap 36/60/60 (`text-4xl md:text-6xl leading-[1.1]`) | Keep both as named variants, or pick one. |
| **HR-4** | The CTA heading on a dark or brand band has two scales and two colours. | tuisblad 30/36/48 in white (primary-foreground, on terracotta) against gemeenskap 30/48/48 in cream (background, on ink) | Size: pick one. Colour can follow the surface. INK uses surface-alt for both. |
| **SB-1** | Section vertical rhythm. | (a) three steps, sm 48, md 64 and lg 80, chosen per section as Lovable has them; (b) a single 64; (c) a responsive 48 → 64 | (a). All three are Lovable-measured. The reading pages are consistently sm. |
| **SB-3** | The cream tint opacity. | 0.4 (8 of 9) against 0.3 (skrywer-werk) | 0.4 |
| **EB-1** | There are two section-eyebrow recipes. | sm, 0.05em (tuisblad, uitdaging) against xs, 0.2em (gemeenskap; copied to oor-ink and kontak) | Keep `tracked` as a hero-only variant, or fold it into base. |
| **EB-2** | The stat-label outliers. | gemeenskap stats (sm, 0.05em); skrywer-prestasies-title (Lora, 0.05em; INK renders it as an h2) | Fold both into stat-label; decide the INK prestasies element. |

The section-band gradient (`background-image`) and the cream-band container `py` are not ruling
questions. They are gaps in the captured property set. Capture them before Stage 3 (R4).

Related items that are already pending in stage-1-progress and are not re-raised here:
- row 05 F10 (the "The Prompt" h2);
- the row 08a copy for the "Biblioteek" h1;
- row 14 S-U11/12 (how far the list card follows Lovable; covers the uitdagings `challenge-card-title` at 20px);
- row 12, the 404 heading.

## 5. Vocabulary problems (proposals; the register is not renamed)

| # | Problem | Proposal |
|---|---|---|
| V-1 | `heading` is one kind with 22 roles. Its variant count is dominated by viewport and role, not by drift. | Keep the kind. Add a `role` column, or a `variant` vocabulary using the role names in §1.2, so the register can be joined without the script. |
| V-2 | `section-band` conflates the surface (outer section) and the layout container (inner `.container`: gutter, max-width, sometimes py). | Split it into `section-band` (background, border, rhythm) and a new kind, `container` (gutter 16, max-width). |
| V-3 | `section-band` collects things that are not bands. | Re-file them: header and footer → chrome, owned by row 00 (`is-style-ink-header`/`-footer` block styles); ontdek `tabs` → a sticky bar; tuisblad `cta-band` (rounded-3xl gradient panel) → `card`; the auth `min-h-screen` wrapper → a page frame; `uitdaging-hero` / `skrywer-kop` → hero wrappers; INK block-root and archive `<section>`s, pattern shells, `skrywer-profiel`, `sponsors-inner`, `winner`, `search` → drop them, as rows 02/12–18 already do. |
| V-4 | `eyebrow` holds two components: section kickers (coloured, above a heading) and muted uppercase **stat labels** (under a number). | Add a new kind, `stat-label`. `skrywer-prestasies-title` crosses kinds: Lovable has it as a label, INK as an h2. |
| V-5 | The `eyebrow` `card` variant is not uppercase and sits inside cards as meta text. | Keep it as an eyebrow variant for now, or merge it into `meta-item` if Stage 3 prefers. Flagged, not proposed. |
| V-6 | The capture property set misses `border-bottom-*` and `background-image` for bands. | Extend `run.sh primitives` for `section-band`, so the intro bottom rule and the gradient band can be canonised from measurements. |
