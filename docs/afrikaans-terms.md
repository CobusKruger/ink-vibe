# INK Afrikaans Terminologiegids

## Doel

Hierdie dokument stel die amptelike INK-terminologie vas wat dwarsdeur die nuwe webwerf, admin-koppelvlak, kommunikasie met lede, en kodebasis gebruik moet word.

Konsekwentheid is die hoofoogmerk. As die stelsel op een bladsy sê "plasing" en op 'n ander bladsy "pos", verloor die gebruiker vertroue. As die kode `post_type = 'story'` registreer maar die UI dit 'n "artikel" noem, lei dit tot verwarring vir ontwikkelaars en redakteurs.

Die reël is eenvoudig: **kies een term per konsep en hou by daardie term.**

---

## Hoe om hierdie dokument te gebruik

- **Webontwikkelaar:** gebruik die kode-ID soos gespesifiseer. Gebruik die Afrikaanse UI-label in alle gebruikersblootgestelde stringe.
- **Redakteur/inhoudsbestuurder:** gebruik die UI-term konsekwent in alle admin-kopie, e-pos aan lede, nuusbriewe en plasing op die webwerf.
- **Ontwerper:** gebruik die UI-term in koppelvlak-ontwerp, ikoonskrifkelette en toestandsboodskappe.
- **Kopieskrywer:** volg die UI-term. Moenie variaante skep nie, behalwe as die tabel eksplisiet 'n aanvaarbare variasie lys.

---

## Deel 1: Kernterme

### Gebruikers en identiteit

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Persoon met 'n rekening | **lid** | `member` / `user` | Gebruik "lid" vir alle gebruikerblootgestelde stringe. "Gebruiker" is tegniese/kode-taal. |
| Iemand wat lees maar nie geregistreer is nie | **besoeker** | `visitor` | |
| Iemand wat werk indien | **skrywer** | `writer` | Nie "outeur", nie "gebruiker" nie. |
| 'n Nuwe lid wat nog nie gepubliseer het nie | **nuwe lid** | — | |
| Admin/personeel | **redakteur** | `editor` / `admin` | WordPress-rolle: `editor` vir redaksionele personeel, `administrator` vir tegniese beheer. |
| Die openbare profiel van 'n skrywer (vir ander sigbaar) | **Skrywerprofiel** | `single-skrywer` | **Eienaarsbesluit 2026-06-20 (G1/C6):** "Skrywerprofiel" is die **openbare** profiel. Let op spelling: Skrywer**profiel** (nie "Skrywersprofiel" nie). |
| Die lid se eie private profiel (slegs vir die lid sigbaar) | **My Profiel** | `page-my-profiel` | Die **private** profiel. Hier verskyn private data soos leesgetalle en die "wins nodig"-subteks. |

---

### Rekening en toegang

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die aanmeldproses | **registreer** | — | |
| Die lid se toegangstoestand (gratis of betaald) | **lidmaatskap** | `membership` | **Eienaarsbesluit 2026-06-20 (G1):** "lidmaatskap" is nou die enigste term vir die membership/subscription-konsep. Die vorige onderskeid tussen "lidmaatskap" en "intekening" is laat vaar — gebruik **nie** meer "intekening" nie. Die gratis/betaald-onderskeid word deur "gratis lid" / "betaalde lid" uitgedruk. |
| Die prys en tydsduur van 'n lidmaatskap | **aansluitingsopsie** | `membership_plan` | Drie vaste termyne: 1 maand / 6 maande / 12 maande. |
| Die tydsduur van 'n aansluitingsopsie (vertoonetiket) | **1 maand** / **6 maande** / **12 maande** | `term_1_month` / `term_6_months` / `term_12_months` | Vaste waardestel (Story 4.1, `LidmaatskapTerm`-enum, agtergrondwaarde = aantal maande 1/6/12). Net die termynlengte word in `ink-core` vasgelê; die prys (R60/R300/R600) word deur die WooCommerce-produk besit en deur 'n redakteur in WooCommerce-admin verstel — geen vaste pryswaarde in kode nie. |
| Iemand met 'n aktiewe betaalde lidmaatskap | **betaalde lid** | — | **Eienaarsbesluit 2026-06-20 (G1):** vervang "intekenlid". Mag werk plaas en alle opleidingstof gebruik. |
| Iemand met 'n gratis geregistreerde rekening | **gratis lid** | — | Het 'n rekening; mag lees, reageer, volg en 'n leeslys hou. Mag **nie** werk plaas of opleidingstof gebruik nie. |

---

### Rekening-goedkeuring (R6 — opsionele backstop)

*Eienaarsbesluit 2026-06-20 (C8) / Storie 3.6: 'n opsionele, **af-by-verstek** goedkeuringstou wat 'n redakteur kan aanskakel slegs as misbruik dit regverdig. Wanneer af, bly registrasie heeltemal vryevloei (UJ-1). Hierdie terme is **[NEEDS HUMAN AFRIKAANS]** — voorgestelde standaard-Afrikaans, **wag op redakteur-bekragtiging** voordat dit aan lede gewys word. Moenie KI-vertaal nie; die volledige lid-gerigte sinne en e-poskopie bly in `ui-copy-translations.md` as `[NEEDS HUMAN AFRIKAANS]` gemerk.*

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die toestand van 'n nuwe rekening wat op 'n redakteur se goedkeuring wag | **wag op goedkeuring** | `account_pending` | ⚠️ Voorgestel — wag op bekragtiging. Slegs aktief wanneer die backstop AAN is; andersins bestaan hierdie toestand glad nie (vryevloei). |
| Die handeling om 'n wagtende rekening te aanvaar | **goedkeur** | `account_approve` | ⚠️ Voorgestel — wag op bekragtiging. Werkwoord op die goedkeuringstou-knoppie. |
| Die handeling om 'n wagtende rekening te weier | **verwerp** | `account_reject` | ⚠️ Voorgestel — wag op bekragtiging. Werkwoord op die goedkeuringstou-knoppie. |
| Die admin-skerm wat wagtende rekeninge lys | **Rekening-goedkeuringstou** | `account_approval_queue` | ⚠️ Voorgestel — wag op bekragtiging. Redakteur-skerm (WP-admin-chrome, `ink_moderate`-gekeur); geen ontwerpstelsel-werk. |

---

### Skrywersvlakke

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die vorderingstelsel vir skrywers | **Gradering** | `writer_tier` | **Eienaarsbesluit 2026-06-20 (G1):** "Gradering" is die primêre UI-term vir die skrywer se vlak. "ster gradering" bly aanvaarbaar as langer vorm. Moenie "tier" in die UI gebruik nie. |
| Vlak 1 | **Brons** | `brons` | Hoofletters in UI. Kleinletters in kode. |
| Vlak 2 | **Silwer** | `silwer` | |
| Vlak 3 | **Goud** | `goud` | |
| Vlak 4 (slegs handmatig) | **Meester** | `meester` | **Eienaarsbesluit 2026-06-20 (R3):** hoogste Gradering, slegs handmatig deur 'n redakteur toegeken (nooit outomaties bevorder nie). Vertoon in die handelsmerk-rooi-oranje (`primary #EA4015`), nie die gold/silwer/brons-kleure nie. |
| Bevorder na 'n hoër vlak | **bevorder** | — | "Jy is na Silwer bevorder." Outomaties na 5 Brons-wins (→ Silwer) en 15 Silwer-wins (→ Goud); Meester slegs handmatig. |
| 'n Top-3-plasing wat tel vir bevordering | **wins / top-3-uitslag** | `ink_tier_win_count` | Subteks op My Profiel: bv. "4 top 3 uitslae nodig om Silwer te bereik". |
| Historiese vlakrekord | **graderingsgeskiedenis** | `tier_history` | |

---

### Werk en indiening

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| 'n Stuk werk wat 'n lid plaas | **bydrae** | `submission` / `post` | "Bydrae" is die algemene term. Moenie "plasing", "pos" of "artikel" as 'n versamelnaamwoord gebruik nie. |
| 'n Gedig | **gedig** | post_type: `gedig` | Meervoud: "gedigte". |
| 'n Kortverhaal of prosa-stuk | **storie** | post_type: `storie` | Meervoud: "stories". Kode-ID in lyn met UI-term (voorheen `verhaal`). |
| 'n Opiniestuk, essay of artikel | **artikel** | post_type: `artikel` | Meervoud: "artikels". |
| Die handeling om 'n bydrae in te dien | **plaas** | — | "Plaas jou werk." Moenie "submit", "indien" of "oplaai" as die primêre term gebruik nie. |
| Die redigeerproses voor publikasie | **redigeer** | — | |
| Die skakel om 'n nuwe bydrae in te dien | **Plaas nuwe werk** | — | Gebruik dit konsekwent as die UI-skakel / knoppie. |
| Gepubliseerde status | **gepubliseer** | `publish` | |
| Konsep-status | **konsep** | `draft` | |

---

### Biblioteek

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die seksie wat uitgesoekte werk bevat | **Biblioteek** | `biblioteek` | Moenie "library" gebruik nie. |
| 'n Item in die biblioteek | **biblioteekitem** | post_type: `biblioteek_item` | |
| Werk wat 'n uitdaging gewen het | **wenwerk** | — | Kan sê "wenwerk uit Oktober 2025". |
| Wenner van 'n uitdaging | **wenner** | `winner` | |
| Skakel van bydrae na biblioteekseksie | **bygevoeg tot die Biblioteek** | — | |

---

### Opleiding en hulpbronne

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die seksie wat leerstofartikel bevat | **Opleiding** | `opleiding` | Moenie "training" gebruik nie. |
| 'n Artikel in die opleidingseksie | **hulpbronartikel** | post_type: `opleiding_artikel` | |
| 'n Groep verwante hulpbronartikels | **hulpbronversameling** | `resource_collection` | |
| Vaardigheidsarea (taksonomie) | **vaardigheidsarea** | taxonomy: `vaardigheid` | Voorbeelde: Digkuns, Prosa, Taalgids, Algemene wenke. |

---

### Uitdagings en projekte

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die maandelikse kompetisie | **uitdaging** | post_type: `uitdaging` | Moenie "kompetisie", "projek" of "challenge" gebruik nie. |
| Die tema of opdrag van 'n uitdaging | **tema** | `challenge_theme` | |
| Die sluitingsdatum | **sluitingsdatum** | `challenge_deadline` | |
| 'n Inskrywing vir 'n uitdaging | **inskrywing** | `challenge_entry` | Meervoud: "inskrywings" ("1 inskrywing" / "4 inskrywings"). |
| Die volgnommer van 'n inskrywing (per tipe, per uitdaging) | **EntryID** | `entry_number` | **R1/R2:** interne/admin-konsep. Per-tipe volgorde (Gedigte, Stories, Artikels apart), toegeken by kollasie en gestoor om uitslae later te pas. Nie noodwendig lid-blootgestel nie; 'n Afrikaanse UI-etiket word deur 'n mens geskryf indien ooit nodig. |
| 'n Inskrywing wat 2de of 3de geplaas het | **wenner** | — | Banier: "[Maand] wenner" (bv. "Desember wenner"), met kleur/ikoon vir Goud/Silwer/Brons. |
| 'n Inskrywing wat 1ste geplaas het | **algehele wenner** | — | Banier: "[Maand] algehele wenner". Kry meer prominente plasing in die voer as gewone wenners. |
| Geen wenner in 'n kategorie/gradering nie | **Geen** | — | Soos in die beoordelaars se uitslae aangedui. |
| Die beoordelaar/moderator se terugvoer op 'n inskrywing | **Terugvoer van die moderator** | `ink_moderator_terugvoer` | **R2:** gestoor as 'n gestruktureerde reaksie (custom `comment_type`), nie 'n oop WP-kommentaar nie. Vertoon op die werk slegs as die skrywer dit op My Profiel aanskakel. |
| Die aankondiging van 'n uitdaging | **uitdagingaankondiging** | — | |
| Die aankondiging van wenners | **wenneraankondiging** | — | Outomaties gegenereer uit 'n vormbriefsjabloon; verskyn in 'n uitgeligte posisie op die tuisblad. |
| Die resultate van 'n uitdaging | **uitdaginguitslae** | — | |

---

### InkPols

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die INK-tydskrif | **InkPols** | — | Die naam bly soos is — dit is 'n handelsmerk. |
| 'n Spesifieke uitgawe | **uitgawe** | post_type: `inkpols_uitgawe` | Moenie "issue" gebruik nie. |
| Die PDF van 'n uitgawe | **lees die uitgawe** | — | Knopieteks vir die PDF-skakel. |

---

### Gemeenskap en sosiale interaksie

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die lys van gestoorde werke van 'n lid | **leeslys** | `reading_list` | Moenie "leesvolgorde" gebruik nie. |
| Eenrigting sosiale verbinding (volg 'n skrywer) | **volg** | `follow` (`ink-core`) | Asimmetries; vervang die vorige vriendskapsmodel. |
| Iemand wat 'n skrywer volg | **volgeling** | — | Meervoud: "volgelinge". **Nie** "volger" nie. |
| Die aksie om 'n skrywer te volg | **Volg** | — | Wisselknoppie wys "Volg tans" sodra jy volg. |
| Die aktiwiteitsvoer van 'n lid | **aktiwiteitsvoer** | `activity` | Moenie "stroom" of "feed" gebruik nie. |
| 'n Reaksie op 'n bydrae | **reaksie** | `reaction` | Dek hartjie, duim op, en "wow". Moenie "like" gebruik nie. Meervoud: "reaksies". Telwoord gebruik enkel-/meervoud: "1 hartjie" / "342 hartjies". |
| 'n Gestruktureerde reaksie op 'n bydrae (die enigste terugvoerpad) | **Gemeenskapsreaksie** | `gemeenskapsreaksie` | Meervoud: "Gemeenskapsreaksies". Tipes: **lof** (`lof`), **insig** (`insig`), **voorstel** (`voorstel`). Vervang WP-kommentaar (sitewyd afgeskakel). |
| 'n Hooglignering van 'n reël in 'n bydrae | **hooglignering** | `highlight` | Die aksie: "Merk hierdie reël." |
| Kommentaar op 'n bydrae | **kommentaar** | `comment` | Meervoud: "kommentaar" (dieselfde). |
| 'n Berig aan 'n lid | **boodskap** | `message` | Meervoud: "boodskappe". |
| Stelselkennisgewings aan 'n lid | **kennisgewing** | `notification` | Meervoud: "kennisgewings". |
| Die ledegids | **ledegids** | `member_directory` | Moenie "members list" of "directory" gebruik nie. |

---

### Borge en ondersteuners

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| 'n Borg | **borg** | post_type: `borg` | Meervoud: "borge". |
| Die borgskapsblad | **Ons borge** | — | Bladtitel. |
| Borgskaptipe / vlak | **borgtipe** | `sponsor_tier` | Bv. "Hoofd borg", "Ondersteunende borg". |

---

## Deel 2: UI-aksietale

Hierdie woorde en frases verskyn as knoppies, skakels en toestandsboodskappe. Gebruik hulle konsekwent.

| Aksie | UI-term |
|---|---|
| bydraes en skrywers verken / deurblaai (nav + Browse-bladsy) | **Ontdek** |
| 'n nuwe bydrae skep | **Plaas nuwe werk** |
| 'n bestaande bydrae wysig | **Redigeer** |
| 'n bydrae verwyder | **Verwyder** |
| inskryf vir 'n uitdaging | **Skryf in** |
| 'n skrywer volg | **Volg** |
| ophou volg | **Volg nie meer nie** |
| kommentaar laat | **Laat kommentaar** |
| reageer op 'n bydrae | **Reageer** |
| 'n reël merk/hoogsig | **Merk hierdie reël** |
| aansluit by 'n betaalde lidmaatskap / 'n plan koop | **Inteken** *(⚠️ hersien — Eienaarsbesluit 2026-06-20 G1 het die selfstandige naamwoord "intekening" laat vaar; bevestig of die aksie-knoppie "Inteken" bly en of 'n lidmaatskap-gebaseerde bewoording verkies word. Geen AI-vertaling — mens moet bevestig.)* |
| rekening skep | **Registreer** |
| aanmeld | **Meld aan** |
| afmeld | **Meld af** |
| profiel sien | **Sien profiel** |
| deel op sosiale media | **Deel** |

---

## Deel 3: Stelsel- en statusboodskappe

| Situasie | Boodskap |
|---|---|
| Indiening suksesvol | "Jou bydrae is geplaas." |
| Konsep gestoor | "Konsep gestoor." |
| Begin volg | "Jy volg nou [naam]." |
| Inskrywing ontvang | "Jou inskrywing is ontvang." |
| Lidmaatskapbevestiging | "Jou lidmaatskap is aktief. Jy kan nou werk plaas." |
| Lidmaatskap verval | "Jou lidmaatskap het verval. Hernu om werk te plaas." |
| Lidmaatskap verval binnekort (herinnering) | "Jou lidmaatskap verval binnekort." |
| Betaling misluk of gekanselleer | "Jou betaling het misluk of is gekanselleer." |
| Gradering-bevordering | "Baie geluk! Jy is na Silwer bevorder." |
| Toegang geweier (nie aangemeld nie) | "Jy moet aangemeld wees om te reageer." |
| Toegang geweier (betaalde lidmaatskap nodig) | "Slegs betaalde lede kan werk plaas. Sien aansluitingsopsies." |

---

## Deel 4: Terme om te vermy

Hierdie woorde en frases moet **nie** gebruik word in enige gebruikerblootgestelde teks nie.

| Moenie gebruik nie | Gebruik eerder |
|---|---|
| post | bydrae, gedig, storie, artikel |
| story | storie |
| artikel (as versamelnaam) | bydrae |
| submit | plaas |
| tier | Gradering |
| intekening / intekenaar / intekenlid | lidmaatskap / betaalde lid (Eienaarsbesluit 2026-06-20, G1) |
| Skrywersprofiel (met 's) | Skrywerprofiel (openbaar) of My Profiel (privaat) |
| badge | — (gebruik nie hierdie konsep nie) |
| feed | aktiwiteitsvoer |
| like | reaksie |
| vriend (as sosiale verbinding) | volg / volgeling (INK gebruik volg-relasies, nie vriendskappe nie) |
| volger | volgeling |
| user | lid |
| profile page | profiel |
| library (Engels) | Biblioteek |
| training (Engels) | Opleiding |
| challenge (Engels) | uitdaging |
| issue (vir InkPols) | uitgawe |
| reading list | leeslys |
| sponsor (Engels) | borg |
| browse / Blaai (as nav/verken-aksie) | Ontdek |

---

## Deel 5: Tegniese naamgewingskonvensies

Hierdie geld vir kode, databasis-sleutels en WordPress-registrasies in `ink-core`.

### Post type slugs

Gebruik Afrikaanse slugs waar dit die inhoudsmodel beskryf:

- `gedig`
- `storie`
- `artikel`
- `uitdaging`
- `biblioteek_item`
- `opleiding_artikel`
- `inkpols_uitgawe`
- `borg`

### User meta sleutels

Gebruik die `ink_` voorvoegsel:

- `ink_writer_tier` (enum: `brons` / `silwer` / `goud` / `meester`)
- `ink_tier_promoted_at`
- `ink_tier_win_count` (top-3-wins tot volgende Gradering; herstel na 0 by bevordering)

### Enum-waardes (`ink-core` Kernel)

- `Tier`: `Brons` / `Silwer` / `Goud` / `Meester` (Meester slegs handmatig)

### Custom comment types

- `ink_moderator_terugvoer` ("Terugvoer van die moderator" — programmaties geskryf, nie 'n oop WP-kommentaar nie)

### Taxonomy slugs

- `vaardigheid` (vir opleidingsartikels)
- `genre` (vir bydraes)
- `uitdagingsrondte` (vir inskrywings en wenwerk)
- `ster_gradering` (waar van toepassing as 'n taksonomieterm)

### Uitdaging-inskrywing velde (custom-tabel `ink_entries`)

- `entry_type` + `entry_number` (die EntryID; per-tipe volgorde, toegeken by kollasie)

---

## Onderhoud

Wanneer 'n nuwe konsep ingevoer word wat nog nie in hierdie gids is nie, voeg dit hier by **voor** dit in die kode of UI verskyn. Die gids is die bron van waarheid, nie die kode nie.

Kodekommentaar en UI-stringe moet hierdie terme volg. As 'n ontwikkelaar 'n nuwe string skryf wat 'n term gebruik wat nie in hierdie gids is nie, is dit 'n sein dat die term eers hier goedgekeur moet word.

Die UI-term-kolom word in 'n masjienleesbare register (ink-core Terms) geprojekteer; die gids bly die menslike bron van waarheid. 'n Termverandering word een keer hier gemaak en dan in die register weerspieël (Story 2.0 / AD-10).
