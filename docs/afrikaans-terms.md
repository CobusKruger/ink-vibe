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

---

### Rekening en toegang

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die aanmeldproses | **registreer** | — | |
| Aanmeldskedule / toegangsreëls | **lidmaatskap** | `membership` | Die stelsel se toegangsbeheer is "lidmaatskap". |
| Maandelikse/termynbetaling vir publiseerregte | **intekening** | `subscription` | "Intekening" is spesifiek vir betaalde toegang. Moenie "lidmaatskap" en "intekening" afwisselend gebruik nie — "lidmaatskap" is die bredere toegangstoestand, "intekening" is die finansiële ooreenkoms. |
| Die prys en tydsduur van 'n intekening | **intekenplan** | `membership_plan` | |
| Iemand met 'n aktiewe betaalde intekening | **intekenlid** | — | |
| Iemand met 'n gratis geregistreerde rekening | **gratis lid** | — | |

---

### Skrywersvlakke

| Konsep | UI-term (Afrikaans) | Kode-ID / sleutel | Notas |
|---|---|---|---|
| Die verderingsstelsel vir skrywers | **skrywervlak** | `writer_tier` | Moenie "tier" in die UI gebruik nie. |
| Vlak 1 | **Brons** | `brons` | Hoofletters in UI. Kleinletters in kode. |
| Vlak 2 | **Silwer** | `silwer` | |
| Vlak 3 | **Goud** | `goud` | |
| Bevorder na 'n hoër vlak | **bevorder** | — | "Jy is na Silwer bevorder." |
| Historiese vlakrekord | **vlakgeskiedenis** | `tier_history` | |

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
| 'n Inskrywing vir 'n uitdaging | **inskrywing** | `challenge_entry` | |
| Die aankondiging van 'n uitdaging | **uitdagingaankondiging** | — | |
| Die aankondiging van wenners | **wenneraankondiging** | — | |
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
| 'n Reaksie op 'n bydrae | **reaksie** | `reaction` | Dek hartjie, duim op, en "wow". Moenie "like" gebruik nie. |
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
| 'n nuwe bydrae skep | **Plaas nuwe werk** |
| 'n bestaande bydrae wysig | **Redigeer** |
| 'n bydrae verwyder | **Verwyder** |
| inskryf vir 'n uitdaging | **Skryf in** |
| 'n skrywer volg | **Volg** |
| ophou volg | **Volg nie meer nie** |
| kommentaar laat | **Laat kommentaar** |
| reageer op 'n bydrae | **Reageer** |
| 'n reël merk/hoogsig | **Merk hierdie reël** |
| inteken vir 'n plan | **Inteken** |
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
| Intekeningbevestiging | "Jou intekening is aktief. Jy kan nou werk plaas." |
| Intekening verval | "Jou intekening het verval. Inteken weer om werk te plaas." |
| Vlak-bevordering | "Baie geluk! Jy is na Silwer bevorder." |
| Toegang geweier (nie ingeteken nie) | "Jy moet aangemeld wees om kommentaar te lewer." |
| Toegang geweier (intekening nodig) | "Slegs intekenlede kan werk plaas. Sien intekenopsies." |

---

## Deel 4: Terme om te vermy

Hierdie woorde en frases moet **nie** gebruik word in enige gebruikerblootgestelde teks nie.

| Moenie gebruik nie | Gebruik eerder |
|---|---|
| post | bydrae, gedig, storie, artikel |
| story | storie |
| artikel (as versamelnaam) | bydrae |
| submit | plaas |
| tier | vlak |
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

- `ink_writer_tier`
- `ink_tier_promoted_at`
- `ink_writer_intent` (leser of skrywer)

### Taxonomy slugs

- `vaardigheid` (vir opleidingsartikels)
- `genre` (vir bydraes)
- `uitdagingsronde` (vir inskrywings en wenwerk)
- `skrywervlak` (waar van toepassing as 'n taksonomieterm)

---

## Onderhoud

Wanneer 'n nuwe konsep ingevoer word wat nog nie in hierdie gids is nie, voeg dit hier by **voor** dit in die kode of UI verskyn. Die gids is die bron van waarheid, nie die kode nie.

Kodekommentaar en UI-stringe moet hierdie terme volg. As 'n ontwikkelaar 'n nuwe string skryf wat 'n term gebruik wat nie in hierdie gids is nie, is dit 'n sein dat die term eers hier goedgekeur moet word.
