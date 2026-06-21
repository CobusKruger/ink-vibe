# Bundled fonts — Lora + Inter (self-hosted)

These are the self-hosted webfonts for the INK typography system (Story 1.2). The theme
loads **only these bundled files** — there is **no Google Fonts / CDN request at runtime**
(privacy/POPIA, performance, Cloudflare-locked origin).

## Provenance

| Family | Source package | Version | Upstream | License |
|---|---|---|---|---|
| Lora | `@fontsource-variable/lora` | 5.2.8 | github.com/cyrealtype/Lora-Cyrillic | SIL OFL 1.1 (`Lora-LICENSE.txt`) |
| Inter | `@fontsource-variable/inter` | 5.2.8 | github.com/rsms/inter | SIL OFL 1.1 (`Inter-LICENSE.txt`) |

Fetched from jsDelivr (`https://cdn.jsdelivr.net/npm/@fontsource-variable/<family>@5.2.8/files/…`)
on 2026-06-20. To update: bump the version, re-fetch the eight files + both `LICENSE`s, and
keep the `unicode-range`s in sync with the package's `wght.css` / `wght-italic.css`.

## Files (variable woff2, weight axis baked in)

| File | Family | Style | Weight axis | Subset |
|---|---|---|---|---|
| `lora-latin-wght-normal.woff2` | Lora | normal | 400–700 | latin |
| `lora-latin-ext-wght-normal.woff2` | Lora | normal | 400–700 | latin-ext |
| `lora-latin-wght-italic.woff2` | Lora | italic | 400–700 | latin |
| `lora-latin-ext-wght-italic.woff2` | Lora | italic | 400–700 | latin-ext |
| `inter-latin-wght-normal.woff2` | Inter | normal | 100–900 | latin |
| `inter-latin-ext-wght-normal.woff2` | Inter | normal | 100–900 | latin-ext |
| `inter-latin-wght-italic.woff2` | Inter | italic | 100–900 | latin |
| `inter-latin-ext-wght-italic.woff2` | Inter | italic | 100–900 | latin-ext |

**Afrikaans coverage:** every Afrikaans diacritic (ê ë î ï ô ö û ü á é í ó ú à è …) lives in
Latin-1 Supplement (U+00C0–U+00FF), which is inside the **`latin`** subset range
(`U+0000-00FF`). The `latin-ext` files are bundled for completeness / European names in
member-generated content. Variable files carry the full weight axis, so the design's 400/500/
600/700 are all real (no browser-synthesised faux-bold).

## theme.json `fontFace` wiring (Story 1.2 / Task 2)

Add these `fontFace` arrays to the matching `settings.typography.fontFamilies[]` entries.
Lora serves both the `display` and `heading` families; Inter serves both `body` and `ui`
(duplicate the relevant block under each — WordPress de-duplicates the emitted `@font-face`).
WordPress resolves `file:./…` against the theme root and emits `format('woff2')`, which modern
browsers apply correctly to variable woff2.

```jsonc
// Lora (use under fontFamilies slug "display" AND slug "heading")
"fontFace": [
  { "fontFamily": "Lora", "fontStyle": "normal", "fontWeight": "400 700", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/lora-latin-wght-normal.woff2"],
    "unicodeRange": "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD" },
  { "fontFamily": "Lora", "fontStyle": "normal", "fontWeight": "400 700", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/lora-latin-ext-wght-normal.woff2"],
    "unicodeRange": "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF" },
  { "fontFamily": "Lora", "fontStyle": "italic", "fontWeight": "400 700", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/lora-latin-wght-italic.woff2"],
    "unicodeRange": "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD" },
  { "fontFamily": "Lora", "fontStyle": "italic", "fontWeight": "400 700", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/lora-latin-ext-wght-italic.woff2"],
    "unicodeRange": "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF" }
]

// Inter (use under fontFamilies slug "body" AND slug "ui"); axis is 100 900
"fontFace": [
  { "fontFamily": "Inter", "fontStyle": "normal", "fontWeight": "100 900", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/inter-latin-wght-normal.woff2"],
    "unicodeRange": "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD" },
  { "fontFamily": "Inter", "fontStyle": "normal", "fontWeight": "100 900", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/inter-latin-ext-wght-normal.woff2"],
    "unicodeRange": "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF" },
  { "fontFamily": "Inter", "fontStyle": "italic", "fontWeight": "100 900", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/inter-latin-wght-italic.woff2"],
    "unicodeRange": "U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD" },
  { "fontFamily": "Inter", "fontStyle": "italic", "fontWeight": "100 900", "fontDisplay": "swap",
    "src": ["file:./assets/fonts/inter-latin-ext-wght-italic.woff2"],
    "unicodeRange": "U+0100-02BA,U+02BD-02C5,U+02C7-02CC,U+02CE-02D7,U+02DD-02FF,U+0304,U+0308,U+0329,U+1D00-1DBF,U+1E00-1E9F,U+1EF2-1EFF,U+2020,U+20A0-20AB,U+20AD-20C0,U+2113,U+2C60-2C7F,U+A720-A7FF" }
]
```

> The `fontFamily` values inside `fontFace` are the raw face names (`"Lora"`, `"Inter"`) — they
> must match the first token of each family's CSS stack. The `fontFamilies[].slug`
> (`display`/`heading`/`body`/`ui`) and `fontFamily` stack (`"Lora, Georgia, serif"` /
> `"Inter, system-ui, sans-serif"`) stay exactly as-is so the system fallback survives.
