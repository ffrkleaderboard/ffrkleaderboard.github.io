# FFRK Community Leaderboards

Static site that shows the leaderboards from the
[FFRecordKeeper Discord sheet](https://docs.google.com/spreadsheets/d/1VSt7_IySH7PxEQNa7h9AYlLtqpO135i1UScY3bR6kzM/edit):
a home page with clickable fight banners in three sections (Elemental, Realm and
Job) and a Normal/Hard Mode toggle, and a page per fight with times, teams and
character usage rates. PHP generates the pages; the published site is plain HTML.

## How it updates

Records reach the sheet when a moderator approves a submission on Discord. The
sheet can't notify the site, so a GitHub Actions workflow reads it every 15
minutes. The sheet is only ever read.

1. `tools/build_data.py` rebuilds `data/` and `assets/banners/` from the sheet's
   public export. If the export looks broken it stops, and the site stays as it is.
2. If anything changed, the workflow commits it, runs `tools/build_site.php` and
   publishes `dist/` to GitHub Pages.

GitHub can delay scheduled runs, and it pauses them after 60 days without activity
in the repository. To run it by hand, or to turn it back on, use the Actions tab.

## Run it locally

Requires PHP 7.4 or later. On Windows run `serve.bat`; elsewhere:

```
php tools/build_site.php
php -S localhost:8000 -t dist
```

Then open http://localhost:8000. To refresh the data first:

```
pip install -r tools/requirements.txt
python tools/build_data.py
```

## Layout

- `includes/functions.php`: loads the CSVs, builds rankings and usage rates
- `includes/render.php`: HTML for the shared parts of a page
- `includes/pages.php`: HTML for each kind of page
- `data/`: generated CSVs (`records.csv` is the sheet's RawData tab)
- `tools/build_data.py`: regenerates `data/` and `assets/banners/` from the sheet
- `tools/build_site.php`: generates the pages in `dist/`
- `.github/workflows/update-site.yml`: the scheduled update

Character sprites, medals and badges load from Google and Imgur, the same files
the sheet uses, so the site needs those hosts to be reachable.
