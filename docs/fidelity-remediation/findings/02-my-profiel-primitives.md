# 02 · my-profiel — primitive register (Phase 4b)

**Register:** `maps/_primitive-observations.my-profiel.csv` has **219 rows**: 106 Lovable, 64 INK and 49 `source`. Rows whose values are identical across viewports are collapsed, with viewports joined by `|`. A `state …` note marks a row captured only in a driven tab or the modal state.

**How the rows were built:**
- Input was `run.sh primitives --exclude-chrome` over 13 Lovable and 10 INK captures. Zero-size nodes (hidden INK panels, the closed modal) were dropped first, so instances are registered only where they render.
- The tool's per-viewport and per-state rows (T6) were then collapsed by hand.
- Lovable `selector` values are the full Tailwind class lists the tool emits.

## Hand resolution (README §4.1)

**Generic `button` rows resolved:**
- Lovable `variant="subtle"` → `button-subtle` (×7).
- Radix TabsTrigger → `tab`.
- Selectable plan `<button>` tiles → `card` (note: "selectable plan tile", including a selected variant).
- INK bare `<button>`s and `<a>`s → `button-outline` (`__wysig`, `.ink-volg-lys__ontdek`), `button-ghost` (`.ink-profiel-oormy__wysig`), `button-subtle` (`is-style-subtle`, `__merk-alles`, `__kanselleer`), `button-primary` (`.ink-bydraes__nuwe`, `__stoor`, and the `is-style-pill` renew CTA).

**Classifier slips fixed:**
- Radix Avatar root (`pill`) → `avatar`.
- Tab count badges (`pill`) → `count-chip`.
- Unread / active dots (`list-bullet`) → `status-dot` (**new kind**).
- Lovable unfollow ghost icon button → `button-icon`.
- "Change photo" `<label>` → `link-underline`.

**Dropped:**
- T6 `tier-label` slips on the `__gradering` wrapper group and the badge's two inner spans. The badge `.ink-gradering` itself is kept as `tier-label`.
- 12 INK `<section>`s the tool tags as `section-band`: the tab panels and the ink-core block roots. They have no fill, border or band padding.

**Added (the tool misses them):**
- Dashed empty-state boxes → `card`: 3 Lovable `EmptyHint`, 4 INK `__leeg`.
- The INK modal window `.ink-profiel-redigeer__venster` → `card`.

## Vocabulary — added during row 02

```
status-dot           small filled dot that signals state (unread, active), not a list
                     marker; Lovable h-2 w-2 rounded-full spans, INK ::before/::after
                     pseudo-elements. The tool files it as list-bullet
```

Existing kinds reused: `count-chip` (tab count badges), `button-subtle`, `card` (plan tiles + empty boxes), `link-underline`.

## Declaration comparison (source rows)

| Kind | Definitions in play | What differs |
|---|---|---|
| button-primary | registered `is-style-ink-primary` (functions.php:1488) + row override profiel.css:98 · `.ink-bydraes__nuwe` :371 · `.ink-bydraes__sien` :473/500 · `.ink-profiel-redigeer__stoor` :1022/1046 · renew pill = `is-style-pill` (functions.php:1466) + colour attrs + `#lidmaatskap` overrides :899/912 | **6 definitions.** Heights 32/40/44px. Shadow present on 2 of 6. The renew CTA is not `ink-primary` at all (radius lg, line-height 1.3, wraps). Weight 500 on the replicas but 400 on the registered button (measured: `profiel-identity-new-btn` font-weight 400). |
| button-subtle | `.wp-block-button.is-style-subtle` profiel.css:141 (**unregistered style name**) · `.ink-bydraes__wysig` :485 · `.ink-volg-lys__ontdek-kop` :593 · `.ink-kennisgewings__merk-alles` :723 · `.ink-profiel-redigeer__kanselleer` :1036 | **5 definitions.** Heights 32/36/40px. Font-family set on 3 of 5. Weight medium on 2 of 5 (measured `profiel-notif-mark-btn` 400 vs Lovable 500). |
| button-outline | registered `is-style-ink-outline` (functions.php:1517) · `.ink-profiel-identiteit__wysig` :110 · `.ink-volg-lys__ontdek` :696 | 2 bare replicas of an unused registered style. They agree on colour, border, radius and font, and differ on gap / cursor / margin. |
| button-ghost | registered `is-style-ink-ghost` (functions.php:1555) · `.ink-profiel-oormy__wysig` :251 | The replica uses font-family **body**; the registered style uses **display**. The comment at profiel.css:247 claims it copies the recipe. |
| card | `.wp-block-group.is-style-card` (functions.php:1431) · `.ink-bydraes__item` :408 (identical copy) · `.ink-volg-lys__item` :627 (s-16 padding) · `.ink-leeslys__item` :540 (no shadow) · `.ink-kennisgewings__lys` :753 and theme.json `.ink-volg-voer__list` (list-as-card, no shadow) · `#lidmaatskap` override :867 (2px border) · modal `__venster` :946 (radius xl, shadow lg, no border) | **8 definitions.** Padding, shadow and border differ; one is an exact duplicate. |
| card (empty box) | `.ink-bydraes__leeg` :523 = `.ink-kennisgewings__leeg` :814 · `.ink-leeslys__leeg` :555 = `.ink-volg-lys__leeg` :676 | 4 copies, 2 variants: the pairs differ only in muted text colour. The Activity empty state has **no** box (F27). |
| heading (panel h2) | profiel.css:214 group (4 selectors) vs theme.json `.ink-volg-voer__heading` | The Aktiwiteit h2 falls outside the group: 20px / 700 vs 24px / 400 (measured). |
| heading (h1 name) | profiel.css:60/1079 vs theme.json `has-xxl-font-size` | The profiel.css font-size is **dead** (preset class `!important`). Measured 24px / 700; Lovable 36px / 400. |
| tab | `.ink-profiel-tabs__knoppie` profiel.css:164 vs theme.json `.ink-ontdek-tabs__knoppie` | Near-duplicate: the profiel copy lacks `cursor:pointer`; weight is a var vs a literal 500. |
| eyebrow | `__etiket` :53 (0.08em, muted) · `.ink-lidmaatskap-status__etiket` :826 (0.08em, primary) · stat `dt` :303 (0.05em) · modal label :985 (0.08em) | 4 definitions, 2 letter-spacings. Lovable uses 0.6px and 0.3px (measured). |
| avatar | :39 (7rem + 4px border, content-box → 120px) · :638 (3rem) · theme.json `.ink-volg-voer__avatar` (2.5rem) · :970 (4rem) | Size per context, as on Lovable. Only the identity avatar has a border (renders 120 vs Lovable 112). |
| status-dot | `.ink-profiel-onlangs__item.is-unread::before` :352 (6px, left) vs `.ink-kennisgewings__item.is-unread::after` :803 (8px, right) | The same dot in two sizes and positions. Lovable: 8px, right. |
| icon-tile | `.ink-kennisgewings__item::before` :774 | Empty tile: no icon (Lovable has a type icon). |

## Load-scope cross-check

`profiel.css` loads on `is_page('my-profiel')` (functions.php:949–963). Every page-scoped class registered here is emitted by `my-profiel.php` or an ink-core renderer that renders only there, with three exceptions:

- **`ink/leeslys` (ReadingList)** is also embedded by `patterns/profile-summary.php`, an inserter pattern reached through `archetype-d-community-utility.php`. On any page whose content uses it, the `.ink-leeslys__item` / `__leeg` treatment from profiel.css would be **inert**. Only the theme.json `.ink-leeslys__list` / `__type` / `__title` rules would apply. **Potential live bug, unverifiable from source:** it depends on page content in the database.
- **`lidmaatskap-hernu.php`** also renders on `templates/page-my-profiel-lidmaatskap.html` (row 20, skipped). There, neither the `#lidmaatskap` fixes nor `.ink-lidmaatskap-plans` apply, so the price / pill wrapping bug described at profiel.css:878–886 would presumably recur. **Not measured.**
- **`is-style-subtle`** is used only on my-profiel, so there is no load-scope gap. But it is not a registered block style, so the editor does not know it.

No class here was observed **rendered** on a page where its sheet does not load, so no confirmed live load-scope bug. The confirmed live bugs on this page are layout bugs (spine F11, F34), not load-scope bugs.

## Not captured

- Pseudo-elements: status dots and the notification icon tile (source rows only).
- Hover and active colour states.
- Every INK repeater instance: the capture user has no data (spine §5).
