# INK UI-teks: Konsepvertalings

Hierdie dokument bevat konsepvertalings van alle UI-koppe, -aksies en -beskrywende teks in die Lovable-mockup — alles behalwe gegenereerde verhaal- en gediginhoud, outeursbios en kritiekteks, wat met werklike INK-inhoud vervang sal word.

**Hoe om hierdie dokument te gebruik:**
- Die kolom "Afrikaans" is 'n werkskonsep — merk enige teks wat jy wil aanpas.
- Terminologie volg `afrikaans-terms.md` waar van toepassing (bv. "bydrae", "skrywer", "plaas").
- Verwys na die toepaslike bladsy/komponent bo elke afdeling.
- Wanneer jy tevrede is, kan die goedgekeurde tekste na `afrikaans-terms.md` verplaas word.

> **Belangrik — die Engels is generiese plekhouerteks.** Die Engelse kolom kom uit die Lovable-mockup en is bloot generiese voorbeeldteks. Die Afrikaans mag (en behoort dikwels) in betekenis af te wyk — dit is 100% gepas en doelbewus vir die INK-konteks. Spesifieke Afrikaanse vertalings moet **nooit** deur 'n letterlike/masjien- (KI-) vertaling van die Engels vervang word nie. Behandel die Afrikaans as die bron van waarheid, nie die Engels nie.

---

## Navigasie (`Header.tsx`, `Footer.tsx`)

### Hoof-navigasiebalk

| Engels | Afrikaans | Notas |
|---|---|---|
| Home | Tuis | |
| Browse | Ontdek | Bevestig: "Ontdek" (verkies bo "Blaai" — meer uitnodigend) |
| Library | Opleiding | Gebruik die amptelike seksienaam |
| Challenges | Uitdagings | |
| Community | Gemeenskap | |
| My Profile | My profiel | |
| Start Writing | Begin skryf | |

### Voettekst

| Engels | Afrikaans | Notas |
|---|---|---|
| A sanctuary for writers and readers, fostering meaningful literary connections since 2018. | 'n Tuiste vir skrywers en lesers, wat sinvolle literêre bande smee sedert 2018. | Stigtingsjaar (2018) word nog met die stigter bevestig — nie 'n blokkeerder nie; behou 2018 voorlopig |
| **Explore** | **Ontdek** | Voettekstkopie |
| Latest Stories | Jongste bydraes | |
| Poetry Collection | Gedigversameling | |
| Featured Authors | Uitgesoekte skrywers | |
| Monthly Challenges | Maandelikse uitdagings | |
| **Community** | **Gemeenskap** | |
| Writing Groups | Skryfgroepe | |
| Critique Circles | Terugvoerkringe | |
| Events | Geleenthede | |
| Newsletter | Nuusbrief | |
| **Support Us** | **Ondersteun ons** | |
| Become a Sponsor | Word 'n borg | |
| Donate | Skenk | |
| Volunteer | Word 'n vrywilliger | |
| About Us | Meer oor INK | |
| © 2024 Inkwell Community. A 501(c)(3) nonprofit organization. | © 2026 INK. 'n Niewinsgerigte gemeenskapsorganisasie. | Bevestig: generiese beskrywing sonder regsbesonderhede; jaar = 2026 |
| Made with ♥ for writers everywhere | Gemaak met ♥ vir skrywers oral | |

---

## Tuisblad (`Index.tsx`)

### Hero-kollig (`HeroSpotlight.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Where Every Word Finds Its Reader | Waar woorde lesers vind | Klein motto bo die opskrif |
| Stories That Deserve to Be **Read & Cherished** | Stories wat verdien om **gelees en gekoester** te word | H1 |
| Join a thriving community of writers and readers who believe in thoughtful feedback and genuine literary connection. | Sluit aan by 'n lewendige gemeenskap van skrywers en lesers met 'n passie vir Afrikaanse letterkunde. | Inleidende alinea |
| Start Reading | Begin lees | Primêre knoppie |
| Share Your Work | Deel jou werk | Sekondêre knoppie |
| Active Writers | Aktiewe skrywers | Statistiek-etiket |
| Published Works | Gepubliseerde werke | Statistiek-etiket |
| Thoughtful Critiques | Deurdagte terugvoer | Statistiek-etiket |
| **Weekly Challenge** *(spotlight-kaart-tipe)* | **Weeklikse uitdaging** | |
| **Workshop** | **Werkswinkel** | |
| **Announcement** | **Aankondiging** | |

### Uitdagingafdeling (`ChallengeSection.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| January Challenge | Januarie-uitdaging | Dinamiese maandnaam |
| Ends Jan 31 | Sluit 31 Jan | |
| 234 Entries | 18 gedigte · 12 stories · 4 artikels | Breek af per inhoudssoort; skuif nulwaardes weg. "Inskrywings" bly vir die knoppie en reëlsykolom |
| Submit Your Entry | Skryf in | Bevestig: "Skryf in" (gidskonsekwent — sien Deel 2 van terminologiegids) |
| December Winner | Desember-wenner | Dinamiese maandnaam (2de/3de plek — "wenner") |
| December Overall Winner | Desember algehele wenner | Dinamiese maandnaam (1ste plek — "algehele wenner", glossaarterm) |
| 3rd Challenge Win | 3de wen | |
| Read Full Story | Lees die volledige storie | |

> **Besluit oor "Submit Your Entry":** Gebruik **"Skryf in"**. Die terminologiegids (Deel 2) definieer die aksie om aan 'n uitdaging deel te neem as "Skryf in", met die ingediende stuk 'n "inskrywing" en die bevestigingsboodskap "Jou inskrywing is ontvang." ("Indien" staan op die vermy-lys in Deel 4; "plaas" is die algemene plaas-aksie, maar vir uitdagings is "Skryf in" die spesifieke term.)

### Uitgesoekte werke (`FeaturedWorks.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Editor's Picks | Die redakteur se keuse | Boskrif-etiket |
| Featured This Week | Hierdie week se uitgesoektes | H2 |
| View All Works | Sien alle werke | Skakel |
| Short Story *(badge)* | Storie | |
| Poetry *(badge)* | Gedig | |
| Article *(badge)* | Artikel | |
| [X] min *(leestyd)* | [X] min | |

### Borge (`SponsorsSection.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Our Supporters | Ons borge | Boskrif-etiket |
| Made Possible By | Moontlik gemaak deur | H2 |
| As a nonprofit, we rely on the generosity of our sponsors to keep this community thriving. Thank you for believing in the power of words. | As 'n niewinsgerigte organisasie steun ons op die gulhartigheid van ons borge om hierdie gemeenskap te laat floreer. Dankie dat jy in die krag van woorde glo. | Beskrywende teks |
| Become a Sponsor | Word 'n borg | Knoppie |

### Oproep tot aksie (`CallToAction.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Your Story Deserves an Audience | Jou woorde verdien lesers | H2 |
| Whether you're a seasoned writer or just starting your journey, our community is here to read, engage, and help you grow. | Of jy nou 'n ervare skrywer is of pas begin, ons gemeenskap is hier om te lees, betrokke te raak en jou te help groei. | Beskrywende teks |
| Start Writing Today | Begin vandag skryf | Primêre knoppie |
| Explore Stories | Ontdek stories | Sekondêre knoppie |

---

## Blaai / Ontdek-bladsy (`Browse.tsx`)

### Bladsy-opskrif

| Engels | Afrikaans | Notas |
|---|---|---|
| Browse the community | Die INK-gemeenskap | Boskrif-etiket |
| Find a piece worth your evening, or a writer worth your week. | Vind 'n stuk wat jou aand verswelg, of 'n skrywer wat jou bybly. | H1 |
| Every story and poem published on Inkwell, alongside the writers behind them. Search by title, theme, or name. | Elke storie en gedig op INK en die skrywers daaragter. Soek volgens titel, tema of naam. | Inleidende alinea |
| Search stories, poems, or authors… | Vind stories, gedigte of skrywers... | Soekveld-plekhouer (Bydraes-blad) |
| Search writers by name, bio, or genre… | Vind skrywers volgens naam, bio of genre... | Soekveld-plekhouer (Skrywers-blad) |

### Tablette en filters

| Engels | Afrikaans | Notas |
|---|---|---|
| Stories *(tab)* | Bydraes | Wys alle inhoudsoorpe; "Bydraes" is die korrekte versamelnaamwoord |
| Writers *(tab)* | Skrywers | |
| All *(filter)* | Alles | |
| Poetry *(filter)* | Gedigte | Inhoudssoort-filter |
| Short Story *(filter)* | Stories | |
| Article *(filter)* | Artikels | |
| Sort by | Sorteer | |
| New | Nuut | |
| Most discussed | Opspraakwekkend | |
| Most loved | Mees geliefd | |
| Poetry *(skrywersgenre)* | Digkuns | Genre-filter op Skrywers-blad |
| Fiction *(skrywersgenre)* | Prosa | |
| Artikels *(skrywersgenre)* | Artikels | |
| Most Read *(skrywersorteer)* | Meeste gelees | |
| New Voices *(skrywersorteer)* | Nuwe stemme | |

### Storie-kaart-aksies

| Engels | Afrikaans | Notas |
|---|---|---|
| Saved to your reading list | Gestoor na jou leeslys | Toast-boodskap |
| Removed from your reading list | Verwyder van jou leeslys | Toast-boodskap |
| Following [name] | Jy volg nou [naam] | Toast-boodskap |
| Unfollowed [name] | Jy volg [naam] nie meer nie | Toast-boodskap |
| Follow | Volg | Skrywer-kaart-knoppie |
| Following | Volg tans | Wisselknoppie |

---

## Opleiding-bladsy (`Library.tsx`)

### Bladsy-opskrif

| Engels | Afrikaans | Notas |
|---|---|---|
| The Learning Library | Opleiding | Boskrif-etiket — gebruik die amptelike seksienaam |
| Essays and guides on the craft of writing. | Artikels en gidse oor die skryfkuns. | H1 |
| A growing shelf of short, careful pieces on poetry, fiction, and the work of revision. Read in any order — there is no syllabus, only company for the page. | 'n Groeiende rak vol kort, sorgvuldige stukke oor digkuns, prosa en die redigering. Lees in enige volgorde — daar is geen sillabus nie, net jy en die bladsy. | Inleidende alinea |
| Search articles, authors, or topics… | Soek artikels, skrywers of onderwerpe... | Soekveld |

### Kategorieë

| Engels | Afrikaans | Notas |
|---|---|---|
| All | Alles | |
| Getting Started | Begin hier | |
| Craft & Technique | Skryfkuns | |
| Poetry | Digkuns | |
| Fiction | Prosa | |
| Literary Devices | Stylfigure | |
| Editing & Revision | Redigeer en hersien | |
| Voice & Style | Stem en styl | |

### Redakteur se rak en leë toestande

| Engels | Afrikaans | Notas |
|---|---|---|
| Editor's shelf | Die redakteur se rak | H2 |
| Three pieces to begin with. | Drie stukke om mee te begin. | Ondersteunende teks |
| Nothing on this shelf yet. | Nog niks op hierdie rak nie. | Leë toestand |
| Try a different search or browse all articles. | Probeer 'n ander soekterm of blaai deur alle artikels. | Leë toestand — ondersteunende teks |
| Clear filters | Vee filters uit | Knoppie |
| [N] article / [N] articles | [N] artikel / [N] artikels | Resultaatteller |
| in [Category] | in [Kategorie] | Resultaatteller-suffix |
| matching "[query]" | wat ooreenstem met "[soekterm]" | Resultaatteller-suffix |
| Read *(kaart-aksie-etiket)* | Lees | |

### Sluitende oproep tot aksie

| Engels | Afrikaans | Notas |
|---|---|---|
| Have something to share? | Het jy iets om te deel? | H2 |
| The library is written by our community. If you have a craft essay or a guide you would like to contribute, we would love to read it. | Die opleidingsafdeling word deur ons gemeenskap geskryf. As jy 'n skryfkunsessay of 'n gids wil bydra, sal ons dit graag wil lees. | Beskrywende teks |
| Submit a piece | Plaas 'n stuk | Knoppie |

---

## Uitdaging-detailbladsy (`Challenge.tsx`)

### Opskrif en metadata

| Engels | Afrikaans | Notas |
|---|---|---|
| ← Back to home | ← Terug na tuis | Navigasieskakel |
| Weekly Challenge | Weeklikse uitdaging | Tipe-etiket |
| [N] writers entered | [N] skrywers het ingeskryf | Metadata |
| Editor's pick | Die redakteur se keuse | Metadata-etiket |
| Ends [date] | Sluit [datum] | Metadata |

### Knoppies

| Engels | Afrikaans | Notas |
|---|---|---|
| Submit Your Entry | Skryf in | Gidskonsekwent — sien Deel 2 van terminologiegids en noot by Uitdagingafdeling |
| Read Entries | Lees inskrywings | |
| Start Writing | Begin skryf | |
| Browse other challenges | Ontdek ander uitdagings | |
| View all entries | Sien alle inskrywings | |

### Inhoudsafdelings

| Engels | Afrikaans | Notas |
|---|---|---|
| The Prompt | Die uitdaging | H2 |
| Literary devices to lean on | Stylfigure om te gebruik | H3 |
| Submission Rules | Indieningsreëls | Sykolom-opskrif |
| Prize | Prys | Etiket |
| **Sharpen Your Craft** | **Slyp jou skryfkuns** | Afdeling-boskrif |
| Learning resources for this challenge | Leerhulpbronne vir hierdie uitdaging | H2 |
| Hand-picked reading to help you wield silence, subtext, and interiority with confidence. | Uitgesoekte leesstof om jou te help om stilte, subteks en innerlikheid met vertroue te gebruik. | Beskrywende teks *(inhoud sal wissel per uitdaging)* |
| **Already Submitted** | **Reeds ingedien** | Afdeling-boskrif |
| Entries from the community | Inskrywings uit die gemeenskap | H2 |

### Sluitende oproep tot aksie

| Engels | Afrikaans | Notas |
|---|---|---|
| Your story is waiting to be written | Jou storie wag om geskryf te word | H2 |
| Join [N] writers already exploring this prompt. Whether it's a polished draft or a brave first attempt — your voice belongs here. | Sluit aan by [N] skrywers wat reeds hierdie uitdaging verken. Of dit 'n verfynde konsep is of 'n dapper eerste poging — jou stem hoort hier. | Beskrywende teks |

---

## Gemeenskap-bladsy (`Community.tsx`)

### Hero

| Engels | Afrikaans | Notas |
|---|---|---|
| The Inkwell Community | Die INK-gemeenskap | Boskrif-etiket |
| A community for writers who want to be read, and readers who want to be moved. | 'n Gemeenskap vir skrywers wat gelees wil word, en lesers wat ontroer wil word. | H1 |
| Inkwell is a nonprofit literary home built around a quiet idea: that thoughtful writing deserves thoughtful readers, and both deserve a better place to find each other. | INK is 'n niewinsgerigte literêre tuiste gebou rondom 'n eenvoudige idee: dat deurdagte skryfwerk lesers verdien, en dat albei 'n beter plek verdien om mekaar te vind. | Inleidende alinea |
| Join as a Writer | Sluit aan as skrywer | Primêre knoppie |
| Join as a Reader | Sluit aan as leser | Sekondêre knoppie |

### Waardekolonne: Vir skrywers / Vir lesers

| Engels | Afrikaans | Notas |
|---|---|---|
| For Writers | Vir skrywers | H2 |
| Publish work that actually gets read — and get the kind of feedback that helps you grow. | Plaas werk wat werklik gelees word — en ontvang die soort terugvoer wat jou laat groei. | |
| Structured critiques | Gestruktureerde terugvoer | Voordeel-opskrif |
| Real readers, not just other writers | Regte lesers, nie net ander skrywers nie | |
| Inkwell is built for readers first, so your work reaches people who came to read — not to be read. | INK is eerste en voorste vir lesers gebou, sodat jou werk mense bereik wat gekom het om te lees — nie om gelees te word nie. | |
| Monthly challenges | Maandelikse uitdagings | |
| Prompts that stretch your craft, with recognition for standout entries and an audience guaranteed. | Uitdagings wat jou skryfvermoëns toets, met erkenning vir uitstaande inskrywings en 'n gewaarwaarborgde gehoor. | |
| A profile that grows with you | 'n Profiel wat saam met jou groei | |
| Pin your best work, surface your accomplishments, and let readers follow your next chapter. | Speld jou beste werk vas, vertoon jou prestasies, en laat lesers jou volgende hoofstuk volg. | |
| For Readers | Vir lesers | H2 |
| Discover writers worth following, and become the kind of reader writers remember. | Ontdek skrywers die volg werd is, en word die soort leser wat skrywers onthou. | |
| Discover new voices | Ontdek nuwe stemme | |
| Curated stories and poems from emerging writers — short enough to read in a coffee break, deep enough to stay with you. | Saamgestelde stories en gedigte van opkomende skrywers — kort genoeg vir 'n koffiepouse, diep genoeg om by jou te bly. | |
| Respond with intention | Reageer met bedoeling | |
| Highlight a sentence. Leave a structured note. Tell a writer what landed, instead of scrolling past. | Merk 'n sin uit. Los 'n gestruktureerde nota. Sê vir 'n skrywer wat geraak het, in plaas van om verby te blaai. | |
| Build your reading list | Bou jou leeslys | |
| Save works to revisit, follow writers you love, and let your saves quietly signal what's worth reading. | Stoor werke om weer te besoek, volg skrywers wat jy liefhet, en laat jou gestoorde werk subtiel wys wat die lees werd is. | |
| Support a nonprofit | Ondersteun 'n nonprofit | |
| Inkwell is a community, not a marketplace. Your time here directly supports independent literary work. | INK is 'n gemeenskap, nie 'n markplek nie. Jou tyd hier ondersteun direk onafhanklike literêre werk. | |

### Statistieke

| Engels | Afrikaans | Notas |
|---|---|---|
| Active writers | Aktiewe skrywers | |
| Works published this month | Werke hierdie maand gepubliseer | Oorweeg om by hoofstadistieke af te breek na "gedigte · stories · artikels" |
| Structured critiques exchanged | Gestruktureerde terugvoer uitgeruil | |
| Challenges this year | Uitdagings hierdie jaar | |

### Kollig

| Engels | Afrikaans | Notas |
|---|---|---|
| This Month's Spotlight | Hierdie maand se kollig | Boskrif-etiket |
| The people who make Inkwell, Inkwell | Die mense wat INK maak | H2 |
| Featured Writer | Uitgesoekte skrywer | Kaart-etiket |
| Featured Reader | Uitgesoekte leser | Kaart-etiket |
| Short fiction · Mexico City | Kortverhale · Buenos Aires | *(Dinamiese inhoud — voorbeeld)* |
| Reader · 312 critiques given | Leser · 312 terugvoere gegee | *(Dinamiese inhoud — voorbeeld)* |

### Hoe INK werk

| Engels | Afrikaans | Notas |
|---|---|---|
| How Inkwell works | Hoe INK werk | H2 |
| A simple loop for both sides of the page. | 'n Eenvoudige siklus vir beide kante van die bladsy. | Ondersteunende teks |
| For Readers | Vir lesers | H3 |
| Read | Lees | Stap 1 |
| Browse curated stories and poems, or follow writers whose voices you trust. | Blaai deur saamgestelde stories en gedigte, of volg skrywers wie se stemme jy vertrou. | |
| Respond | Reageer | Stap 2 |
| Highlight a line. Leave a structured critique. Save it to your reading list. | Merk 'n reël. Los 'n gestruktureerde kritiek. Stoor dit na jou leeslys. | |
| Connect | Verbind | Stap 3 |
| Discover more writers through what other thoughtful readers are saving. | Ontdek meer skrywers deur wat ander deurdagte lesers stoor. | |
| For Writers | Vir skrywers | H3 |
| Write | Skryf | Stap 1 |
| Publish a piece on its own or as an entry to a monthly challenge. | Publiseer 'n stuk op sy eie of as 'n inskrywing vir 'n maandelikse uitdaging. | |
| Receive structured feedback | Ontvang gestruktureerde terugvoer | Stap 2 |
| Praise, insight, and suggestion — from readers who came to read. | Lof, insig en voorstelle — van lesers wat gekom het om te lees. | |
| Grow your audience | Bou jou gehoor | Stap 3 |
| Build a profile readers follow, and watch your readership compound. | Bou 'n profiel wat lesers volg, en kyk hoe jou leserskring groei. | |

### Gemeenskapsbeginsels

| Engels | Afrikaans | Notas |
|---|---|---|
| How we treat each other | Hoe ons mekaar behandel | Boskrif-etiket |
| Community principles | Gemeenskapsbeginsels | H2 |
| Critique with care | Gee terugvoer met sorg | Beginsel-opskrif |
| Feedback is a gift. We praise specifically, suggest gently, and never punch down. | Terugvoer is 'n gawe. Ons prys spesifiek, stel saggies voor, en trep nooit op mense nie. | |
| Read generously | Lees grootmoedig | |
| Every piece here took courage to publish. Begin with what's working. | Elke stuk hier het moed gekos om te publiseer. Begin met wat werk. | |
| Writers and readers, equal partners | Skrywers en lesers, gelyke vennote | |
| Neither group exists without the other. Both are the community. | Geen groep bestaan sonder die ander nie. Albei is die gemeenskap. | |
| Quiet over loud | Stil bo luidrugtig | |
| Resonance beats reach. A thoughtful reader matters more than a viral moment. | Weerklank wen van bereik. 'n Deurdagte leser tel meer as 'n virale oomblik. | |

### Sluitende oproep tot aksie

| Engels | Afrikaans | Notas |
|---|---|---|
| Ready to join Inkwell? | Gereed om by INK aan te sluit? | H2 |
| It's free, it's nonprofit, and it's quietly becoming the best place online to read and be read. | Dit is gratis, dit is niewinsgericht, en dit word stilletjies die beste plek aanlyn om te lees en gelees te word. | |
| Create your account | Skep jou rekening | Primêre knoppie |
| Look around first | Kyk eers rond | Sekondêre knoppie |

---

## Skryf-bladsy (`Write.tsx`)

### Bladsy-opskrif en inhoudskeuse

| Engels | Afrikaans | Notas |
|---|---|---|
| Share Your Words | Deel jou woorde | H1 |
| Every story begins with a single word. Start yours here. | Elke storie begin met 'n enkele woord. Begin joune hier. | Ondersteunende teks |
| Poem | Gedig | Inhoudsoorttipe |
| Express emotions through verse, rhythm, and imagery. | Druk emosies uit deur vers, ritme en beelding. | |
| Short Story | Storie | Gidsterm "storie" (badge en raam-etiket gebruik reeds "Storie"; "verhaal" is afgeskaf) |
| Craft a narrative with characters, plot, and meaning. | Skep 'n vertelling met karakters, intrige en betekenis. | |
| Article | Artikel | |
| Share an essay, reflection, or piece of journalism. | Deel 'n essay, besinning of joernalistieke stuk. | |

### Redigeer-koppelvlak

| Engels | Afrikaans | Notas |
|---|---|---|
| Change type | Verander tipe | Skakeletiket |
| Active challenges *(opsioneel)* | Aktiewe uitdagings *(opsioneel)* | Blokopskrif |
| Tick any challenges this piece responds to. | Merk enige uitdagings waarop hierdie stuk reageer. | Blokonderskrif |
| Ends [date] | Sluit [datum] | Uitdagings-meta |
| Title | Titel | Veldnaam |
| Give your work a title... | Gee jou werk 'n titel... | Plekhouer |
| Your [Poem / Short Story / Article] | Jou [Gedig / Storie / Artikel] | Inhoudsraam-etiket |
| [N] lines · [N] words | [N] reëls · [N] woorde | Gedigtellerresultaat |
| [N] words | [N] woorde | Prosatellerresultaat |
| Save Draft | Stoor konsep | Knoppie |
| Publish | Plaas | Primêre knoppie — gebruik "plaas" per terminologiegids |

### Teksblok-plekhouers

| Engels | Afrikaans |
|---|---|
| Begin your poem here...\n\nTip: Use line breaks to structure your verses. | Begin jou gedig hier...\n\nWenk: Gebruik reëlbreuke om jou verse te struktureer. |
| Start your story here...\n\nTip: Short stories typically range from 1,000 to 7,500 words. | Begin jou storie hier...\n\nWenk: Kortverhale is gewoonlik tussen 1 000 en 7 500 woorde. |
| Open with a strong hook...\n\nTip: Lead with the idea, then ground it in story. | Begin met 'n sterk openingsreël...\n\nWenk: Begin met die idee, grond dit dan in 'n storie. |

### Suksesskerm

| Engels | Afrikaans | Notas |
|---|---|---|
| Your [poem / story / article] is live | Jou [gedig / storie / artikel] is gepubliseer | H1 |
| Thank you for sharing "[title]". Writing is a conversation — the community grows when writers read and respond to each other. | Dankie dat jy "[titel]" gedeel het. Skryf is 'n gesprek — die gemeenskap groei wanneer skrywers mekaar lees en op mekaar reageer. | |
| Write another piece | Skryf nog 'n stuk | Knoppie |
| Back to home | Terug na tuis | Knoppie |
| Read & respond | Lees en reageer | Afdeling-boskrif |
| Lift another writer today | Gee 'n skrywer vandag 'n hupstoot | H2 |
| A thoughtful comment can change someone's week. Start with one of these. | 'n Deurdagte opmerking kan iemand se week verander. Begin met een van hierdie. | |

---

## Lees-bladsy (`ReadStory.tsx`)

### Bladsy-opskrif

| Engels | Afrikaans | Notas |
|---|---|---|
| Short Story *(badge)* | Storie | |
| [N] min read | [N] min lees | |
| Select any text to highlight your favorite passages | Kies enige teks om jou gunsteling passasies uit te lig | Wenk-etiket |

### Dryf-aksie-balk

*(Ikoonknoppies sonder teks — geen vertaling nodig)*

### Outeur-afdeling

| Engels | Afrikaans | Notas |
|---|---|---|
| Follow | Volg | Knoppie |
| View All Works | Sien alle werke | Knoppie |

### Gemeenskapsreaksies

| Engels | Afrikaans | Notas |
|---|---|---|
| Community Responses | Gemeenskapsreaksies | H2 |
| Share a thoughtful response — what resonated, what surprised you, or what could be even stronger. | Deel 'n deurdagte reaksie — wat jou geraak het, wat jou verras het, of wat nog sterker kon gewees het. | Instruksieteks |
| What worked well | Wat goed gewerk het | Reaksie-tipe-etiket |
| Share an insight | Deel 'n insig | |
| Constructive feedback | Konstruktiewe terugvoer | |
| Your thoughts on this piece... | Jou gedagtes oor hierdie stuk... | Plekhouer |
| Share Response | Deel reaksie | Knoppie |
| Insight *(badge)* | Insig | |
| Praise *(badge)* | Lof | |
| Suggestion *(badge)* | Voorstel | |
| Moderator feedback *(label)* | Terugvoer van die moderator | Beoordelaar/moderator se terugvoer op 'n inskrywing; glossaarterm (R2). Verskyn slegs as die skrywer dit op My Profiel aanskakel |

---

## My Profiel-bladsy (`Profile.tsx`)

### Identiteitsstrook

| Engels | Afrikaans | Notas |
|---|---|---|
| Your profile | Jou profiel | Boskrif-etiket |
| Edit profile | Wysig profiel | Knoppie |
| New post | Nuwe bydrae | Knoppie |
| View public page | Sien openbare bladsy | Knoppie |

### Tablette

| Engels | Afrikaans | Notas |
|---|---|---|
| Overview | Oorsig | |
| Posts | Bydraes | |
| Reading | Leeslys | Leeslys-blad |
| Following | Wie ek volg | Vervang die ou "Vriende"-blad (volg-besluit) |
| Activity | Aktiwiteit | Aktiwiteit van wie jy volg |
| Notifications | Kennisgewings | |
| Membership | Lidmaatskap | |

### Oorsig-blad

| Engels | Afrikaans | Notas |
|---|---|---|
| About | Oor my | Kaart-opskrif |
| At a glance | In 'n oogopslag | Kaart-opskrif |
| Posts *(statistiek-etiket)* | Bydraes | Oorweeg ook "[N] stories · [N] gedigte · [N] artikels" in die uitgebreide oorsig |
| Friends *(statistiek-etiket)* | Vriende | |
| Unread *(statistiek-etiket)* | Ongelees | |
| Inkwell Member · renews [date] | INK-lid · hernu [datum] | |
| Recent activity | Onlangse aktiwiteit | Kaart-opskrif |

### Bydraes-blad

| Engels | Afrikaans | Notas |
|---|---|---|
| Your posts | Jou bydraes | H2 |
| Published *(statusbadge)* | Gepubliseer | |
| Draft *(statusbadge)* | Konsep | |
| Edit | Wysig | Knoppie |
| View | Sien | Knoppie |

### Kennisgewings-blad

| Engels | Afrikaans | Notas |
|---|---|---|
| Notifications | Kennisgewings | H2 |
| Mark all as read | Merk alles as gelees | Knoppie |

### Kennisgewingtemplates

| Engels | Afrikaans | Notas |
|---|---|---|
| [Name] and [N] others liked "[title]" | [Naam] en nog [N] ander het "[titel]" liefgehad | |
| [Name] left a critique on "[title]" | [Naam] het terugvoer gelewer op "[titel]" | |
| [Name] started following you | [Naam] volg jou nou | |
| [Challenge] closes in [N] days | [Uitdaging] sluit oor [N] dae | |

### Lidmaatskap-blad

| Engels | Afrikaans | Notas |
|---|---|---|
| Inkwell Member | INK-lid | Kaart-opskrif |
| Active subscription | Aktiewe lidmaatskap | "lidmaatskap" — sien Deel 3 van terminologiegids (G1, 2026-06-20); "intekening" is afgeskaf |
| Status | Status | Etiket |
| Active | Aktief | Waarde |
| Renews | Hernu | Etiket |
| Member since | Lid sedert | Etiket |
| Renew membership | Hernu lidmaatskap | H3 |
| Choose how long you'd like to extend your Inkwell membership. | Kies hoe lank jy jou INK-lidmaatskap wil verleng. | |
| Monthly | Maandeliks | Planetiket |
| 6 months | 6 maande | |
| 12 months | 12 maande | |
| Save 12% | Spaar 12% | |
| Save 25% | Spaar 25% | |
| / [N]mo | / [N] mnd | Prys-suffix |
| Your support keeps Inkwell ad-free and independent. | Jou ondersteuning hou INK advertensievry en onafhanklik. | |
| Renew for [N] month(s) | Hernu vir [N] maand(e) | Knoppie |

### Gradering-blad (My Profiel — privaat)

*(Scope-increase 2026-06-20, G1/R3. Gradering-subteks verskyn slegs op My Profiel, nie op die openbare Skrywerprofiel nie.)*

| Engels | Afrikaans | Notas |
|---|---|---|
| [N] top-3 results needed to reach Silver | [N] top 3 uitslae nodig om Silwer te bereik | Gradering-bevorderingsubteks; glossaarterm (Deel 1, Skrywersvlakke) |
| [N] top-3 results needed to reach Gold | [N] top 3 uitslae nodig om Goud te bereik | Gradering-bevorderingsubteks; glossaarterm |
| Master *(tier label)* | Meester | Hoogste Gradering, slegs handmatig toegeken; handelsmerk-rooi-oranje. Glossaarterm (R3) |

### Lidmaatskap-lewensiklus e-pos (scope-increase 2026-06-20)

*(Nuwe transaksionele e-pos. Afrikaanse onderwerpe/snippette nog nie in die glossaar nie — wag op menslike kopie.)*

| Engels | Afrikaans | Notas |
|---|---|---|
| Thank-you email (subject + snippet) | [NEEDS HUMAN AFRIKAANS] | Dankie-e-pos ná aansluiting/betaling; menslike kopie nodig |
| 1-month expiry warning (subject + snippet) | [NEEDS HUMAN AFRIKAANS] | Herinnering 1 maand voor verval; menslike kopie nodig |
| 1-week expiry warning (subject + snippet) | [NEEDS HUMAN AFRIKAANS] | Herinnering 1 week voor verval; menslike kopie nodig |

### Sosiale aanmelding (R6 — Storie 3.5, grasieus-degraderende naat)

*(Sosiale-aanmeldafdeling op die meld-aan/registreer-bladsye. Verskyn slegs as 'n gekeurde sosiale-aanmeld-inprop by ontplooiing geaktiveer is (af by verstek ⇒ niks hiervan verskyn nie). Die afdeling-kopie hieronder word as voorlopige plekhouer in die patroonlêers gewys en is **menslike kopie nodig — moenie KI-vertaal nie**; die provider-knoppie-etikette self kom van die derdeparty-inprop (sy `af`-etikette word by integrasie gestel; enige Engels word vir die Epic-17 lek-agterstand aangeteken).)*

| Engels | Afrikaans | Notas |
|---|---|---|
| Social divider line ("Or continue with") | [NEEDS HUMAN AFRIKAANS] | Voorlopig "Of gaan voort met" in die patroon; wag op menslike kopie |
| POPIA consent note (social sign-in shares basic profile data) | [NEEDS HUMAN AFRIKAANS] | Voorlopig "Deur met 'n sosiale rekening voort te gaan, deel jy basiese profielinligting met INK." in die patroon; wag op menslike kopie |
| Privacy-policy link label | [NEEDS HUMAN AFRIKAANS] | Voorlopig "Privaatheidsbeleid"; skakel na die privaatheidsblad (`get_privacy_policy_url()` met `/privaatheidsbeleid`-terugval — die werklike blad is 'n voor-lansering inhoud-hek) |

### Rekening-goedkeuring backstop (R6 — Storie 3.6, af by verstek)

*(Opsionele goedkeuringstou wat 'n redakteur kan aanskakel. **Af by verstek** — wanneer af, bly registrasie vryevloei en niks hiervan verskyn nie (UJ-1). Die enkelwoord-etikette (goedkeur/verwerp/"wag op goedkeuring"/toustnaam) is in die terminologieregister geprojekteer maar **wag op redakteur-bekragtiging**; die volledige lid-gerigte sinne en e-poskopie hieronder is **menslike kopie nodig — moenie KI-vertaal nie**.)*

| Engels | Afrikaans | Notas |
|---|---|---|
| Login-blocked pending notice (full sentence shown when a pending account tries to log in) | [NEEDS HUMAN AFRIKAANS] | Volledige lid-gerigte sin; "jy"-stem, sinskas; voorlopige plekhouer in kode tot menslike kopie land |
| Approve button label | [NEEDS HUMAN AFRIKAANS] | Enkelwoord "Goedkeur" geprojekteer in register; wag op bekragtiging |
| Reject button label | [NEEDS HUMAN AFRIKAANS] | Enkelwoord "Verwerp" geprojekteer in register; wag op bekragtiging |
| Approve result notice ("account approved") | [NEEDS HUMAN AFRIKAANS] | Redakteur-statusboodskap ná goedkeuring; menslike kopie nodig |
| Reject result notice ("account rejected") | [NEEDS HUMAN AFRIKAANS] | Redakteur-statusboodskap ná verwerping; menslike kopie nodig |
| Approval email (subject + body) | [NEEDS HUMAN AFRIKAANS] | Transaksionele e-pos ná goedkeuring; stuur-skakelaar AF tot menslike kopie land |
| Rejection email (subject + body) | [NEEDS HUMAN AFRIKAANS] | Transaksionele e-pos ná verwerping; stuur-skakelaar AF tot menslike kopie land |

### Outomatiese plasing-bevestigingskennisgewings (R7 — gerandomiseerde lys)

*(Gerandomiseerde aanmoedigingsboodskappe ná 'n bydrae geplaas is. Menslike kopie nodig — moenie KI-vertaal nie.)*

| Engels | Afrikaans | Notas |
|---|---|---|
| Auto post-receipt message (randomized variant 1…N) | [NEEDS HUMAN AFRIKAANS] | R7-lys; elke variant deur 'n mens geskryf |

### Wysig-profiel-dialoog

| Engels | Afrikaans | Notas |
|---|---|---|
| Edit profile | Wysig profiel | Dialoogopskrif |
| Change photo | Verander foto | Skakel |
| Name | Naam | Veldnaam |
| Tagline | Slagspreuk | Veldnaam |
| Bio | Bio | Veldnaam |
| Cancel | Kanselleer | Knoppie |
| Save changes | Stoor veranderinge | Knoppie |
| Profile updated | Profiel bygewerk | Toast-boodskap |

---

## Skrywerprofiel-bladsy (`Writer.tsx`)

### Statistieke en metadata

| Engels | Afrikaans | Notas |
|---|---|---|
| Reader rating | Lesergradering | Etiket |
| [N] reader reviews | [N] leseroordele | |
| Works | [N] stories · [N] gedigte · [N] artikels | Breek af per inhoudssoort op skrywerprofiel; gebruik "werke" slegs as oorhoofse optelsom |
| Followers | volgelinge | Telwoord langs die volgeling-telling; nie "Volgers" nie |
| Likes | hartjies | Telword langs die ♥-ikoon — geen werkwoord benodig nie |
| Joined [date] | Aangesluit [datum] | Meta |

### Opskrifte en aksies

| Engels | Afrikaans | Notas |
|---|---|---|
| About [first name] | Oor [naam] | H2 |
| Accomplishments | Prestasies | Sykolom-opskrif |
| Selected work | Uitgesoekte werk | Boskrif-etiket |
| A curated reading list, in [name]'s own order. | 'n Saamgestelde leeslys, in [naam] se eie volgorde. | H2 |
| See all works | Sien alle werke | Skakel |
| Don't miss [name]'s next piece. | Moenie [naam] se volgende stuk misloop nie. | H2 |
| Follow to get new stories in your reading feed, and be the first to leave a thoughtful note when they publish. | Volg om nuwe stories in jou leesvloei te ontvang, en wees die eerste om 'n deurdagte nota te los wanneer hulle publiseer. | |
| Follow | Volg | Knoppie |
| Following | Volg tans | Wisselknoppie |
| You're following | Jy volg | Toast-boodskap *(sukses)* |
| Follow [first name] | Volg [naam] | CTA-knoppie |
| Discover more writers | Ontdek meer skrywers | Sekondêre CTA-knoppie |
| Share | Deel | Knoppie |
| Profile link copied to your clipboard | Profielskakel gekopieër na jou knipbord | Toast-boodskap |
| Pinned | Vasgespeld | Werkbadge |

---

## Gedeelde inhoud-badges en etikette

| Engels | Afrikaans | Notas |
|---|---|---|
| Short Story | Storie | Inhoudsoordbadge |
| Poetry | Gedig | |
| Article | Artikel | |
| [N] min *(leestyd)* | [N] min | |
| by [author] | deur [skrywer] | Kaart-attribusie |
| [N] days ago | [N] dae gelede | Tydstempel |
| Last month | Verlede maand | |
| Edited yesterday | Gister gewysig | |
| Pinned | Vasgespeld | |

---

## Aantekeninge vir verdere oorsig

1. **"hartjies" vir "Likes":** Vertoon as 'n telwoord langs die ♥-ikoon (bv. "342 hartjies"). Geen werkwoord benodig nie — die ikoon doen die werk.

2. **Inhoudssoort-spesifieke tellings:** Waar 'n telling gemengde inhoudsoorte dek, breek dit af na "gedigte · stories · artikels" in plaas van die generiese "bydraes". Skuif nulwaardes weg (moenie "0 artikels" vertoon nie). Gebruik "bydraes" of "werke" slegs vir oorhoofse optelgetalle wat te groot is om af te breek (bv. "48K+ gepubliseerde werke" op die tuisblad).

3. **Dinamiese tekste:** Baie stringe bevat plekhouers soos `[N]`, `[naam]`, `[datum]`. Hierdie moet vir grammatiese getal en werkwoordsvorm in Afrikaans nagegaan word (bv. "1 artikel" vs. "2 artikels").

4. **Hoofletters in die UI:** Afrikaans gebruik oor die algemeen minder hoofletters as Engels vir opskrifte. Die vertalings weerspieël dit (bv. "Begin skryf" nie "Begin Skryf").

---

## Sync 2026-06-14 — nuwe stringe

### Gedigleser (`components/reading/PoetryReader.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Mark this line as resonant | Merk hierdie reël | aria-label; volg terminologiegids ("Merk hierdie reël") |
| Remove resonance | Verwyder merk | aria-label |
| You marked [N] line as resonant | Jy het [N] reël gemerk | Enkelvoud |
| You marked [N] lines as resonant | Jy het [N] reëls gemerk | Meervoud |

### My Profiel — Volg- en Aktiwiteit-blaaie (`Profile.tsx`)

| Engels | Afrikaans | Notas |
|---|---|---|
| Following | Wie ek volg | Blad-opskrif |
| Writers you follow | Skrywers wat jy volg | H2 |
| New work from these writers shows up in your activity feed. | Nuwe werk van hierdie skrywers verskyn in jou aktiwiteitsvoer. | |
| You're not following anyone yet | Jy volg nog niemand nie | Leë toestand |
| Follow a writer to see their new pieces appear in your activity feed. | Volg 'n skrywer om hul nuwe stukke in jou aktiwiteitsvoer te sien. | Leë toestand |
| Discover writers | Ontdek skrywers | Knoppie |
| Activity from your follows | Aktiwiteit van wie jy volg | H2 |
| [Name] started following you | [Naam] volg jou nou | Kennisgewing |
