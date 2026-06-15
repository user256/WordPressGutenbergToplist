# Lite → Premium upgrade

Both plugins register the same block (`toplist/rankings`). Post content in the database is unchanged when you switch plugins.

## Steps

1. **Buy Toplist Block Pro** on the portal and download the premium zip.
2. In WordPress: **Plugins → Deactivate** Toplist Block Lite (do not delete yet).
3. **Plugins → Add New → Upload** the premium `toplist-block.zip` and activate.
   - If lite was still active, premium activation deactivates lite automatically.
4. **Settings → Toplist Block**: paste your license key → **Save & verify**.
5. Optional: delete the lite plugin folder after confirming blocks render correctly.

## What transfers automatically

| Data | Behaviour |
|------|-----------|
| Block posts (`toplist/rankings` in post content) | Unchanged — same block name and attributes |
| Global settings (`toplist_global_*` options) | Shared prefix — CSS, toggles, defaults persist |
| Lite-only user meta | Ignored by premium |

## What does not migrate automatically

| Data | Action |
|------|--------|
| Pipe rows in block attributes | Already in post content — no migration needed |
| Future library CPT (`toplist_list`) | Premium-only — create/import lists after license is valid |

## Mutual exclusion

Only one plugin may be active:

- Activating **premium** deactivates **lite**.
- Activating **lite** while premium is active is blocked with an error message.

## Verify

1. Open a page that used the lite block before upgrade.
2. Confirm the front-end toplist still renders.
3. With a valid license, confirm **Toplists** admin menu and library features appear.

See also: [`docs/portal-setup.md`](portal-setup.md), [`docs/free-vs-premium.md`](free-vs-premium.md).
