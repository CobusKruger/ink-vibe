# Stage 4 — owner questions

Everything Stage 4 could not decide: features, scope, data or new Afrikaans copy. Style and
layout followed Lovable without asking. Lovable runs at http://localhost:4173; file:line is
in `ink-lovable/src/`.

## Data / bugs (no design call, just a go-ahead)

| ID | Description | Where |
|---|---|---|
| 16-L1 | R50 bug is data: `ink_membership_plan_products` = `{1:926,6:926,12:926}`; only product #926 (R50) exists. Create 1/6/12-month products and remap. Also fixes the my-profiel renewal. | https://nuwe-ink.local/lidmaatskap/ |
| 18-Q6 | QA accounts "INK QA MyProfiel" / "INK QA Skryf" listed publicly. Filter fixtures out, or delete the users? | /ledegids/, /ontdek/ writers tab |
| S-U1b | Challenge archive is `/uitdaging/` (all links now point there). Rename the slug to `/uitdagings/` instead? Saved nav menus need the rebuilder re-run either way. | https://nuwe-ink.local/uitdagings/ |
| 02-SEED | Seed `fidelity-capture` with profile data so populated My Profiel states can be measured? | /my-profiel/ |
| 17-cov | Seed a QA-fixture InkPols issue so list + single page can be measured? | /inkpols/ |
| T3b | Write a reset key to the capture user so `wp-login.php?action=rp` can be audited? | wp-login.php?action=rp |
| T3a | Redirect `?action=lostpassword` to /wagwoord-herstel/ (it is now branded instead)? | wp-login.php?action=lostpassword |

## Copy needed (Afrikaans)

| ID | Description | Lovable | INK |
|---|---|---|---|
| 02-CD | `[NEEDS HUMAN AFRIKAANS]` visible on My Profiel. | — | `my-profiel.php:247`; `functions.php:945-946`; `ProfileEditor.php:152,162,163`; `FollowingFeed.php:291`; `BydraesSurface.php:196`; `KennisgewingsSurface.php:306`; `ReadingList.php:122` |
| 404 | `404-BOODSKAP`, `404-TUIS-SKAKEL` (placeholders now public); `<title>` is English "Page not found". | `NotFound.tsx:15-17` | any 404 |
| T4 | Core lost-password screen: "Meld aan" (retired term), English "Change". | — | wp-login.php?action=lostpassword |
| B-3/B-4 | Biblioteek intro: eyebrow + lede; h1 is just "Biblioteek". | `/library` · `Library.tsx:202-213` | /biblioteek/ |
| B-8/B-14 | Biblioteek shelf subtitle + result-count line. | `Library.tsx:238-240, 294-298` | /biblioteek/ |
| B-20/O-16 | Empty states: biblioteek help text; opleiding filtered title. | `Library.tsx:275-291` | /biblioteek/?biblioteek_soek=zzz |
| B-17/O-13 | "deur" before the card author. | `Library.tsx:365` | /qa-bloks/ |
| 03-Q3 | Ontdek: "Sort by", result count "N stukke/skrywers", empty state title/hint/"Clear filters". | `/browse` · `Browse.tsx:466,494-499,769-779` | `WorksArchive.php:353-361,679`; `SkrywersTab.php:249-254` |
| 05-F10 | "The Prompt" h2 above the challenge brief. | `/challenges/silent-protagonist` · `Challenge.tsx:197` | /uitdaging/skryf-n-storie-… |
| 05-F15/F16 | "Already Submitted" eyebrow + "View all entries" link (also needs a destination). | `Challenge.tsx:309,319` | same |
| 06-F7b | "N reader reviews" line on the writer profile. | `/writer/w1` · `Writer.tsx:280-282` | /author/cobus/ |
| 02-F19..F30 | My Profiel panel subtitles, empty-state CTAs, membership card title/subtitle. | `/profile` · `Profile.tsx:362-512, 600-604` | /my-profiel/ |
| 04-S9 / S11 | Skryf: per-challenge "Ends …" line (+ data); per-type body label "Jou gedig/storie/artikel". | `/write` · `Write.tsx:364-367, 389-391` | `skryf.php:186-189, 202` |
| 22-S14 | Poetry-specific response heading, intro, chips, placeholder. | `/read/s2` · `ReadStory.tsx:111-121,278-308` | /gedig/piet-punte/ |

## Header, footer, auth

| ID | Question | Lovable | INK |
|---|---|---|---|
| H-1 | At 768 the nav + auth buttons wrap the header to 89px. Hamburger up to 1023px, or drop a nav link for guests? | `/` · `layout/Header.tsx:40` | / at 768 |
| H-2 | At 390, move "Teken in"/"Sluit aan" into the mobile menu like Lovable? | `Header.tsx:59, 120-146` | / at 390 |
| H-3 | Footer social links (Lovable has none): keep? | `Footer.tsx:10-18` | `footer-main.php:44-52` |
| M1 | Keep site header/footer on auth pages and 404 (Lovable has none)? | `/auth` · `Auth.tsx:66-143`; `NotFound.tsx:12` | /meld-aan/ |
| M2/M13 | Add brand lock-up above the auth card and "← back" link below? | `Auth.tsx:69-72, 139-141` | /meld-aan/ |
| M4/M5 | Separate login/register pages + switch line, vs Lovable's tabs? | `Auth.tsx:75-81` | /meld-aan/, /registreer/ |
| M6/M7 | Keep h1 + intro on the auth cards (Lovable shows only the tabs)? | `Auth.tsx:74-82` | `auth-login.php:40-46` |
| M10 | Keep "Onthou my" (implementer comment only)? | — | `auth-login.php:72-77` |
| R8/R9 | Registreer: username + e-mail, no password (WP core) vs Lovable e-mail + password. | `Auth.tsx:103-115` | /registreer/ |
| R10/W6 | Keep INK-only field hints (taller cards)? | `Auth.tsx:111`; `ForgotPassword.tsx:54-57` | /registreer/, /wagwoord-herstel/ |
| W9 | Forgot-password switch line as one muted link (drops "Onthou jy jou wagwoord?")? | `ForgotPassword.tsx:61-63` | `auth-forgot-password.php:68-69` |
| W12 | Echo the submitted address in the "sent" notice? | `ForgotPassword.tsx:46` | /wagwoord-herstel/?wagwoord=gestuur |
| M11 | Turn on the social login section? | `Auth.tsx:120-136` | /meld-aan/ |

## Features and structure

| ID | Question | Lovable | INK |
|---|---|---|---|
| T-1 | Home hero stats row (needs real figures + labels). | `/` · `home/HeroSpotlight.tsx:134-147` | / |
| T-2 | Mobile-only "View All Works →" link. | `home/FeaturedWorks.tsx:138-140` | / at 390 |
| T-3 | Whole featured card as the link (INK: title only). | `FeaturedWorks.tsx:76-83` | / |
| T-4 | "deur {author}" on the winner card. | `home/ChallengeSection.tsx:63` | / |
| T-5 | Sort sponsor chips goud → silwer → brons (now data order). | `home/SponsorsSection.tsx:4-11` | /, /oor-ink/ |
| O-1 | Sponsor chips: INK links every active sponsor; Lovable shows 6 inert chips. Keep INK's? | `SponsorsSection.tsx:34-50` | /oor-ink/ |
| G-1 | Build the community "By the numbers" band + "This Month's Spotlight" (copy ratified, needs live data). | `/community` · `Community.tsx:172-249` | /gemeenskap/ |
| 05-F19/F22 | Read time + likes/comments on challenge entry cards. | `Challenge.tsx:337,358-366` | /uitdaging/skryf-n-storie-… |
| 05-F6/F13 | Editor's-pick meta (no data) and Resources section (needs content model). | `Challenge.tsx:168,251-300` | same |
| 14-S-U11 | Challenge archive card: filled "Submit" button instead of hover-only "Lees meer". | `/` · `ChallengeSection.tsx:41-44` | /uitdaging/ |
| 14-S-U12/S-U9 | Cap a lone open challenge card at 668 (fills 1368 now)? Drop its bordered footer? | `ChallengeSection.tsx:8-10,30` | /uitdaging/ |
| 14-S-U3..10 | Archive card: corner tint, trophy tile, month eyebrow, description, entry count. | `ChallengeSection.tsx:11-38` | /uitdaging/ |
| B-22 | Closing "share a piece" CTA band on biblioteek (opleiding has one). | `Library.tsx:309-322` | /biblioteek/ |
| B-11/O-7 | Category icons on library filter chips (needs term→icon map). | `Library.tsx:254-268` | /biblioteek/, /opleiding/ |
| B-18 | Biblioteek cards carry a 16:9 cover Lovable lacks (implementer comment only). Keep? | `Library.tsx:342-371` | /qa-bloks/ |
| 03-Q1 | Move the ontdek search into the cream intro band? | `/browse` · `Browse.tsx:404-416` | `ontdek.php:52-60` |
| 03-Q2 | Tab item counts on ontdek (needs a count source). | `Browse.tsx:428,433` | `ontdek.php:77-84` |
| 03-Q4 | Story-card bookmark; writer-card stats + Follow. | `Browse.tsx:648-660,743-755` | `WorksArchive.php:474-499`; `SkrywersTab.php:304-345` |
| 03-Q5 | Card title / writer name as `<h3>`, whole card as the link. | `Browse.tsx:662,673,717,724` | `WorksArchive.php:488`; `SkrywersTab.php:323` |
| 03-Q7 | Writer sort: Lovable defaults to Most read; INK to Nuwe stemme. Follow Lovable? | `Browse.tsx:536-547` | `SkrywersTab.php:97-99,374-385` |
| 06-F3 | Location field on the writer profile (new user field). | `/writer/w1` · `Writer.tsx:226-229` | /author/cobus/ |
| 06-F6/F8 | Followers as a stats cell; works breakdown line; always show 4 stat cells (INK hides zeros). | `Writer.tsx:284-286` | /author/cobus/ |
| 06-F16 | "Pinned" badge (INK lists pinned works only). | `Writer.tsx:434-438` | /author/cobus/ |
| 06-F22 | Show Follow buttons to guests? | `Writer.tsx:238-244, 357-363` | /author/cobus/ |
| 02-F3/F35 | Avatar camera button / "Change photo" (deferred; contradicts §7.1). | `/profile` · `Profile.tsx:181-187, 695-704` | /my-profiel/ |
| 07-S5/S6 | Byline: read time instead of publish date? | `/read/sample` · `ReadStory.tsx:166-169` | /storie/die-wenteltrap-na-die-lig/ |
| 07-S8 | Share button as the 4th floating-bar control? | `ReadStory.tsx:238-240` | same |
| 07-S15 | Upvote: inert span, no icon, always 0. Confirm deferral; add the icon? | `ReadStory.tsx:349-352` | `ResponsesList.php:38-43` |
| 07-S16 | Reply removed on an implementer comment only. Keep it out? | `ReadStory.tsx:353-355` | `ResponsesList.php:59-65` |
| 22-S7 | Poems pre-fill hearts/highlights on every line any reader reacted to; Lovable shows none until tapped. Keep? | `/read/s2` · `PoetryReader.tsx:13,53-67` | /gedig/piet-punte/ |
| 22-S4 | Dedication field on gedig. | `PoetryReader.tsx:26-30` | /gedig/piet-punte/ |
| 04-S2 | Skryf: one form with a type radio vs Lovable's two steps. | `/write` · `Write.tsx:265-329` | `skryf.php:139-165` |
| 04-S4 | Skryf success: "Lift another writer today" 3-card grid (needs a selection rule). | `Write.tsx:197-241` | `skryf.php:105-117` |
| 04-S13 | Save / Send / Feather icons on the skryf buttons. | `Write.tsx:409,419,171` | `skryf.php:224-225,82` |

## Small polish not done (low value; Lovable values known)

`.ink-label--caps` inline; `ink-pill`/`ink-link` wrapping; `.ink-toolbar` vs constrained layout
(worked around in `reading.css`); `ink-meta` 14px icons; site-wide `has-lg-font-size` 18/28 (patched per
page); merge `.ink-borg-erkenning*` with `.ink-borg-strook*`; primary-button shadow on /auth and /write
submits (Lovable renders none); `ink-field` `display:flex`; a 36/40/700 heading for the 404.
