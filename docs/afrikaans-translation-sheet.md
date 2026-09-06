# Afrikaans translation sheet

Fill in the `AF:` lines. That's the only thing you do here.

- The `EN:` text is a **sample to translate** — adjust the wording freely; your
  Afrikaans is what becomes authoritative, not the English.
- Leave any `AF:` line blank if you're unsure — I'll leave that one as a tracked
  gap rather than guess.
- `{skrywer}` is a name-merge token — keep it as-is in your Afrikaans.
- House rules: sentence case, "jy"-voice, **no auto-renew framing**, **no
  savings / %-off framing**.

When you're done, hand it back and I'll wire every line into
`ui-copy-translations.md`, `afrikaans-terms.md`, and the code, then re-run the
leak scan and lower the baseline. The `ID` ties each line back to its home —
ignore it, it's for me.

---

## 1. Lidmaatskap page — HIGHEST PRIORITY (public page, leaks to visitors today)

LID-INTRO
EN: Choose the access term that suits you.
AF: Vir hoe lank wil jy vandag aansluit?

LID-PLAN-1MO
EN: Full access for one month.
AF: Volle toegang vir 'n maand.

LID-PLAN-6MO
EN: Full access for six months.
AF: Volle toegang vir ses maande.

LID-PLAN-12MO
EN: Full access for twelve months.
AF: Volle toegang vir 'n jaar.

LID-BENEFIT-2
EN: Submit your writing to INK. *(sample — replace with the real benefit)*
AF: Laat jou skryfwerk op INK pryk.

LID-BENEFIT-3
EN: Take part in challenges and the community. *(sample — replace with the real benefit)*
AF: Neem deel aan kompetisies en maandelikse uitdagings.

LID-FAQ-A1
EN: A membership lasts for the term you choose — 1, 6, or 12 months.
AF: Jou lidmaatskap duur vir die termyn wat jy gekies het — 'n maand, ses maande, of 'n jaar.

LID-FAQ-A2
EN: No. Memberships do not renew automatically. When your term ends you choose whether to renew.
AF: Nee. Lidmaatskap hernieu nie outomaties nie. Ons sal jou laat weet 'n week voordat dit verval.

LID-FAQ-A3
EN: You pay securely with PayFast, in South African rand.
AF: Betaling word veilig hanteer deur PayFast. Ons sien nooit jou kaartbesonderhede nie.

LID-CTA
EN: Choose a plan above to start your membership.
AF: Kies 'n opsie hierbo om 'n lid te word.

---

## 2. Onboarding (after registration — currently shows interim copy)

ONBOARD-WELCOME
EN: Your account is ready as a free member. Complete your profile and take a first step — you can skip this any time.
AF: Jy is nou 'n gratis lid. Welkom! Vertel ons asseblief meer van jou op die "My Profiel" bladsy.

ONBOARD-PROFILE
EN: Add a name and a short description so others can get to know you.
AF: Gee jou naam en 'n kort beskrywing, sodat ander jou kan leer ken.

---

## 3. Social sign-in (only shows if a social-login plugin is enabled)

SOCIAL-DIVIDER
EN: Or continue with
AF: Of gebruik eerder

SOCIAL-CONSENT
EN: By continuing with a social account, you share basic profile information with INK.
AF: As jy 'n sosiale media-rekening gebruik, sien INK jou basiese besonderhede.

SOCIAL-PRIVACY-LINK
EN: Privacy policy
AF: Privaatheidsbeleid

---

## 4. Membership lifecycle emails (send-toggles are OFF until copy lands)

EMAIL-ACTIVATE-SUBJECT
EN: Your membership is active
AF: Jou lidmaatskap is nou aktief

EMAIL-ACTIVATE-BODY
EN: Hello {skrywer}, your membership is now active. Thank you for supporting INK.
AF: Hallo {skrywer}! Jou lidmaatskap is nou aktief. Dankie dat jy vir INK ondersteun.

EMAIL-1MONTH-SUBJECT
EN: Your membership expires soon
AF: Jou lidmaatskap verval binnekort

EMAIL-1MONTH-BODY
EN: Hello {skrywer}, your membership expires in one month.
AF: Hallo {skrywer}. Jou lidmaatskap verval oor een maand.

EMAIL-1WEEK-SUBJECT
EN: Your membership expires soon
AF: Jou lidmaatskap verval binnekort

EMAIL-1WEEK-BODY
EN: Hello {skrywer}, your membership expires in one week.
AF: Hallo {skrywer}. Jou lidmaatskap verval oor een week.

---

## 5. Account-welcome email (subject "Welkom by INK" is already approved — body only)

REG-WELCOME-BODY
EN: Hello {skrywer}, your account has been created. Welcome to INK.
AF: Hallo {skrywer}, en welkom by INK! Jou rekening is pas geskep.

---

## 6. Approval backstop (OFF by default — only used if an editor turns on the approval queue)

APPROVAL-LOGIN-PENDING
EN: Your account is awaiting approval by an editor.
AF: Jou rekening wag vir goedkeuring. Ons kyk binnekort daarna.

APPROVAL-LOGIN-REJECTED
EN: Your account application was declined.
AF: Jou rekening is ongelukkig afgekeur.

APPROVAL-RESULT-APPROVE
EN: The account has been approved.
AF: Jou rekening is goedgekeur.

APPROVAL-RESULT-REJECT
EN: The account has been declined.
AF: Jou rekening is afgekeur.

APPROVAL-RESULT-ERROR
EN: The action could not be completed.
AF: Iets het foutgegaan.

APPROVAL-EMAIL-APPROVE-SUBJECT
EN: Your INK account has been approved
AF: Jou INK rekening is goedgekeur

APPROVAL-EMAIL-APPROVE-BODY
EN: Hello {skrywer}, your account has been approved. You can now sign in and start writing.
AF: Hallo {skrywer}. Jou rekening is goedgekeur. Jy kan nou inteken en begin skryf.

APPROVAL-EMAIL-REJECT-SUBJECT
EN: About your INK account application
AF: Aangaande jou INK rekeningaansoek

APPROVAL-EMAIL-REJECT-BODY
EN: Hello {skrywer}, your account application was not approved.
AF: Hallo {skrywer}. Jou rekeningaansoek is ongelukkig afgekeur.

---

## 7. Glossary terms — confirm, don't translate

These single words are already drafted in the terminology registry and just need
your sign-off (or a correction). Edit the `AF:` if you want a different word.

APPROVAL-BTN-APPROVE
EN: Approve *(button)*
AF: Keur goed

APPROVAL-BTN-REJECT
EN: Reject *(button)*
AF: Verwerp

CADENCE-FIELD-LABEL
EN: Cadence *(admin field label on a uitdaging — monthly vs annual competition)*
AF: Kadens

CADENCE-OPT-MONTHLY
EN: Monthly *(cadence option)*
AF: Maandeliks

CADENCE-OPT-ANNUAL
EN: Annual *(cadence option — the yearly competition)*
AF: Jaarliks

---

## 8. Auth & Kontak microcopy — ✅ WIRED (2026-06-30)

Your approved `AF:` lines below are now live: wired into the form code, recorded in
`docs/ui-copy-translations.md` (Sync 2026-06-30), and the leak-scan baseline lowered
to **empty** (all 8 markers cleared — `composer copy:scan` reports zero placeholders).
Kept here as the record of what was authored.

### 8a. Kontak form (`Ink\Forms\ContactForm`, Story 15.4)

KONTAK-SUBJECT-OPTIONAL
EN: Subject (optional)
AF: Onderwerp (opsioneel)

KONTAK-MSG-HINT
EN: Tell us how we can help.
AF: Hoe ons kan help?

KONTAK-PRIVACY
EN: We use your details only to reply to this message.
AF: Ons gebruik jou besonderhede net om op hierdie boodskap te antwoord.

KONTAK-ERR-NAME
EN: Please enter your name.
AF: Vul asseblief jou naam in.

KONTAK-ERR-EMAIL
EN: Please enter a valid email address.
AF: Vul asseblief 'n geldige e-posadres in.

KONTAK-ERR-MESSAGE
EN: Please enter a message.
AF: Vul asseblief 'n boodskap in.

### 8b. Register form (`auth-register.php`)

AUTH-REG-USERNAME-HINT
EN: Choose a username — other members will see this.
AF: Kies 'n gebruikersnaam — ander lede sal dit sien.

AUTH-REG-EMAIL-HINT
EN: We'll send your sign-in details to this address.
AF: Ons stuur jou intekenbesonderhede na hierdie adres.

### 8c. Password-reset form (`auth-forgot-password.php`)

AUTH-FORGOT-HINT
EN: Enter the email or username linked to your account.
AF: Vul die e-pos of gebruikersnaam in wat aan jou rekening gekoppel is.

---

## 9. My Profiel — Leeslys tab empty-state body (heading already ratified)

The empty-state heading ("Nothing saved yet") is already ratified as **"Nog niks
gestoor nie."** — nothing to do there. Only the line below it, shown under that
heading, still needs your Afrikaans.

LEESLYS-LEEG-BODY
EN: Tap the bookmark on any story or poem and it will land here for later.
AF:

---

## 10. My Profiel — "Wysig profiel" edit modal (new field/button copy, not in Lovable's ratified sheet)

The modal itself ("Wysig profiel" heading, "Naam", "Oor my") reuses copy already
ratified elsewhere on this page. These five strings are new — the modal's tagline
field, its Save/Cancel buttons, and its transient saving/error status text — and
have no ratified Afrikaans yet.

PROFIEL-REDIGEER-LEUSE-LABEL
EN: Tagline *(short field label, under the field for the writer's own one-line tagline shown in the identity strip)*
AF:

PROFIEL-REDIGEER-STOOR
EN: Save *(button)*
AF:

PROFIEL-REDIGEER-KANSELLEER
EN: Cancel *(button)*
AF:

PROFIEL-REDIGEER-BESIG
EN: Saving… *(transient status text shown while the save request is in flight)*
AF:

PROFIEL-REDIGEER-FOUT
EN: Couldn't save. Please try again. *(transient status text shown if the save request fails)*
AF:

---

## 11. My Profiel — Oorsig/Bydraes tabs (new empty-state copy, not in Lovable's ratified sheet)

Two more empty-state strings from the Oorsig-tab and Bydraes-tab build, neither
covered by the existing ratified sheet.

OORSIG-BIO-LEEG
EN: (No Lovable equivalent — a fallback line shown in the "Oor my" card when the
member hasn't written a bio yet, in place of the bio text itself.)
AF:

BYDRAES-LEEG
EN: (No Lovable equivalent — shown in the Bydraes tab when the member has no
published works yet, in place of the post list.)
AF:

---

## 12. My Profiel — Aktiwiteit tab activity-row action phrase (new copy, not in Lovable's ratified sheet)

`FollowingFeed`'s Aktiwiteit-tab card gained a connecting phrase between the author
name and the (already-ratified) type label + days-ago timing — Lovable's English
reference reads "published a new {type} · {N}d ago"; only the connecting verb
phrase itself is new copy (the type label and the days-ago timing either side of
it already have ratified Afrikaans sources elsewhere and are reused, not
retranslated here).

VOLG-VOER-AKSIE
EN: published a new {type} *(connecting phrase in the activity-feed row, between
the author name and the type label + relative "Nd ago" timing, e.g. "Anja
published a new gedig · 3d ago" — the {type}/timing either side are already
ratified/established sources, only this phrase is new)*
AF:
AF:
