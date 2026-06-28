# Afrikaans copy — ID crosswalk (reference, not for editing)

Maps each `ID` in **`docs/afrikaans-translation-sheet.md`** (the sheet Cobus fills)
to where its approved Afrikaans gets wired: a row in `docs/ui-copy-translations.md`
and/or `docs/afrikaans-terms.md`, plus the live-code `file:line` whose placeholder
it replaces. Used to "fix up" the filled sheet and then lower the leak-scan
baseline (`composer copy:scan -- --update-baseline`).

Line numbers are approximate (they drift as files change); the section + label is
the durable anchor. Code paths under `wp-content/`.

| ID | ui-copy-translations.md | code file:line | notes |
|---|---|---|---|
| LID-INTRO | NEW row → §"Gekureerde blad-kopie (4.4)" (~559) | `themes/ink-foundation/patterns/lidmaatskap.php` ~70 | page intro under "Aansluitingsopsies" H1 |
| LID-PLAN-1MO | L589 (replace placeholder) | `…/lidmaatskap.php` ~116 (plan loop) | no savings framing |
| LID-PLAN-6MO | L590 | `…/lidmaatskap.php` ~116 | |
| LID-PLAN-12MO | L591 | `…/lidmaatskap.php` ~116 | |
| LID-BENEFIT-2 | NEW row → §"Gekureerde blad-kopie" | `…/lidmaatskap.php` ~158 | bullet 1 already approved |
| LID-BENEFIT-3 | NEW row → §"Gekureerde blad-kopie" | `…/lidmaatskap.php` ~162 | |
| LID-FAQ-A1 | NEW row → §"Gekureerde blad-kopie" | `…/lidmaatskap.php` ~183 | question `<summary>` already approved (L564) |
| LID-FAQ-A2 | NEW row → §"Gekureerde blad-kopie" | `…/lidmaatskap.php` ~191 | must state NO auto-renew (L565) |
| LID-FAQ-A3 | NEW row → §"Gekureerde blad-kopie" | `…/lidmaatskap.php` ~200 | PayFast/ZAR (L566) |
| LID-CTA | L592 | `…/lidmaatskap.php` ~220 | |
| ONBOARD-WELCOME | NEW row → new §"Onboarding (Storie 3.3)" | `…/patterns/onboarding.php` ~35 | replaces interim line |
| ONBOARD-PROFILE | NEW row → same new § | `…/patterns/onboarding.php` ~49 | replaces interim line |
| SOCIAL-DIVIDER | L612 | `…/patterns/auth-register.php` ~57 **+** `auth-login.php` ~36 | one string, two render sites |
| SOCIAL-CONSENT | L613 | `auth-register.php` ~71 **+** `auth-login.php` ~49 | |
| SOCIAL-PRIVACY-LINK | L614 | same two patterns | links to `/privaatheidsbeleid` |
| EMAIL-ACTIVATE-SUBJECT | L602 (split row into subj/body) | `plugins/ink-core/src/Entitlement/PurchaseActivation.php`:235 | toggle OFF |
| EMAIL-ACTIVATE-BODY | L602 | `…/PurchaseActivation.php`:238 | fires on any →active (incl. comp/admin) |
| EMAIL-1MONTH-SUBJECT | L603 | `…/Entitlement/LifecycleEmails.php`:656 | |
| EMAIL-1MONTH-BODY | L603 | `…/LifecycleEmails.php`:657 | |
| EMAIL-1WEEK-SUBJECT | L604 | `…/LifecycleEmails.php`:644 | |
| EMAIL-1WEEK-BODY | L604 | `…/LifecycleEmails.php`:647 | |
| REG-WELCOME-BODY | NEW row → §"Lidmaatskap-lewensiklus e-pos" or new account-email § | `…/Accounts/Registration.php`:121 | subject "Welkom by INK" already approved |
| APPROVAL-LOGIN-PENDING | L622 | `…/Accounts/Approval.php`:326 | interpolates `account_pending` label |
| APPROVAL-LOGIN-REJECTED | NEW row → §"Rekening-goedkeuring backstop" | `…/Approval.php`:317 | 622 covers pending only |
| APPROVAL-RESULT-APPROVE | L625 | `…/Approval.php`:589 | admin notice |
| APPROVAL-RESULT-REJECT | L626 | `…/Approval.php`:590 | admin notice |
| APPROVAL-RESULT-ERROR | NEW row → §"Rekening-goedkeuring backstop" | `…/Approval.php`:591 | admin error notice |
| APPROVAL-EMAIL-APPROVE-SUBJECT | L627 (split subj/body) | `…/Approval.php`:472 | toggle OFF |
| APPROVAL-EMAIL-APPROVE-BODY | L627 | `…/Approval.php`:473 | |
| APPROVAL-EMAIL-REJECT-SUBJECT | L628 (split subj/body) | `…/Approval.php`:481 | toggle OFF |
| APPROVAL-EMAIL-REJECT-BODY | L628 | `…/Approval.php`:482 | |
| APPROVAL-BTN-APPROVE | L623 + `afrikaans-terms.md` §"Rekening-goedkeuring" | (registry label) | ratify only |
| APPROVAL-BTN-REJECT | L624 + `afrikaans-terms.md` §"Rekening-goedkeuring" | (registry label) | ratify only |
| (scope) auth microcopy | — | `auth-register.php` ~47, `auth-forgot-password.php` ~40 | hidden-span markers; not discrete strings yet |
| (scope) kontak microcopy | — | `…/Forms/ContactForm.php` (hidden span in `toHtml()`) | Story 15.4; field labels (Naam/E-pos/Onderwerp/Boodskap/Stuur boodskap) + the two notices render in Afrikaans; hidden-span marker stands for the not-yet-curated validation/success cluster |

## Fix-up procedure (after the sheet comes back)

1. For each filled `AF:`, write it into the mapped `ui-copy-translations.md` row
   (replace `[NEEDS HUMAN AFRIKAANS]` / add the NEW row in the named section).
2. Ratify glossary terms in `afrikaans-terms.md` where flagged.
3. Replace the inline placeholder in the mapped code `file:line` with the approved
   string (via the `ink-foundation` / `ink-core` text domain, matching siblings).
4. For email/approval strings, the send-toggles stay as-is — copy landing does not
   flip them on; that's a separate deliberate step.
5. Run `composer copy:scan`; then `composer copy:scan -- --update-baseline` to lock
   in the reduced count. Launch target: baseline empty.
6. Skip any line left blank — leave its placeholder + baseline entry intact.
