# Primitive synthesis: `surfaces` group (Stage 2)

Input: `maps/_primitive-observations.csv` (merged register). This group covers 16 kinds and **634 rows**. The brief says 630; the count here comes from the register as it stands now.
Output: `maps/_recipes.surfaces.csv` (347 rows: 331 `canonical`, 16 `needs-ruling`).
Scripts (re-runnable): `tmp/stage2/surfaces/analyse.py` (variants, writes `variants.md`), `enrich.py` (sizes and type for each audit-id, read from the Stage-1 captures `tmp/fidelity-captures/`, writes `enrich.md`) and `build.py` (writes the CSV).

How values were chosen:
- Every canonical value is Lovable-measured, from the rendered computed style or the rendered box `x.w×x.h`. Nothing is read from source (R4).
- Block styles were checked by reading `functions.php:1424-1907`. The register's `defined_in` line numbers for functions.php are stale. The current lines are: `is-style-card` group **1431**, details **1451**, `is-style-ink-card` **1644/1652**, `is-style-emphasis` **1895**.
- Colours in the CSV are Lovable values. The `note` column names the INK token. See X3: several INK tokens render a slightly different value.

## Summary

| kind | M rows | N variants (ink+lov) | ink var / inst | lovable var / inst | source defs | canonical variants | status |
|---|---|---|---|---|---|---|---|
| card | 213 | 47 | 27 / 63 | 20 / 86 | 64 | base, base-lg, shelf, feature, feature-hero, soft, soft-sm, panel, panel-sm, selectable, selectable-lg, empty, winner, sheet | canonical |
| pill | 115 | 24 | 12 / 35 | 12 / 54 | 26 | outline, soft, soft-xs, tint, tint-sm, tint-bordered, hint, floating-bar | canonical; 1 needs-ruling (accent tint α) |
| meta-item | 88 | 11 | 5 / 29 | 6 / 41 | 18 | xs, sm, md | canonical; 1 needs-ruling (sm gap) |
| avatar | 59 | 15 | 7 / 19 | 8 / 26 | 14 | base (+size scale), ring, profile, hero, gold-ring, initials | canonical |
| tier-chip | 36 | 12 (effectively 3+3) | 6 / 12 | 6 / 12 | 12 | base + goud / silwer / brons | canonical |
| icon-tile | 34 | 14 | 6 / 7 | 8 / 17 | 10 | square, square-sm, square-lg, circle, circle-lg | canonical |
| badge | 22 | 10 | 4 / 4 | 6 / 9 | 9 | tint-lg, tint-caps, tint-xs | canonical |
| tier-label | 14 | 2 | 2 / 7 | 0 | 7 | (base) | **needs-ruling** (no Lovable ref) |
| meta-row | 13 | 5 | 3 / 5 | 2 / 5 | 3 | base, tight | canonical |
| accent-block | 9 | 2 (same box) | 1 / 4 | 1 / 4 | 1 | base | canonical (INK already matches) |
| step-badge | 7 | 1 | 0 (pseudo) | 1 / 6 | 1 | base | canonical |
| count-chip | 6 | 5 | 1 / 1 | 4 / 4 | 1 | outline, solid, highlight | canonical |
| status-dot | 6 | 2 | 0 (pseudo, not rendered) | 2 / 4 | 2 | base | canonical |
| notice | 6 | 3 | 3 / 3 | 0 | 3 | (base) | **needs-ruling** (no Lovable ref) |
| list-bullet | 4 | 1 | 0 | 1 / 4 | 0 | base | canonical |
| divider | 2 | 2 | 0 | 2 / 2 | 0 | base, label | canonical (INK instance not captured) |

## Cross-cutting findings (they affect every kind below)

| # | Finding | Evidence | Recipe consequence |
|---|---|---|---|
| X1 | **INK primitives are `content-box`, Lovable is `border-box`.** Any surface with a border plus an explicit size renders larger on INK. | Rendered box: winner avatar 52 vs 48, profile avatar 120 vs 112, skrywer avatar 168 vs 160 (`enrich.md`). `profiel.css:39` notes the 120. | Every recipe sets `box-sizing: border-box`. |
| X2 | **xs/sm text sits 2px/1px taller on INK.** INK applies line-height 1.5 (xs → 18px, sm → 21px). Tailwind uses 16px / 20px. | Every pill, badge and meta-item: INK pills are 26px tall vs 24px, meta-items 18px vs 16px. | Recipes pin `line-height` explicitly. Overlaps with the type-layout group. |
| X3 | **Token values drift.** Lovable → INK as rendered: card 253,253,252 → 253,252,250; border 231,225,218 → 232,228,220; muted-fg 103,111,126 → 107,114,128; secondary 240,235,230 → 237,233,224; foreground 24,29,37 → 26,29,33; background 250,248,245 → 248,246,242; **gold-muted 217,201,166 → 201,184,138** (a visible difference). | Property spreads in `variants.md`. `theme.json` palette. | The recipes cite tokens. Whether the tokens move to Lovable's values is NR-5. |
| X4 | **Shadow names collide.** INK `shadow.sm` = Lovable `shadow-card` (0 2px 12px -2px .06). The shadcn Card uses Tailwind `shadow-sm` (0 1px 2px rgba(0,0,0,.05)), and the skrywer avatar uses Tailwind `shadow-lg`. **No INK preset has either value.** | card L8 (36 inst), avatar L7 | Stage 3 needs two shadow tokens, or a ruling to approximate them. |
| X5 | **INK puts `shadow.sm` on every card at rest.** Lovable gives `feature` and `panel` cards no shadow, and `base` a much lighter one. This is the most widespread card drift: 44 of 63 INK card rows carry 0 2px 12px -2px .06. | card ink property spread | `feature`/`panel`: `box-shadow:none`; `base`: Tailwind shadow-sm. |

---

## card — 213 rows: 47 instance variants and 64 source definitions

The 20 Lovable variants are 14 genuine card types. Each one is canonised separately (CSV `variant`). The shared base for every type is `box-sizing:border-box; background: card; border-color: border`.

| variant | Lovable reference (variant / inst) | radius | padding (390 / 768 / 1440) | border | shadow at rest | INK today (variant) → delta |
|---|---|---|---|---|---|---|
| **base** (shadcn Card) | L8 / 36: my-profiel ×12, skrywer, ontdek stories-card, biblioteek ×16 | 8 | 24 (on CardContent) | 1px | TW shadow-sm | I7, I12, I23/I25, I11, I17 → **shadow.sm instead of TW sm**; I17 skrywer has no shadow; I23 biblioteek item has padding 0 on the box (inner padding not checked); I10 edit modal is r12, no border, shadow.lg (Lovable edit card is `base`) |
| base-lg | L20 / 3: auth-card | 8 | 32 | 1px | TW sm | I5 auth (is-style-card + `auth.css:54` `!important`) → shadow; I26 core `#lostpasswordform` is unbranded (row 11 load-scope bug) |
| shelf | L19 / 3: library-shelf-card | 8 | 24 | 1px primary/20 | TW sm | I22/I24 → **opaque `secondary` fill vs background/60**; shadow.sm; biblioteek box padding 0 |
| **feature** | L1/L5/L16/L17 / 19: featured cards, uitdaging entry/resource, responses | 12 | 24 | 1px | none (hover lift) | I1, I16 → **shadow.sm at rest** (the registered `is-style-ink-card` adds it); I18/I19 responses match |
| feature-hero | L1@390 + L4 / 2: hero-spotlight | 12 | 24 / 32 / 32 | 1px | none | I1@390 + I4 → padding matches; shadow.sm |
| soft | L2 / 2: challenge-card (tuisblad, uitdagings) | 16 | 32 | 1px | shadow-soft (= INK md) | I2 tuisblad matches. uitdagings I12 is r8/p24/sm: **already pending, row 14 S-U11/S-U12** |
| soft-sm | L15 / 1: uitdaging-rules | 16 | 24 | 1px | shadow-soft | no INK card instance registered |
| panel | L6/L7 / 6: gemeenskap cards | 8 | 32 / 40 / 40 | 1px | none | I5/I6 → padding matches through `theme.json` `!important` on `is-style-card`; **shadow.sm** |
| panel-sm | L13 / 1: skryf-challenges | 8 | 20 | 1px | none | I15 → padding 16 |
| selectable | L9/L10 / 3: plan tile | 8 | 16 | 2px; selected primary + primary/5 fill | none | I8 (`profiel.css:867`) → padding 24, surface fill, shadow.sm |
| selectable-lg | L12/L14 / 6: skryf type card | 12 | 24 / 28 / 28 | 2px | none | I13/I14 → padding 24 at every width; I13 (selected) adds primary + shadow.md (selected state not captured on Lovable) |
| empty | L11 / 3: EmptyHint | 8 | 64 0 | 1px **dashed** | none | I9 my-profiel matches; I21 biblioteek/opleiding pad 64 24 (Lovable /library has no empty state) |
| winner | L3 / 1 | 16 | 32 | 1px gold-muted/30 | none; gradient gold/10→gold-muted/20 | I3 → surface-alt under the gradient; gold-muted token drift (X3) |
| sheet (off-canvas) | L18 / 1: highlights panel | 0 | 0 | left 1px | shadow-elevated (= lg) | I20 matches |

Why these are canonical: every variant is Lovable-measured on an A-page. Two registered block styles back their nearest variant on everything except the shadow:
- `is-style-card` (`functions.php:1431`) = base, apart from the shadow.
- `is-style-ink-card` (`functions.php:1644-1655`) = feature plus a shadow at rest. Its hover (translateY(-4px) + shadow.lg) equals Lovable's `.card-hover`. Hover was not captured, so feature's hover row has source `block-style`.

**Unmeasured:**
- Hover on `base` cards that are clickable (library, stories). Measure in Stage 4.
- `selectable-lg` selected state.

**C-page instances:**
- I27 lidmaatskap FAQ: `core/details.is-style-card`, padding 16. The details registration (`functions.php:1451`) sets no padding.
- I7 lidmaatskap plan cards.

Neither needs a ruling, because `base` is the reference-backed sibling. Both go to Stage 4 OFF-RECIPE.

**Duplication: definitions Stage 3 deletes or folds into the variants.** Line numbers are as registered.

| canonical variant | definitions today |
|---|---|
| base | `functions.php:1431` (keep, change shadow); `functions.php:1451` details (keep); `profiel.css:408` `.ink-bydraes__item`; `:627` `.ink-volg-lys__item`; `:540` `.ink-leeslys__item`; `:753` `.ink-kennisgewings__lys`; theme.json `.ink-volg-voer__list`; theme.json `.ink-ontdek-werke__item.is-style-card,…` (a replica for raw `<li>`, because the block style cannot match an `<li>`, #223); `.ink-skrywerprofiel__prestasie`; `.ink-skrywerprofiel__vasgespel-item` (+:hover); `.ink-biblioteek__item` (+:hover); `.ink-opleiding__item` (+:hover); `profiel.css:946` `.ink-profiel-redigeer__venster` |
| base-lg | `auth.css:54/59` (`!important` padding + a hover kill aimed at a hover nothing declares); `wp-login-brand.css:42` (hex literals) |
| shelf | theme.json `.ink-biblioteek__uitgelig-item`, `.ink-opleiding__rak-item` |
| feature | `functions.php:1644` (keep, drop the shadow at rest); `home.css:441/450/470` `--kompak` and `home.css:878/887` `__kaart` (both disclosed replicas, PRIM-4); theme.json `.ink-uitdaging__inskrywing-skakel`, `.ink-reaksies__form`, `.ink-reaksies__item` |
| soft | `home.css:530` `--kenmerk`; theme.json `.ink-uitdagings__item` (r8, pending S-U11/12) |
| panel | theme.json `.ink-gemeenskap-card` (`!important` override of is-style-card) |
| panel-sm | `skryf.css:287` |
| selectable / -lg | `profiel.css:867` `#lidmaatskap .is-style-card…`; `skryf.css:91-120` |
| empty | `profiel.css:523/555/676/814` (two identical pairs); theme.json `.ink-biblioteek__leeg`, `.ink-opleiding__leeg` (the latter also sets display type) |
| winner | `home.css:611/623` |
| sheet | theme.json `.ink-highlights-panel` |

## pill — 115 rows: 24 instance variants and 26 source definitions

| variant | Lovable (inst) | padding | fill / border | type (px / lh / wt) | rendered h | INK today → delta |
|---|---|---|---|---|---|---|
| **outline** (shadcn Badge outline) | L2–L4 (32): library, skrywer genre/type, ontdek, my-profiel | 2 10 | transparent / 1px tone/40 | 12 / 16 / 600, tone text | 22 | I6 skrywer + opleiding match (2 10, h22). I3 ontdek writers/ledegids **2 12**, lh 18 → h24. I10 biblioteek **4 12** → h28 (known 08a drift) |
| soft | L1 (7): featured-card pill, uitdaging entry type | 4 10 | secondary / none | 12 / 16 / 500, ink | 24 | I5 uitdaging/uitdagings matches (h24). Tuisblad featured and ontdek works type pills are tint by **DIVERGENCE (accepted), `home.css:940-955`** and WorksArchive. Do not fix (R6) |
| soft-xs | L6 (4): resource tag | 2 8 | secondary | 12 / 16 / 500 | 20 | no INK instance (Resources section = disclosed SCOPE gap) |
| tint | L8/L11 (2): storie/gedig type badge | 4 12, gap 6 | tone/10 (primary), accent/15 | 14 / 20 / 500 | 28 | I7/I11 `.ink-lees-tipe` box matches (h28); **no gap** (6px) |
| tint-sm | L7 (1): Pinned | 2 10 | accent/15 | 12 / 16 / 600 | 20 | not registered on INK |
| tint-bordered | L5 (4): device chip | 4 12 | accent/10 + 1px accent/20 | 14 / 20 / 500 | 30 | no INK instance |
| hint | L9/L12 (2) | 8 16, gap 8 | highlight/20 (storie), accent/15 (gedig) | 14 / 20 / 400 | 36 | I8/I12 box matches; lh 21 → h37 |
| floating-bar | L10 (2) | 12 24 | card/95 + 1px border, shadow-elevated | 16 / 24 | 50 | I9 box matches; INK is fit-content (~180px) vs `max-w-md` (448 / 358) |

**Needs ruling:** the accent tint alpha. Lovable uses accent/15 on gedig-badge, gedig-hint and Pinned, but accent/10 on reading-response-badge (insight) and the device chip. Options: 15% (the reading-page badge, which INK already follows, disclosed `reading-gedig.php:23-27`) or 10% (consistent with primary/10).

**INK-only:** `.ink-uitdaging__toestand-pil` (Oop/Gesluit, 2 12, success/12). It has no Lovable counterpart. Recommend conforming it to `tint-sm` (success tone) in Stage 4.

**Duplication:**
- `.ink-skrywerprofiel__genre` ≡ `__vasgespel-tipe` (theme.json).
- `.ink-biblioteek__genre` (4 12) vs `.ink-opleiding__item-pil` (2 10): sibling archives with two recipes.
- `.ink-reaksie-bar` is defined twice, in theme.json and `reading.css:36`.
- `.ink-uitgesoekte-bydraes__pil` (`home.css:929-972`) copies `.ink-lees-tipe` by hand.
- `.ink-ontdek-werke__type.ink-lees-tipe` is a compound override.

Stage 3 keeps one `outline` rule, one `soft` rule and one `tint` rule with tone modifiers.

## badge — 22 rows: 10 instance variants and 9 source definitions

| variant | Lovable | padding | fill | type | h | INK → delta |
|---|---|---|---|---|---|---|
| tint-lg | hero-badge | 6 16 | primary/10 | 14 / 20 / 500 | 32 | I1 matches (h32) |
| tint-caps | hero-spotlight-badge, uitdaging-badge | 4 12, gap 6 | primary/10 | 12 / 16 / 600, uppercase, 0.3px; icon 14 | 24 | I2: gap 4, lh 18 → h26, tracking 0.6px, icon 16. I3 (`.ink-lees-tipe` + `__badge`): h24, no gap |
| tint-xs | reading-response-badge ×3 tones | 2 8, gap 4 | tone/10 (accent, gold, primary) | 12 / 16 / **400** | 20 | I4 `.ink-reaksies__badge`: weight 500, lh 18 → h22 |

`.ink-lees-tipe` serves pill `tint` and badge `tint-caps` alike (see V1).

## meta-item — 88 rows: 11 instance variants and 18 source definitions

| variant | Lovable (inst) | type | gap / icon | INK → delta |
|---|---|---|---|---|
| xs | L1/L4/L6 (31) | 12 / **16** / 400, muted-fg | 4 / 12px | I1/I5: **lh 18** (X2); muted-text token (X3) |
| sm | L2/L3 (9) | 14 / **20** / 400 | **6** / 16px | I2 lh 21; I3 uitdaging rows gap **8**; I4 matches |
| md | L5 (1) byline read time | 16 / 24 | 4 / 16px | INK shows the date (I2, 14/21). **Pending: row 07 S-5/S-6** |

**Needs ruling:** the `sm` gap is 6px on 7 instances and 4px on featured-card-stat and featured-lead-stat. The skrywer-meta-joined icon is 14px, not 16px.

**Duplication:** 18 definitions for 2 recipes. They are `home.css:376/384/575/975/1068` and, in theme.json:
- `.ink-ontdek-werke__leestyd` / `__teller`
- `.ink-uitdaging__sluitingsdatum-ry` ≡ `__deelnemers-ry`, identical
- `.ink-skrywerprofiel__meta` / `__vasgespel-voet` / `__vasgespel-telling`
- `.ink-reaksies__date`
- `.ink-biblioteek__item-leestyd` ≡ `.ink-opleiding__item-leestyd`, identical, including the `svg` 12px rule

## avatar — 59 rows: 15 instance variants and 14 source definitions

The shared base is `border-radius: full; object-fit: cover; box-sizing: border-box`. Lovable's measured size scale: **28 · 32 · 40 · 48 · 64 · 96 · 112 (96 at 390) · 160 (128 at 390)**.

| variant | Lovable | ring / shadow | INK → delta |
|---|---|---|---|
| base | L2 (16) | none | I2 matches (28/32/40) |
| ring | reading-author 96 | 4px background | I7 matches (surface) |
| profile | profiel-identity 112 / 96@390 | 4px background + shadow-soft | I3: **renders 120** (X1); ring is surface-alt, not background; shadow.sm, not soft |
| hero | skrywer 160 / 128@390 | 4px background + TW shadow-lg | I5: **renders 168 / 136** (X1); shadow.lg (X4) |
| gold-ring | winner 48 | 2px gold/30 | I1: **renders 52**; uses gold-muted/30 |
| initials | gemeenskap spotlight 40 | tone/20–30 fill, Lora | no INK instance (Kollig section MISSING) |

INK writers-card (I4) is 56px with a 1px border. It has no Lovable reference, because the writers tab was not paired on row 03.

**Duplication:** 13 rules each restate radius and object-fit. Stage 3 keeps one base rule plus size modifiers.

## tier-chip — 36 rows: 3+3 variants (the same 3 per page on each side) and 12 source definitions

| property | Lovable (goud / silwer / brons) | INK |
|---|---|---|
| padding, radius | 12 24, 8 | matches |
| font | **Inter 16/24 400** (goud name is text-lg) | **Lora 500**, goud 18 (row 13 finding) |
| goud fill / border | **gold**/10, gold/20 | `goud` token (= gold-muted #C9B88A)/10, /20 |
| silwer | secondary + 1px border | matches (token drift only) |
| brons | **muted/50**, no border (h48) | secondary/50 + 1px transparent border (h50) |

**Duplication:** `home.css:1166-1221` `.ink-borg-strook__chip*` ≡ theme.json `.ink-borg-erkenning__item*`, identical. On top of that there are two `:hover` scale rules on one element (`home.css:134` and `:1178`). Stage 3 keeps one family.

Cited, not re-raised: row 13 S-6/S-7 (chips as links / live data).

## icon-tile — 34 rows: 14 instance variants and 10 source definitions

| variant | Lovable | box | radius | pad | glyph | INK → delta |
|---|---|---|---|---|---|---|
| square | challenge-card-icon, winner-eyebrow-icon | **36** | 8 | 8 | **20** | I1/I2 render **32**, with a 16px glyph |
| square-sm | profiel recent/notif, skrywer prestasie | 32 | 6 | 8 | 16 | `.ink-skrywerprofiel__prestasie-ikoon` is 2em at radius md (not rendered); `profiel.css:774` is a pseudo tile with **no icon** |
| square-lg | uitdaging resource | 44 | 8 | 12 | 20 | SCOPE gap |
| circle | gemeenskap card icon, skryf indicator | 40 | full | 0 | 20 | I3 matches |
| circle-lg | skryf type card (primary / accent / ink tones) | 48 | full | 0 | 20 | I4–I6 match |

Fill is tone/10, except the gold tile at gold/20 and the neutral square-sm at muted. The definitions are all bespoke: `home.css:544/682`, theme.json gemeenskap icon (same recipe as the step-badge pseudo-element), and `skryf.css:148-178` (R8).

## tier-label — 14 rows: 2 INK variants, 0 Lovable — **needs-ruling**

- I2 (bare label): `.ink-ontdek-skrywers__gradering` ≡ `.ink-ontdek-vlak__gradering` ≡ `.ink-ontdek-soek__gradering`. That is three identical copies of xs 12/18, uppercase, 0.08em, accent.
- I1 `.ink-gradering`: Lora 16 / 600 with a star mark. This is a **different component** (see V4).

No Lovable instance exists: the writers tab is React state and stayed unpaired on row 03. The options:
- (a) canonise I2 as the most-used variant. Its description matches README row 03's from-source note.
- (b) capture Lovable's writers tab first.

## meta-row — 13 rows: 5 instance variants and 3 source definitions

| variant | Lovable | gap | type | INK → delta |
|---|---|---|---|---|
| base (stats / meta) | challenge-card-meta, featured stats | 16 | row sets 14/20 muted | I1 `home.css:569`: **gap 24**, no row type; I3 matches |
| tight (pill + meta) | featured-card-meta | 8, align center | — | I2 matches (row is 26 tall because of the pill, X2) |

## accent-block — 9 rows: 1 variant on each side, identical

Recipe: 2px primary left rule; padding 8 0 8 24. It has a single INK definition, theme.json `.ink-gemeenskap-principle`, and INK already matches. Related but separate is the registered `is-style-emphasis` (`functions.php:1895`: 4px rule, secondary fill, radius 6, padding 24), which has no Lovable instance in the register. Keep it out of this recipe.

## step-badge · count-chip · status-dot · list-bullet · divider

| kind | canonical (Lovable) | INK |
|---|---|---|
| step-badge | 32 circle, primary/10, Lora 16/24 600, primary | `.ink-gemeenskap-steps li::before` (theme.json). A pseudo-element, so not capturable. Same recipe as icon-tile circle at 32px |
| count-chip | outline = pill outline (2 10, h22); **solid** = primary fill, white, 2 10, 12/16 600, h20; highlight = 2 8 highlight fill | only `.ink-highlights-panel__count` (matches). Profile tab count badges absent: **pending row 02 F7** |
| status-dot | 8px circle, primary (unread) / accent (active) | pseudo-elements, not rendered: `profiel.css:352` **6px**, `:803` 8px |
| list-bullet | 6px circle, primary, margin-top 6 | no INK counterpart (uitdaging rules list) |
| divider | 1px border rule; label = 0 8 padding, card mask, 12/16 uppercase muted | core/separator + paragraph, not captured. Measure in Stage 4 |

## notice — 6 rows: 3 INK variants, 0 Lovable — **needs-ruling**

The only branded recipe is `.ink-auth-notice` (`auth.css:152-170`): padding 12 16; radius 6; 1px tone border; tone/10 fill; ink text 14/21. Per the comment at `auth.css:149-151`, the same recipe is re-declared as `.ink-kontak-vorm__notice` (`kontak.css`, not rendered). Row 11's core `.notice-info` is unbranded.

The options:
- (a) canonise `.ink-auth-notice` as the shared notice.
- (b) take Lovable's toast/alert styling, which was never captured, as the reference.

---

## Needs-ruling items

| # | Item | Options |
|---|---|---|
| NR-1 | tier-label recipe (no Lovable reference) | canonise INK I2 / capture Lovable writers tab first |
| NR-2 | notice recipe (no Lovable reference) | canonise `.ink-auth-notice` / derive from a Lovable alert |
| NR-3 | accent tint alpha (pill `tint`/`tint-sm`/`hint`, badge `tint-xs`) | 15% / 10% |
| NR-4 | meta-item `sm` icon gap | 6px (7 inst) / 4px (2 featured stats) |
| NR-5 | Token values (X3): card, border, muted-fg, secondary, foreground, background, **gold-muted** | move INK tokens to Lovable's values / accept INK's (gold-muted is visibly different) |
| NR-6 | Shadow tokens (X4): Tailwind shadow-sm (card base) and Tailwind shadow-lg (avatar hero) | add 2 presets / map them to the nearest existing ones |

Already pending (not raised again): row 14 S-U11/S-U12 (uitdagings list card: `soft` vs today's r8); row 13 S-6/S-7; row 07 S-5/S-6 (meta-item md); row 02 F7 (tab count chips); 08a cover image (shelf card). Accepted divergence (R6): type pills tinted on tuisblad and ontdek (`home.css:940-955`).

Unmeasured, for Stage 4:
- card hover (`base`, `feature`)
- `selectable-lg` selected state
- divider (INK)
- status-dot (INK)
- avatar initials (INK)

## Vocabulary problems (proposals; the register is unchanged)

| # | Problem | Proposal |
|---|---|---|
| V1 | **pill and badge are one component split by a tool heuristic.** Tinted rounded-full chips land in both: storie-badge is filed as `pill`, uitdaging-badge as `badge`. They share one Lovable recipe family, and both pair with INK `.ink-lees-tipe`. count-chip `outline` is literally a shadcn Badge outline. | `pill` = neutral classification chip (outline, soft). `badge` = tone-tinted (tint*, hint, solid). count-chip becomes a usage of those two, not a kind. |
| V2 | **card holds at least 14 components.** Some are not cards at all. | Keep the 14 variants. Split out `sheet` (off-canvas panel), `dialog` (INK edit-modal window, which Lovable renders as a `base` card) and `empty-state` (dashed box, no content). |
| V3 | pill `floating-bar` (the engagement bar) is a toolbar container | a new kind `toolbar`, or file it under section/layout |
| V4 | tier-label covers two components | bare uppercase label = `tier-label`; `.ink-gradering` (Lora 600 + star mark) = a new `gradering-badge`, or `badge` |
| V5 | step-badge, status-dot, list-bullet and icon-tile.circle share one shape (a filled or tinted circle) | keep them separate by meaning; Stage 3 shares one circle base |
