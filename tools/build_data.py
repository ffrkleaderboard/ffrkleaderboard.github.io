#!/usr/bin/env python3
"""Regenerates data/*.csv and assets/banners/ from the public Google Sheet.

The sheet is only ever read, through its public xlsx export.

    pip install openpyxl pillow
    python tools/build_data.py                  # download the sheet and rebuild
    python tools/build_data.py --xlsx sheet.xlsx  # rebuild from a local export
"""
import argparse
import csv
import hashlib
import re
import sys
import tempfile
import urllib.request
from collections import defaultdict
from pathlib import Path

import openpyxl

SHEET_ID = "1VSt7_IySH7PxEQNa7h9AYlLtqpO135i1UScY3bR6kzM"
EXPORT_URL = f"https://docs.google.com/spreadsheets/d/{SHEET_ID}/export?format=xlsx"

ROOT = Path(__file__).resolve().parent.parent
DATA_DIR = ROOT / "data"
BANNER_DIR = ROOT / "assets" / "banners"

# Tabs shown on the site, left to right. Everything after JCD-HM is internal.
FIRST_TAB, LAST_TAB = "LICH", "JCD-HM"

BADGE_COLUMNS = ["F2P", "NSS", "NE", "KRBY", "PPP", "BNTY"]
# Theme colours used by the sheet's header fills (theme index -> hex).
THEME_COLORS = {7: "#34a853"}
FALLBACK_HEAD = "#333333"
# A shrinking export means something is wrong with it, not that records left.
MIN_RECORDS_KEPT = 0.9


def lh3(url):
    """`uc?export=view` Drive links do not load in <img>; lh3 links do."""
    match = re.search(r"[?&]id=([\w-]+)", url or "")
    return f"https://lh3.googleusercontent.com/d/{match.group(1)}" if match else (url or "")


def write_csv(name, header, rows):
    with open(DATA_DIR / name, "w", newline="", encoding="utf-8") as handle:
        writer = csv.writer(handle, lineterminator="\n")
        writer.writerow(header)
        writer.writerows(rows)
    print(f"  data/{name}: {len(rows)} rows")


def clean(value):
    return "" if value is None else str(value).strip()


def column(sheet, index):
    """Non-empty values of a Config column, skipping its header."""
    values = [clean(row[0]) for row in sheet.iter_rows(min_row=2, min_col=index, max_col=index, values_only=True)]
    return [value for value in values if value]


def fill_hex(cell):
    color = cell.fill.fgColor
    if color.type == "theme":
        return THEME_COLORS.get(color.theme, FALLBACK_HEAD)
    if color.type == "rgb" and isinstance(color.rgb, str) and color.rgb != "00000000":
        return "#" + color.rgb[-6:].lower()
    return FALLBACK_HEAD


def font_hex(cell):
    color = cell.font.color
    if color is not None and color.type == "rgb" and isinstance(color.rgb, str):
        return "#" + color.rgb[-6:].lower()
    return "#ffffff"


def readable_text(background):
    red, green, blue = (int(background[i:i + 2], 16) for i in (1, 3, 5))
    return "#000000" if 0.299 * red + 0.587 * green + 0.114 * blue > 150 else "#ffffff"


def load_palettes(book):
    rows = book["Colors"].iter_rows(min_row=2, max_col=7, values_only=True)
    return {row[0]: {"even": row[5], "odd": row[6]} for row in rows if row[0]}


def load_fights(book, tabs):
    """Subtitles live in the About tab, e.g. 'Paradox Lich D900-Diamond'."""
    subtitles = {}
    for row in book["About"].iter_rows(values_only=True):
        tab = clean(row[1])
        if tab in tabs:
            text = clean(row[8]) if clean(row[7]) == "∟" else clean(row[7])
            subtitles[tab] = re.sub(r"\s+", " ", text.lstrip("•")).replace("A r k", "Ark")
    return [[tab, subtitles.get(tab, "")] for tab in tabs]


def block_keys(tab, position, config):
    """Mirrors the LET() header of each block's formula: (cd, boss, variant)."""
    cd = config["cd_by_tab"][tab.replace("-HM", "")]
    suffix = " (HM)" if tab.endswith("-HM") else ""
    if cd in ("RCD", "RCD2"):
        return cd, config["realms"][position] + suffix, ""
    if cd in ("JCD", "JCD2"):
        return cd, config["jobs"][position] + suffix, ""
    return cd, config["boss_by_tab"][tab], config["elements"][position]


def block_palette(cd, variant, palettes):
    if cd in ("RCD", "RCD2"):
        return palettes["Fire"]
    if cd in ("JCD", "JCD2"):
        return palettes["Wind"]
    return palettes[variant.split()[1]]


def save_banner(image):
    data = image._data()
    name = hashlib.md5(data).hexdigest()[:12] + "." + (image.format or "png").lower()
    (BANNER_DIR / name).write_bytes(data)
    return name


def build_blocks(formula_book, value_book, tabs, config, palettes):
    rows = []
    for tab in tabs:
        styled, values = formula_book[tab], value_book[tab]
        banners = defaultdict(list)
        for image in styled._images:
            if image.anchor._from.row == 0:
                banners[image.anchor._from.col].append(image)
        starts = [c for c in range(1, styled.max_column + 1) if values.cell(2, c).value == "RANK"]
        for position, start in enumerate(starts):
            cd, boss, variant = block_keys(tab, position, config)
            palette = block_palette(cd, variant, palettes)
            head = fill_hex(styled.cell(2, start))
            found = [image for col in range(start - 1, start + 8) for image in banners[col]]
            label = clean(values.cell(1, start).value)
            if not re.search(r"[A-Za-z]", label):  # "----->" filler used by the sheet
                label = ""
            rows.append([
                tab, position + 1, cd, boss, variant,
                save_banner(found[0]) if found else "",
                label,
                clean(values.cell(1, start + 8).value) if found else "",
                1 if clean(values.cell(2, start + 8).value) else 0,
                head, readable_text(head), palette["odd"], palette["even"],
                font_hex(styled.cell(1, start)),
            ])
    return rows


def load_records(book):
    """The RawData tab, refusing an export that looks broken."""
    records = [
        [clean(cell) for cell in row[:12]]
        for row in book["RawData"].iter_rows(min_row=2, max_col=12, values_only=True)
        if row[0]
    ]
    current = DATA_DIR / "records.csv"
    if current.exists():
        with open(current, newline="", encoding="utf-8") as handle:
            known = sum(1 for _ in csv.reader(handle)) - 1
        if len(records) < known * MIN_RECORDS_KEPT:
            sys.exit(f"Export has {len(records)} records, data/ has {known}: keeping data/")
    for record in records:
        try:
            float(record[4])
        except ValueError:
            sys.exit(f"Invalid time in record: {record[:5]}")
    return records


def read_rows(name):
    """Rows of a data file without its header, or none if it doesn't exist yet."""
    path = DATA_DIR / name
    if not path.exists():
        return []
    with open(path, newline="", encoding="utf-8") as handle:
        return list(csv.reader(handle))[1:]


def find_changes(known, records):
    """Submissions in `records` that `known` lacks, as [cd, boss, variant, submitted_on, status].

    The sheet keeps one record per keeper and fight, so an update overwrites the old
    time: this comparison is the only moment the two can be told apart. A record with
    an unchanged date is a moderator's correction, and so is a new name on a date that
    fight already had.
    """
    dates = {(row[0], *row[1:4]): row[10] for row in known}
    fight_dates = {(*row[1:4], row[10]) for row in known}
    changes = []
    for record in records:
        fight, date = record[1:4], record[10]
        previous = dates.get((record[0], *fight))
        if date == "" or date == previous:
            continue
        if previous is not None:
            changes.append([*fight, date, "updated"])
        elif (*fight, date) not in fight_dates:
            changes.append([*fight, date, "new"])
    return changes


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--xlsx", help="use a local xlsx export instead of downloading")
    args = parser.parse_args()

    if args.xlsx:
        source = Path(args.xlsx)
    else:
        print("Downloading sheet...")
        source = Path(tempfile.mkdtemp()) / "sheet.xlsx"
        urllib.request.urlretrieve(EXPORT_URL, source)

    print("Reading workbook...")
    formula_book = openpyxl.load_workbook(source)
    value_book = openpyxl.load_workbook(source, data_only=True)

    records = load_records(value_book)
    names = value_book.sheetnames
    tabs = names[names.index(FIRST_TAB):names.index(LAST_TAB) + 1]

    cfg = value_book["Config"]
    sheets, cds = column(cfg, 7), column(cfg, 6)
    bosses = [clean(row[0]) for row in cfg.iter_rows(min_row=2, max_row=len(sheets) + 1, min_col=8, max_col=8, values_only=True)]
    config = {
        "elements": column(cfg, 1),
        "realms": column(cfg, 2),
        "jobs": column(cfg, 3),
        "cd_by_tab": dict(zip(sheets, cds)),
        "boss_by_tab": dict(zip(sheets, bosses)),
    }

    DATA_DIR.mkdir(exist_ok=True)
    BANNER_DIR.mkdir(parents=True, exist_ok=True)
    for old in BANNER_DIR.iterdir():
        old.unlink()

    print("Writing data...")
    write_csv("fights.csv", ["sheet", "subtitle"], load_fights(value_book, tabs))
    write_csv(
        "blocks.csv",
        ["sheet", "position", "cd", "boss", "variant", "banner", "label", "date_label", "has_date",
         "head", "head_text", "row_odd", "row_even", "label_color"],
        build_blocks(formula_book, value_book, tabs, config, load_palettes(value_book)),
    )

    known = read_rows("records.csv")
    if known:
        write_csv(
            "record_changes.csv",
            ["cd", "boss", "variant", "submitted_on", "status"],
            read_rows("record_changes.csv") + find_changes(known, records),
        )

    write_csv(
        "records.csv",
        ["username", "cd", "boss", "variant", "time", "char1", "char2", "char3", "char4", "char5", "submitted_on", "video"],
        records,
    )

    characters = [
        [clean(row[0]).lower(), clean(row[1]), lh3(row[2]), lh3(row[3])]
        for row in value_book["CharIcons"].iter_rows(min_row=2, max_col=4, values_only=True)
        if row[0]
    ]
    write_csv("characters.csv", ["code", "name", "icon", "sprite"], characters)

    badges = [
        [clean(row[0])] + [badge for badge, flag in zip(BADGE_COLUMNS, row[1:7]) if flag is True]
        for row in value_book["Badges"].iter_rows(min_row=2, max_col=7, values_only=True)
        if row[0]
    ]
    write_csv("badges.csv", ["username", "badges"], [[row[0], " ".join(row[1:])] for row in badges])

    icons = [
        [clean(row[0]).replace(".0", ""), lh3(row[1])]
        for row in value_book["Icons"].iter_rows(min_row=2, max_col=2, values_only=True)
        if row[0] is not None and row[1]
    ]
    write_csv("icons.csv", ["icon", "url"], icons)

    print(f"  assets/banners: {sum(1 for _ in BANNER_DIR.iterdir())} files")


if __name__ == "__main__":
    sys.exit(main())
