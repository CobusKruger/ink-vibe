# INK — Glossary, Migration & Voice Extract

> Curated from `docs/afrikaans-terms.md` (terminology source of truth), `docs/migration-plan.md` (brownfield migration), and `docs/ui-copy-translations.md` (UI copy & voice). The glossary is the bron van waarheid (source of truth) — code and UI must follow it, not the reverse.

---

## Glossary

The load-bearing domain terms. Afrikaans UI term is canonical for all user-facing strings; English is the meaning; code ID is the slug/key where relevant.

### Users, identity & intent
| Afrikaans term | English meaning | Code ID / key |
|---|---|---|
| **lid** | a member (person with an account) | `member` / `user` ("user" is code-only) |
| **besoeker** | unregistered visitor / reader | `visitor` |
| **skrywer** | writer (someone who submits work) — never "outeur" | `writer` |
| **redakteur** | editor / admin staff | `editor` / `admin` (WP roles) |
| **leser / skrywer** (intent) | reader vs. writer base intent chosen at join | user meta `ink_writer_intent` |

### Membership, access & tiers
| Afrikaans term | English meaning | Code ID / key |
|---|---|---|
| **lidmaatskap** | membership = broad access state | `membership` |
| **intekening** | subscription = the paid financial agreement (do NOT use interchangeably with lidmaatskap) | `subscription` |
| **intekenplan** | subscription plan (price + term) | `membership_plan` |
| **skrywervlak** | writer tier / progression system (never "tier" in UI) | `writer_tier`, user meta `ink_writer_tier` |
| **Brons** | tier 1 (Bronze) | `brons` |
| **Silwer** | tier 2 (Silver) | `silwer` |
| **Goud** | tier 3 (Gold) | `goud` |
| **bevorder** | promote to a higher tier | — (`ink_tier_promoted_at`) |
| **vlakgeskiedenis** | tier history record | `tier_history` |

### Content types (CPTs)
| Afrikaans term | English meaning | Code ID / key |
|---|---|---|
| **bydrae** | a contribution = the general/collective term for submitted work (never "post"/"plasing"/"artikel" as collective) | `submission` / `post` |
| **gedig** | a poem (pl. gedigte) | post_type: `gedig` |
| **storie** | a short story / prose piece (pl. stories) | post_type: `storie` (renamed from `verhaal`) |
| **artikel** | an opinion/essay/article (pl. artikels) | post_type: `artikel` |
| **biblioteekitem** | an item in the Library (curated/winning work) | post_type: `biblioteek_item` |
| **hulpbronartikel** | a Training/resource article | post_type: `opleiding_artikel` |
| **uitdaging** | the monthly challenge (never "kompetisie"/"projek"/"challenge") | post_type: `uitdaging` |
| **uitgawe** | an InkPols magazine issue (never "issue") | post_type: `inkpols_uitgawe` |
| **borg** | a sponsor (pl. borge) | post_type: `borg` |
| **plaas** | the act of submitting/publishing work (never "submit"/"indien"/"oplaai") | — (status `publish` = gepubliseer, `draft` = konsep) |

> Note: migration plan also references a catch-all `skryfwerk` CPT for unclassifiable posts — this is a migration holding bucket, NOT in the glossary as a user-facing term.

### Taxonomies
| Afrikaans term | English meaning | Code ID / key |
|---|---|---|
| **genre** | genre of a contribution | taxonomy: `genre` |
| **vaardigheidsarea** | skill area (Digkuns, Prosa, Taalgids, etc.) | taxonomy: `vaardigheid` |
| **uitdagingsronde** | challenge round (for entries & winning work) | taxonomy: `uitdagingsronde` |
| **skrywervlak** | writer tier as a taxonomy term where applicable | taxonomy: `skrywervlak` |

### Community & social
| Afrikaans term | English meaning | Code ID / key |
|---|---|---|
| **volg** | follow — one-way (asymmetric) social link; replaces old friendship model | `follow` (in `ink-core`) |
| **volgeling** | a follower (pl. volgelinge) — never "volger" | — |
| **leeslys** | a member's saved reading list | `reading_list` |
| **aktiwiteitsvoer** | activity feed (never "stroom"/"feed") | `activity` |
| **reaksie** | a reaction (covers heart, thumbs-up, "wow"; never "like") | `reaction` |
| **hooglignering** | highlight of a line in a contribution ("Merk hierdie reël") | `highlight` |
| **kommentaar** | comment | `comment` |
| **boodskap** | private message | `message` |
| **kennisgewing** | system notification | `notification` |
| **ledegids** | member directory | `member_directory` |

### Critique / response types (structured feedback)
| Afrikaans term | English meaning | Notes |
|---|---|---|
| **lof** | Praise — "wat goed gewerk het" | one of three structured response types |
| **insig** | Insight — "deel 'n insig" | |
| **voorstel** | Suggestion — "konstruktiewe terugvoer" | |

---

## Migration / Rollout (brownfield)

WordPress rebuild via clone-and-restructure. Subscriptions already live in WooCommerce Memberships; community on BuddyPress. Most risk sits in **post→CPT reclassification, the tier spreadsheet import, and the redirect layer**.

### Ordered sequence (high level)
1. Clone & sanitise production DB (strip transients/logs).
2. Define CPTs, taxonomies & data models in `ink-core` first — do not migrate content until target structure is stable.
3. Import users (baseline accounts must exist first).
4. Import tier data from spreadsheet (depends on user accounts).
5. **Verify** subscription data in WooCommerce Memberships (migrates with the clone — no import).
6. Classify existing posts (category/tag audit → classification map; unclassifiable → `skryfwerk`).
7. Migrate library & training content (cleaner URL sub-paths, lower risk).
8. Migrate general posts (gedig/storie/artikel) to typed CPTs; **generate 301 redirects during this step**.
9. Migrate InkPols (manual or short script).
10. Enter sponsor records manually.
11. Rebuild navigation in the new block theme (new IA, not a copy).
12. Verify redirects (crawl old URLs → confirm 301s).
13. Verify media (uploads accessible, audio plays, PDFs open).
14. Verify BuddyPress data (friendships, activity, profile fields).
15. Smoke-test community + subscription flows end to end.
16. DNS cutover — only after all verifications pass.

### What must survive
- **Members / users** — carry across with DB clone (no scripting for accounts); roles reassigned to reader/writer base role.
- **Subscriptions** — WooCommerce Memberships records, plan IDs, access rules, start/expiry dates ride the DB clone; verify active state, plan IDs, and expiry/suspension logic fire on the new host.
- **Content** — title, content, author, publication date, featured image, comments, existing categories/tags (remapped to new taxonomy).
- **Media** — `wp-content/uploads/` migrates as-is; attachment records via DB clone.
- **URLs / 301s** — every moved post needs a redirect from old URL; mandatory and planned before launch.
- **Friendships → follow** — BuddyPress is the relationship engine; existing friendship/message tables survive the clone; the new front-end UX is one-way **volg** (follow) on top. The "Following"/"Wie ek volg" tab replaces the old "Vriende" tab.

### Risks / edge cases a PRD should flag
- **CPT slug mismatch: glossary says `storie`, migration plan & decisions say `verhaal`.** Must be reconciled before scripting or redirects/permalinks will diverge from the canonical term. (Also `bronze` vs `brons` casing for tier default.)
- **Post reclassification reliability** — depends on writers' self-assigned content-type categories (`Gedig`/`Verhaal`/`Artikel`); unreliable categories force a manual bulk-edit pass. Volume is several thousand posts (50–300/month over years).
- **`skryfwerk` holding bucket** — unclassifiable posts land here automatically; PRD should define how/whether they're ever reclassified, and that it's not a user-facing term.
- **Redirect volume** — flat `/[slug]/` posts moving to `/gedig/`, `/storie/`, `/artikel/` bases is very high volume. Recommendation: keep `/biblioteek/` and `/opleiding/` prefixes unchanged to preserve high-value archive URLs and cut redirect volume.
- **Tier import edge cases** — writers in spreadsheet with no WP account (flag for manual follow-up); ambiguous/missing tier → default Brons and flag; promotion history needs a second meta key or log table; join on email.
- **Subscriptions are EFT/manual today** — owner activates memberships manually after EFT; PayFast is installed but unused. Enabling PayFast self-purchase is a NEW feature, not a migration task.
- **BuddyPress / Youzify data** — activity stream may be very large (consider trimming >2yr); notifications NOT migrated (regenerate naturally); if Youzify removed, extract its custom-table profile/social + FES member upload data BEFORE deactivation, and re-associate uploads with the new submission model.
- **Challenge history scope undecided** — recommended low-risk start: new challenge CPT from launch date, leave historical challenges as flat archive posts.
- **Don't clone `wp_options` wholesale** — set the new site up cleanly; carry only deliberate values (site URL/name, Afrikaans locale, intentional Yoast SEO config).
- **Comments** — migrate with posts; verify counts match before/after.

---

## Voice & Tone

- **Afrikaans-first and warm**, addressing the member as "jy". The platform is framed as a literary *tuiste* (home/sanctuary), a community "nie 'n markplek nie" — quiet, human, non-commercial.
- **Developmental, not gatekeeping.** Feedback is framed as a gift: "Ons prys spesifiek, stel saggies voor, en trep nooit op mense nie" / "Begin met wat werk." Critique is structured into lof / insig / voorstel rather than judgement. CTAs invite ("Of dit 'n verfynde konsep is of 'n dapper eerste poging — jou stem hoort hier").
- **Sentence-case headings** — Afrikaans uses fewer capitals than English ("Begin skryf", not "Begin Skryf").
- **Terminology discipline** — UI copy must follow `afrikaans-terms.md` (bydrae, skrywer, plaas, volg, leeslys); avoid English loanwords (Biblioteek not library, uitdaging not challenge) and the banned terms list.
- **Quiet over loud** — "Weerklank wen van bereik. 'n Deurdagte leser tel meer as 'n virale oomblik." Tone favours resonance over hype; counts shown without verbs (e.g. "342 hartjies").

---

## Launch content gates (pre-launch content requirements)

The Lovable mockup ships with placeholder org details that need **real, confirmed values before launch**:

- **Founding year** — footer string "...sedert 2018" / "[stigtingsjaar]"; INK's real founding year must be confirmed and used.
- **Copyright year** — "© [jaar] INK" must use the real/current year.
- **Legal status** — placeholder "501(c)(3) nonprofit" / "[regstatus]" is US/mockup boilerplate; INK's actual legal/registration status ("niewinsgerigte gemeenskapsorganisasie") must be confirmed before display.
- **Stats placeholders** — "Active Writers", "Published Works", "[N]" counts etc. are mockup figures; real values needed at launch.
- **Generated sample content** — mockup story/poem bodies, author bios, and critique text are placeholders to be replaced with real INK content.
- **Copy decision pending** — challenge submission CTA: "Plaas jou bydrae" (informal, glossary-true) vs "Dien jou inskrywing in" (formal, competition tone) — confirm preference.
