# Toplist Block

WordPress Gutenberg block for casino/product toplists — pipe-delimited editing, library CPT, JSON/CSV import, and linked live lists.

**Roadmap:** [`tickets/overview.md`](tickets/overview.md) · **Programme:** Sprints 1–5 complete

## Two-plugin model

| Build | Folder / zip | Where |
|-------|----------------|-------|
| **Premium** (edit here) | `toplist-block/` | Portal download |
| **Lite** (generated) | `toplist-block-lite/` | WordPress.org |

```bash
php scripts/build-lite.php    # builds lite + premium zips
composer test                 # PHPUnit parse/import tests
composer test:build           # lite smoke tests
bash scripts/make-pot.sh      # regenerate languages/toplist.pot (requires WP-CLI)
```

See [`docs/free-vs-premium.md`](docs/free-vs-premium.md) and [`docs/build.md`](docs/build.md). Lite and premium are **mutually exclusive** installs; both use block `toplist/rankings`.

## Repo layout

```
toplist/
├── toplist-block/         # premium canonical source
├── scripts/build-lite.php
├── tests/build/run.php
├── docs/
├── tickets/overview.md
└── process_tickets.py
```

## Install (development)

```bash
bash ../install-local.sh --plugin-only   # from Casino-project root → /var/www/html
```

Or zip `toplist-block/` (premium) or `toplist-block-lite.zip` (lite) and upload in WP Admin.

## Block

Posts → Add New → **Toplist** block. Inspector tabs: Theme, Defaults, Toggle visibility.

**Premium only:** Saved Toplist library, JSON import/export in editor, admin CSV/JSON on Toplists CPT.

## Pipe format (full)

One row per line, `|` separated:

```
operator|product|offer|href|logo|year|ctaText|terms|bullets|payout|code|rating|regulator|payments|games|liveGames|smallPrint|readReviewHref|readReviewText|withdrawals
```

- Multi-value fields (`bullets`, `payments`, `games`, `withdrawals`): use `;` inside the column.
- Optional header row: field names as directives; prefix with `-` or `!` to exclude columns.

Example:

```
Mr Vegas|Mr Vegas Casino|100% Bonus|https://example.com|https://via.placeholder.com/150|2020|Visit|T&Cs|Fast payouts;Good slots|||||||||||
```

## External JSON schema

Import/export (premium) uses an array of objects. Fixtures: `toplist.json`, `toplist_updated.json`.

| JSON field | Internal field | Notes |
|------------|----------------|-------|
| `name` | `operator`, `product` | Both set from name |
| `bonus` | `offer` | |
| `visit_link` | `href` | Preferred |
| `bonus_link` | `href` | Fallback if no `visit_link` |
| `image_url` | `logo` | |
| `launched` | `year` | |
| `features[]` | `bullets` | |
| `games` | `games` | String or array |
| `live_games` | `liveGames` | |
| `withdrawals` | `withdrawals` | String or array |
| `review_link` | `readReviewHref` | |
| `payments[]` | `payments` | |
| `payout_time` | `payout` | |
| `code` | `code` | |
| `rating` | `rating` | |
| `regulator` | `regulator` | |

## Global settings

Settings → Toplist Block — global CSS, heading, default header row, field visibility toggles.

## Ticket workflow

1. `tickets/overview.md` → pick ticket  
2. Implement; log notes in ticket file  
3. Mark `[x]` in overview  
4. `python3 process_tickets.py --apply`

## License

GPL-2.0-or-later (WordPress plugin). See plugin header.
