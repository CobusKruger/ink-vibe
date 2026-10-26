# Stage 3 class map — `assets/css/primitives.css`

The class vocabulary for every primitive in `wp-content/themes/ink-foundation/assets/css/primitives.css`, by `component_kind` (the vocabulary of `README.md` §4.1) and variant. Use it when mapping a design element to a ratified recipe. Moved from the gitignored `tmp/stage3/class-map.md` on 2026-10-26; keep it current when `primitives.css` gains or loses a class.

Convention: base class + modifier (`ink-card ink-card--feature`). Tones: add `ink-tone-{primary|accent|gold|muted|ink|success|danger}` to a pill / icon tile / avatar initials to change `--ink-tone` (pills take success/danger since 3c).
States: `.is-active` | `[aria-pressed=true]` | `[aria-selected=true]` | `[aria-current=page]` (chips/tabs); `.is-selected` | `[aria-checked=true]` | `:has(input:checked)` (selectable cards).
Block-style bindings (no inline_style any more): listed in notes. Breakpoints: base / md 768 / lg 1024 (= recipe 390 / 768 / 1440).

| component_kind | variant | class | notes |
|---|---|---|---|
| heading | h1-index | `ink-heading-h1-index` | 36/45 → 48/48 → 60/60 |
| heading | h1-entity | `ink-heading-h1-entity` | 30/37.5 → 48/48 |
| heading | h1-hero | `ink-heading-h1-hero` | home hero (HR-3) 30 → 36 → 48 |
| heading | h1-hero-landing | `ink-heading-h1-hero-landing` | community hero (HR-3) 36 → 60 |
| heading | h1-reading | `ink-heading-h1-reading` | 30 → 36 → 48 |
| heading | h1-reading-poem | `ink-heading-h1-reading-poem` | italic, -0.025em; 30 → 36 |
| heading | h2-section (+ -regular) | `ink-heading-h2-section` | HR-1 600; 30/36 → 36/40 |
| heading | h2-sub (+ -regular) | `ink-heading-h2-sub` | 24/32 → 30/36 |
| heading | h2-block (+ -regular) | `ink-heading-h2-block` | 24/32 |
| heading | h2-on-dark | `ink-heading-h2-on-dark` | HR-4 30 → 36 → 48; white; cream inside `ink-band--dark` or with `ink-heading--on-ink` |
| heading | h3-spotlight | `ink-heading-h3-spotlight` | 20/27.5 → 24/32 |
| heading | h3 (+ -regular) | `ink-heading-h3` | 20/28 |
| heading | h3-card | `ink-heading-h3-card` | HR-2 weight 400, 20/27.5 |
| heading | h4 (+ -regular) | `ink-heading-h4` | 18/28 |
| heading | h5 / h5-list-item | `ink-heading-h5` / `ink-heading-h5-list-item` | 16/24; list-item 500 |
| heading | base | `:where(h1…h6)` | weight 600 element default |
| section-band | base | `ink-band` | py 64 (default when no Lovable ref), px 0 |
| section-band | sm / lg / step / flush | `ink-band--sm` / `--lg` / `--step` / `--flush` | 48 / 80 / 48→64 / 0 (py on container) |
| section-band | hero / poem / landing (3c) | `ink-band--hero` / `--poem` / `--landing` | 48→80 (home hero, uitdaging hero) / 64→80 (gedig body) / 80→112 (community hero) |
| section-band | tint-cream / tint-stone | `ink-band--cream` / `ink-band--stone` | SB-3 secondary/30 (same value) |
| section-band | intro / strip | `ink-band--intro` / `ink-band--strip` | cream + border-b / cream + border-y |
| section-band | tint-stone-soft | `ink-band--stone-soft` | secondary/20 |
| section-band | dark | `ink-band--dark` | ink bg, cream text, border-t |
| section-band | gradient | `ink-band--gradient` | primary/10 → bg → accent/10, border-t |
| section-band | border modifiers | `ink-band--border-y` / `--border-t` / `--border-b` | per-use |
| container | (proposed kind) | `ink-container` | max 1400, px 16, centred |
| eyebrow | base | `ink-eyebrow` | 14/20 500 .05em upper primary |
| eyebrow | accent | `ink-eyebrow ink-eyebrow--accent` | sage |
| eyebrow | tracked | `ink-eyebrow ink-eyebrow--tracked` | EB-1 12/16 .2em |
| eyebrow | muted | `ink-eyebrow ink-eyebrow--muted` | 12/16 400 muted |
| eyebrow | kicker-serif | `ink-eyebrow ink-eyebrow--kicker` | italic Lora, sentence case, icon row gap 8 |
| eyebrow | card | `ink-eyebrow ink-eyebrow--card` (+ `ink-eyebrow--gold`) | sentence case, normal tracking |
| stat-label | (split from eyebrow) | `ink-stat-label` | EB-2 12/16 upper .025em muted |
| card | base | `ink-card` | = `.wp-block-group.is-style-card`; `.wp-block-details.is-style-card` (surface only, no padding) |
| card | base-lg | `ink-card ink-card--lg` | p-8 (auth card) |
| card | shelf | `ink-card ink-card--shelf` | bg/60, primary/20 border |
| card | feature | `ink-card--feature` | radius 12, no shadow, hover lift; = `.wp-block-group.is-style-ink-card` |
| card | feature-hero | `ink-card--feature-hero` | p 24 → 32 |
| card | soft / soft-sm | `ink-card--soft` / `ink-card--soft-sm` | radius 16, shadow md, p 32 / 24 |
| card | panel / panel-sm | `ink-card--panel` / `ink-card--panel-sm` | radius 8, p 32 → 40 / p 20 |
| card | panel-lg (3c) | `ink-card--panel-lg` | panel at radius 16 (skryf success card) |
| card | selectable / selectable-lg | `ink-card--selectable` / `ink-card--selectable-lg` | 2px border; selected primary + primary/5; `--selectable` hover primary/40 (3c) |
| card | empty | `ink-card--empty` | dashed, py 64 |
| card | winner | `ink-card--winner` | gold gradient, radius 16, p 32 |
| card | sheet | `ink-sheet` | 320px side panel, border-l, shadow lg |
| pill | outline / count-chip outline | `ink-pill ink-pill--outline` | tone/40 border; tone via `ink-tone-*` |
| pill | soft / soft-xs | `ink-pill ink-pill--soft` / `ink-pill--soft-xs` | secondary fill; `--soft` + `ink-tone-*` = tone/10 fill + tone text (3c, home featured per-type pill) |
| pill | tint | `ink-pill ink-pill--tint` | tone/10, 14/20 (storie/gedig badge; gedig = `ink-tone-accent`) |
| pill | tint-sm | `ink-pill ink-pill--tint-sm` | sage/10 (NR-3), 600 |
| pill | tint-bordered | `ink-pill ink-pill--tint-bordered` | the sage chip |
| pill | hint | `ink-pill ink-pill--hint` (+ `ink-tone-accent` for gedig) | highlight/20 |
| badge | tint-lg | `ink-pill ink-pill--tint-lg` | hero badge |
| badge | tint-caps | `ink-pill ink-pill--caps` | 12/16 600 upper |
| badge | tint-xs | `ink-pill ink-pill--tint-xs` (+ `ink-tone-gold|primary`) | response badge, accent default |
| count-chip | solid / highlight | `ink-pill ink-pill--count` / `ink-pill ink-pill--highlight` | |
| toolbar | (pill floating-bar) | `ink-toolbar` | engagement bar, max-w 28rem |
| meta-item | xs / sm / md | `ink-meta` / `ink-meta ink-meta--sm` / `ink-meta ink-meta--md` | sm gap 6 (NR-4) |
| meta-row | base / tight | `ink-meta-row` / `ink-meta-row ink-meta-row--tight` | |
| avatar | base + size | `ink-avatar ink-avatar--{28,32,40,48,64,96}` | border-box (fixes content-box drift) |
| avatar | bordered (3c) | `ink-avatar--bordered` | 1px border-colour outline (Ontdek writer card: `--64 --bordered`) |
| avatar | ring / profile / hero / gold-ring | `ink-avatar--ring` / `--profile` / `--hero` / `--gold-ring` | profile 96→112, hero 128→160 |
| avatar | initials | `ink-avatar ink-avatar--initials` (+ `ink-tone-accent`) | |
| icon-tile | square / square-sm / square-lg | `ink-icon-tile` / `ink-icon-tile ink-icon-tile--sm` / `--lg` | 36 / 32 / 44 |
| icon-tile | circle / circle-lg / circle-xl | `ink-icon-tile ink-icon-tile--circle` / `--circle-lg` / `--circle-xl` | 40 / 48 / 64 (32px glyph, 3c) |
| icon-tile | gold / neutral | `ink-icon-tile--gold` / `ink-icon-tile--neutral` | gold/20; muted |
| tier-chip | base + tier | `ink-tier-chip ink-tier-chip--{goud,silwer,brons}` | sponsor chips; name Lora 500 (3c) |
| tier-label | base+chip + tier | `ink-tier-label ink-tier-label--{brons,silwer,goud,meester}` | NR-1; text uses `*-text` tokens (AA); meester = goud chip (3c) |
| gradering | — | (not defined) | `.ink-gradering` has no recipe; stays in theme.json for now |
| accent-block | base | `ink-accent-block` | |
| step-badge | base | `ink-step-badge` | |
| status-dot | base / active | `ink-status-dot` / `ink-status-dot--active` | |
| notice | base | `ink-notice ink-notice--{ok,fout,info,waarskuwing}` | NR-2 INK recipe; waarskuwing = warning tone (3c) |
| list-bullet | base | `ink-list-bullet` | |
| divider | base / label | `ink-divider` / `ink-divider--label` > `ink-divider__label` | |
| button-base | default size | `ink-btn` | 40, 8/16, 14/20, 500, radius 6, serif, focus ring, disabled |
| button-base | sm / lg / xl / cta | `ink-btn--sm` / `--lg` / `--xl` / `--cta` | wrapper: `.wp-block-button.ink-btn-{sm,lg,xl,cta}` |
| button-base | full width (form-submit) | `ink-btn--block` | wrapper `ink-btn-block` |
| button-primary | base | `ink-btn ink-btn--primary` | = `is-style-ink-primary`; the `pill` block style is unregistered (BTN-2, 3c) |
| button-primary | on-dark | `ink-btn ink-btn--xl ink-btn--on-dark` | CTA band; block style `is-style-ink-on-dark` (3c) |
| button-primary | form-submit | `ink-btn ink-btn--primary ink-btn--block` | FORMS |
| button-outline | base (2px primary) | `ink-btn ink-btn--outline` | = `is-style-ink-outline` |
| button-outline | neutral (1px) | `ink-btn ink-btn--neutral` | BTN-3 sage hover; = `is-style-ink-neutral` (bound, not registered) |
| button-outline | gold | `ink-btn ink-btn--gold` | LEG-2 |
| button-outline | on-dark | `ink-btn ink-btn--cta ink-btn--outline-on-dark` | LEG-1; block style `is-style-ink-outline-on-dark` (3c) |
| button-ghost | base / muted | `ink-btn ink-btn--sm ink-btn--ghost` / `ink-btn--ghost-muted` | = `is-style-ink-ghost` |
| button-subtle | base | `ink-btn ink-btn--subtle` | = `is-style-subtle` (bound, not registered) |
| button-sage-outline | base | `ink-btn ink-btn--lg ink-btn--sage-outline` | = `is-style-ink-sage-outline` |
| button (sage fill) | INK-only | `ink-btn ink-btn--sage` | = `is-style-ink-sage`; Lovable `sage` variant values |
| search-button | base | `ink-btn ink-btn--primary` | SRCH-1: default size is 40 |
| text button | Reply | `ink-text-button` | not a ghost; Inter |
| button-icon | base / menu / round / round-primary / highlight-tab / panel-close | `ink-icon-btn` + `--menu` / `--round` / `--round-primary` / `--highlight-tab` / `--panel-close` | Inter; `--menu` bound to the header's core nav open/close toggles (3c) |
| button-icon-label | base / sm | `ink-icon-label` / `ink-icon-label ink-icon-label--sm` | counters; active = primary |
| filter-chip | base / active | `ink-chip` (+ `.is-active`) | |
| filter-chip | sort / sort-active | `ink-chip ink-chip--sort` | |
| filter-chip | choice | `ink-chip ink-chip--choice` | selected = secondary + primary/30 ring |
| filter-chip | date / date-active | `ink-chip ink-chip--date` | CHIP-1 sage chip; active solid sage |
| tab | base / active | `ink-tab` | underline tab |
| tab | segmented / -active / -list | `ink-segmented` > `ink-segmented__tab` | shadcn Tabs |
| nav-link | base | `ink-nav-link` | bound to `.is-style-ink-header .wp-block-navigation-item__content` |
| nav-link | footer | `ink-nav-link--footer` | bound to `.is-style-ink-footer .ink-footer-lys a` |
| link-underline | base / slide | `ink-link` / `ink-link--slide`; utility `ink-underline-slide` | no underline at rest |
| link | default (3c) | `:where(a:not(.wp-element-button))` | primary, no underline (moved from theme.json `elements.link`) |
| pager | row / link (3c) | `ink-pager` > `ink-pager__link` | INK-only bordered pill prev/next (ArchiveRender + Ontdek skrywers) |
| form-field | base (= /auth, FORMS) | `ink-field`; zero-specificity default on bare text inputs/select/textarea | 16/24 → md 14/20, 40px |
| form-field | textarea | `ink-field ink-field--textarea` (or `textarea.ink-field`) | 14/20, min-h 80 |
| form-label | base / caps | `ink-label` / `ink-label ink-label--caps` | LBL-1 20px |
| form-hint | base | `ink-hint` | |
| search-field | base / with-button | `ink-search-field` / `ink-search-field ink-search-field--with-button` | 48 / 40 (SRCH-1) |
| checkbox | base / checked | `ink-checkbox` | appearance none; bare checkboxes get `accent-color` |
| choice-row | base / checked | `ink-choice` (+ `.is-selected` / `:has(input:checked)`) | checked = primary border + primary/5 (3c) |
