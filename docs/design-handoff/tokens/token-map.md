# Token Map: Lovable to theme.json

Use this table to map exported Lovable tokens to WordPress theme.json keys.

| Lovable token | Normalized token | theme.json path | Notes |
|---|---|---|---|
| color.primary | color.primary | settings.color.palette[].color | slug: primary |
| color.secondary | color.secondary | settings.color.palette[].color | slug: secondary |
| color.accent | color.accent | settings.color.palette[].color | slug: accent |
| color.surface | color.surface | settings.color.palette[].color | slug: surface |
| color.surfaceAlt | color.surfaceAlt | settings.color.palette[].color | slug: surface-alt |
| color.text | color.text | settings.color.palette[].color | slug: text |
| color.mutedText | color.mutedText | settings.color.palette[].color | slug: muted-text |
| typography.fontFamily.display | typography.fontFamily.display | settings.typography.fontFamilies[] | slug: display |
| typography.fontFamily.heading | typography.fontFamily.heading | settings.typography.fontFamilies[] | slug: heading |
| typography.fontFamily.body | typography.fontFamily.body | settings.typography.fontFamilies[] | slug: body |
| typography.fontSize.xs-3xl | typography.fontSize.xs-3xl | settings.typography.fontSizes[] | slugs: xs..3xl |
| spacing.4-96 | spacing.4-96 | settings.spacing.spacingSizes[] | slugs: s-4..s-96 |
| layout.contentSize | layout.contentSize | settings.layout.contentSize | text readability |
| layout.wideSize | layout.wideSize | settings.layout.wideSize | feature rows |

## Rules

1. Never use raw Lovable token names directly in template markup.
2. Normalize names first in theme-tokens.json.
3. Generate theme.json values from normalized tokens only.
4. Keep this mapping updated when design tokens change.
