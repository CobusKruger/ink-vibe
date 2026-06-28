# Epic 15 (Organisation pages & contact) — Code Review (R15)

Date: 2026-06-28
Reviewer: bmad-code-review (3-layer adversarial: Blind Hunter / Edge Case Hunter / Acceptance Auditor)
Scope: the full Epic-15 branch diff vs `main` (`1aa8e8a`), stories 15.1–15.6 — ~1840 lines of code across `ink-core/src/Forms/ContactForm`, `ink-core/src/Challenges/FeaturedWinners`, the module wiring, six `ink-foundation` patterns + four slug templates + the footer, and the unit suite.

## Outcome

**0 HIGH defects.** 5 patches applied (2 MEDIUM, 3 LOW); 2 items deferred; 4 dismissed as non-issues. Tests 940 → 942 (+2 net from review tests); phpcs/phpstan/deptrac/copy:scan all green (deptrac unchanged at the 3 pre-existing `Kernel\Activation → Content\PostTypes` violations — outside Epic-15 scope).

The Acceptance Auditor confirmed every Epic-15 AC is implemented and the load-bearing guarantees hold:
- **Three-layer clean** — the org pages are presentation-only (no `WP_Query`/`wp:ink/` business block in the static patterns); the only dynamic surfaces are sanctioned ink-core blocks (`borg-erkenning`, `kontak-vorm`, `wenner-kollig`). No new deptrac edge (Forms + Challenges layers clean).
- **Conflation-clean** — `ContactForm` and `FeaturedWinners` carry ZERO `Tiers`/`Entitlement` reference; the contact form is not gated on membership, and placements ⟂ entitlement.
- **Security** — the contact form nonce-verifies, `is_scalar`-guards every `$_POST`/`$_GET` read before `wp_unslash` + a field-appropriate sanitiser, drops bots via a honeypot, and escapes all block output. wp_mail header-injection is blocked (newline-stripping sanitisers).
- **Afrikaans copy verbatim** — all authored strings trace to `docs/ui-copy-translations.md` (no AI re-translation); the not-yet-curated Kontak microcopy is flagged with the `ink-needs-human-af` marker and the baseline raised deliberately (6 → 8).
- **Org placeholders** — Oor INK uses `[stigtingsjaar]`/`[regstatus]`; a guard test asserts the US "501(c)(3)" wording never leaks.
- **Forward-compatibility** — 15.6's winners slot collapses to empty markup until the unbuilt 12A supplies data via the `ink_home_featured_winner` filter; no fake winner shown.

## Patches applied (5)

1. **[15.4 MEDIUM] Contact form no longer claims "gestuur" when delivery fails** — `ContactForm::handlePost()` discarded `send()`'s return and always showed the success notice, so an empty `admin_email`, a disabled send-toggle, or a `wp_mail` transport failure silently lost the message while telling the visitor it was sent. Now captures the result and routes to a new honest `NOTICE_SEND_FAIL` ("Ons kon nie jou boodskap stuur nie…") on failure; the honeypot path's deliberate success-looking redirect is unchanged. (+1 test.)
2. **[15.6 MEDIUM] Re-added the specified "Lees die volledige storie" read-more link** — Task 1 / the design (ui-copy line 83) called for each winner row to carry a "Lees die volledige storie" link; the first cut silently descoped it (linked the title instead). `FeaturedWinners::toHtml()` now renders the title as text plus the authored read-more link to the work (only when a permalink exists — never a dead link). (+ test assertion.)
3. **[15.6 LOW→MED] Defensive one-per-rank dedup in `FeaturedWinners::order()`** — a dirty 12A payload with two rank-1 rows would have surfaced two "algehele wenners". `order()` now collapses to one entry per rank (lowest id wins), mirroring the canonical invariant in `Placements::arrange()`. Authoritative dedup remains 12A.3's ingestion job, but the slot is now self-defending. (+1 test.)
4. **[15.4 LOW] Reply-To display name RFC-quoted** — `'Reply-To: ' . $name . ' <…>'` could form a malformed header when the (CRLF-stripped, injection-safe) name contains commas/angle-brackets/quotes. Now quoted + residual-quote-stripped.
5. **[15.1 LOW] Front-page hero buttons block-locked** — the pre-existing inline hero `wp:buttons` lacked the `lock:{move,remove}` that every new Epic-15 pattern carries; added for AC-#7 editorial-structure consistency.

## Deferred (2 — real, not actionable in Epic 15)

- **Contact form has no rate-limiting; the public `nopriv` path + honeypot-only defence is a mail-flood vector** [`ContactForm`] — a bot that leaves the honeypot empty can fire unlimited mails to `admin_email`. The honeypot is the accepted v1 defence; rate-limiting / a challenge belongs to the Epic-18 security pass (and aligns with the Cloudflare edge posture). Owner: Epic 18.
- **Source-doc Afrikaans typos propagated to live patterns** — `gemeenskap.php` carries "niewinsgericht" (should be "niewinsgerig") and "trep nooit op mense" (should be "trap"), copied verbatim from `docs/ui-copy-translations.md` (lines 341/354). Per "Afrikaans is source of truth — never AI-retranslate the curated copy", these were NOT unilaterally edited. Owner: the human copy author corrects the source doc, then the patterns are re-synced.

## Dismissed (4 — verified non-issues)

- **Social-link `url:"#"` placeholders** [`footer-main.php`] — intentional org-detail pre-launch value (the owner sets real handles), the same class as `[stigtingsjaar]`; documented, not a defect.
- **`FeaturedSlotTemplateTest` positional `strpos` assertions** — fragile only if a slug recurs; all slugs are unique in `front-page.html`, and the assertion correctly captures the page-map order. Adequate.
- **`ContactFormTest` permissive `is_email` mock** — the invalid-email path IS tested (a no-`@` value is rejected); real `is_email` is WordPress's, correctly not re-implemented in a unit test.
- **`ContactForm::validate()` `WP_Error` messages not gettext-wrapped** — display-dead (only the error *code* is read, then a notice slug is shown), and matches the established `Submission\SubmissionForm::buildPost()` precedent exactly. No front-end leak.

## Story statuses

15.1 / 15.2 / 15.3 / 15.4 / 15.5 / 15.6 → **done** (all ACs met, 0 HIGH, the 2 MEDIUM + 3 LOW patches applied).
