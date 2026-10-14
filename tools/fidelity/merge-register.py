#!/usr/bin/env python3
"""Merge the per-page primitive registers into maps/_primitive-observations.csv.

Stage 1 writes one register per page (maps/_primitive-observations.<page>.csv) so
parallel audits never append to a shared file. At the end of Stage 1 the lead merges
them once (stage-1-kickoff.md, "Shared-file rule"). This script is that merge:

- reads every per-page register, in inventory order (agent-brief.md §2.1);
- accepts LF or CRLF line endings, and requires the 11-column header (README §4.1);
- writes the merged file with one header, LF line endings, rows in page order;
- fails if a register is missing, has a different header, or a row of the wrong width.

Run (writes under docs/, so outside the sandbox):
    python3 tools/fidelity/merge-register.py
"""
import csv
import pathlib
import sys

MAPS = pathlib.Path(__file__).resolve().parents[2] / "docs/fidelity-remediation/maps"
HEADER = ["page", "side", "viewport", "component_kind", "selector", "defined_in",
          "load_scope", "used_in", "declarations", "audit_id", "note"]

# Inventory order (agent-brief.md §2.1). Rows 19-21 are skipped by owner ruling.
PAGES = ["tuisblad", "gemeenskap", "my-profiel", "ontdek", "skryf", "uitdaging-enkel",
         "skrywer", "lees-storie", "biblioteek", "opleiding", "meld-aan", "registreer",
         "wagwoord-herstel", "wagwoord-terugstel", "vier-nul-vier", "oor-ink",
         "uitdagings", "kontak", "lidmaatskap", "inkpols", "ledegids", "lees-gedig"]


def main() -> int:
    rows, counts = [], []
    for page in PAGES:
        path = MAPS / f"_primitive-observations.{page}.csv"
        if not path.exists():
            print(f"missing register: {path.name}", file=sys.stderr)
            return 1
        with path.open(newline="", encoding="utf-8") as fh:
            reader = csv.reader(fh)
            header = next(reader, None)
            if header != HEADER:
                print(f"{path.name}: unexpected header {header}", file=sys.stderr)
                return 1
            page_rows = list(reader)
        for n, row in enumerate(page_rows, start=2):
            if len(row) != len(HEADER):
                print(f"{path.name}:{n}: {len(row)} columns, expected {len(HEADER)}",
                      file=sys.stderr)
                return 1
        rows.extend(page_rows)
        counts.append((page, len(page_rows)))

    out = MAPS / "_primitive-observations.csv"
    with out.open("w", newline="", encoding="utf-8") as fh:
        writer = csv.writer(fh, lineterminator="\n")
        writer.writerow(HEADER)
        writer.writerows(rows)

    for page, n in counts:
        print(f"{n:5d}  {page}")
    print(f"{len(rows):5d}  total -> {out.relative_to(MAPS.parents[2])}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
