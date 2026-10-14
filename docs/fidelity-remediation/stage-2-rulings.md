# Stage 2 — owner rulings

These are the owner's answers to the outstanding items in `findings/_primitive-synthesis.md`,
recorded as given on 2026-10-14. Stage 3 applies them.

## General rule

Lovable takes preference on colour, spacing, font sizes and weights, line heights and so
on. This settles TOK-1 (palette and shadows), BTN-2 (no pill buttons) and BTN-3 (the
neutral outline button's hover uses Lovable's sage).

## Item rulings

| ID | Ruling |
|---|---|
| HR-1 | Section headings are 36px, weight 600. |
| HR-2 | Work-card titles use weight 400. |
| HR-3 | The home hero and the community hero are different things. Keep both as they are. |
| HR-4 | The CTA-band heading is 30 / 36 / 48px: white on the brand colour, cream on dark. |
| SB-1 | Keep Lovable's per-section padding. Reading pages are deliberately tighter, because their sections are closely related. |
| SB-3 | Cream section backgrounds use `bg-secondary/30` everywhere, including the `/writer` "Werk" section. |
| EB-1 | Keep both eyebrows. Left-aligned eyebrows (such as Featured) serve a different purpose from centred ones. |
| EB-2 | Stat labels are 12px, uppercase. |
| BTN-1 | All CTA buttons use the serif font at 18px, with a 48px button height. |

## Lovable is broken here: legibility (owner raised, 2026-10-14)

The owner raised two buttons as illegible, and the Lovable agent diagnosed both. On these
two buttons the Lovable reference is wrong, so it must not be copied.

1. **"Explore stories" (home CTA band, `components/home/CallToAction.tsx`).**
   - Cause: the `outline` variant injects `bg-background`, giving white text on
     off-white. On hover, the 10% white tint gives almost no contrast with the terracotta.
   - Proposed fix: a transparent background with a 2px white border and white text. On
     hover it inverts to a white fill with terracotta text.
2. **"Read Full Story" (home "December Winner" card,
   `components/home/ChallengeSection.tsx`).**
   - Cause: the `outline` variant's `hover:text-accent-foreground` (near-white) is
     never overridden, so the label vanishes on hover.
   - Proposed fix: `border-gold/40`, with hover `bg-gold/15`, the text kept at
     foreground and the border at gold. The alternative is a filled hover:
     `bg-gold text-ink`.

**Ruled:** adopt fix 1 as proposed. For fix 2, use the **filled** hover: `bg-gold
text-ink border-gold`. Apply **both fixes in the Lovable reference as well**, so later
comparisons measure against legible buttons.

## Button font (owner ruling, 2026-10-14)

**Every button on the site uses the serif font.** "Read Full Story" is currently sans in
Lovable. This overrides the Stage 2 synthesis finding that Lovable's ghost and
sage-outline buttons are Inter (`_primitive-synthesis.controls.md`), and it also
overrides the Inter in the shared shadcn button base.

## Remaining item rulings (2026-10-14)

| ID | Ruling |
|---|---|
| LBL-1 | Form labels have a 20px line-height. |
| NR-3 | Sage tints behind badges and chips are 10%. |
| NR-4 | The icon-to-text gap in small meta items is 6px. |
| SRCH-1 | Keep the "Soek" button, styled like every other brand-colour button. The search input and the button are both 40px high. |
| CHIP-1 | Keep the ontdek date filter, styled like the sage chips on `/challenges/silent-protagonist` (`pages/Challenge.tsx:214`). |
| NR-1 | Tier labels use their tier colours (bronze, silver, gold), each with a lighter background and a darker border, in the same pattern as the sage chips in CHIP-1. |
| NR-2 | Keep INK's notice box styling as is. The English "Please fill out this field." popup the owner saw is the browser's native required-field bubble. It must become Afrikaans, "Vul hierdie veld in", via a small script that sets the field's custom validity message. This is a script fix, not CSS; apply it to every form with `required` fields. |

## Forms without a Lovable equivalent

Style the contact form's inputs and buttons like the `/auth` page. The same applies to any
input field that has no Lovable equivalent: use the `/auth` field and button recipe.

**Scope of the serif rule:** it covers buttons only: primary, outline, ghost,
sage-outline, subtle, search and CTA. Filter chips, tabs, the bare "Reply" text button and
the icon buttons that show a count (like, comment, upvote) keep Lovable's font.

## Still open

Nothing. All Stage 2 rulings have been given.
