# Primitive synthesis: controls group (Stage 2)

Input: `maps/_primitive-observations.csv`, 19 kinds, 635 rows (228 source, 407 instance). The brief said 639; this run counts 635. Output recipes: `maps/_recipes.controls.csv` (202 rows, 7 `needs-ruling`).
Scripts (re-runnable): `tmp/stage2/controls/analyse.py` (variant clustering), `enrich.py` (pulls height/gap/borders/letter-spacing from `tmp/fidelity-captures/` on audit_id; these are columns the register does not carry), `compact.py` (size × colour signature per instance), `build_recipes.py` (emits the CSV).

Heights come from the capture's rendered rect, not `cs.height`. INK controls are `content-box`, so their computed `height` leaves out the padding.

## Summary

| kind | M instances | N variants | ink N | lovable N | status |
|---|---|---|---|---|---|
| button-size (shared base) | 0 (sizes measured on 51 button instances) | 4 sizes | — | 4 | canonical |
| button-primary | 55 | 24 | 17 | 7 | canonical (`pill` needs-ruling) |
| button-outline | 32 | 22 | 16 | 8 | canonical (`neutral` hover, `on-dark` need ruling) |
| button-ghost | 16 | 6 | 2 | 4 | canonical |
| button-subtle | 14 | 6 | 4 | 2 | canonical |
| button-sage-outline | 4 | 2 | 1 | 1 | canonical |
| button-icon | 22 | 15 | 7 | 8 | canonical (base + 4 named modifiers) |
| button-icon-label | 13 | 4 | 2 | 2 | canonical |
| search-button | 3 | 3 | 3 | 0 | **needs-ruling** |
| filter-chip | 46 | 13 | 8 | 5 | canonical (`date` needs-ruling) |
| tab | 35 | 7 | 2 | 5 | canonical (two recipes; F7/M4 pending) |
| nav-link | 39 | 6 | 3 | 3 | canonical |
| link-underline | 14 | 8 | 5 | 4 | canonical |
| form-field | 59 | 31 | 20 | 11 | canonical |
| form-label | 27 | 7 | 4 | 3 | canonical (line-height needs-ruling) |
| form-hint | 8 | 5 | 3 | 2 | canonical |
| search-field | 10 | 8 | 6 | 2 | canonical |
| checkbox | 10 | 3 | 2 | 1 | canonical |
| choice-row | 5 | 2 | 1 | 1 | canonical |

Lovable variant counts look high only because the same recipe renders at several sizes, and `display: flex` and `inline-flex` count as separate variants. Once size is factored out, every Lovable button kind has one colour recipe.

## Cross-cutting findings

**X1. All button-* kinds share one base recipe.** Every Lovable button is shadcn `buttonVariants` (`ink-lovable/src/components/ui/button.tsx:7-37`). All 51 measured instances share the same layout and differ only in colour/border/font. The base is recorded as `button-size`:

| property | base | sm | default | lg | xl |
|---|---|---|---|---|---|
| display / align / justify | inline-flex / center / center | | | | |
| gap | 8px | | | | |
| font-weight | 500 | | | | |
| height | — | 36px | 40px | 44px | 48px |
| padding | — | 0 12px | 8px 16px | 0 32px | 0 40px |
| font-size / line-height | — | 14/20 | 14/20 | 16/24 | 18/28 |
| border-radius | 6px | 6px | 6px | 6px | 8px |
| focus / disabled / transition | outline 2px primary +2px offset · opacity .5 · all .15s (block-style, `functions.php:1495-1506`) | | | | |

Colour layer per kind:

| kind (Lovable variant) | bg | text | border | font | shadow |
|---|---|---|---|---|---|
| button-primary (`literary`) | primary | primary-fg (#FFF) | 0 | Lora | shadow.md |
| button-outline (`literary-outline`) | transparent | primary | 2px primary | Lora | — |
| button-outline `neutral` (`outline`) | background | foreground | 1px input | Inter | — |
| button-ghost (`ghost`) | transparent | foreground | 0 | Inter | — |
| button-subtle (`subtle`) | secondary/50 | foreground | 0 | Inter | — |
| button-sage-outline (`sage-outline`) | transparent | accent | 2px accent | Inter | — |

INK has no shared base. Size comes from inline padding (`header-main.php:89,93`), page-scoped `ink-btn-sm|lg|xl` (`home.css:196-225`), theme.json per-section rules (gemeenskap, uitdaging, oor-ink, lidmaatskap: four copies of the same 44px / 0 32px / 500 rule), and per-component replicas. Two drifts affect the whole set:
- **D-wt:** INK renders weight 400 wherever only a registered block style applies (`header-btn-join`, `header-btn-signin`, `hero-btn-primary`, `cta-btn-*`), because no `is-style-ink-*` declares a weight.
- **D-lh:** INK 14px and 18px controls inherit line-height 1.5 (21px/27px) instead of 20px/28px. This makes `header-btn-join` 37px instead of 36px and every chip 1px too tall.

**X2. Registered block styles that contradict Lovable.** `is-style-ink-ghost` (`functions.php:1558`) and `is-style-ink-sage-outline` (`:1616`) declare `font-family: display`. Lovable renders both in Inter, and both INK instances render in Lora. Following the preference order, the Lovable value wins and Stage 3 must change these two registrations.

**X3. Token deltas (ruling TOKEN-1).** These are palette-level, not recipe drift. Each one makes every INK control a few RGB units off:

| Lovable | INK |
|---|---|
| primary-foreground #FFFFFF | surface-alt rgb(253,252,250) |
| foreground rgb(24,29,37) | ink-text rgb(26,29,33) |
| background rgb(250,248,245) | surface rgb(248,246,242) |
| border/input rgb(231,225,218) | border rgb(232,228,220) |
| muted-foreground rgb(103,111,126) | muted-text rgb(107,114,128) |
| secondary rgb(240,235,230) | secondary rgb(237,233,224) |
| shadow-sm `0 1px 2px rgba(0,0,0,.05)` | shadow.sm `0 2px 12px -2px …/.06` |

The recipes store the Lovable literal and name the token. Whether the palette moves or the recipes accept the token value is for the token owner to decide.

**X4. Unstyled native controls.** Several INK `<button>`/`<input>` elements render UA defaults: Arial 13.33px (icon buttons, `opleiding` search button), white/black (`reaksies` input, `ontdek` search text), native checkboxes. `primitives.css` needs a control font/colour reset.

States (hover, checked) were never captured. Rows that give one cite either the block style or `source=token` with "not captured".

## Per kind

### button-primary — canonical, `lovable-measured`
Colour/shadow/font are uniform across 26 Lovable `literary` instances. The `on-dark` modifier (white fill, primary text, no shadow, xl) comes from `tuisblad:cta-btn-primary`.

| property | Lovable | INK variants |
|---|---|---|
| font-family | Lora | Lora; Inter (`opleiding:library-cta-btn`); WP system (`wagwoord-terugstel`, C) |
| shadow | shadow.md | missing on 9 (`auth-submit`, `kontak`, `oor-ink`, `lidmaatskap`, `reaksies`, `profiel-redigeer`, `renew`) |
| radius | 6 / 8 (xl) | 6; 8 (`renew`); 9999 (`lidmaatskap` is-style-pill); 2 (core) |
| auth-submit size | default 40 / 8 16 / **14/20** | 40 / 8 16 / **16/24** |
| library-cta | lg 44 | 48 (12px padding) |

Duplication for Stage 3 to delete (keep `functions.php:1483-1508`): `home.css:405-434`, `home.css:1380-1386` (becomes `on-dark`), `profiel.css:98,371,473/500,899/912,1022/1046`, `skryf.css:441,468`, theme.json `.ink-volg-knoppie` (+`.is-following`, `__aksies`), `.ink-reaksies__submit`, `.ink-opleiding-bydra__knoppie`, `.ink-oor-ink-kontak …`, `.ink-lidmaatskap-plans …`, `auth.css:207-223`, `kontak.css:115`. `wp-login-brand.css:64-77` is a separate login context: tokenise it rather than delete it.

### button-outline — canonical, but the kind holds two recipes
- `base` (`literary-outline`, 9 Lovable instances): 2px primary, Lora. INK matches apart from D-wt on the hero.
- `neutral` (shadcn `outline`, 5 instances: gemeenskap hero, skryf draft, oauth): 1px input, background fill, Inter. INK replicas: `.ink-gemeenskap-btn-neutral` (theme.json), `skryf.css:414-458`, `auth.css:232-256`.
- `gold`: `tuisblad:winner-btn` (gold/30 border). INK renders transparent + Lora.
- `on-dark`: **needs-ruling BTN-1**.
- Delete candidates: `profiel.css:110,696`, `home.css:753-776,1391-1397`, theme.json `.ink-skrywerprofiel__deel|__cta-ontdek`, `__leeg-skoon`. The pager `__vorige/__volgende` are rounded-full and filed here; see V1.

### button-ghost — canonical
Always sm, Inter, foreground. `muted` modifier: `skryf-change-type`, `activity-read`. INK header sign-in renders Lora 400 (X2, D-wt). `profiel.css:251` is a replica. The two Lovable "Reply" rows are bare text buttons (0 padding, 20px, weight 400) and are not ghost buttons; see V2.

### button-subtle — canonical
One Lovable recipe. INK uses sm padding 0 16 instead of 0 12 and weight 400 on 3 of 5 instances. There is no registered block style: `is-style-subtle` lives only in `profiel.css:141`, which is page-scoped. Replicas: `profiel.css:473,593,723,1022`, theme.json `.ink-outeur-kaart__sien-alle`. Stage 3 should register the style.

### button-sage-outline — canonical
lg, 2px accent, Inter (X2). Replicas: `home.css:1243-1265` and theme.json `.ink-borg-erkenning__cta`. They are byte-identical in effect, so this is duplication.

### button-icon — canonical base + modifiers
The Lovable base is the bare icon action (bookmark/share: 0 padding, 20px, muted-fg). Named modifiers, all lovable-measured: `menu-toggle` (p-2, 40px), `round` (32px circle), `highlight-tab`, `panel-close`. INK differences: menu toggle is 24px with no padding (core navigation CSS); all reading icons use Arial 13.33px (X4). See V3.

### button-icon-label — canonical
Engagement counters: 16/24, gap 8, muted. INK `reading-comments-link` is 14/21 with gap 4. INK upvote is an inert `<span>` with no icon, which is a structural gap. `.ink-btn-icon` (`home.css:303` + theme.json:162, a duplicate) is a separate concept; see V4.

### search-button — **needs-ruling SRCH-1**
No Lovable instance. Three INK variants: ontdek 43px/600/14px; biblioteek 50px/500/UA 13.33px; opleiding 39px/Arial. This is the same question as the pending row 08a "Soek button SCOPE?". If kept, the recommendation is button-primary + size default. Either way, the three theme.json definitions collapse to one.

### filter-chip — canonical, four sub-recipes
`base`/`active` (filter, 38px, 1px border); `sort` (32px, no border, secondary when active); `choice` (response type, 36px, secondary/50, weight 500). INK differences: D-lh (+1px); biblioteek/opleiding inactive fill is transparent instead of background; response-type chips are weight 400. `date` chips (12/18, accent active) are INK-only: **needs-ruling CHIP-1**. Duplication: `.ink-biblioteek__filter-knoppie` and `.ink-opleiding__filter-knoppie` (theme.json) are identical copies of `.ink-ontdek-werke__filter-knoppie`.

### tab — canonical, two recipes
`base` = underline TabButton (ontdek): 54px, 16/20 padding, 2px primary bottom when active. INK is 51px (line-height normal). Lovable also uses `segmented` (shadcn Tabs) on my-profiel and auth; INK has no segmented tab. Which recipe applies there is already pending (row 02 F7; rows 09a/b M4), so it is not re-raised here. Duplication: `profiel.css:164-184` is a near-copy of theme.json `.ink-ontdek-tabs__knoppie` and differs only in the cursor.

### nav-link — canonical
Header: 16/24 muted, hover → foreground + underline-slide. Footer/back links: 14/20 muted, no slide. INK matches except D-lh in the footer and TOKEN-1. The header `::after` (`functions.php` ink-header) duplicates `.ink-underline-slide` (`home.css:106-124`).

### link-underline — canonical
Primary text, Inter 400, size inherited, **no underline at rest**, underline on hover. INK auth links are underlined at rest by theme.json `elements.link`, which is drift. The `slide` modifier (featured "all" link) exists INK-side only in `home.css`, which is page-scoped. Its other definitions: `auth.css:96-102`, theme.json `.ink-skrywerprofiel__sien-alle`, `.ink-uitdagings__lees`. See V5.

### form-field — canonical
Input: 40px, 8/12 padding, 1px input border, radius 6, background, Inter. Font is **16/24 at 390 and 14/20 at 768|1440** (shadcn `text-base md:text-sm`). INK renders 14px at 390 everywhere. Textarea: 14/20 at all widths, min-height 80. INK heights are 35 (kontak), 36 (skryf), 38 (profiel). The `reaksies` input is unstyled (white/black, 12px padding). There are five definitions of one recipe, all page-scoped except `.ink-reaksies__input`: `profiel.css:992`, `skryf.css:214-271`, `auth.css:106-131`, `kontak.css:33-60`, `wp-login-brand.css:51-58`.

### form-label — base canonical, line-height **needs-ruling LBL-1**
14px/500/foreground. Lovable conflicts: shadcn `Label` uses `leading-none` (14px) on auth, while skryf uses 20px. INK is 21px everywhere. The `caps` modifier (profile edit: 12/16 uppercase, 0.6px tracking, muted) is lovable-measured; INK uses 0.96px tracking and an 18px line. Definitions: `profiel.css:985`, `skryf.css:56,205,297`, `auth.css:74,92,175`, `kontak.css:24`.

### form-hint — canonical
12/16 muted. INK is 12/18 on all 6 instances. Four definitions (`skryf.css:313`, `auth.css:140`, `kontak.css:69,76`) for one recipe.

### search-field — canonical
48px, left padding 44 (icon), background, 1px border, 16/24 at 390 and 14/20 above. INK has three different fields: 43px/UA black text (ontdek), 50px/surface-alt/16px (biblioteek), 39px (opleiding). These are three theme.json definitions of one recipe.

### checkbox — canonical
Radix: 16px, 1px primary border, radius 4, primary fill when checked. INK uses native UA controls (`skryf.css:138,355`, `auth.css:184`), so nothing is computed.

### choice-row — canonical
flex, gap 12, padding 12, 1px border, radius 6, hover secondary/50. INK matches apart from TOKEN-1. Only one definition (`skryf.css:319,343`).

## Needs-ruling items (new; pending rulings F7, M4 and 08a "Soek" are cited, not re-raised)

| id | kind | question | options |
|---|---|---|---|
| TOKEN-1 | all | Six Lovable↔INK palette deltas + shadow-sm (X3) | (a) move INK palette to Lovable values; (b) accept INK tokens as canonical |
| BTN-1 | button-outline `on-dark` | Lovable tuisblad CTA (background fill, white text, Lora, xl) and gemeenskap CTA (transparent, background text, Inter, lg) conflict; INK tuisblad uses a 2px surface-alt border | (a) gemeenskap recipe (transparent, 1px 30% border, Inter); (b) tuisblad recipe |
| BTN-2 | button-primary `pill` | `is-style-pill` (radius full) on lidmaatskap (C page) vs `functions.php:1478` "never a pill" | (a) conform to radius 6; (b) sanction a pill variant |
| BTN-3 | button-outline `neutral` | No registered style; Lovable hover is `bg-accent` (sage) and was not measured | (a) register `is-style-ink-neutral` with the Lovable hover; (b) other hover |
| SRCH-1 | search-button | INK-only; = row 08a "Soek button SCOPE?" | (a) drop (search-as-you-type); (b) keep as button-primary default size |
| CHIP-1 | filter-chip `date` | INK-only date-archive chip (xs, accent active) | (a) fold into `sort`; (b) sanction as a `date` variant |
| LBL-1 | form-label | Line-height 14px (shadcn Label) vs 20px (skryf) | (a) 14px; (b) 20px |

## Vocabulary problems (proposals; the register is not renamed)

- **V0** `button-size` holds the shared button *base*, not just sizes. Rename it to `button-base`, with size variants.
- **V1** `button-outline` is two components: `literary-outline` (2px primary, Lora) and shadcn `outline` (1px neutral, Inter). Split off `button-outline-neutral`. The rounded-full archive pager links filed here (`__vorige/__volgende`) are a pager component, not a button-outline.
- **V2** Lovable's bare "Reply" text button (0 padding, weight 400) is not a ghost button. It needs a `text-button` kind, or should be filed as `link-underline`'s muted sibling.
- **V3** `button-icon` holds at least four components (bare icon action, boxed menu toggle, round overlay button, highlights edge tab). Split `icon-action` (bare) from `button-icon` (boxed).
- **V4** `button-icon-label` mixes the `.ink-btn-icon` layout modifier on filled buttons with the bare engagement counters (like/comment/upvote). Rename the counters `engagement-action`.
- **V5** `link-underline` is a misnomer: the reference links are *not* underlined at rest. `text-link` with a `slide` modifier fits better.
- **V6** `filter-chip` holds filter, sort, response-type (a radio "choice chip") and date chips. Keep them as variants. The response-type chip is arguably a `choice-row` sibling.
- **V7** `form-label` `caps` (uppercase muted xs) is closer to `eyebrow` than to a field label.
