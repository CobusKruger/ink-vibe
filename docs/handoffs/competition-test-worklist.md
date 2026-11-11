# Worklist: test data for the competition lifecycle

**Written:** 2026-11-10. **For:** the owner, to set up test content on dev (nuwe-ink) once stories K1 to K6 are built. Design: `docs/lovable-delta-2026-10/specs/05-kompetisies.md` §14. Handoff: `docs/handoffs/competition-lifecycle.md`.

**Scenario:** the 2026 competition is closed, the 2026 gala is imminent, and the 2025 gala's winners are loaded.

**Status:** field names below are the *planned* ones from §14. Nothing is built yet, so this cannot be run today.

## Summary

| # | Action | Post |
|---|---|---|
| 1 | **Create** | `kompetisie` for 2026 (competition and gala together) |
| 2 | **Edit** (do not create) | the migrated `gala-2025` `kompetisie` |

No other posts are needed. A gala is fields on its competition's post, so there is no separate gala post.

## 0. Check before you start

- [ ] The default announcement post exists (a `kompetisie` with `standaard` = `ja`). `Module::ensureDefault()` creates it once; only check it.
- [ ] The migrated 2025 gala exists (Kompetisies list, key `gala-2025`, with its photos). If it doesn't, `migrate-kompetisies` has not run on this database: run it from a restored pre-migrate snapshot, because it cannot be re-run after `clean-shortcodes`.
- [ ] User accounts exist for every winner you want to link to a profile.

## 1. Create: the 2026 competition and gala

**Kompetisies → add.**

| Field | Value |
|---|---|
| Title | e.g. "INK dig- en Skryfkompetisie 2026" |
| `tipe` | `jaarliks` |
| `jaar` | 2026 |
| `uitgawe` | edition badge text |
| `beskrywing` | the announcement text |
| `oopdatum` | in the past, e.g. February 2026 |
| `sluitingsdatum` | in the past, e.g. May 2026 (together with `oopdatum` this makes the state `closed`) |
| `inskrywing_nota`, `naaswenner_nota` | optional pill and intro text |
| `kat1..3_naam`, `_ikoon`, `_tema`, `_beskrywing`, `_woordlimiet`, `_riglyne` | the three categories |
| `gala_naam` | the gala name |
| `gala_datum` | a near-future date |
| `gala_deure` | doors-open time |
| `gala_plek` | venue |
| `gala_drag` | dress code |
| `gala_program` | programme, one item per line |
| `kaartjie1..3_etiket`, `_prys` | ticket labels and prices (placeholders until payment exists) |
| Photos, highlight, results | leave empty |

Publish.

**Expect on `/kompetisies/`:**
- [ ] Hero shows the generic annual announcement (state `closed`), not the 2026 text and no Enter.
- [ ] Gala band shows the planned 2026 gala, with its tickets and the Reserve button.
- [ ] No results link for 2026.
- [ ] `/kompetisies/gala/2026/` returns 404 (no photos yet).

## 2. Edit: the 2025 results

**Kompetisies → the `gala-2025` post.** Do not create a second one.

- [ ] Add the results intro (the celebration text).
- [ ] Add winner records, one per award. Cover each case:

| Case | `toekenning` | Winner | Other fields |
|---|---|---|---|
| A category win by a site user | e.g. a competition category | `wenner_gebruiker` = the user | `inskrywing` = the winning post; `foto` = the winner at the gala, picked from the 2025 gallery |
| An award to someone with no account | e.g. "Uitstaande bydrae op die Facebook-groep" | `wenner_naam` = free text | none |
| A book prize | e.g. a prize supporting development | `wenner_naam` = free text | `aantekening` = citation (optional) |

- [ ] Publish the results.

**Expect:**
- [ ] `/kompetisies/2025/uitslae/` returns 200 and lists the winners grouped by award.
- [ ] A linked winner shows their profile name and link; a free-text winner shows the name only; an award with no photo shows none.
- [ ] The 2025 year chip and `/kompetisies/gala/2025/` link to the results page.
- [ ] `/kompetisies/2024/uitslae/` (no results) returns 404.

## 3. Notes

- With the 2026 post present, 2025's state is `archive`, so the "Nou bekend: Die wenners van die 2025…" hero does **not** show. That line appears only for the latest competition once its results are published. 2025's results stay reachable from its chip and gala page.
- Entries are **not** covered here: a closed competition is not offered on Skryf. To test K2, add a third `kompetisie` whose `oopdatum` is in the past and `sluitingsdatum` is in the future, then check it appears on `/skryf/` before any term exists, and that the term is created at the first submission.
- To test a partner competition (K1/K3): a `kompetisie` with `tipe` = `vennoot` and a `vennoot_naam`. It should show no gala band or photos.
- Purge the cache after saving (the page is cached; the save should purge it, but check).
