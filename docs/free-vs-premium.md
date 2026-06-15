# Free vs Premium Manifest

Authoritative split for `scripts/build-lite.php`. Premium source: `toplist-block/`. Lite output: `toplist-block-lite/` (generated — never hand-edit).

## Install model

| Build | Slug | Distribution | Active with |
|-------|------|--------------|-------------|
| Premium | `toplist-block` | Portal download | Premium only |
| Lite | `toplist-block-lite` | WordPress.org | Lite only |

Both register block `toplist/rankings` so post content survives lite → premium upgrade. **Mutually exclusive** — only one plugin active at a time.

## Feature matrix

| Feature | Lite | Premium |
|---------|:----:|:-------:|
| Gutenberg block + front-end render | ✓ | ✓ |
| Pipe-delimited row editing in block | ✓ | ✓ |
| Inspector: theme, defaults, field toggles | ✓ | ✓ |
| Global settings (CSS, toggles, defaults) | ✓ | ✓ |
| Toplist library CPT (`toplist_list`) | — | ✓ |
| Linked live lists (`savedToplistMode=linked`) | — | ✓ |
| Saved Toplist panel in block editor | — | ✓ |
| JSON import/export in block editor | — | ✓ |
| Admin per-toplist CSV/JSON import/export | — | ✓ |
| Row builder metabox | — | ✓ |
| Bulk CSV all-toplists (settings) | — | ✓ |
| REST `toplist-block/v1/toplists` | — | ✓ |
| Portal license validation | — | ✓ (ticket 301) |
| OTA plugin updates | — | ✓ |
| Admin spreadsheet editor (CPT) | — | ✓ |
| ItemList JSON-LD schema | — | ✓ (license-gated) |
| Lite upgrade admin notice | ✓ | — |

## PREMIUM_FILES (deleted from lite tree)

```
admin-diagnostics.php
check-plugin.php
includes/class-toplist-block-license.php
includes/class-toplist-block-license-admin.php
includes/class-toplist-block-updater.php
```

## Build transforms (marker strips)

Regions wrapped in `// @toplist-premium-start` … `// @toplist-premium-end` (PHP) or `/* @toplist-premium-start */` … `/* @toplist-premium-end */` (JS) are removed from lite copies:

| File | Premium regions |
|------|-----------------|
| `toplist-block.php` | CPT, REST, import handlers, metaboxes, premium hooks, linked render branch, admin-diagnostics require |
| `block.js` | JSON helpers, library UI, apiFetch usage |
| `settings-page.php` | Bulk CSV handler, URLs, notices, settings UI row |

## Premium residue tokens (smoke test)

Lite tree must not contain (except whitelist): `toplist_list`, `toplist_handle_import`, `toplist_register_rest_routes`, `class-toplist-block-license`, `class-toplist-block-updater`, `pre_set_site_transient_update_plugins`, `plugins_api`, `apiFetch`, `renderLibraryTab`, `if (false)`.

**Whitelist:** upgrade CTA strings (`Toplist Block Pro`, portal product URL placeholder).

## Option keys (shared)

Both builds use `toplist_*` options so global CSS and toggles persist across upgrade.
