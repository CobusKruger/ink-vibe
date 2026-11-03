<?php
/**
 * Title: Borg-strook
 * Slug: ink-foundation/borg-strook
 * Categories: ink-foundation
 * Description: 'n Borg-afdeling vir die tuisblad (Storie 14.3 / 19.5, §7, FR-58).
 *
 * The homepage sponsor section (Story 14.3, restyled to fidelity in 19.5 §7). The whole
 * section, band included, is the server-rendered `ink/borg-strook` block (ink-core/Sponsors):
 * ALL business logic (which sponsors are active, their tier, collapse-when-none) stays in
 * ink-core (three-layer separation), and the block emits Lovable's SponsorsSection.tsx
 * structure node for node. With no active sponsor it renders nothing, so no empty band.
 * This pattern carries NO user-facing copy (copy-scan-exempt). Page layout lives in
 * assets/css/home.css.
 *
 * @package Ink\Foundation
 */

?>
<!-- wp:ink/borg-strook /-->
