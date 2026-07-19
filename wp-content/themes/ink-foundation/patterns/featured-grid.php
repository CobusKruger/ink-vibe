<?php
/**
 * Title: Uitgesoekte bydraes
 * Slug: ink-foundation/featured-grid
 * Categories: featured, ink-foundation
 * Description: Die "Die redakteur se keuse"-afdeling — die ink-core ink/uitgesoekte-bydraes-blok lewer die werklike uitgesoekte werke (asimmetriese rooster, leestyd + tellers uit ink-core). Val heeltemal weg wanneer daar geen werke is nie.
 */

/**
 * Story 19.4 (§6). The featured-works section is the server-rendered
 * `ink/uitgesoekte-bydraes` block: it owns the data (real published
 * gedig/storie/artikel), the read-time (computed from word count in ink-core) and
 * the engagement counts (Heart = hartjies, MessageCircle = Gemeenskapsreaksies, both
 * sourced from the existing Engagement surfaces) — the theme performs NO query and NO
 * computation (three-layer separation). The block emits the WHOLE section (eyebrow +
 * serif title + "Sien alle werke" link + asymmetric grid of cards) and COLLAPSES to
 * nothing when the feed is empty (owner decision: empty feed hides entirely — no
 * placeholder, no orphan header). The theme styles the block's own
 * `.ink-uitgesoekte-bydraes*` markup via assets/css/home.css.
 */

?>
<!-- wp:ink/uitgesoekte-bydraes /-->
